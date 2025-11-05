<?php
/**
 * Update Enrollment Status
 * Backend handler for admin enrollment management
 */

session_start();
require_once('../config/db_connect.php');
require_once('../config/session_manager.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is admin
if (!$isLoggedIn || $userRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Acceso denegado. Solo administradores pueden gestionar inscripciones.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['enrollment_id']) || !isset($input['status'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Datos incompletos. Se requiere enrollment_id y status.'
    ]);
    exit;
}

$enrollment_id = (int)$input['enrollment_id'];
$new_status = trim($input['status']);

// Validate status from UI (canonical keys)
$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Estado no válido.'
    ]);
    exit;
}

try {
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Get enrollment information before updating
    $stmt = $conn->prepare("
        SELECT e.*, u.first_name, u.last_name, u.email 
        FROM enrollments e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    
    if (!$enrollment) {
        echo json_encode([
            'success' => false, 
            'message' => 'Inscripción no encontrada.'
        ]);
        exit;
    }
    
    // Preserve old status for logging
    $old_status = $enrollment['status'];

    // Map UI status to DB status (legacy schema uses 'active' for approved)
    $db_status = $new_status;
    if ($new_status === 'approved') {
        $db_status = 'active';
    } else if ($new_status === 'pending') {
        $db_status = 'pending';
    } else if ($new_status === 'rejected') {
        $db_status = 'rejected';
    }

    // Update enrollment status in DB
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $db_status, $enrollment_id);
    
    if ($stmt->execute()) {
        // Log the status change
        $log_stmt = $conn->prepare("
            INSERT INTO enrollment_status_log (enrollment_id, old_status, new_status, changed_by, change_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $admin_id = $_SESSION['user_id'];
        $log_stmt->bind_param("issi", $enrollment_id, $old_status, $new_status, $admin_id);
        $log_stmt->execute();
        
        // Send notification email to user based on status
        $notification_sent = false;
        $user_email = $enrollment['email'];
        $user_name = $enrollment['first_name'] . ' ' . $enrollment['last_name'];
        $class_name = $enrollment['class_name'];
        
        $email_subject = "Actualización de tu Inscripción - Academia Legend";
        $email_body = "";
        
        switch ($new_status) {
            case 'approved':
                $email_subject = "¡Inscripción Aprobada! - Academia Legend";
                $email_body = "
                <h3>¡Felicitaciones {$user_name}!</h3>
                <p>Tu inscripción en la clase <strong>{$class_name}</strong> ha sido aprobada por nuestra instructora Vanessa.</p>
                <p><strong>Próximos pasos:</strong></p>
                <ul>
                    <li>Te contactaremos en las próximas 24 horas para coordinar tu primera clase</li>
                    <li>Te enviaremos información sobre el horario y preparación</li>
                    <li>Recibirás detalles sobre el método de pago</li>
                </ul>
                <p>¡Esperamos verte pronto en nuestro estudio!</p>
                ";
                $notification_sent = true;
                break;
                
            case 'rejected':
                $email_subject = "Actualización de tu Inscripción - Academia Legend";
                $email_body = "
                <h3>Hola {$user_name},</h3>
                <p>Lamentamos informarte que tu inscripción en <strong>{$class_name}</strong> no pudo ser procesada en este momento.</p>
                <p>Esto puede deberse a:</p>
                <ul>
                    <li>Cupo completo en la clase</li>
                    <li>Requisitos específicos no cumplidos</li>
                    <li>Horarios no disponibles</li>
                </ul>
                <p>Te invitamos a contactarnos para explorar otras opciones de clases que puedan interesarte.</p>
                <p>Teléfono: +1 (555) 123-4567</p>
                ";
                $notification_sent = true;
                break;
            case 'pending':
                // No email on revert to pending
                $notification_sent = false;
                break;
        }
        
        // Send notification email (best-effort) and log
        if ($notification_sent && $email_body) {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Academia Legend <info@academialegend.com>',
            ];

            $full_email = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%); color: white; padding: 20px; text-align: center;'>
                    <h1>Academia Legend</h1>
                    <p>Actualización de tu Inscripción</p>
                </div>
                <div style='padding: 20px; background: #f9f9f9;'>
                    {$email_body}
                </div>
                <div style='padding: 20px; text-align: center; font-size: 12px; color: #666;'>
                    <p>Academia de Danza Legend<br>
                    Transformando vidas a través de la danza desde 2008</p>
                </div>
            </div>";

            // Try to send email (may be disabled in local env)
            @mail($user_email, $email_subject, $full_email, implode("\r\n", $headers));

            // Always log to file for audit
            try {
                $logLine = sprintf(
                    "%s - Email to: %s (%s) - Subject: '%s' - Type: enrollment-%s - Sender: %s\n",
                    date('Y-m-d H:i:s'),
                    $user_email,
                    $user_name,
                    $email_subject,
                    $new_status,
                    'Admin'
                );
                @file_put_contents(__DIR__ . '/student_emails_log.txt', $logLine, FILE_APPEND);
            } catch (Throwable $t) { /* ignore */ }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado de inscripción actualizado exitosamente.',
            'new_status' => $new_status,
            'enrollment_id' => $enrollment_id,
            'notification_sent' => $notification_sent
        ]);
        
    } else {
        throw new Exception('Error al actualizar el estado de la inscripción');
    }
    
} catch (Exception $e) {
    error_log("Enrollment status update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo más tarde.'
    ]);
}

$conn->close();
?>