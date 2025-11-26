<?php
require_once '../config/db_connect.php';

// Migration script to transfer JSON data to database and set up the system
echo "<!DOCTYPE html><html><head><title>Database Migration</title></head><body>";
echo "<h1>Vale V Photography Database Migration</h1>";

try {
    if (!$conn) {
        throw new Exception('No database connection');
    }

    // Check if classes table has data
    $result = $conn->query("SELECT COUNT(*) as count FROM classes");
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "<p style='color: orange;'>Classes table already has " . $row['count'] . " records. Skipping migration.</p>";
    } else {
        // Migrate classes from JSON if it exists
        $classesJsonFile = '../data/classes.json';
        if (file_exists($classesJsonFile)) {
            echo "<h3>Migrating Classes from JSON...</h3>";
            
            $classesJson = file_get_contents($classesJsonFile);
            $classes = json_decode($classesJson, true);
            
            $migrated = 0;
            foreach ($classes as $class) {
                // Prepare class data for database
                $id = $class['id'] ?? strtolower(str_replace(' ', '-', $class['name'] ?? ''));
                $name = $class['name'] ?? '';
                $description = $class['description'] ?? '';
                $level = $class['level'] ?? 'Principiante';
                $duration = $class['duration'] ?? '60 min';
                $schedule = $class['schedule'] ?? '';
                $price = floatval($class['price'] ?? 0);
                $image = $class['image'] ?? '';
                $instructor = $class['instructor'] ?? 'Vanessa Mora';
                $capacity = intval($class['capacity'] ?? 20);
                $ageGroup = $class['ageGroup'] ?? $class['age_group'] ?? '18+ años';
                $category = $class['category'] ?? 'General';
                $featured = isset($class['featured']) ? ($class['featured'] ? 1 : 0) : 0;
                $benefits = json_encode($class['benefits'] ?? []);
                
                $sql = "INSERT INTO classes (id, name, description, level, duration, schedule, price, image, instructor, capacity, age_group, category, featured, benefits, active, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssdssisssi", $id, $name, $description, $level, $duration, $schedule, $price, $image, $instructor, $capacity, $ageGroup, $category, $featured, $benefits);
                
                if ($stmt->execute()) {
                    $migrated++;
                    echo "<p style='color: green;'>✓ Migrated class: " . htmlspecialchars($name) . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to migrate class: " . htmlspecialchars($name) . " - " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
            
            echo "<p><strong>Migrated $migrated classes successfully!</strong></p>";
        } else {
            echo "<p>No classes.json file found to migrate.</p>";
        }
    }

    // Check if products table has data
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "<p style='color: orange;'>Products table already has " . $row['count'] . " records. Skipping migration.</p>";
    } else {
        // Migrate products from JSON if it exists
        $productsJsonFile = '../data/products.json';
        if (file_exists($productsJsonFile)) {
            echo "<h3>Migrating Products from JSON...</h3>";
            
            $productsJson = file_get_contents($productsJsonFile);
            $products = json_decode($productsJson, true);
            
            $migrated = 0;
            foreach ($products as $product) {
                $name = $product['name'] ?? '';
                $description = $product['description'] ?? '';
                $price = floatval($product['price'] ?? 0);
                $category = $product['category'] ?? 'General';
                $image_url = $product['image'] ?? '';
                $sizes = json_encode($product['sizes'] ?? []);
                $colors = json_encode($product['colors'] ?? []);
                $featured = isset($product['featured']) ? ($product['featured'] ? 1 : 0) : 0;
                
                $sql = "INSERT INTO products (name, description, price, category, image_url, sizes, colors, featured, is_active, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdissii", $name, $description, $price, $category, $image_url, $sizes, $colors, $featured);
                
                if ($stmt->execute()) {
                    $migrated++;
                    echo "<p style='color: green;'>✓ Migrated product: " . htmlspecialchars($name) . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to migrate product: " . htmlspecialchars($name) . " - " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
            
            echo "<p><strong>Migrated $migrated products successfully!</strong></p>";
        } else {
            echo "<p>No products.json file found to migrate.</p>";
        }
    }

    echo "<h3>Database Migration Complete!</h3>";
    echo "<p style='color: green; font-size: 18px;'><strong>✓ Your Vale V Photography system is now fully database-powered!</strong></p>";
    echo "<p><a href='admin.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Migration Error:</strong> " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn) && $conn) {
        closeConnection($conn);
    }
}

echo "</body></html>";
?>