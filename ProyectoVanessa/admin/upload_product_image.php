<?php
// Upload product image and update DB (no Cloudinary IDs shown to admin)
set_time_limit(60);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '22M');

require_once '../config/cloudinary_config.php';
require_once '../config/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception('No hay conexión con la base de datos');
    }

    // Validate inputs
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if ($productId <= 0) {
        throw new Exception('Producto inválido');
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ninguna imagen válida');
    }

    $file = $_FILES['image'];

    // Validate type and size
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif'];
    if (!in_array($file['type'], $allowedImageTypes)) {
        throw new Exception('Tipo de imagen no válido. Solo JPG, PNG, WebP o GIF.');
    }
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        throw new Exception('La imagen es demasiado grande (máx 10MB)');
    }

    // Upload to Cloudinary into 'products' folder
    $publicId = 'products/' . uniqid('prod_') . '_' . time();
    $result = uploadToCloudinary($file['tmp_name'], $publicId, 'products');
    if (!$result || empty($result['secure_url'])) {
        throw new Exception('Error al subir la imagen');
    }

    $imageUrl = $result['secure_url'];

    // Update product image_url
    $stmt = $conn->prepare('UPDATE products SET image_url = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $imageUrl, $productId);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el producto: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagen actualizada',
        'image_url' => $imageUrl
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
