<?php
/**
 * Process Order
 * Handles order creation and email notifications
 */

// Start output buffering to prevent any accidental output
ob_start();

session_start();
require_once '../config/db_connect.php';
require_once '../config/paths.php';
require_once '../config/cloudinary_config.php';
require_once '../includes/email_helper.php';
use Cloudinary\Api\Upload\UploadApi;

// Clean any previous output and set content type to JSON for AJAX responses
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión para realizar pedidos.'
    ]);
    exit();
}

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a la base de datos.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
// Keep last Cloudinary error in-memory for debug on localhost
$GLOBALS['CLOUDINARY_LAST_ERROR'] = null;

// Helper: write payment proof logs with fallback location
function proof_log($message) {
    $line = $message . "\n";
    $paths = [
        __DIR__ . '/../admin/payment_proof_uploads.log',
        __DIR__ . '/../data/payment_proof_uploads.log',
        // Heroku-friendly temp dir
        sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payment_proof_uploads.log',
        // Last resort inside admin student log
        __DIR__ . '/../admin/student_emails_log.txt',
    ];
    foreach ($paths as $p) {
        $ok = @file_put_contents($p, $line, FILE_APPEND);
        if ($ok !== false) { break; }
    }
}

try {
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'payment_method', 'total_amount', 'delivery_type'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false, 
                'message' => "Campo requerido faltante: $field"
            ]);
            exit();
        }
    }
    
    // Validate address for delivery
    if ($_POST['delivery_type'] === 'delivery' && empty($_POST['address'])) {
        echo json_encode([
            'success' => false, 
            'message' => "La dirección es requerida para entregas a domicilio"
        ]);
        exit();
    }
    
    // Handle SINPE proof upload if payment method is SINPE
    $sinpe_proof_data = null;
    $sinpe_proof_tmp = null; // tmp path for upload
    $sinpe_proof_mime = null;
    if ($_POST['payment_method'] === 'sinpe' && isset($_FILES['sinpe_proof']) && $_FILES['sinpe_proof']['error'] === UPLOAD_ERR_OK) {
        $upload = $_FILES['sinpe_proof'];
        
        // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($upload['type'], $allowed_types)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Formato de archivo no válido para el comprobante.'
            ]);
            exit();
        }
        
        if ($upload['size'] > $max_size) {
            echo json_encode([
                'success' => false, 
                'message' => 'El archivo del comprobante es muy grande (máx. 5MB).'
            ]);
            exit();
        }
        
        // Read file data for email attachment and keep tmp path for cloud upload
        $sinpe_proof_data = [
            'name' => $upload['name'],
            'type' => $upload['type'],
            'content' => file_get_contents($upload['tmp_name']),
            'size' => $upload['size']
        ];
        $sinpe_proof_tmp = $upload['tmp_name'];
        $sinpe_proof_mime = $upload['type'];
    }
    
    // Get cart items
    $cart_items = getCartItems($user_id);
    
    if (empty($cart_items)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tu carrito está vacío.'
        ]);
        exit();
    }
    
    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Sanitize input data
    $customer_name = sanitizeInput($_POST['first_name'] . ' ' . $_POST['last_name']);
    $customer_email = sanitizeInput($_POST['email']);
    $customer_phone = sanitizeInput($_POST['phone']);
    $shipping_address = sanitizeInput($_POST['address']);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $payment_method = sanitizeInput($_POST['payment_method']);
    $total_amount = (float)$_POST['total_amount'];
    
    // Validate email
    if (!validateEmail($customer_email)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email inválido.'
        ]);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Create order
    $order_stmt = $conn->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status, 
                           customer_name, customer_email, customer_phone, shipping_address, notes, delivery_type, delivery_cost) 
        VALUES (?, ?, ?, 'pending', ?, 'pending', ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $delivery_type = $_POST['delivery_type'];
    $delivery_cost = ($delivery_type === 'delivery') ? 3000 : 0;
    $shipping_address = ($delivery_type === 'delivery') ? $_POST['address'] : 'Recoger en academia';
    
    $order_stmt->bind_param("isdsssssssi", 
        $user_id, $order_number, $total_amount, $payment_method, 
        $customer_name, $customer_email, $customer_phone, $shipping_address, $notes, $delivery_type, $delivery_cost
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception('Error creando la orden');
    }
    
    $order_id = $conn->insert_id;
    $order_stmt->close();

    // If SINPE proof uploaded, try Cloudinary ONLY (no local fallback)
    if ($payment_method === 'sinpe' && $sinpe_proof_tmp) {
        $proofUrl = '';
        $okProof = false;
        try {
            // Audit log: start upload attempt
            $logMeta = sprintf("mime=%s; size=%d; cloud=%s; user_id=%d; folder=%s", 
                $sinpe_proof_mime ?: 'n/a', (int)($sinpe_proof_data['size'] ?? 0), getCloudName(), (int)$user_id, getPaymentProofFolder());
            proof_log(sprintf("%s | %s | START | %s", date('Y-m-d H:i:s'), $order_number, $logMeta));

            // Use new helper (auto resource_type + env folder override)
            $uploadRes = uploadPaymentProof($sinpe_proof_tmp, $order_number, $sinpe_proof_mime);
            $proofUrl = $uploadRes['secure_url'] ?? ($uploadRes['url'] ?? '');
            $okMeta = sprintf("public_id=%s; resource_type=%s; folder=%s", $uploadRes['public_id'] ?? 'n/a', $uploadRes['resource_type'] ?? 'n/a', getPaymentProofFolder());
            proof_log(sprintf("%s | %s | OK | %s | %s", date('Y-m-d H:i:s'), $order_number, $proofUrl, $okMeta));
        } catch (Exception $upErr) {
            error_log('Cloudinary upload error (SINPE proof): ' . $upErr->getMessage());
            $proofUrl = '';
            $GLOBALS['CLOUDINARY_LAST_ERROR'] = $upErr->getMessage();
            $failMeta = sprintf("msg=%s; mime=%s; size=%d; cloud=%s; folder=%s", $upErr->getMessage(), $sinpe_proof_mime ?: 'n/a', (int)($sinpe_proof_data['size'] ?? 0), getCloudName(), getPaymentProofFolder());
            proof_log(sprintf("%s | %s | FAIL | %s", date('Y-m-d H:i:s'), $order_number, $failMeta));
        }

        if (!empty($proofUrl)) {
            // Try to store in dedicated columns
            $upd = $conn->prepare("UPDATE orders SET payment_proof_url = ?, payment_proof_type = ? WHERE id = ?");
            if ($upd) {
                $typeVal = $sinpe_proof_mime ?: '';
                $upd->bind_param('ssi', $proofUrl, $typeVal, $order_id);
                $okProof = $upd->execute();
                if (!$okProof) {
                    error_log('orders.payment_proof update failed: ' . $upd->error);
                }
                $upd->close();
            }
            // Append proof URL to notes ONLY if it's a Cloudinary URL (redundancy for admin viewing)
            if (preg_match('#^https?://res\.cloudinary\.com/#i', $proofUrl)) {
                $append = "\nComprobante: " . $proofUrl;
                $notesUpd = $conn->prepare("UPDATE orders SET notes = CONCAT(COALESCE(notes,''), ?) WHERE id = ?");
                if ($notesUpd) { $notesUpd->bind_param('si', $append, $order_id); $notesUpd->execute(); $notesUpd->close(); }
            }
        } else {
            // Enforce proof presence: if Cloudinary upload failed, abort the order
            throw new Exception('No se pudo subir el comprobante a Cloudinary. Por favor, intenta nuevamente.');
        }
    }
    
    // Add order items
    $item_stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, product_price, product_image, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($cart_items as $item) {
        $subtotal = $item['product_price'] * $item['quantity'];
        $item_stmt->bind_param("issdsid", 
            $order_id, $item['product_id'], $item['product_name'], 
            $item['product_price'], $item['product_image'], $item['quantity'], $subtotal
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception('Error agregando productos a la orden');
        }
    }
    $item_stmt->close();
    
    // Enforce and decrement product stock (DB-based)
    if (!updateProductStock($cart_items)) {
        throw new Exception('No se pudo actualizar el stock de los productos');
    }
    
    // Clear user's cart
    clearUserCart($user_id);
    
    // Log order creation
    $log_stmt = $conn->prepare("
        INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, notes) 
        VALUES (?, NULL, 'pending', ?, 'Orden creada por el cliente')
    ");
    $log_stmt->bind_param("ii", $order_id, $user_id);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Also register a payment record for this order (products checkout)
    try {
        // total_amount already includes any delivery cost from checkout
        $payment_amount = (float)$total_amount;
        $payment_method_db = $payment_method; // 'sinpe' | 'efectivo' | 'tarjeta'
    $payment_status_db = ($payment_method_db === 'sinpe') ? 'pending' : 'completed';
        $payment_date_db = date('Y-m-d');
        $reference_number_db = $order_number; // link to order
        $notes_db = 'Pago de productos - Pedido ' . $order_number . ' - Entrega: ' . $delivery_type;
        $recorded_by_db = $user_id; // recorded by the customer placing the order
        $class_name_db = 'Productos';

        // First, try full insert (with class_name and recorded_by columns)
        $ok = false;
        $pay_stmt = $conn->prepare("INSERT INTO payment_records (enrollment_id, user_id, class_name, amount, payment_method, payment_status, payment_date, reference_number, notes, recorded_by, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if ($pay_stmt) {
            $pay_stmt->bind_param(
                "isdsssssi",
                $user_id,
                $class_name_db,
                $payment_amount,
                $payment_method_db,
                $payment_status_db,
                $payment_date_db,
                $reference_number_db,
                $notes_db,
                $recorded_by_db
            );
            if ($pay_stmt->execute()) {
                $ok = true;
            } else {
                error_log('Auto-payment insert (full) failed: ' . $pay_stmt->error);
            }
            $pay_stmt->close();
        }

        // Fallback: insert without class_name and recorded_by (older schema)
        if (!$ok) {
            $pay_stmt2 = $conn->prepare("INSERT INTO payment_records (enrollment_id, user_id, amount, payment_method, payment_status, payment_date, reference_number, notes, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if ($pay_stmt2) {
                $pay_stmt2->bind_param(
                    "idsssss",
                    $user_id,
                    $payment_amount,
                    $payment_method_db,
                    $payment_status_db,
                    $payment_date_db,
                    $reference_number_db,
                    $notes_db
                );
                if (!$pay_stmt2->execute()) {
                    error_log('Auto-payment insert (fallback) failed: ' . $pay_stmt2->error);
                }
                $pay_stmt2->close();
            } else {
                error_log('Auto-payment fallback prepare failed');
            }
        }
    } catch (Exception $pe) {
        error_log('Exception creating payment record for order ' . $order_number . ': ' . $pe->getMessage());
    }

    // Commit transaction (order, items, logs). Payment record is independent and best-effort
    $conn->commit();
    
    // Send email notifications (don't let email errors break the order process)
    $email_result = ['customer_sent' => false, 'admin_sent' => false];
    try {
        $email_result = sendOrderNotifications($order_number, $customer_name, $customer_email, $cart_items, $total_amount, $payment_method, $shipping_address, $notes, $customer_phone, $sinpe_proof_data);
    } catch (Exception $email_error) {
        // Log email error but don't break the order process
        error_log("Email sending failed for order $order_number: " . $email_error->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pedido creado exitosamente.',
        'order_number' => $order_number,
        'email_sent' => $email_result
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn) {
        $conn->rollback();
    }
    
    $resp = [
        'success' => false, 
        'message' => 'Error procesando el pedido: ' . $e->getMessage()
    ];
    // If local dev, include debug hint
    $host = $_SERVER['SERVER_NAME'] ?? '';
    if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
        if (!empty($GLOBALS['CLOUDINARY_LAST_ERROR'])) {
            $resp['debug_cloudinary'] = $GLOBALS['CLOUDINARY_LAST_ERROR'];
            $resp['debug_cloud'] = getCloudName();
        }
    }
    echo json_encode($resp);
}

