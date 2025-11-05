<?php
/**
 * Student Feedback Management API
 * Handles CRUD operations for instructor feedback
 */

session_start();
require_once('../config/db_connect.php');
require_once('../config/session_manager.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is admin
if (!$isLoggedIn || $userRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Acceso denegado. Solo administradores pueden gestionar feedback.'
    ]);
    exit;
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all feedback records
        $sql = "SELECT f.*, 
                       u.first_name, u.last_name, u.email,
                       e.class_name,
                       i.first_name as instructor_first_name, i.last_name as instructor_last_name
                FROM instructor_feedback f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN enrollments e ON f.enrollment_id = e.id
                LEFT JOIN users i ON f.instructor_id = i.id
                ORDER BY f.class_date DESC, f.created_at DESC";
        
        $result = $conn->query($sql);
        $feedback = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['student_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $row['instructor_name'] = trim($row['instructor_first_name'] . ' ' . $row['instructor_last_name']);
            $feedback[] = $row;
        }
        
        echo json_encode($feedback);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? 'add';
        
        switch ($action) {
            case 'add':
                $user_id = (int)($input['user_id'] ?? 0);
                $enrollment_id = (int)($input['enrollment_id'] ?? 0);
                $instructor_id = (int)($_SESSION['user_id'] ?? 0); // Current admin/instructor
                $class_date = trim($input['class_date'] ?? '');
                $attendance_status = strtolower(trim($input['attendance_status'] ?? 'present'));
                // Enforce allowed attendance values to satisfy DB CHECK constraints
                $allowed_attendance = ['present','absent','late','excused'];
                if (!in_array($attendance_status, $allowed_attendance, true)) {
                    $attendance_status = 'present';
                }
                $performance_rating = (int)($input['performance_rating'] ?? 0);
                $strengths = $input['strengths'] ?? '';
                $areas_for_improvement = $input['areas_for_improvement'] ?? '';
                $general_notes = $input['general_notes'] ?? '';
                $homework_assigned = $input['homework_assigned'] ?? '';
                
                // Validate date format (YYYY-MM-DD)
                $dt = DateTime::createFromFormat('Y-m-d', $class_date);
                $dateValid = $dt && $dt->format('Y-m-d') === $class_date;

                if (!$user_id || !$enrollment_id || !$dateValid || !$performance_rating) {
                    throw new Exception('Datos incompletos. Se requieren todos los campos obligatorios.');
                }
                
                if ($performance_rating < 1 || $performance_rating > 10) {
                    throw new Exception('La calificación debe estar entre 1 y 10.');
                }
                
                $sql = "INSERT INTO instructor_feedback (user_id, enrollment_id, instructor_id, class_date, attendance_status, performance_rating, strengths, areas_for_improvement, general_notes, homework_assigned, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                // Helper to perform insert with current sanitized values (with one retry on CHECK failure)
                $doInsert = function() use ($conn, $sql, &$user_id, &$enrollment_id, &$instructor_id, &$class_date, &$attendance_status, &$performance_rating, &$strengths, &$areas_for_improvement, &$general_notes, &$homework_assigned) {
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) { throw new Exception('Error al preparar consulta: ' . $conn->error); }
                    $stmt->bind_param("iiississss", $user_id, $enrollment_id, $instructor_id, $class_date, $attendance_status, $performance_rating, $strengths, $areas_for_improvement, $general_notes, $homework_assigned);
                    if (!$stmt->execute()) {
                        $err = $stmt->error;
                        $stmt->close();
                        // MySQL 8 CHECK constraint violation code is 3819 typically; we can inspect error string for guidance
                        if (stripos($err, 'check constraint') !== false) {
                            throw new RuntimeException('CHECK_FAIL:' . $err);
                        }
                        throw new Exception('Error al guardar feedback: ' . $err);
                    }
                    $id = $conn->insert_id;
                    $stmt->close();
                    return $id;
                };

                try {
                    $newId = $doInsert();
                } catch (RuntimeException $rex) {
                    // Attempt one retry with safer defaults if CHECK failed
                    // Clamp rating to [1,10] to respect UI scale and likely DB constraint
                    if ($performance_rating > 10) { $performance_rating = 10; }
                    if ($performance_rating < 1) { $performance_rating = 1; }
                    // Force attendance to a safe default
                    $attendance_status = 'present';
                    // Ensure class_date is not empty and valid; fallback to today
                    $dt2 = DateTime::createFromFormat('Y-m-d', $class_date);
                    if (!$dt2 || $dt2->format('Y-m-d') !== $class_date) {
                        $class_date = date('Y-m-d');
                    }
                    // Retry once
                    $newId = $doInsert();
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback guardado exitosamente',
                    'feedback_id' => $newId
                ]);
                break;
                
            case 'update':
                $feedback_id = (int)($input['id'] ?? 0);
                $performance_rating = (int)($input['performance_rating'] ?? 0);
                $strengths = $input['strengths'] ?? '';
                $areas_for_improvement = $input['areas_for_improvement'] ?? '';
                $general_notes = $input['general_notes'] ?? '';
                $homework_assigned = $input['homework_assigned'] ?? '';
                
                if (!$feedback_id || !$performance_rating) {
                    throw new Exception('ID de feedback y calificación son requeridos.');
                }
                
                if ($performance_rating < 1 || $performance_rating > 10) {
                    throw new Exception('La calificación debe estar entre 1 y 10.');
                }
                
                $sql = "UPDATE instructor_feedback SET performance_rating = ?, strengths = ?, areas_for_improvement = ?, general_notes = ?, homework_assigned = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssi", $performance_rating, $strengths, $areas_for_improvement, $general_notes, $homework_assigned, $feedback_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar feedback: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback actualizado exitosamente'
                ]);
                break;
                
            case 'delete':
                $feedback_id = (int)($input['id'] ?? 0);
                
                if (!$feedback_id) {
                    throw new Exception('ID de feedback requerido.');
                }
                
                $sql = "DELETE FROM instructor_feedback WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $feedback_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar feedback: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback eliminado exitosamente'
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
} catch (Exception $e) {
    error_log("Feedback API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}
?>