<?php
// api/get_classes.php - Get classes from database
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once '../config.php';
    
    // Get all active classes from database
    $stmt = $pdo->prepare("
        SELECT id, name, description, level, duration, schedule, price, 
               image, instructor, capacity, age_group, category, featured, benefits
        FROM classes 
        WHERE active = 1 
        ORDER BY featured DESC, name ASC
    ");
    
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert benefits from JSON string to array
    foreach ($classes as &$class) {
        if ($class['benefits']) {
            $class['benefits'] = json_decode($class['benefits'], true);
        } else {
            $class['benefits'] = [];
        }
        
        // Convert boolean values
        $class['featured'] = (bool) $class['featured'];
        $class['price'] = (float) $class['price'];
        $class['capacity'] = (int) $class['capacity'];
        
        // Add ageGroup for backward compatibility
        $class['ageGroup'] = $class['age_group'];
    }
    
    echo json_encode($classes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>