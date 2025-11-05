<?php
/**
 * Delete Enrollment API
 * Safely removes an enrollment record from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

require_once '../config/db_connect.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['enrollment_id']) || !is_numeric($input['enrollment_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de inscripción inválido']);
        exit;
    }
    
    $enrollment_id = (int)$input['enrollment_id'];
    
    // First, get enrollment details for logging
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.user_id,
            e.class_name,
            e.selected_schedule,
            e.status,
            e.enrollment_date,
            u.first_name,
            u.last_name,
            u.email
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        WHERE e.id = ?
    ");
    
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Inscripción no encontrada']);
        exit;
    }
    
    $enrollment = $result->fetch_assoc();
    $stmt->close();
    
    // Log the deletion attempt for security
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $student_name = $enrollment['first_name'] . ' ' . $enrollment['last_name'];
    
    error_log("ENROLLMENT DELETION: Admin {$admin_name} (ID: {$admin_id}) deleted enrollment ID {$enrollment_id} for student {$student_name} ({$enrollment['email']}) in class {$enrollment['class_name']}");
    
    // Start transaction for safety
    $conn->begin_transaction();
    
    try {
        // Delete related records first (if any)
        // Note: Add here any related tables that reference enrollments
        
        // Delete attendance records if they exist
        if ($conn->query("SHOW TABLES LIKE 'attendance'")->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM attendance WHERE enrollment_id = ?");
            $stmt->bind_param("i", $enrollment_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete payment records if they exist and reference this enrollment
        if ($conn->query("SHOW TABLES LIKE 'payment_records'")->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM payment_records WHERE enrollment_id = ?");
            $stmt->bind_param("i", $enrollment_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Finally, delete the enrollment
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Commit the transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Inscripción de {$student_name} en {$enrollment['class_name']} eliminada exitosamente",
                    'deleted_enrollment' => [
                        'id' => $enrollment_id,
                        'student' => $student_name,
                        'class' => $enrollment['class_name'],
                        'status' => $enrollment['status']
                    ]
                ]);
            } else {
                throw new Exception('No se encontró la inscripción para eliminar');
            }
        } else {
            throw new Exception('Error al ejecutar la eliminación');
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error deleting enrollment: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al eliminar la inscripción: ' . $e->getMessage()
    ]);
}

$conn->close();
?>