<?php
require_once '../config/db_connect.php';
// Ensure session is available to identify the admin performing actions
require_once '../config/session_manager.php';
// Best-effort transactional emails (SendGrid -> mail) helper
require_once '../includes/email_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!$conn) {
        throw new Exception('No hay conexión con la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $endpoint = $_GET['endpoint'] ?? '';
        
        switch ($endpoint) {
            case 'users':
                // Fetch all active users
                $sql = "SELECT id, first_name, last_name, email, phone, role, created_at, is_active, 
                               weight, height, medical_conditions, emergency_contact_name, 
                               emergency_contact_phone, emergency_contact_relationship 
                        FROM users WHERE is_active = 1 ORDER BY created_at DESC";
                
                $result = $conn->query($sql);
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
                    $users[] = $row;
                }
                
                echo json_encode($users);
                break;
                
            case 'enrollments':
                // Fetch enrollments with user details, only for active users
                $sql = "SELECT e.*, u.first_name, u.last_name, u.email, u.phone,
                               CONCAT(u.first_name, ' ', u.last_name) as full_name
                        FROM enrollments e 
                        JOIN users u ON e.user_id = u.id AND u.is_active = 1
                        ORDER BY e.enrollment_date DESC";
                
                $result = $conn->query($sql);
                $enrollments = [];
                while ($row = $result->fetch_assoc()) {
                    $enrollments[] = $row;
                }
                
                echo json_encode($enrollments);
                break;
                
            case 'payment_records':
                // Fetch payment records with user details
                $sql = "SELECT pr.*, u.first_name, u.last_name, u.email,
                               CONCAT(u.first_name, ' ', u.last_name) as full_name
                        FROM payment_records pr 
                        JOIN users u ON pr.user_id = u.id 
                        ORDER BY pr.payment_date DESC";
                
                $result = $conn->query($sql);
                $payments = [];
                while ($row = $result->fetch_assoc()) {
                    $row['amount'] = floatval($row['amount']);
                    $payments[] = $row;
                }
                
                echo json_encode($payments);
                break;
                
            case 'orders':
                // Fetch orders with item details and computed subtotal for safer totals
                $sql = "SELECT o.*, 
                               COALESCE(SUM(oi.product_price * oi.quantity), 0) AS items_subtotal,
                               GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
                        FROM orders o 
                        LEFT JOIN order_items oi ON o.id = oi.order_id 
                        GROUP BY o.id 
                        ORDER BY o.created_at DESC";
                
                $result = $conn->query($sql);
                $orders = [];
                while ($row = $result->fetch_assoc()) {
                    $row['total_amount'] = floatval($row['total_amount']);
                    $row['delivery_cost'] = floatval($row['delivery_cost']);
                    $row['items_subtotal'] = floatval($row['items_subtotal']);
                    $orders[] = $row;
                }
                
                echo json_encode($orders);
                break;
                
            default:
                throw new Exception('Endpoint no válido');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No se recibieron datos válidos');
        }
        
    $action = $input['action'] ?? '';
    $type = $input['type'] ?? '';
        
        switch ($type) {
            case 'user':
                switch ($action) {
                    case 'delete':
                        $userId = (int)($input['user_id'] ?? 0);
                        if ($userId <= 0) {
                            throw new Exception('ID de usuario inválido');
                        }
                        // Soft delete user by setting is_active = 0
                        $stmt = $conn->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("i", $userId);
                        if (!$stmt->execute()) {
                            throw new Exception('Error al eliminar usuario: ' . $stmt->error);
                        }
                        echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
                        break;
                    case 'hard_delete':
                        $userId = (int)($input['user_id'] ?? 0);
                        if ($userId <= 0) {
                            throw new Exception('ID de usuario inválido');
                        }
                        // Safety: only allow hard delete if no dependent records exist
                        $dependencies = [
                            ['sql' => 'SELECT COUNT(*) c FROM enrollments WHERE user_id = ?', 'label' => 'inscripciones'],
                            ['sql' => 'SELECT COUNT(*) c FROM orders WHERE user_id = ?', 'label' => 'órdenes'],
                            ['sql' => 'SELECT COUNT(*) c FROM payment_records WHERE user_id = ?', 'label' => 'pagos'],
                            ['sql' => 'SELECT COUNT(*) c FROM cart WHERE user_id = ?', 'label' => 'carrito'],
                        ];
                        $blocked = [];
                        foreach ($dependencies as $dep) {
                            if ($stmt = $conn->prepare($dep['sql'])) {
                                $stmt->bind_param('i', $userId);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $row = $res ? $res->fetch_assoc() : ['c' => 0];
                                if ((int)($row['c'] ?? 0) > 0) { $blocked[] = $dep['label']; }
                                $stmt->close();
                            }
                        }
                        if (!empty($blocked)) {
                            throw new Exception('No se puede eliminar permanentemente. El usuario tiene registros relacionados: ' . implode(', ', $blocked) . '. Desactiva el usuario o limpia primero esos datos.');
                        }
                        // Proceed with actual delete
                        $del = $conn->prepare('DELETE FROM users WHERE id = ?');
                        $del->bind_param('i', $userId);
                        if (!$del->execute()) {
                            throw new Exception('Error al eliminar permanentemente: ' . $del->error);
                        }
                        $del->close();
                        echo json_encode(['success' => true, 'message' => 'Usuario eliminado permanentemente']);
                        break;
                    default:
                        throw new Exception('Acción de usuario no válida');
                }
                break;
            case 'order':
                switch ($action) {
                    case 'attach_proof_url':
                        $orderId = (int)($input['order_id'] ?? 0);
                        $url = trim((string)($input['url'] ?? ''));
                        if ($orderId <= 0) {
                            throw new Exception('ID de orden inválido');
                        }
                        if ($url === '' || !preg_match('#^https?://#i', $url)) {
                            throw new Exception('URL inválida (debe empezar con http o https)');
                        }
                        // Guess type by extension
                        $type = '';
                        $lu = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
                        if (preg_match('/\.pdf(?:$|\?)/', $lu)) $type = 'application/pdf';
                        elseif (preg_match('/\.(png|jpg|jpeg|gif|webp)(?:$|\?)/', $lu, $m)) $type = 'image/'.($m[1]==='jpg'?'jpeg':$m[1]);

                        // Update orders table
                        if ($stmt = $conn->prepare('UPDATE orders SET payment_proof_url = ?, payment_proof_type = ?, updated_at = NOW() WHERE id = ?')) {
                            $stmt->bind_param('ssi', $url, $type, $orderId);
                            if (!$stmt->execute()) {
                                throw new Exception('No se pudo guardar el comprobante: ' . $stmt->error);
                            }
                            $stmt->close();
                        }

                        // Append to notes ONLY if it's a Cloudinary URL (redundancy)
                        if (preg_match('#^https?://res\\.cloudinary\\.com/#i', $url)) {
                            if ($stmt2 = $conn->prepare("UPDATE orders SET notes = CONCAT(COALESCE(notes,''), ?) WHERE id = ?")) {
                                $append = "\nComprobante: ".$url;
                                $stmt2->bind_param('si', $append, $orderId);
                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }

                        echo json_encode(['success' => true, 'message' => 'Comprobante adjuntado']);
                        break;
                    case 'update_status':
                        $orderId = (int)($input['order_id'] ?? 0);
                        $newStatus = strtolower(trim($input['status'] ?? ''));
                        if ($orderId <= 0) {
                            throw new Exception('ID de orden inválido');
                        }
                        $allowed = ['pending','approved','completed','canceled','cancelled'];
                        if (!in_array($newStatus, $allowed, true)) {
                            throw new Exception('Estado de orden no válido');
                        }
                        // Normalize spelling
                        if ($newStatus === 'cancelled') { $newStatus = 'canceled'; }

                        // Map to DB enum-compatible values
                        $dbStatusMap = [
                            'pending'   => 'pending',
                            'approved'  => 'paid',       // treat approved as payment confirmed
                            'completed' => 'delivered',   // treat completed as delivered/fulfilled
                            'canceled'  => 'cancelled'
                        ];
                        $dbStatus = $dbStatusMap[$newStatus] ?? 'pending';

                        // Fetch old status for logging
                        $oldStatus = null;
                        if ($st0 = $conn->prepare('SELECT status FROM orders WHERE id = ?')) {
                            $st0->bind_param('i', $orderId);
                            $st0->execute();
                            $res0 = $st0->get_result();
                            if ($res0 && ($r0 = $res0->fetch_assoc())) { $oldStatus = $r0['status'] ?? null; }
                            $st0->close();
                        }

                        // Update order status using DB-compatible status; optionally align payment_status
                        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param('si', $dbStatus, $orderId);
                        if (!$stmt->execute()) {
                            throw new Exception('Error al actualizar orden: ' . $stmt->error);
                        }
                        $stmt->close();

                        // Payment status sync (best-effort)
                        $payMap = [ 'completed' => 'completed', 'canceled' => 'cancelled', 'approved' => 'paid' ];
                        if (isset($payMap[$newStatus])) {
                            $ps = $payMap[$newStatus];
                            if ($ps) {
                                if ($ps === 'cancelled') { $ps = 'cancelled'; }
                                $psStmt = $conn->prepare('UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?');
                                if ($psStmt) { $psStmt->bind_param('si', $ps, $orderId); $psStmt->execute(); $psStmt->close(); }
                            }
                        }

                        // Log status change (store external/new status label for readability)
                        $who = ($isLoggedIn && !empty($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : 0;
                        if ($who > 0) {
                            if ($log = $conn->prepare('INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)')) {
                                $old = $oldStatus; $new = $newStatus; $notes = 'Cambio desde admin';
                                $log->bind_param('issis', $orderId, $old, $new, $who, $notes);
                                $log->execute();
                                $log->close();
                            }
                        }

                        // Notify customer via email (best-effort) and log
                        try {
                            // Fetch order with customer info and items for richer email
                            $cust = null; $itemsHtml = ''; $itemsTotal = 0;
                            if ($s1 = $conn->prepare('SELECT o.order_number, o.status, o.total_amount, o.delivery_cost, o.payment_method, o.payment_status, o.created_at, u.first_name, u.last_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?')) {
                                $s1->bind_param('i', $orderId);
                                $s1->execute();
                                $r1 = $s1->get_result();
                                $cust = $r1 ? $r1->fetch_assoc() : null;
                                $s1->close();
                            }
                            if ($cust) {
                                if ($s2 = $conn->prepare('SELECT product_name, quantity, product_price FROM order_items WHERE order_id = ?')) {
                                    $s2->bind_param('i', $orderId);
                                    $s2->execute();
                                    $r2 = $s2->get_result();
                                    while ($row = $r2->fetch_assoc()) {
                                        $sub = (float)$row['product_price'] * (int)$row['quantity'];
                                        $itemsHtml .= '<tr>'
                                            .'<td style="padding:8px 4px;border-bottom:1px solid #eee">'.htmlspecialchars($row['product_name']).'</td>'
                                            .'<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:center">'.(int)$row['quantity'].'</td>'
                                            .'<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:right">₡'.number_format($row['product_price'],0,',','.').'</td>'
                                            .'<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:right">₡'.number_format($sub,0,',','.').'</td>'
                                            .'</tr>';
                                        $itemsTotal += $sub;
                                    }
                                    $s2->close();
                                }
                            }
                            $sentCustomer = false; $sentAdmin = false;
                            if ($cust && !empty($cust['email'])) {
                                $to = $cust['email'];
                                $name = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                                $orderNo = $cust['order_number'] ?? (string)$orderId;
                                $baseTotal = (float)($cust['total_amount'] ?? 0);
                                $deliveryCost = (float)($cust['delivery_cost'] ?? 0);
                                $computedTotal = $itemsTotal > 0
                                    ? max($baseTotal, $itemsTotal + $deliveryCost)
                                    : ($baseTotal > 0 ? $baseTotal : $deliveryCost);
                                $statusLabel = ($newStatus==='approved'?'Aprobada':($newStatus==='completed'?'Completada':($newStatus==='canceled'?'Cancelada':ucfirst($newStatus))));
                                $subject = "Actualización de tu orden #{$orderNo} - {$statusLabel}";
                                $msgIntro = '';
                                if ($newStatus === 'approved') {
                                    $msgIntro = "Tu orden <strong>#{$orderNo}</strong> ha sido <strong>aprobada</strong>. Estamos preparando tu pedido.";
                                } elseif ($newStatus === 'completed') {
                                    $msgIntro = "¡Buenas noticias! Tu orden <strong>#{$orderNo}</strong> ha sido <strong>completada</strong>. Gracias por tu compra.";
                                } elseif ($newStatus === 'canceled') {
                                    $msgIntro = "Tu orden <strong>#{$orderNo}</strong> ha sido <strong>cancelada</strong>. Si no reconoces esta acción o tienes dudas, contáctanos.";
                                } else {
                                    $msgIntro = "Tu orden <strong>#{$orderNo}</strong> se actualizó al estado <strong>{$statusLabel}</strong>.";
                                }
                                $itemsTable = $itemsHtml ? ('<table style="width:100%;border-collapse:collapse;margin-top:12px"><thead><tr><th style="text-align:left;padding:8px;background:#fafafa">Producto</th><th style="text-align:center;padding:8px;background:#fafafa">Cant</th><th style="text-align:right;padding:8px;background:#fafafa">Precio</th><th style="text-align:right;padding:8px;background:#fafafa">Subtotal</th></tr></thead><tbody>' . $itemsHtml . '<tr style="background:#f3f4f7;font-weight:bold"><td colspan="3" style="padding:10px;text-align:right">TOTAL</td><td style="padding:10px;text-align:right">₡'.number_format($computedTotal,0,',','.').'</td></tr></tbody></table>') : '';
                                $bodyHtml = '<p>Hola '.htmlspecialchars($name).',</p><p>'.$msgIntro.'</p>' . $itemsTable . '<p style="margin-top:18px">Gracias por apoyar a Vale V Photography.</p>';
                                $accent = ($newStatus==='approved') ? '#0d6efd' : (($newStatus==='completed') ? '#198754' : (($newStatus==='canceled') ? '#6c757d' : '#ff6600'));
                                $sentCustomer = send_branded_email($to, $subject, 'Actualización de tu pedido', $bodyHtml, $statusLabel, $accent);
                                $lineC = sprintf("%s - Email %s to: %s (%s) - Subject: '%s' - Type: order-%s-customer - Sender: %s\n", date('Y-m-d H:i:s'), $sentCustomer?'SENT':'NOT_SENT', $to, $name, $subject, $newStatus, 'Admin');
                                @file_put_contents(__DIR__ . '/student_emails_log.txt', $lineC, FILE_APPEND);
                                // Admin notification
                                $adminEmail = studio_email_default('admin_email', 'admin@valevphotography.com');
                                $adminBody = '<p><strong>Cambio de estado de orden</strong></p>'
                                    .'<p><strong>Orden:</strong> '.htmlspecialchars($orderNo).' <br><strong>Estado nuevo:</strong> '.htmlspecialchars($statusLabel).' <br><strong>Cliente:</strong> '.htmlspecialchars($name).' <br><strong>Total:</strong> ₡'.number_format($computedTotal,0,',','.').'</p>'
                                    .$itemsTable
                                    .'<p style="margin-top:14px;font-size:12px;color:#666">Este correo confirma el cambio de estado realizado en el panel.</p>';
                                $adminAccent = ($newStatus==='approved') ? '#0d6efd' : (($newStatus==='completed') ? '#198754' : (($newStatus==='canceled') ? '#6c757d' : '#ff6600'));
                                $adminSubject = "Orden #{$orderNo} cambiada a {$statusLabel}";
                                $sentAdmin = send_branded_email($adminEmail, $adminSubject, 'Estado de pedido actualizado', $adminBody, $statusLabel, $adminAccent);
                                $lineA = sprintf("%s - Email %s to: %s (%s) - Subject: '%s' - Type: order-%s-admin - Sender: %s\n", date('Y-m-d H:i:s'), $sentAdmin?'SENT':'NOT_SENT', $adminEmail, 'Admin', $adminSubject, $newStatus, 'System');
                                @file_put_contents(__DIR__ . '/student_emails_log.txt', $lineA, FILE_APPEND);
                            }
                        } catch (Throwable $t) { /* ignore email/log errors */ $sentCustomer = false; $sentAdmin = false; }

                        echo json_encode([
                            'success' => true,
                            'message' => 'Orden actualizada',
                            'notification_sent' => !empty($sentCustomer),
                            'admin_notification_sent' => !empty($sentAdmin)
                        ]);
                        break;
                    case 'delete':
                        $orderId = (int)($input['order_id'] ?? 0);
                        if ($orderId <= 0) {
                            throw new Exception('ID de orden inválido');
                        }
                        // Use transaction to delete related data safely
                        $conn->begin_transaction();
                        try {
                            // Delete order items
                            if ($stmt = $conn->prepare('DELETE FROM order_items WHERE order_id = ?')) {
                                $stmt->bind_param('i', $orderId);
                                $stmt->execute();
                                $stmt->close();
                            }
                            // Delete order status logs
                            if ($stmt = $conn->prepare('DELETE FROM order_status_log WHERE order_id = ?')) {
                                $stmt->bind_param('i', $orderId);
                                $stmt->execute();
                                $stmt->close();
                            }
                            // Finally delete the order
                            if ($stmt = $conn->prepare('DELETE FROM orders WHERE id = ?')) {
                                $stmt->bind_param('i', $orderId);
                                if (!$stmt->execute()) {
                                    throw new Exception('No se pudo eliminar la orden: ' . $stmt->error);
                                }
                                $stmt->close();
                            }
                            $conn->commit();
                            echo json_encode(['success' => true, 'message' => 'Orden eliminada correctamente']);
                        } catch (Exception $de) {
                            $conn->rollback();
                            throw $de;
                        }
                        break;
                    default:
                        throw new Exception('Acción de orden no válida');
                }
                break;
            case 'enrollment':
                switch ($action) {
                    case 'add':
                        $enrollment = $input['data'];
                        
                        $sql = "INSERT INTO enrollments (user_id, class_name, selected_schedule, class_schedule, enrollment_date, status) 
                                VALUES (?, ?, ?, ?, NOW(), 'active')";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("isss", $enrollment['user_id'], $enrollment['class_name'], 
                                        $enrollment['selected_schedule'], $enrollment['class_schedule']);
                        
                        if (!$stmt->execute()) {
                            throw new Exception('Error al crear la inscripción: ' . $stmt->error);
                        }
                        
                        echo json_encode(['success' => true, 'message' => 'Inscripción creada exitosamente']);
                        break;
                        
                    case 'update_status':
                        $enrollmentId = $input['enrollment_id'];
                        $newStatus = $input['status'];
                        $notes = $input['notes'] ?? '';
                        
                        // Update enrollment status
                        $sql = "UPDATE enrollments SET status = ?, updated_at = NOW() WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $newStatus, $enrollmentId);
                        
                        if (!$stmt->execute()) {
                            throw new Exception('Error al actualizar el estado: ' . $stmt->error);
                        }
                        
                        // Log the status change
                        $sql = "INSERT INTO enrollment_status_log (enrollment_id, old_status, new_status, changed_by, change_date, notes) 
                                VALUES (?, (SELECT status FROM enrollments WHERE id = ?), ?, 'admin', NOW(), ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iiss", $enrollmentId, $enrollmentId, $newStatus, $notes);
                        $stmt->execute();
                        
                        echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
                        break;
                }
                break;
                
            case 'payment':
                switch ($action) {
                    case 'add':
                        $payment = $input['data'];
                        
                        $sql = "INSERT INTO payment_records (enrollment_id, user_id, class_name, amount, payment_method, 
                                payment_status, payment_date, reference_number, notes, recorded_by, created_at, updated_at) 
                                VALUES (?, ?, ?, ?, ?, 'completed', NOW(), ?, ?, 'admin', NOW(), NOW())";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iisdsss", $payment['enrollment_id'], $payment['user_id'], 
                                        $payment['class_name'], $payment['amount'], $payment['payment_method'], 
                                        $payment['reference_number'], $payment['notes']);
                        
                        if (!$stmt->execute()) {
                            throw new Exception('Error al registrar el pago: ' . $stmt->error);
                        }
                        
                        echo json_encode(['success' => true, 'message' => 'Pago registrado exitosamente']);
                        break;
                }
                break;
                
            default:
                throw new Exception('Tipo no válido');
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        closeConnection($conn);
    }
}
?>