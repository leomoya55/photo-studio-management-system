<?php
session_start();
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is admin/instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Get and validate input data
    $enrollment_id = intval($_POST['enrollment_id']);
    $user_id = intval($_POST['user_id']);
    $instructor_id = intval($_POST['instructor_id']);
    $class_date = $_POST['class_date'];
    $attendance_status = $_POST['attendance_status'];
    $performance_rating = !empty($_POST['performance_rating']) ? intval($_POST['performance_rating']) : null;
    $strengths = !empty($_POST['strengths']) ? trim($_POST['strengths']) : null;
    $areas_for_improvement = !empty($_POST['areas_for_improvement']) ? trim($_POST['areas_for_improvement']) : null;
    $general_notes = !empty($_POST['general_notes']) ? trim($_POST['general_notes']) : null;
    $homework_assigned = !empty($_POST['homework_assigned']) ? trim($_POST['homework_assigned']) : null;
    
    // Validate required fields
    if (empty($enrollment_id) || empty($user_id) || empty($instructor_id) || empty($class_date) || empty($attendance_status)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit();
    }
    
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $class_date)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit();
    }
    
    // Validate attendance status
    $valid_attendance = ['present', 'absent', 'late'];
    if (!in_array($attendance_status, $valid_attendance)) {
        echo json_encode(['success' => false, 'message' => 'Estado de asistencia inválido']);
        exit();
    }
    
    // Validate rating if provided
    if ($performance_rating !== null && ($performance_rating < 1 || $performance_rating > 5)) {
        echo json_encode(['success' => false, 'message' => 'La calificación debe estar entre 1 y 5']);
        exit();
    }
    
    // Check if feedback already exists for this date and enrollment
    $stmt = $conn->prepare("SELECT id FROM instructor_feedback WHERE enrollment_id = ? AND class_date = ?");
    $stmt->bind_param("is", $enrollment_id, $class_date);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update existing feedback
        $stmt = $conn->prepare("
            UPDATE instructor_feedback SET 
                attendance_status = ?,
                performance_rating = ?,
                strengths = ?,
                areas_for_improvement = ?,
                general_notes = ?,
                homework_assigned = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sissssi",
            $attendance_status,
            $performance_rating,
            $strengths,
            $areas_for_improvement,
            $general_notes,
            $homework_assigned,
            $existing['id']
        );
        $action = 'actualizada';
    } else {
        // Insert new feedback
        $stmt = $conn->prepare("
            INSERT INTO instructor_feedback (
                user_id, enrollment_id, instructor_id, class_date, 
                attendance_status, performance_rating, strengths, 
                areas_for_improvement, general_notes, homework_assigned
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiississss",
            $user_id,
            $enrollment_id,
            $instructor_id,
            $class_date,
            $attendance_status,
            $performance_rating,
            $strengths,
            $areas_for_improvement,
            $general_notes,
            $homework_assigned
        );
        $action = 'guardada';
    }
    
    if ($stmt->execute()) {
        // Update attendance count in enrollments table if present
        if ($attendance_status === 'present') {
            $update_stmt = $conn->prepare("
                UPDATE enrollments SET 
                    total_classes_attended = total_classes_attended + 1,
                    last_attendance_date = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("si", $class_date, $enrollment_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Evaluación $action exitosamente"
        ]);
    } else {
        throw new Exception('Error al guardar en la base de datos');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Feedback save error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}

closeConnection($conn);
?>