<?php
/**
 * Simplified Process Enrollment - Temporary Fix
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

$class_id = $input['class_id']; // Keep as string
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
        throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
    }
    
    // Get class information from database by class id (slug)
    $class_info = null;
    $stmt = $conn->prepare("SELECT id, name, schedule FROM classes WHERE id = ? AND active = 1");
    $stmt->bind_param("s", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $class_info = $row;
    }
    $stmt->close();
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
    $stmt = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND class_name = ?");
    $stmt->bind_param("is", $user_id, $class_info['name']);
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
    
    // Check if selected_schedule column exists
    $column_check = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'selected_schedule'");
    $has_schedule_column = $column_check->num_rows > 0;
    
    // Create enrollment record
    if ($has_schedule_column) {
        // Use new structure with selected_schedule
        $stmt = $conn->prepare("
            INSERT INTO enrollments (
                user_id, 
                class_name, 
                selected_schedule,
                enrollment_date, 
                status,
                progress_notes
            ) VALUES (?, ?, ?, NOW(), 'pending', ?)
        ");
        
        $notes = "Solicitud de inscripción enviada desde el portal web";
        if ($selected_schedule) {
            $notes .= " - Horario elegido: " . $selected_schedule;
        }
        $stmt->bind_param("isss", $user_id, $class_info['name'], $selected_schedule, $notes);
    } else {
        // Use old structure without selected_schedule
        $stmt = $conn->prepare("
            INSERT INTO enrollments (
                user_id, 
                class_name, 
                enrollment_date, 
                status,
                progress_notes
            ) VALUES (?, ?, NOW(), 'pending', ?)
        ");
        
        $notes = "Solicitud de inscripción enviada desde el portal web";
        if ($selected_schedule) {
            $notes .= " - Horario elegido: " . $selected_schedule;
        }
        $stmt->bind_param("iss", $user_id, $class_info['name'], $notes);
    }
    
    if ($stmt->execute()) {
        $enrollment_id = $conn->insert_id;
        
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
        throw new Exception('Error al guardar la inscripción en la base de datos: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Detalles: ' . $e->getMessage()
    ]);
}

$conn->close();
?>