<?php
/**
 * Update Enrollment Status
 * Backend handler for admin enrollment management
 */

session_start();
require_once('../config/db_connect.php');
require_once('../config/session_manager.php');
require_once('../includes/email_helper.php');

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
$new_status = strtolower(trim($input['status']));

// Validate status from UI (canonical keys)
$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Estado no válido.'
    ]);
    exit;
}

// Determine allowed enum values in DB for defensive mapping
$allowed_db_statuses = ['pending', 'approved', 'active', 'inactive', 'rejected'];
try {
    $columnResult = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'status'");
    if ($columnResult && $columnRow = $columnResult->fetch_assoc()) {
        if (!empty($columnRow['Type']) && preg_match("/enum\\((.*)\\)/i", $columnRow['Type'], $matches)) {
            $enumValues = array_map(function($value){
                return strtolower(trim(str_replace("'", '', $value)));
            }, explode(',', $matches[1] ?? ''));
            if (!empty($enumValues)) {
                $allowed_db_statuses = $enumValues;
            }
        }
    }
} catch (Exception $ignore) {
    // If introspection fails we keep default fallback list
}

// Helper to select the first allowed status from a preference list
$pickDbStatus = function(array $preferred) use ($allowed_db_statuses) {
    foreach ($preferred as $candidate) {
        if (in_array($candidate, $allowed_db_statuses, true)) {
            return $candidate;
        }
    }
    return $allowed_db_statuses[0] ?? 'pending';
};

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

    // Map UI status to DB status using available enum values
    switch ($new_status) {
        case 'approved':
            $db_status = $pickDbStatus(['active', 'approved']);
            break;
        case 'rejected':
            $db_status = $pickDbStatus(['rejected', 'inactive', 'denied']);
            break;
        default:
            $db_status = $pickDbStatus(['pending']);
            break;
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
        
        // Prepare and send notification email to user based on status
        $notification_sent = false;
        $user_email = $enrollment['email'];
        $user_name = trim(($enrollment['first_name'] ?? '') . ' ' . ($enrollment['last_name'] ?? ''));
        $class_name = $enrollment['class_name'] ?? '';

        $escape = static function ($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $emailMeta = [
            'subject' => 'Actualización de tu sesión - Vale V Photography',
            'headline' => 'Actualización de tu sesión',
            'body' => '',
            'statusLabel' => '',
            'accent' => '#111827'
        ];

        $fromEmail = studio_email_default('from_email', 'info@valevphotography.com');
        $fromName = studio_email_default('from_name', 'Vale V Photography');

        switch ($new_status) {
            case 'approved':
                $emailMeta['subject'] = '¡Tu sesión está confirmada! - Vale V Photography';
                $emailMeta['headline'] = '¡Tu sesión está confirmada!';
                $emailMeta['statusLabel'] = 'Sesión confirmada';
                $emailMeta['accent'] = '#0f172a';
                $emailMeta['body'] = '<p>Hola ' . $escape($user_name) . ',</p>'
                    . '<p>Confirmamos tu sesión <strong>' . $escape($class_name) . '</strong>. Nuestro equipo te contactará en las próximas 24 horas para coordinar locación, horario y vestuario.</p>'
                    . '<p><strong>Próximos pasos:</strong></p>'
                    . '<ul>'
                    . '<li>Recibirás un moodboard con recomendaciones de estilo y maquillaje.</li>'
                    . '<li>Coordinaremos logística final (tiempos, dirección, integrantes).</li>'
                    . '<li>Te compartiremos opciones de inversión y métodos de pago seguros.</li>'
                    . '</ul>'
                    . '<p>Si deseas ajustar algún detalle, responde este correo o escríbenos al <a href="tel:+50686764740" style="color:#0f172a;">+506 8676-4740</a>.</p>'
                    . '<p>Gracias por confiar en Vale V Photography para narrar tu historia.</p>';
                break;
            case 'rejected':
                $emailMeta['subject'] = 'Actualización de tu sesión - Vale V Photography';
                $emailMeta['headline'] = 'Tu solicitud requiere un ajuste';
                $emailMeta['statusLabel'] = 'Seguimiento requerido';
                $emailMeta['accent'] = '#b91c1c';
                $emailMeta['body'] = '<p>Hola ' . $escape($user_name) . ',</p>'
                    . '<p>Por el momento no podemos confirmar la sesión <strong>' . $escape($class_name) . '</strong>. Queremos revisar contigo la mejor alternativa antes de avanzar.</p>'
                    . '<p>Los motivos más comunes son:</p>'
                    . '<ul>'
                    . '<li>Disponibilidad limitada para la fecha solicitada.</li>'
                    . '<li>Requerimientos de producción especiales (locación, horarios, permisos).</li>'
                    . '<li>Solapamiento con otra sesión previamente reservada.</li>'
                    . '</ul>'
                    . '<p>Escríbenos a <a href="mailto:info@valevphotography.com" style="color:#b91c1c;">info@valevphotography.com</a> o al <a href="tel:+50686764740" style="color:#b91c1c;">+506 8676-4740</a> para proponerte nuevas fechas o alternativas personalizadas.</p>'
                    . '<p>Gracias por tu comprensión, estaremos encantados de ayudarte a reprogramar.</p>';
                break;
            case 'pending':
                // No email when reverting to pending
                break;
        }

        if ($emailMeta['body'] !== '' && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $notification_sent = send_branded_email(
                $user_email,
                $emailMeta['subject'],
                $emailMeta['headline'],
                $emailMeta['body'],
                $emailMeta['statusLabel'],
                $emailMeta['accent'],
                [],
                $fromName,
                $fromEmail
            );

            try {
                $logLine = sprintf(
                    "%s - Email to: %s (%s) - Subject: '%s' - Type: enrollment-%s - Result: %s - Sender: %s\n",
                    date('Y-m-d H:i:s'),
                    $user_email,
                    $user_name,
                    $emailMeta['subject'],
                    $new_status,
                    $notification_sent ? 'SENT' : 'NOT_SENT',
                    'Admin'
                );
                @file_put_contents(__DIR__ . '/student_emails_log.txt', $logLine, FILE_APPEND);
            } catch (Throwable $t) {
                // ignore log failures
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado de inscripción actualizado exitosamente.',
            'new_status' => $new_status,
            'db_status' => $db_status,
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