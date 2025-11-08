<?php
/**
 * Attendance Management API
 * Handles CRUD operations for attendance records
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
        'message' => 'Acceso denegado. Solo administradores pueden gestionar asistencia.'
    ]);
    exit;
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all attendance records
        $sql = "SELECT a.*, 
                       u.first_name, u.last_name, u.email,
                       cs.session_date, cs.start_time, cs.end_time,
                       c.name as class_name,
                       e.class_name as enrollment_class
                FROM attendance a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN class_sessions cs ON a.session_id = cs.id
                LEFT JOIN classes c ON cs.class_id = c.id
                LEFT JOIN enrollments e ON a.enrollment_id = e.id
                ORDER BY cs.session_date DESC, cs.start_time DESC";
        
        $result = $conn->query($sql);
        $attendance = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $attendance[] = $row;
        }
        
        echo json_encode($attendance);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? 'add';
        
        switch ($action) {
            case 'add':
                $session_id = (int)($input['session_id'] ?? 0);
                $user_id = (int)($input['user_id'] ?? 0);
                $enrollment_id = (int)($input['enrollment_id'] ?? 0);
                $attended = (int)($input['attended'] ?? 0);
                // Optional fields (UI may not send these anymore)
                $late_minutes = (int)($input['late_minutes'] ?? 0);
                $early_departure_minutes = (int)($input['early_departure_minutes'] ?? 0);
                $notes = $input['notes'] ?? '';
                $recorded_by = $_SESSION['user_id'];
                
                if (!$session_id || !$user_id) {
                    throw new Exception('Se requiere sesión y estudiante.');
                }
                
                // Auto-resolve enrollment_id if not provided (or zero)
                if (!$enrollment_id) {
                    // Find class name from session -> classes
                    $className = null;
                    $cs_sql = "SELECT c.name AS class_name FROM class_sessions cs LEFT JOIN classes c ON cs.class_id = c.id WHERE cs.id = ?";
                    if ($cs_stmt = $conn->prepare($cs_sql)) {
                        $cs_stmt->bind_param("i", $session_id);
                        $cs_stmt->execute();
                        $cs_res = $cs_stmt->get_result();
                        if ($cs_row = $cs_res->fetch_assoc()) {
                            $className = $cs_row['class_name'] ?? null;
                        }
                        $cs_stmt->close();
                    }
                    if ($className) {
                        // Look for the most recent valid enrollment for this user and class name
                        $enr_sql = "SELECT id FROM enrollments WHERE user_id = ? AND class_name = ? AND status IN ('active','approved','accepted') ORDER BY enrollment_date DESC LIMIT 1";
                        if ($enr_stmt = $conn->prepare($enr_sql)) {
                            $enr_stmt->bind_param("is", $user_id, $className);
                            $enr_stmt->execute();
                            $enr_res = $enr_stmt->get_result();
                            if ($enr_row = $enr_res->fetch_assoc()) {
                                $enrollment_id = (int)$enr_row['id'];
                            }
                            $enr_stmt->close();
                        }
                    }
                    // If still no enrollment, block with a helpful error instead of DB FK error
                    if (!$enrollment_id) {
                        throw new Exception("El estudiante no tiene una inscripción activa para la clase '{$className}'. Crea o aprueba una inscripción antes de registrar asistencia.");
                    }
                }
                
                // Check if attendance already exists for this session and user
                $check_sql = "SELECT id FROM attendance WHERE session_id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $session_id, $user_id);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    throw new Exception('Ya existe un registro de asistencia para este estudiante en esta sesión.');
                }
                
        $sql = "INSERT INTO attendance (session_id, user_id, enrollment_id, attended, late_minutes, early_departure_minutes, notes, recorded_at, recorded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiiisi", $session_id, $user_id, $enrollment_id, $attended, $late_minutes, $early_departure_minutes, $notes, $recorded_by);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al registrar asistencia: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Asistencia registrada exitosamente',
                    'attendance_id' => $conn->insert_id
                ]);
                break;
                
            case 'update':
                $attendance_id = (int)($input['id'] ?? 0);
                $attended = (int)($input['attended'] ?? 0);
                // Optional, default to zero when not provided
                $late_minutes = (int)($input['late_minutes'] ?? 0);
                $early_departure_minutes = (int)($input['early_departure_minutes'] ?? 0);
                $notes = $input['notes'] ?? '';
                
                if (!$attendance_id) {
                    throw new Exception('ID de asistencia requerido.');
                }
                
                $sql = "UPDATE attendance SET attended = ?, late_minutes = ?, early_departure_minutes = ?, notes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                // attended (i), late (i), early (i), notes (s), id (i)
                $stmt->bind_param("iiisi", $attended, $late_minutes, $early_departure_minutes, $notes, $attendance_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar asistencia: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Asistencia actualizada exitosamente'
                ]);
                break;
                
            case 'delete':
                $attendance_id = (int)($input['id'] ?? 0);
                
                if (!$attendance_id) {
                    throw new Exception('ID de asistencia requerido.');
                }
                
                $sql = "DELETE FROM attendance WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $attendance_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar registro de asistencia: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro de asistencia eliminado exitosamente'
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
} catch (Exception $e) {
    error_log("Attendance API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}
?>