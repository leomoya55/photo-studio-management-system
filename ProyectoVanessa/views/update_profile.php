<?php
session_start();
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Sanitize input data
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $medical_conditions = !empty($_POST['medical_conditions']) ? trim($_POST['medical_conditions']) : null;
    $emergency_contact_name = !empty($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : null;
    $emergency_contact_phone = !empty($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : null;
    $emergency_contact_relationship = !empty($_POST['emergency_contact_relationship']) ? trim($_POST['emergency_contact_relationship']) : null;
    
    // Validate weight and height if provided
    if ($weight !== null && ($weight < 20 || $weight > 300)) {
        echo json_encode(['success' => false, 'message' => 'El peso debe estar entre 20 y 300 kg']);
        exit();
    }
    
    if ($height !== null && ($height < 50 || $height > 250)) {
        echo json_encode(['success' => false, 'message' => 'La altura debe estar entre 50 y 250 cm']);
        exit();
    }
    
    // Validate phone number format if provided
    if ($emergency_contact_phone !== null && !preg_match('/^[\d\s\-\+\(\)]+$/', $emergency_contact_phone)) {
        echo json_encode(['success' => false, 'message' => 'Formato de teléfono inválido']);
        exit();
    }
    
    // Update user profile
    $stmt = $conn->prepare("
        UPDATE users SET 
            weight = ?, 
            height = ?, 
            medical_conditions = ?, 
            emergency_contact_name = ?, 
            emergency_contact_phone = ?, 
            emergency_contact_relationship = ?,
            profile_updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "ddsssssi", 
        $weight, 
        $height, 
        $medical_conditions, 
        $emergency_contact_name, 
        $emergency_contact_phone, 
        $emergency_contact_relationship,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Perfil actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Por favor, intenta de nuevo.'
    ]);
}

closeConnection($conn);
?>