<?php
/**
 * Class Sessions Management API
 * Handles CRUD operations for class sessions
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
        'message' => 'Acceso denegado. Solo administradores pueden gestionar sesiones.'
    ]);
    exit;
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all class sessions
        $sql = "SELECT cs.*, c.name as class_name 
                FROM class_sessions cs 
                LEFT JOIN classes c ON cs.class_id = c.id 
                ORDER BY cs.session_date DESC, cs.start_time DESC";
        
        $result = $conn->query($sql);
        $sessions = [];
        
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        echo json_encode($sessions);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Handle form data submission
            $input = $_POST;
        }
        
        $action = $input['action'] ?? 'add';
        
    switch ($action) {
            case 'add':
        // Accept class_id as string (matches classes.id which is a slug)
        $class_id = trim($input['class_id'] ?? '');
                $session_date = $input['session_date'] ?? '';
                $start_time = $input['start_time'] ?? '';
                $end_time = $input['end_time'] ?? '';
                $status = $input['status'] ?? 'scheduled';
                $capacity_override = !empty($input['capacity_override']) ? (int)$input['capacity_override'] : null;
                $notes = $input['notes'] ?? '';
                
        if (!$class_id || !$session_date || !$start_time || !$end_time) {
                    throw new Exception('Datos incompletos. Se requieren todos los campos obligatorios.');
                }
                
        $sql = "INSERT INTO class_sessions (class_id, session_date, start_time, end_time, status, capacity_override, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
        // Bind: class_id(s), session_date(s), start_time(s), end_time(s), status(s), capacity_override(i), notes(s)
        $stmt->bind_param("sssssis", $class_id, $session_date, $start_time, $end_time, $status, $capacity_override, $notes);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al crear la sesión: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión creada exitosamente',
                    'session_id' => $conn->insert_id
                ]);
                break;
                
            case 'update':
                $session_id = (int)($input['id'] ?? 0);
                $status = $input['status'] ?? '';
                
                if (!$session_id || !$status) {
                    throw new Exception('ID de sesión y estado son requeridos.');
                }
                
                $sql = "UPDATE class_sessions SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $session_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar la sesión: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado de sesión actualizado exitosamente'
                ]);
                break;
                
            case 'delete':
                $session_id = (int)($input['id'] ?? 0);
                
                if (!$session_id) {
                    throw new Exception('ID de sesión requerido.');
                }
                
                // Check if session has attendance records
                $check_sql = "SELECT COUNT(*) as count FROM attendance WHERE session_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $session_id);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_assoc()['count'];
                
                if ($count > 0) {
                    throw new Exception('No se puede eliminar la sesión porque tiene registros de asistencia.');
                }
                
                $sql = "DELETE FROM class_sessions WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $session_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar la sesión: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión eliminada exitosamente'
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
} catch (Exception $e) {
    error_log("Sessions API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}
?>