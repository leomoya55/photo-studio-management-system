<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!$conn) {
        throw new Exception('No hay conexión con la base de datos');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all active products
        $featured_only = isset($_GET['featured']) && $_GET['featured'] === 'true';
        $category = $_GET['category'] ?? null;
        
    $sql = "SELECT id, name, description, price, category, image_url, sizes, colors, stock, featured, created_at, updated_at FROM products WHERE is_active = 1";
        
        if ($featured_only) {
            $sql .= " AND featured = 1";
        }
        
        if ($category) {
            $sql .= " AND category = ?";
        }
        
        $sql .= " ORDER BY featured DESC, created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        if ($category) {
            $stmt->bind_param("s", $category);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Convert numeric strings back to proper types
            $row['price'] = floatval($row['price']);
            $row['featured'] = (bool)$row['featured'];
            $row['stock'] = isset($row['stock']) ? intval($row['stock']) : 0;
            
            // Decode JSON fields
            $row['sizes'] = json_decode($row['sizes'] ?? '[]', true);
            $row['colors'] = json_decode($row['colors'] ?? '[]', true);
            
            $products[] = $row;
        }
        
        echo json_encode($products);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No se recibieron datos válidos');
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $product = $input['product'];
                
                $name = $product['name'] ?? '';
                $description = $product['description'] ?? '';
                $price = floatval($product['price'] ?? 0);
                $category = $product['category'] ?? 'General';
                $image_url = $product['image_url'] ?? '';
                $sizes = json_encode($product['sizes'] ?? []);
                $colors = json_encode($product['colors'] ?? []);
                $featured = isset($product['featured']) ? 1 : 0;
                $stock = isset($product['stock']) ? max(0, intval($product['stock'])) : 0;
                
                $sql = "INSERT INTO products (name, description, price, category, image_url, sizes, colors, stock, featured, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdssssii", $name, $description, $price, $category, $image_url, $sizes, $colors, $stock, $featured);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al crear el producto: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente']);
                break;
                
            case 'edit':
                $productId = $input['id'];
                $product = $input['product'];
                
                $name = $product['name'] ?? '';
                $description = $product['description'] ?? '';
                $price = floatval($product['price'] ?? 0);
                $category = $product['category'] ?? 'General';
                $sizes = json_encode($product['sizes'] ?? []);
                $colors = json_encode($product['colors'] ?? []);
                $featured = isset($product['featured']) ? 1 : 0;
                $stock = isset($product['stock']) ? max(0, intval($product['stock'])) : 0;
                
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, sizes = ?, colors = ?, stock = ?, featured = ?, updated_at = NOW() WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdsssiii", $name, $description, $price, $category, $sizes, $colors, $stock, $featured, $productId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar el producto: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
                break;
                
            case 'update_image':
                $productId = $input['id'];
                $imageUrl = $input['image_url'];
                
                $sql = "UPDATE products SET image_url = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $imageUrl, $productId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar la imagen: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Imagen actualizada exitosamente']);
                break;
                
            case 'delete':
                $productId = $input['id'];
                
                // Soft delete
                $sql = "UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $productId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar el producto: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente']);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    }
    
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