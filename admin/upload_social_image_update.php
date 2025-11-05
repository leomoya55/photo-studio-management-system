<?php
// Upload a new image/video for an existing social post and update image_url
set_time_limit(60);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '102M');

require_once '../config/cloudinary_config.php';
require_once '../config/db_connect.php';

use Cloudinary\Api\Upload\UploadApi;

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

    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($postId <= 0) {
        throw new Exception('Post inválido');
    }

    if (!isset($_FILES['social_media']) || $_FILES['social_media']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ningún archivo válido');
    }

    $file = $_FILES['social_media'];

    $isVideo = strpos($file['type'], 'video/') === 0;
    $isImage = strpos($file['type'], 'image/') === 0;
    if (!$isVideo && !$isImage) {
        throw new Exception('Tipo de archivo no válido. Solo imágenes o videos.');
    }

    if ($isImage) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif'];
        if (!in_array($file['type'], $allowedImageTypes)) {
            throw new Exception('Tipo de imagen no válido.');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('La imagen es demasiado grande (máx 10MB)');
        }
    } else {
        $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/avi', 'video/mov'];
        if (!in_array($file['type'], $allowedVideoTypes)) {
            throw new Exception('Tipo de video no válido.');
        }
        if ($file['size'] > 100 * 1024 * 1024) {
            throw new Exception('El video es demasiado grande (máx 100MB)');
        }
    }

    // Upload to Cloudinary in 'social_media' folder
    $publicId = 'social_media/' . uniqid('post_') . '_' . time();

    if ($isVideo) {
        // Use UploadApi directly to set resource type video
        $uploadApi = new UploadApi();
        $result = $uploadApi->upload($file['tmp_name'], [
            'public_id' => $publicId,
            'overwrite' => true,
            'resource_type' => 'video',
            'eager' => [ ['duration' => '60', 'crop' => 'limit'] ],
            'eager_async' => false
        ]);
        if (isset($result['duration']) && $result['duration'] > 60) {
            // Optional: destroy if too long
            $uploadApi->destroy($result['public_id'], ['resource_type' => 'video']);
            throw new Exception('El video supera 60 segundos.');
        }
    } else {
        $result = uploadToCloudinary($file['tmp_name'], $publicId, 'social_media');
    }

    if (!$result || empty($result['secure_url'])) {
        throw new Exception('Error al subir el archivo');
    }

    $imageUrl = $result['secure_url'];

    // Update the post
    $stmt = $conn->prepare('UPDATE social_posts SET image_url = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $imageUrl, $postId);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el post: ' . $stmt->error);
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
    if (isset($conn) && $conn) { closeConnection($conn); }
}
?>
