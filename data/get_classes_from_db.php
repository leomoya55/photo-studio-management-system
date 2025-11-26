<?php
// data/get_classes_from_db.php - Get classes from database (replaces classes.json)
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    require_once '../config/db_connect.php';
    
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Get all active classes from database
    $sql = "SELECT id, name, description, level, duration, schedule, price, 
                   image, instructor, age_group, category, featured, benefits
            FROM classes 
            WHERE active = 1 
            ORDER BY featured DESC, name ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    // If no classes in database, fallback to JSON file
    if (empty($classes)) {
        $jsonFile = __DIR__ . '/classes.json';
        if (file_exists($jsonFile)) {
            $jsonData = file_get_contents($jsonFile);
            echo $jsonData;
            exit;
        } else {
            throw new Exception('No classes found in database or JSON file');
        }
    }
    
    // Convert data to match the expected JSON format
    foreach ($classes as &$class) {
        // Normalize image path for front-end helpers
        if (!empty($class['image'])) {
            $image = is_string($class['image']) ? trim($class['image']) : '';
            if ($image !== '') {
                $class['image'] = str_replace('\\', '/', $image);
            } else {
                $class['image'] = '';
            }
        } else {
            $class['image'] = '';
        }

        // Clean up primary title/description strings
        $class['name'] = isset($class['name']) ? trim((string)$class['name']) : '';
        $class['description'] = isset($class['description']) ? trim((string)$class['description']) : '';

        // Convert benefits from JSON string to array
        if ($class['benefits']) {
            $class['benefits'] = json_decode($class['benefits'], true);
        } else {
            $class['benefits'] = [];
        }
        
        // Normalize category casing and trim whitespace
        if (isset($class['category'])) {
            $class['category'] = trim((string)$class['category']);
        } else {
            $class['category'] = '';
        }

        // Ensure instructor, duration and schedule strings are trimmed for display consistency
        foreach (['instructor', 'duration', 'schedule'] as $key) {
            if (isset($class[$key])) {
                $class[$key] = trim((string)$class[$key]);
            } else {
                $class[$key] = '';
            }
        }

        // Convert boolean and numeric values
        $class['featured'] = (bool) $class['featured'];
        $class['price'] = (int) $class['price'];
        
        // Add ageGroup for backward compatibility and trim values
        $class['age_group'] = isset($class['age_group']) ? trim((string)$class['age_group']) : '';
        $class['ageGroup'] = $class['age_group'];
    }
    
    echo json_encode($classes, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Fallback to JSON file if database fails
    $jsonFile = __DIR__ . '/classes.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        echo $jsonData;
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error',
            'message' => 'Could not load classes: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} finally {
    // Close connection if it exists
    if (isset($conn) && $conn && !$conn->connect_error) {
        $conn->close();
    }
}
?>