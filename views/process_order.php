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
    $primary = __DIR__ . '/../admin/payment_proof_uploads.log';
    $fallback = __DIR__ . '/../data/payment_proof_uploads.log';
    $ok = @file_put_contents($primary, $line, FILE_APPEND);
    if ($ok === false) {
        $ok2 = @file_put_contents($fallback, $line, FILE_APPEND);
        if ($ok2 === false) {
            // Last resort: append to existing admin student log if writable
            $alt = __DIR__ . '/../admin/student_emails_log.txt';
            @file_put_contents($alt, $line, FILE_APPEND);
        }
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
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
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
            $logMeta = sprintf("mime=%s; size=%d; cloud=%s; user_id=%d", 
                $sinpe_proof_mime ?: 'n/a', (int)($sinpe_proof_data['size'] ?? 0), getCloudName(), (int)$user_id);
            proof_log(sprintf("%s | %s | START | %s", date('Y-m-d H:i:s'), $order_number, $logMeta));

            if (!is_readable($sinpe_proof_tmp)) {
                proof_log(sprintf("%s | %s | FAIL | tmp_unreadable: %s", date('Y-m-d H:i:s'), $order_number, $sinpe_proof_tmp));
                throw new Exception('Archivo temporal del comprobante no accesible.');
            }

            // Choose resource_type explicitly for better compatibility
            $resourceType = 'image';
            if (is_string($sinpe_proof_mime)) {
                if (stripos($sinpe_proof_mime, 'application/pdf') !== false) {
                    $resourceType = 'raw';
                } elseif (stripos($sinpe_proof_mime, 'image/') === 0) {
                    $resourceType = 'image';
                } else {
                    // Fallback to auto for unknown types
                    $resourceType = 'auto';
                }
            }
            $publicId = 'order_' . preg_replace('/[^A-Za-z0-9_-]/','', $order_number);
            $uploader = new UploadApi();
            $uploadRes = $uploader->upload($sinpe_proof_tmp, [
                'folder' => 'payment_proofs',
                'public_id' => $publicId,
                'resource_type' => $resourceType, // image or raw (pdf)
                'overwrite' => true
            ]);
            $proofUrl = isset($uploadRes['secure_url']) ? $uploadRes['secure_url'] : ($uploadRes['url'] ?? '');
            // Audit log: successful Cloudinary upload
            $okMeta = sprintf("public_id=%s; resource_type=%s", $uploadRes['public_id'] ?? 'n/a', $uploadRes['resource_type'] ?? 'n/a');
            proof_log(sprintf("%s | %s | OK | %s | %s", date('Y-m-d H:i:s'), $order_number, $proofUrl, $okMeta));
        } catch (Exception $upErr) {
            error_log('Cloudinary upload error (SINPE proof): ' . $upErr->getMessage());
            $proofUrl = '';
            $GLOBALS['CLOUDINARY_LAST_ERROR'] = $upErr->getMessage();
            // Audit log: failed Cloudinary upload
            $failMeta = sprintf("msg=%s; mime=%s; size=%d; cloud=%s", $upErr->getMessage(), $sinpe_proof_mime ?: 'n/a', (int)($sinpe_proof_data['size'] ?? 0), getCloudName());
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
        // Email configuration (you may want to use a proper email service like SendGrid, Mailgun, etc.)
        $admin_email = 'vanessa@legenddanceacademy.com'; // Vanessa's email
        $from_email = 'noreply@legenddanceacademy.com';
        $from_name = 'Legend Dance Academy';
        
        // Create order items HTML for email
        $items_html = '';
        foreach ($cart_items as $item) {
            $subtotal = $item['product_price'] * $item['quantity'];
            $items_html .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['product_name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₡" . number_format($item['product_price'], 0, ',', '.') . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₡" . number_format($subtotal, 0, ',', '.') . "</td>
                </tr>
            ";
        }
        
        // Payment method display
        $payment_display = ($payment_method === 'sinpe') ? 'SINPE Móvil' : ucfirst($payment_method);
        
        // Email to customer
        $customer_subject = "Confirmación de Pedido #$order_number - Legend Dance Academy";
        $customer_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #ff6600; color: white; padding: 12px; text-align: left; }
                .total-row { background: #f0f0f0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Gracias por tu pedido!</h1>
                    <p>Pedido #$order_number</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>$customer_name</strong>,</p>
                    <p>Tu pedido ha sido recibido exitosamente. A continuación encontrarás los detalles:</p>
                    
                    <div class='order-details'>
                        <h3>Detalles del Pedido</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style='text-align: center;'>Cantidad</th>
                                    <th style='text-align: right;'>Precio</th>
                                    <th style='text-align: right;'>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                $items_html
                                <tr class='total-row'>
                                    <td colspan='3' style='padding: 15px; text-align: right;'>TOTAL:</td>
                                    <td style='padding: 15px; text-align: right;'>₡" . number_format($total_amount, 0, ',', '.') . "</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class='order-details'>
                        <h3>Información de Entrega</h3>
                        <p><strong>Dirección:</strong> $shipping_address</p>
                        <p><strong>Teléfono:</strong> $customer_phone</p>
                        <p><strong>Método de Pago:</strong> $payment_display</p>
                        " . (!empty($notes) ? "<p><strong>Notas:</strong> $notes</p>" : "") . "
                    </div>
                    
                    <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>
                        <h4 style='color: #28a745; margin-top: 0;'>Próximos Pasos</h4>
                        <p>• Nos pondremos en contacto contigo para confirmar la entrega</p>
                        <p>• Si seleccionaste SINPE Móvil, realiza la transferencia a +506 8888-8888</p>
                        <p>• Una vez confirmado el pago, procesaremos tu pedido</p>
                    </div>
                    
                    <p>Si tienes alguna pregunta, no dudes en contactarnos:</p>
                    <p>📧 Email: info@legenddanceacademy.com<br>
                    📞 Teléfono: +506 8888-8888</p>
                    
                    <div class='footer'>
                        <p>Gracias por confiar en Legend Dance Academy</p>
                        <p>¡Seguimos transformando vidas a través de la danza!</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Email to admin (Vanessa)
        $admin_subject = "Nuevo Pedido Recibido #$order_number";
        $admin_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #28a745; color: white; padding: 12px; text-align: left; }
                .total-row { background: #f0f0f0; font-weight: bold; }
                .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nuevo Pedido Recibido</h1>
                    <p>Pedido #$order_number</p>
                </div>
                <div class='content'>
                    <div class='alert'>
                        <strong>🎉 ¡Tienes un nuevo pedido!</strong><br>
                        Por favor revisa los detalles y contacta al cliente para coordinar la entrega.
                    </div>
                    
                    <div class='order-details'>
                        <h3>Información del Cliente</h3>
                        <p><strong>Nombre:</strong> $customer_name</p>
                        <p><strong>Email:</strong> $customer_email</p>
                        <p><strong>Teléfono:</strong> $customer_phone</p>
                        <p><strong>Dirección:</strong> $shipping_address</p>
                        " . (!empty($notes) ? "<p><strong>Notas:</strong> $notes</p>" : "") . "
                    </div>
                    
                    <div class='order-details'>
                        <h3>Productos Pedidos</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style='text-align: center;'>Cantidad</th>
                                    <th style='text-align: right;'>Precio</th>
                                    <th style='text-align: right;'>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                $items_html
                                <tr class='total-row'>
                                    <td colspan='3' style='padding: 15px; text-align: right;'>TOTAL:</td>
                                    <td style='padding: 15px; text-align: right;'>₡" . number_format($total_amount, 0, ',', '.') . "</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class='order-details'>
                        <h3>Detalles de Pago</h3>
                        <p><strong>Método:</strong> $payment_display</p>
                        <p><strong>Estado:</strong> Pendiente de confirmación</p>
                        <p><strong>Total:</strong> ₡" . number_format($total_amount, 0, ',', '.') . "</p>
                        " . ($sinpe_proof_data ? "<p><strong>Comprobante SINPE:</strong> Adjunto en este email</p>" : "") . "
                    </div>
                    
                    <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>
                        <h4 style='color: #28a745; margin-top: 0;'>Acciones Requeridas</h4>
                        <p>1. Contactar al cliente para confirmar el pedido</p>
                        <p>2. Verificar el pago (si es SINPE Móvil)</p>
                        <p>3. Coordinar la entrega</p>
                        <p>4. Actualizar el estado del pedido en el sistema</p>
                    </div>
                    
                    <p><strong>Fecha del pedido:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send emails (basic PHP mail - in production you should use a proper email service)
        $customer_headers = "MIME-Version: 1.0" . "\r\n";
        $customer_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $customer_headers .= "From: $from_name <$from_email>" . "\r\n";
        
        // Admin email with attachment if SINPE proof is provided
        // Send emails with error suppression for local development
        if ($sinpe_proof_data) {
            $admin_sent = @sendEmailWithAttachment($admin_email, $admin_subject, $admin_body, $from_name, $from_email, $sinpe_proof_data);
        } else {
            $admin_headers = "MIME-Version: 1.0" . "\r\n";
            $admin_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $admin_headers .= "From: $from_name <$from_email>" . "\r\n";
            $admin_sent = @mail($admin_email, $admin_subject, $admin_body, $admin_headers);
        }
        
        $customer_sent = @mail($customer_email, $customer_subject, $customer_body, $customer_headers);
        
        return [
            'customer_sent' => $customer_sent,
            'admin_sent' => $admin_sent
        ];
        
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        return [
            'customer_sent' => false,
            'admin_sent' => false,
            'error' => $e->getMessage()
        ];
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