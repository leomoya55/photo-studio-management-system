<?php
// Upload class image to Cloudinary 'classes' and optionally update DB
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
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ninguna imagen válida');
    }

    $file = $_FILES['image'];

    // Validate type and size
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Tipo de imagen no válido. Solo JPG, PNG, WebP o GIF.');
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('La imagen es demasiado grande (máx 10MB)');
    }

    // Optional params
    $classId = isset($_POST['class_id']) ? trim($_POST['class_id']) : '';
    $prefix = isset($_POST['prefix']) ? preg_replace('/[^a-z0-9-]+/i', '-', $_POST['prefix']) : '';

    // Upload to Cloudinary in 'classes'
    $publicId = 'classes/' . ($prefix ? strtolower(trim($prefix, '-')) . '_' : 'cls_') . time();
    $result = uploadToCloudinary($file['tmp_name'], $publicId, 'classes');
    if (!$result || empty($result['secure_url'])) {
        throw new Exception('Error al subir la imagen');
    }

    $imageUrl = $result['secure_url'];

    // Update DB if class_id provided
    if (!empty($classId)) {
        if (!$conn || $conn->connect_error) {
            throw new Exception('No hay conexión con la base de datos');
        }
        $stmt = $conn->prepare('UPDATE classes SET image = ?, updated_at = NOW() WHERE id = ?');
        if (!$stmt) { throw new Exception('Error DB: ' . $conn->error); }
        $stmt->bind_param('ss', $imageUrl, $classId);
        if (!$stmt->execute()) { throw new Exception('No se pudo actualizar la clase: ' . $stmt->error); }
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagen subida',
        'image_url' => $imageUrl
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) { closeConnection($conn); }
}
?>
