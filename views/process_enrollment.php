<?php
/**
 * Process Class Enrollment
 * Handles user enrollment in classes with admin approval system
 */

session_start();
require_once('../config/db_connect.php');
require_once('../config/session_manager.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Debes estar logueado para inscribirte a una clase'
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

if (!$input || !isset($input['class_id']) || !isset($input['action'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Datos de inscripción incompletos'
    ]);
    exit;
}

$class_id = $input['class_id']; // Keep as string, don't cast to int
$user_id = $_SESSION['user_id'];
$action = $input['action'];
$selected_schedule = $input['selected_schedule'] ?? '';

if ($action !== 'enroll') {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

try {
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Get class information from JSON file
    $classes_file = '../data/classes.json';
    if (!file_exists($classes_file)) {
        throw new Exception('Archivo de clases no encontrado');
    }
    
    $classes_data = json_decode(file_get_contents($classes_file), true);
    $class_info = null;
    
    // Debug: Log the search for troubleshooting
    error_log("Looking for class_id: " . $class_id . " (type: " . gettype($class_id) . ")");
    
    foreach ($classes_data as $class) {
        if ($class['id'] === $class_id) {
            $class_info = $class;
            break;
        }
    }
    
    // Debug: If not found, log available IDs
    if (!$class_info) {
        $available_ids = array_column($classes_data, 'id');
        error_log("Available class IDs: " . implode(', ', $available_ids));
    }
    
    if (!$class_info) {
        echo json_encode(['success' => false, 'message' => 'Clase no encontrada']);
        exit;
    }
    
    // Get user information
    $stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_info = $user_result->fetch_assoc();
    
    if (!$user_info) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Check if user is already enrolled in this class
    $stmt = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND class_id = ?");
    $stmt->bind_param("is", $user_id, $class_id); // Changed to "is" for int user_id and string class_id
    $stmt->execute();
    $existing_enrollment = $stmt->get_result()->fetch_assoc();
    
    if ($existing_enrollment) {
        $status_message = '';
        switch ($existing_enrollment['status']) {
            case 'pending':
                $status_message = 'Ya tienes una solicitud pendiente para esta clase';
                break;
            case 'approved':
                $status_message = 'Ya estás inscrito en esta clase';
                break;
            case 'active':
                $status_message = 'Ya estás activo en esta clase';
                break;
            case 'rejected':
                $status_message = 'Tu solicitud anterior fue rechazada. Contacta a administración para más información';
                break;
            default:
                $status_message = 'Ya tienes un registro para esta clase';
        }
        
        echo json_encode(['success' => false, 'message' => $status_message]);
        exit;
    }
    
    // Create enrollment record
    $stmt = $conn->prepare("
        INSERT INTO enrollments (
            user_id, 
            class_id, 
            class_name, 
            selected_schedule,
            enrollment_date, 
            status,
            notes
        ) VALUES (?, ?, ?, ?, NOW(), 'pending', ?)
    ");
    
    $notes = "Solicitud de inscripción enviada desde el portal web";
    if ($selected_schedule) {
        $notes .= " - Horario elegido: " . $selected_schedule;
    }
    $stmt->bind_param("issss", $user_id, $class_id, $class_info['name'], $selected_schedule, $notes);
    
    if ($stmt->execute()) {
        $enrollment_id = $conn->insert_id;
        
        // Send notification email to admin (optional)
        $admin_email = "admin@valevphotography.com"; // Configure this
        $subject = "Nueva Solicitud de Inscripción - Vale V Photography";
        $message = "
        <h3>Nueva Solicitud de Inscripción</h3>
        <p><strong>Estudiante:</strong> {$user_info['first_name']} {$user_info['last_name']} ({$user_info['email']})</p>
        <p><strong>Clase:</strong> {$class_info['name']}</p>
        <p><strong>Nivel:</strong> {$class_info['level']}</p>
        <p><strong>Horarios Disponibles:</strong> {$class_info['schedule']}</p>" . 
        ($selected_schedule ? "<p><strong>Horario Elegido por el Estudiante:</strong> <span style='color: #000000; font-weight: bold;'>{$selected_schedule}</span></p>" : "") . "
        <p><strong>Precio:</strong> ₡{$class_info['price']}/mes</p>
        <p><strong>Fecha de Solicitud:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>ID de Inscripción:</strong> {$enrollment_id}</p>
        
        <p>Por favor, revisa y aprueba esta solicitud en el panel de administración.</p>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Vale V Photography <noreply@valevphotography.com>',
        ];
        
        // Send email (uncomment when ready)
        // mail($admin_email, $subject, $message, implode("\r\n", $headers));
        
        $success_message = '¡Solicitud enviada exitosamente! Tu inscripción está pendiente de aprobación por parte de la instructora Vanessa.';
        if ($selected_schedule) {
            $success_message .= ' Horario elegido: ' . $selected_schedule . '.';
        }
        $success_message .= ' Te contactaremos pronto.';
        
        echo json_encode([
            'success' => true,
            'message' => $success_message,
            'enrollment_id' => $enrollment_id,
            'status' => 'pending',
            'selected_schedule' => $selected_schedule
        ]);
        
    } else {
        throw new Exception('Error al guardar la inscripción en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo más tarde.'
    ]);
}

$conn->close();
?>