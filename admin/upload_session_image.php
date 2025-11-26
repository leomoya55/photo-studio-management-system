<?php
// Upload session image to Cloudinary under the session_photos folder
set_time_limit(60);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '22M');

session_start();

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../config/cloudinary_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!$isLoggedIn || $userRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Solo administradores pueden subir fotos de sesiones.'
    ]);
    exit;
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Archivo de imagen no encontrado o inválido');
    }

    $file = $_FILES['image'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo ? $finfo->file($file['tmp_name']) : ($file['type'] ?? '');
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowedArchiveTypes = ['application/zip', 'application/x-zip-compressed'];
    if (!in_array($mime, array_merge($allowedImageTypes, $allowedArchiveTypes), true)) {
        throw new Exception('Formato no permitido. Usa JPG, PNG, WebP, GIF o ZIP.');
    }
    if ($file['size'] > 12 * 1024 * 1024) {
        throw new Exception('El archivo supera el límite de 12MB.');
    }

    $resourceType = in_array($mime, $allowedArchiveTypes, true) ? 'raw' : 'image';

    $prefix = isset($_POST['prefix']) ? preg_replace('/[^a-z0-9-]+/i', '-', $_POST['prefix']) : '';
    $prefix = trim($prefix, '-');
    if ($prefix === '') {
        $prefix = 'session';
    }

    $publicId = 'session_photos/' . strtolower($prefix) . '_' . time();
    $uploadResult = uploadToCloudinary($file['tmp_name'], $publicId, 'session_photos', $resourceType);

    if (!$uploadResult || empty($uploadResult['secure_url'])) {
        throw new Exception('No se pudo subir la imagen a Cloudinary');
    }

    echo json_encode([
        'success' => true,
        'image_url' => $uploadResult['secure_url'],
        'public_id' => $uploadResult['public_id'] ?? $publicId
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
