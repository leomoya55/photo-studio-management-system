<?php
require_once '../config/db_connect.php';

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!$conn) {
        throw new Exception('No hay conexión con la base de datos');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $rawInput = file_get_contents('php://input');
        error_log("Failed to decode JSON. Raw input: " . $rawInput);
        throw new Exception('No se recibieron datos válidos JSON. Raw input logged.');
    }
    
    if (!isset($input['action'])) {
        error_log("No action specified. Input: " . print_r($input, true));
        throw new Exception('No se especificó la acción a realizar');
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'add':
            if (!isset($input['class'])) {
                error_log("No class data provided. Input: " . print_r($input, true));
                throw new Exception('No se proporcionaron datos de la clase');
            }
            
            $newClass = $input['class'];
            
            // Debug: Log received data
            error_log("Received class data: " . print_r($newClass, true));
            
            // Validate required fields
            if (empty($newClass['name'])) {
                throw new Exception('El nombre de la clase es requerido');
            }
            if (empty($newClass['instructor'])) {
                throw new Exception('El instructor es requerido');
            }
            
            // Prepare data with defaults
            $name = $newClass['name'] ?? '';
            $description = $newClass['description'] ?? '';
            $level = $newClass['level'] ?? 'Principiante';
            $duration = $newClass['duration'] ?? '60 min';
            $schedule = $newClass['schedule'] ?? '';
            $price = floatval($newClass['price'] ?? 0);
            $instructor = $newClass['instructor'] ?? '';
            // capacity removed
            $ageGroup = $newClass['ageGroup'] ?? '18+ años';
            $category = $newClass['category'] ?? 'General';
            $featured = isset($newClass['featured']) ? 1 : 0;
            
            // Handle benefits properly - ensure it's always valid JSON
            $benefits = [];
            
            // Debug log the raw benefits data
            error_log("Raw benefits data: " . print_r($newClass['benefits'] ?? 'NOT SET', true));
            
            if (isset($newClass['benefits'])) {
                if (is_array($newClass['benefits'])) {
                    $benefits = array_filter($newClass['benefits'], function($benefit) {
                        return !empty(trim($benefit));
                    });
                    // Reindex array to ensure sequential keys
                    $benefits = array_values($benefits);
                } else if (is_string($newClass['benefits']) && !empty($newClass['benefits'])) {
                    // If it's already a JSON string, decode it first
                    $decoded = json_decode($newClass['benefits'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $benefits = array_filter($decoded, function($benefit) {
                            return !empty(trim($benefit));
                        });
                        $benefits = array_values($benefits);
                    } else {
                        // If it's not valid JSON, treat as single benefit
                        $trimmed = trim($newClass['benefits']);
                        if (!empty($trimmed)) {
                            $benefits = [$trimmed];
                        }
                    }
                }
            }
            
            // Ensure we always have valid JSON - use MYSQL compatible JSON
            $benefitsJson = json_encode($benefits, JSON_UNESCAPED_UNICODE);
            if ($benefitsJson === false || $benefitsJson === null) {
                $benefitsJson = '[]'; // Fallback to empty array
                error_log("JSON encoding failed, using fallback: []");
            }
            
            // Additional validation - ensure it's valid JSON
            $testDecode = json_decode($benefitsJson);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $benefitsJson = '[]';
                error_log("JSON validation failed, using fallback: []");
            }
            
            // Debug: Log the JSON that will be inserted
            error_log("Benefits JSON: " . $benefitsJson);
            
            $image = $newClass['image'] ?? '';
            
            // Generate ID from name
            $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            
            // Ensure ID is not empty and doesn't start/end with hyphens
            $id = trim($id, '-');
            if (empty($id)) {
                $id = 'clase-' . time(); // Fallback ID
                error_log("Generated fallback ID: " . $id);
            }
            
            error_log("Generated class ID: " . $id);
            
            // Check if ID already exists
            $checkStmt = $conn->prepare("SELECT id FROM classes WHERE id = ?");
            $checkStmt->bind_param("s", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            if ($result->num_rows > 0) {
                $id = $id . '-' . time(); // Make it unique
                error_log("ID already exists, using: " . $id);
            }
            $checkStmt->close();
            
            $sql = "INSERT INTO classes (id, name, description, level, duration, schedule, price, image, instructor, age_group, category, featured, benefits, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparing statement: ' . $conn->error);
            }
            
            // id, name, description, level, duration, schedule, price, image, instructor, age_group, category, featured, benefits
            $stmt->bind_param("ssssssdssssis", $id, $name, $description, $level, $duration, $schedule, $price, $image, $instructor, $ageGroup, $category, $featured, $benefitsJson);
            
            if (!$stmt->execute()) {
                error_log("SQL Error: " . $stmt->error);
                error_log("Data being inserted: ID=$id, benefits=$benefitsJson");
                throw new Exception('Error al crear la clase: ' . $stmt->error);
            }
            
            $stmt->close();
            break;
            
        case 'edit':
            $classId = $input['id'];
            $updatedClass = $input['class'];
            
            // Debug: Log received edit data
            error_log("Received edit class data: " . print_r($updatedClass, true));
            
            // Prepare update data
            $name = $updatedClass['name'] ?? '';
            $description = $updatedClass['description'] ?? '';
            $level = $updatedClass['level'] ?? 'Principiante';
            $duration = $updatedClass['duration'] ?? '60 min';
            $schedule = $updatedClass['schedule'] ?? '';
            $price = floatval($updatedClass['price'] ?? 0);
            $instructor = $updatedClass['instructor'] ?? '';
            // capacity removed
            $ageGroup = $updatedClass['ageGroup'] ?? '';
            $category = $updatedClass['category'] ?? 'General';
            $featured = isset($updatedClass['featured']) ? 1 : 0;
            
            // Handle benefits for edit (same logic as add)
            $benefits = [];
            if (isset($updatedClass['benefits'])) {
                if (is_array($updatedClass['benefits'])) {
                    $benefits = array_filter($updatedClass['benefits'], function($benefit) {
                        return !empty(trim($benefit));
                    });
                    $benefits = array_values($benefits);
                } else if (is_string($updatedClass['benefits']) && !empty($updatedClass['benefits'])) {
                    $decoded = json_decode($updatedClass['benefits'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $benefits = array_filter($decoded, function($benefit) {
                            return !empty(trim($benefit));
                        });
                        $benefits = array_values($benefits);
                    } else {
                        $trimmed = trim($updatedClass['benefits']);
                        if (!empty($trimmed)) {
                            $benefits = [$trimmed];
                        }
                    }
                }
            }
            
            // Ensure we always have valid JSON - use MYSQL compatible JSON
            $benefitsJson = json_encode($benefits, JSON_UNESCAPED_UNICODE);
            if ($benefitsJson === false || $benefitsJson === null) {
                $benefitsJson = '[]'; // Fallback to empty array
                error_log("JSON encoding failed, using fallback: []");
            }
            
            // Double check the JSON is valid
            $testDecode = json_decode($benefitsJson);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $benefitsJson = '[]';
                error_log("JSON validation failed, using fallback: []");
            }
            
            error_log("Edit Benefits JSON before binding: " . $benefitsJson);
            error_log("Edit Benefits JSON type: " . gettype($benefitsJson));
            error_log("Edit Benefits JSON length: " . strlen($benefitsJson));
            
            // Force the benefits to be a string and escape it properly
            $benefitsJson = (string)$benefitsJson;
            $escapedBenefits = $conn->real_escape_string($benefitsJson);
            
            error_log("Original benefits JSON: " . $benefitsJson);
            error_log("Escaped benefits: " . $escapedBenefits);
            
            // Use direct query for benefits to avoid parameter binding issues
            $sql = "UPDATE classes SET 
                name = ?, 
                description = ?, 
                level = ?, 
                duration = ?, 
                schedule = ?, 
                price = ?, 
                instructor = ?, 
                age_group = ?, 
                category = ?, 
                featured = ?, 
                benefits = '$escapedBenefits', 
                updated_at = NOW() 
                WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparing edit statement: ' . $conn->error);
            }
            
            // Bind parameters (excluding benefits since it's directly in query) - capacity removed
            $bindResult = $stmt->bind_param("sssssdsssis", $name, $description, $level, $duration, $schedule, $price, $instructor, $ageGroup, $category, $featured, $classId);
            
            if (!$bindResult) {
                error_log("Bind param failed: " . $stmt->error);
                throw new Exception('Error binding parameters: ' . $stmt->error);
            }
            
            error_log("Parameters bound successfully");
            
            // Let's try a direct query instead to see if binding is the issue
            error_log("Attempting direct query test...");
            $testQuery = "SELECT benefits FROM classes WHERE id = '$classId'";
            $testResult = $conn->query($testQuery);
            if ($testResult) {
                $beforeData = $testResult->fetch_assoc();
                error_log("Before update - benefits: " . var_export($beforeData['benefits'], true));
            }
            
            if (!$stmt->execute()) {
                error_log("Edit SQL Error: " . $stmt->error);
                error_log("Edit SQL: " . $sql);
                throw new Exception('Error al actualizar la clase: ' . $stmt->error);
            }
            
            // Check what was actually stored
            $verifyQuery = "SELECT benefits FROM classes WHERE id = '$classId'";
            $verifyResult = $conn->query($verifyQuery);
            if ($verifyResult) {
                $afterData = $verifyResult->fetch_assoc();
                error_log("After update - benefits stored as: " . var_export($afterData['benefits'], true));
                error_log("After update - benefits type: " . gettype($afterData['benefits']));
                error_log("After update - benefits length: " . strlen($afterData['benefits']));
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Clase no encontrada o sin cambios');
            }
            
            $stmt->close();
            break;
            
        case 'update_image':
            $classId = $input['id'];
            $newImage = $input['image'];
            
            $sql = "UPDATE classes SET image = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $newImage, $classId);
            
            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar la imagen: ' . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Clase no encontrada');
            }
            
            $stmt->close();
            break;
            
        case 'delete':
            $classId = $input['id'];
            
            // Soft delete - mark as inactive instead of actual deletion
            $sql = "UPDATE classes SET active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $classId);
            
            if (!$stmt->execute()) {
                throw new Exception('Error al eliminar la clase: ' . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Clase no encontrada');
            }
            
            $stmt->close();
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Operación completada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        closeConnection($conn);
    }
}
?>