function getCartItems($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, ci.product_id, ci.product_name, ci.product_price, ci.product_image, ci.quantity
        FROM cart c
        JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    return $items;
}

function clearUserCart($user_id) {
    global $conn;
    
    // First get cart ID
    $cart_stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows > 0) {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
        
        // Delete cart items
        $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $delete_stmt->bind_param("i", $cart_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    
    $cart_stmt->close();
}

function sendOrderNotifications($order_number, $customer_name, $customer_email, $cart_items, $total_amount, $payment_method, $shipping_address, $notes, $customer_phone, $sinpe_proof_data = null) {
    try {
        $admin_email = studio_email_default('admin_email', 'admin@valevphotography.com');
        $from_email = studio_email_default('from_email', 'noreply@valevphotography.com');
        $from_name = studio_email_default('from_name', 'Vale V Photography');

        // Items table fragment
        $rows = '';
        foreach ($cart_items as $item) {
            $subtotal = $item['product_price'] * $item['quantity'];
            $rows .= '<tr>'
                .'<td style="padding:8px 6px;border-bottom:1px solid #eee">'.htmlspecialchars($item['product_name']).'</td>'
                .'<td style="padding:8px 6px;border-bottom:1px solid #eee;text-align:center">'.(int)$item['quantity'].'</td>'
                .'<td style="padding:8px 6px;border-bottom:1px solid #eee;text-align:right">₡'.number_format($item['product_price'],0,',','.').'</td>'
                .'<td style="padding:8px 6px;border-bottom:1px solid #eee;text-align:right">₡'.number_format($subtotal,0,',','.').'</td>'
                .'</tr>';
        }
        $items_table = '<table style="width:100%;border-collapse:collapse;margin-top:12px">'
            .'<thead><tr><th style="text-align:left;padding:8px;background:#fafafa">Producto</th><th style="text-align:center;padding:8px;background:#fafafa">Cant</th><th style="text-align:right;padding:8px;background:#fafafa">Precio</th><th style="text-align:right;padding:8px;background:#fafafa">Subtotal</th></tr></thead>'
            .'<tbody>'.$rows.'<tr style="background:#f3f4f7;font-weight:bold"><td colspan="3" style="padding:10px;text-align:right">TOTAL</td><td style="padding:10px;text-align:right">₡'.number_format($total_amount,0,',','.').'</td></tr></tbody></table>';

        $payment_display = ($payment_method === 'sinpe') ? 'SINPE Móvil' : ucfirst($payment_method);
        $notes_block = $notes ? ('<p><strong>Notas:</strong> '.htmlspecialchars($notes).'</p>') : '';

        // Customer email
        $cust_subject = "Tu pedido #{$order_number} se envió para aprobación";
        $cust_body = '<p>Hola <strong>'.htmlspecialchars($customer_name).'</strong>,</p>'
            .'<p>Hemos recibido tu pedido y está <strong>pendiente de aprobación</strong>. Pronto validaremos el pago y te notificaremos.</p>'
            .$items_table
            .'<div style="margin-top:18px;background:#fff7e6;border-left:4px solid #ff9800;padding:14px;border-radius:8px">'
            .'<p style="margin:0 0 6px 0"><strong>Siguientes pasos:</strong></p>'
            .'<p style="margin:4px 0">1. Validaremos tu pago.</p>'
            .'<p style="margin:4px 0">2. Prepararemos tu pedido.</p>'
            .'<p style="margin:4px 0">3. Te avisaremos cuando esté listo.</p>'
            .'</div>'
            .'<p style="margin-top:18px"><strong>Entrega:</strong> '.htmlspecialchars($shipping_address).'<br><strong>Teléfono:</strong> '.htmlspecialchars($customer_phone).'<br><strong>Método de pago:</strong> '.$payment_display.'</p>'
            .$notes_block
            .'<p style="margin-top:20px">Gracias por confiar en Vale V Photography.</p>';

        $attachments = [];
        if ($sinpe_proof_data) {
            $attachments[] = [
                'name' => $sinpe_proof_data['name'],
                'type' => $sinpe_proof_data['type'],
                'content' => $sinpe_proof_data['content']
            ];
        }

        $customer_sent = send_branded_email($customer_email, $cust_subject, 'Pedido recibido', $cust_body, 'Pendiente', '#ff6600');

        // Admin email (includes attachment if SINPE)
        $admin_subject = "Nuevo pedido #{$order_number} (pendiente)";
        $admin_body = '<p><strong>Nuevo pedido recibido</strong></p>'
            .'<p><strong>Cliente:</strong> '.htmlspecialchars($customer_name).' ('.htmlspecialchars($customer_email).')<br><strong>Teléfono:</strong> '.htmlspecialchars($customer_phone).'<br><strong>Entrega:</strong> '.htmlspecialchars($shipping_address).'<br><strong>Método Pago:</strong> '.$payment_display.'</p>'
            .$items_table
            .$notes_block
            .($sinpe_proof_data ? '<p style="margin-top:12px"><strong>Comprobante SINPE adjunto.</strong></p>' : '')
            .'<p style="margin-top:14px;font-size:13px;color:#555">Acciones sugeridas: validar pago, actualizar estado, coordinar entrega.</p>';
        $admin_sent = send_branded_email($admin_email, $admin_subject, 'Nuevo pedido recibido', $admin_body, 'Pendiente', '#28a745', $attachments, $from_name, $from_email);

        // Log basic email outcomes
        $logLine = sprintf("%s | ORDER_EMAIL | %s | customer=%s sent=%d | admin=%s sent=%d\n", date('Y-m-d H:i:s'), $order_number, $customer_email, $customer_sent?1:0, $admin_email, $admin_sent?1:0);
        @file_put_contents(__DIR__ . '/../admin/student_emails_log.txt', $logLine, FILE_APPEND);

        return [ 'customer_sent' => $customer_sent, 'admin_sent' => $admin_sent ];
    } catch (Throwable $e) {
        error_log('sendOrderNotifications error: '.$e->getMessage());
        return [ 'customer_sent' => false, 'admin_sent' => false, 'error' => $e->getMessage() ];
    }
}

function sendEmailWithAttachment($to_email, $subject, $html_body, $from_name, $from_email, $attachment_data) {
    try {
        $boundary = md5(time());
        
        // Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "From: $from_name <$from_email>" . "\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"" . "\r\n";
        
        // Message body
        $message = "--$boundary" . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $message .= "Content-Transfer-Encoding: 7bit" . "\r\n\r\n";
        $message .= $html_body . "\r\n";
        
        // Attachment
        if ($attachment_data) {
            $message .= "--$boundary" . "\r\n";
            $message .= "Content-Type: " . $attachment_data['type'] . "; name=\"" . $attachment_data['name'] . "\"" . "\r\n";
            $message .= "Content-Transfer-Encoding: base64" . "\r\n";
            $message .= "Content-Disposition: attachment; filename=\"" . $attachment_data['name'] . "\"" . "\r\n\r\n";
            $message .= chunk_split(base64_encode($attachment_data['content'])) . "\r\n";
        }
        
        $message .= "--$boundary--" . "\r\n";
        
        return @mail($to_email, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("Email attachment error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update product stock after order is placed
 */
function updateProductStock($cart_items) {
    try {
        global $conn;
        foreach ($cart_items as $item) {
            $pid = $item['product_id'];
            $qty = (int)$item['quantity'];
            // Only enforce for numeric product IDs stored in DB
            if (!ctype_digit((string)$pid)) { continue; }
            $pidNum = (int)$pid;
            // Atomic decrement with guard to avoid negative stock
            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->bind_param("iii", $qty, $pidNum, $qty);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                // Lookup product name for error clarity
                $n = 'producto';
                $nameStmt = $conn->prepare("SELECT name, stock FROM products WHERE id = ?");
                $nameStmt->bind_param("i", $pidNum);
                $nameStmt->execute();
                $res = $nameStmt->get_result();
                if ($res && $row = $res->fetch_assoc()) { $n = $row['name'] . " (stock actual: " . ((int)$row['stock']) . ")"; }
                $nameStmt->close();
                throw new Exception('Stock insuficiente para ' . $n);
            }
            $stmt->close();
        }
        return true;
    } catch (Exception $e) {
        error_log('DB stock update error: ' . $e->getMessage());
        return false;
    }
}

if (isset($conn)) {
    closeConnection($conn);
}
?>