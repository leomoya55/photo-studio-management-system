<?php
/**
 * Payment Records Management API
 * Handles CRUD operations for payment records
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
        'message' => 'Acceso denegado. Solo administradores pueden gestionar pagos.'
    ]);
    exit;
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all payment records
    $sql = "SELECT p.*, 
               u.first_name, u.last_name, u.email,
               e.class_name,
               r.first_name as recorded_by_first_name, r.last_name as recorded_by_last_name
        FROM payment_records p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN enrollments e ON p.enrollment_id = e.id
        LEFT JOIN users r ON p.recorded_by = r.id
        ORDER BY p.payment_date DESC, p.created_at DESC";
        
        $result = $conn->query($sql);
        $payments = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['student_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $row['recorded_by_name'] = trim(($row['recorded_by_first_name'] ?? '') . ' ' . ($row['recorded_by_last_name'] ?? ''));
            // Ensure class_name is helpful for non-enrollment (product) payments
            if ((int)($row['enrollment_id'] ?? 0) === 0) {
                if (empty($row['class_name'])) {
                    $row['class_name'] = 'Productos';
                }
            }
            // Ensure payment_method is present; attempt a light inference as fallback
            $pm = strtolower(trim($row['payment_method'] ?? ''));
            if ($pm === '' || $pm === 'null') {
                $hint = strtolower(trim(($row['reference_number'] ?? '') . ' ' . ($row['notes'] ?? '')));
                if (strpos($hint, 'sinpe') !== false) { $pm = 'sinpe'; }
                elseif (strpos($hint, 'efectivo') !== false || strpos($hint, 'cash') !== false) { $pm = 'efectivo'; }
                elseif (strpos($hint, 'tarjeta') !== false || strpos($hint, 'card') !== false) { $pm = 'tarjeta'; }
                else { $pm = 'sinpe'; }
                $row['payment_method'] = $pm;
            }
            $payments[] = $row;
        }
        
        echo json_encode($payments);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? 'add';
        
        switch ($action) {
            case 'add':
                $enrollment_id = isset($input['enrollment_id']) ? (int)$input['enrollment_id'] : 0;
                if ($enrollment_id <= 0) {
                    $enrollment_id = null;
                }
                $user_id = (int)($input['user_id'] ?? 0);
                $amount = floatval($input['amount'] ?? 0);
                // Normalize/sanitize payment method
                $payment_method = strtolower(trim($input['payment_method'] ?? ''));
                $allowed_methods = ['sinpe', 'efectivo', 'tarjeta'];
                if (!in_array($payment_method, $allowed_methods, true)) {
                    $hint = strtolower(trim(($input['reference_number'] ?? '') . ' ' . ($input['notes'] ?? '')));
                    if (strpos($hint, 'efectivo') !== false || strpos($hint, 'cash') !== false) $payment_method = 'efectivo';
                    elseif (strpos($hint, 'tarjeta') !== false || strpos($hint, 'card') !== false) $payment_method = 'tarjeta';
                    else $payment_method = 'sinpe';
                }
                $payment_status = $input['payment_status'] ?? 'completed';
                $payment_date = $input['payment_date'] ?? date('Y-m-d');
                $reference_number = $input['reference_number'] ?? '';
                $notes = $input['notes'] ?? '';
                $recorded_by = $_SESSION['user_id'];
                
                if (!$user_id || !$amount || $amount <= 0) {
                    throw new Exception('Se requiere estudiante y monto válido.');
                }
                
                // Get class name from enrollment if enrollment_id is provided
                $class_name = '';
                if (!is_null($enrollment_id) && $enrollment_id > 0) {
                    $class_sql = "SELECT class_name FROM enrollments WHERE id = ?";
                    $class_stmt = $conn->prepare($class_sql);
                    $class_stmt->bind_param("i", $enrollment_id);
                    $class_stmt->execute();
                    $class_result = $class_stmt->get_result()->fetch_assoc();
                    $class_name = $class_result['class_name'] ?? '';
                } else {
                    $class_name = 'Pago General';
                }
                
                if (is_null($enrollment_id)) {
                    try {
                        $nullableCheck = $conn->query("SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'payment_records' AND column_name = 'enrollment_id' LIMIT 1");
                        if ($nullableCheck) {
                            $columnMeta = $nullableCheck->fetch_assoc();
                            if (isset($columnMeta['IS_NULLABLE']) && strtoupper($columnMeta['IS_NULLABLE']) === 'NO') {
                                $conn->query("ALTER TABLE payment_records MODIFY enrollment_id INT NULL");
                            }
                            $nullableCheck->free();
                        }
                    } catch (Throwable $schemaAdjustError) {
                        // If the schema update fails we continue; insertion will raise a readable error.
                    }
                    $sql = "INSERT INTO payment_records (enrollment_id, user_id, class_name, amount, payment_method, payment_status, payment_date, reference_number, notes, recorded_by, created_at, updated_at) 
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isdsssssi", $user_id, $class_name, $amount, $payment_method, $payment_status, $payment_date, $reference_number, $notes, $recorded_by);
                } else {
                    $sql = "INSERT INTO payment_records (enrollment_id, user_id, class_name, amount, payment_method, payment_status, payment_date, reference_number, notes, recorded_by, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisdsssssi", $enrollment_id, $user_id, $class_name, $amount, $payment_method, $payment_status, $payment_date, $reference_number, $notes, $recorded_by);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al registrar pago: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago registrado exitosamente',
                    'payment_id' => $conn->insert_id
                ]);
                break;
                
            case 'update':
                $payment_id = (int)($input['id'] ?? 0);
                $amount = floatval($input['amount'] ?? 0);
                $payment_method = $input['payment_method'] ?? '';
                $payment_status = $input['payment_status'] ?? '';
                $reference_number = $input['reference_number'] ?? '';
                $notes = $input['notes'] ?? '';
                
                if (!$payment_id || !$amount || $amount <= 0) {
                    throw new Exception('ID de pago y monto válido son requeridos.');
                }
                
                $sql = "UPDATE payment_records SET amount = ?, payment_method = ?, payment_status = ?, reference_number = ?, notes = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("dssssi", $amount, $payment_method, $payment_status, $reference_number, $notes, $payment_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar pago: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago actualizado exitosamente'
                ]);
                break;
                
            case 'delete':
                $payment_id = (int)($input['id'] ?? 0);
                
                if (!$payment_id) {
                    throw new Exception('ID de pago requerido.');
                }
                
                $sql = "DELETE FROM payment_records WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $payment_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar registro de pago: ' . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro de pago eliminado exitosamente'
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
} catch (Exception $e) {
    error_log("Payment Records API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}
?>