<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!$conn) {
        throw new Exception('No hay conexión con la base de datos');
    }

    // Fetch all active classes (capacity removed)
    $sql = "SELECT id, name, description, level, duration, schedule, price, image, instructor, age_group, category, featured, benefits, created_at, updated_at FROM classes WHERE active = 1 ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Error al obtener las clases: ' . $conn->error);
    }
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        // Convert numeric strings back to proper types
        $row['price'] = floatval($row['price']);
    // capacity removed; no longer returned
        $row['featured'] = (bool)$row['featured'];
        
        // Decode JSON benefits if it exists
        if (!empty($row['benefits'])) {
            $row['benefits'] = json_decode($row['benefits'], true);
        } else {
            $row['benefits'] = [];
        }
        
        // Rename age_group to ageGroup for consistency with frontend
        $row['ageGroup'] = $row['age_group'];
        unset($row['age_group']);
        
        $classes[] = $row;
    }
    
    echo json_encode($classes);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        closeConnection($conn);
    }
}
?>