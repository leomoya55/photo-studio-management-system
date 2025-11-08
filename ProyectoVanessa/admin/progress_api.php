<?php
/**
 * User Progress Management API
 * Handles CRUD operations for student progress tracking
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
        'message' => 'Acceso denegado. Solo administradores pueden gestionar progreso de estudiantes.'
    ]);
    exit;
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all user progress records
        $sql = "SELECT p.*, 
                       u.first_name, u.last_name, u.email,
                       i.first_name as instructor_first_name, i.last_name as instructor_last_name
                FROM user_progress p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN users i ON p.instructor_id = i.id
                ORDER BY p.assessment_date DESC, p.created_at DESC";
        
        $result = $conn->query($sql);
        $progress_records = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['student_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $row['instructor_name'] = trim($row['instructor_first_name'] . ' ' . $row['instructor_last_name']);
            $progress_records[] = $row;
        }
        
        echo json_encode($progress_records);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? 'add';
        
        switch ($action) {
            case 'add':
                $user_id = (int)($input['user_id'] ?? 0);
                $class_name = $input['class_name'] ?? '';
                $skill_level = $input['skill_level'] ?? 'principiante';
                $achievements = $input['achievements'] ?? '';
                $areas_improvement = $input['areas_improvement'] ?? '';
                $goals = $input['goals'] ?? '';
                $assessment_date = $input['assessment_date'] ?? date('Y-m-d');
                $instructor_id = (int)($input['instructor_id'] ?? $_SESSION['user_id']);
                $notes = $input['notes'] ?? '';
                
                if (!$user_id || !$class_name) {
                    throw new Exception('Se requiere estudiante y clase.');
                }
                
                $sql = "INSERT INTO user_progress (user_id, class_name, skill_level, achievements, areas_improvement, goals, assessment_date, instructor_id, notes, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssssis", $user_id, $class_name, $skill_level, $achievements, $areas_improvement, $goals, $assessment_date, $instructor_id, $notes);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al registrar progreso: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Progreso registrado exitosamente',
                    'progress_id' => $conn->insert_id
                ]);
                break;
                
            case 'update':
                $progress_id = (int)($input['id'] ?? 0);
                $skill_level = $input['skill_level'] ?? '';
                $achievements = $input['achievements'] ?? '';
                $areas_improvement = $input['areas_improvement'] ?? '';
                $goals = $input['goals'] ?? '';
                $assessment_date = $input['assessment_date'] ?? '';
                $notes = $input['notes'] ?? '';
                
                if (!$progress_id) {
                    throw new Exception('ID de progreso requerido.');
                }
                
                $sql = "UPDATE user_progress SET skill_level = ?, achievements = ?, areas_improvement = ?, goals = ?, assessment_date = ?, notes = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $skill_level, $achievements, $areas_improvement, $goals, $assessment_date, $notes, $progress_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar progreso: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Progreso actualizado exitosamente'
                ]);
                break;
                
            case 'delete':
                $progress_id = (int)($input['id'] ?? 0);
                
                if (!$progress_id) {
                    throw new Exception('ID de progreso requerido.');
                }
                
                $sql = "DELETE FROM user_progress WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $progress_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar registro de progreso: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro de progreso eliminado exitosamente'
                ]);
                break;
                
            case 'get_student_progress':
                $user_id = (int)($input['user_id'] ?? 0);
                
                if (!$user_id) {
                    throw new Exception('ID de estudiante requerido.');
                }
                
                $sql = "SELECT p.*, 
                               u.first_name, u.last_name,
                               i.first_name as instructor_first_name, i.last_name as instructor_last_name
                        FROM user_progress p
                        LEFT JOIN users u ON p.user_id = u.id
                        LEFT JOIN users i ON p.instructor_id = i.id
                        WHERE p.user_id = ?
                        ORDER BY p.assessment_date DESC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $progress_history = [];
                while ($row = $result->fetch_assoc()) {
                    $row['student_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
                    $row['instructor_name'] = trim($row['instructor_first_name'] . ' ' . $row['instructor_last_name']);
                    $progress_history[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'progress_history' => $progress_history
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
} catch (Exception $e) {
    error_log("User Progress API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}
?>