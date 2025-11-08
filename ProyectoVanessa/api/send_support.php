<?php
session_start();
require_once('../config/db_connect.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Datos inválidos');
    }

    $order_number = trim($input['order_number'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message'] ?? '');

    if ($message === '') {
        throw new Exception('El mensaje es requerido');
    }

    // Basic user context
    $user_id = $_SESSION['user_id'] ?? null;
    $first_name = $_SESSION['first_name'] ?? '';
    $last_name = $_SESSION['last_name'] ?? '';
    $user_email = $_SESSION['email'] ?? '';
    $full_name = trim($first_name . ' ' . $last_name);

    // If we have an order number, validate it belongs to this user
    if ($order_number !== '' && $user_id) {
        $stmt = $conn->prepare('SELECT id, customer_email, customer_name FROM orders WHERE order_number = ? AND user_id = ?');
        $stmt->bind_param('si', $order_number, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows) {
            $ord = $res->fetch_assoc();
            if ($user_email === '') {
                $user_email = $ord['customer_email'] ?? '';
            }
            if ($full_name === '') {
                $full_name = $ord['customer_name'] ?? '';
            }
        }
        $stmt->close();
    }

    // Email targets
    $admin_email = 'vanessa@legenddanceacademy.com';
    $from_email = 'noreply@legenddanceacademy.com';
    $from_name = 'Legend Dance Academy';

    $safeSubject = 'Soporte';
    if ($order_number !== '') { $safeSubject .= ' Pedido #' . $order_number; }
    if ($subject !== '') { $safeSubject .= ' - ' . $subject; }

    // Build HTML email
    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>".
            "<h2>Nuevo mensaje de soporte</h2>".
            ($order_number !== '' ? ("<p><strong>Pedido:</strong> ".htmlspecialchars($order_number)."</p>") : "") .
            (trim($full_name) !== '' ? ("<p><strong>Cliente:</strong> ".htmlspecialchars($full_name)."</p>") : "") .
            ($user_email !== '' ? ("<p><strong>Email:</strong> ".htmlspecialchars($user_email)."</p>") : "") .
            ($subject !== '' ? ("<p><strong>Asunto:</strong> ".htmlspecialchars($subject)."</p>") : "") .
            "<p><strong>Mensaje:</strong><br>".nl2br(htmlspecialchars($message))."</p>".
            "<hr><p style='color:#666;font-size:12px'>Este mensaje fue enviado desde la página de confirmación de pedido.</p>".
            "</body></html>";

    // Send email
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    if ($user_email) {
        $headers .= "Reply-To: $user_email\r\n";
    }

    // Suppress errors in local dev
    $sent = @mail($admin_email, $safeSubject, $html, $headers);

    if (!$sent) {
        throw new Exception('No se pudo enviar el correo en este momento');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) { closeConnection($conn); }
}
