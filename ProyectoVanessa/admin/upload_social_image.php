<?php
// Increase execution time and memory for image processing
set_time_limit(60); // 60 seconds
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '102M');

require_once '../config/cloudinary_config.php';
require_once '../config/db_connect.php';

// Helper function to upload videos to Cloudinary with duration limits
function uploadVideoToCloudinary($filePath, $publicId, $folder = '') {
    try {
        $uploadApi = new \Cloudinary\Api\Upload\UploadApi();
        
        $options = [
            'public_id' => $publicId,
            'overwrite' => true,
            'resource_type' => 'video',
            'eager' => [
                [
                    'duration' => '60', // Limit to 60 seconds
                    'crop' => 'limit'
                ]
            ],
            'eager_async' => false
        ];
        
        if (!empty($folder)) {
            $options['folder'] = $folder;
        }
        
        $result = $uploadApi->upload($filePath, $options);
        
        // Check if video duration is within limits (60 seconds)
        if (isset($result['duration']) && $result['duration'] > 60) {
            // Delete the uploaded video since it's too long
            $uploadApi->destroy($result['public_id'], ['resource_type' => 'video']);
            throw new Exception('El video es demasiado largo. Máximo 60 segundos permitidos.');
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Cloudinary video upload error: " . $e->getMessage());
        throw new Exception("Error al subir el video: " . $e->getMessage());
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!isset($_FILES['social_media']) || $_FILES['social_media']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ningún archivo válido');
    }
    
    $file = $_FILES['social_media'];
    $platform = $_POST['platform'] ?? 'Instagram';
    $caption = $_POST['caption'] ?? '';
    
    if (empty($caption)) {
        throw new Exception('La descripción es requerida');
    }
    
    // Determine if it's a video or image
    $isVideo = strpos($file['type'], 'video/') === 0;
    $isImage = strpos($file['type'], 'image/') === 0;
    
    if (!$isVideo && !$isImage) {
        throw new Exception('Tipo de archivo no válido. Solo se permiten imágenes y videos.');
    }
    
    // Validate file types
    if ($isImage) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif'];
        if (!in_array($file['type'], $allowedImageTypes)) {
            throw new Exception('Tipo de imagen no válido. Solo se permiten JPG, PNG, WebP y GIF.');
        }
        $maxSize = 10 * 1024 * 1024; // 10MB for images
    } else {
        $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/avi', 'video/mov'];
        if (!in_array($file['type'], $allowedVideoTypes)) {
            throw new Exception('Tipo de video no válido. Solo se permiten MP4, MOV y AVI.');
        }
        $maxSize = 100 * 1024 * 1024; // 100MB for videos
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $maxSizeMB = $isVideo ? '100MB' : '10MB';
        throw new Exception("El archivo es demasiado grande. Máximo {$maxSizeMB} para " . ($isVideo ? 'videos' : 'imágenes') . '.');
    }
    
    // Upload to Cloudinary
    $publicId = 'social_media/' . uniqid() . '_' . time();
    
    // Set resource type based on file type
    $resourceType = $isVideo ? 'video' : 'image';
    
    // For videos, we need to use the Cloudinary API directly to set duration limits
    if ($isVideo) {
        // Upload video with duration validation
        $cloudinaryResult = uploadVideoToCloudinary($file['tmp_name'], $publicId, 'social_media');
    } else {
        // Upload image normally
        $cloudinaryResult = uploadToCloudinary($file['tmp_name'], $publicId, 'social_media');
    }
    
    if (!$cloudinaryResult) {
        throw new Exception('Error al subir el archivo a Cloudinary');
    }
    
    // Save post to database
    if (!$conn) {
        throw new Exception('No hay conexión con la base de datos');
    }
    
    $imageUrl = $cloudinaryResult['secure_url'];
    $postDate = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO social_posts (platform, caption, image_url, post_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $platform, $caption, $imageUrl, $postDate);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar el post: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Post publicado exitosamente',
        'image_url' => $imageUrl,
        'public_id' => $cloudinaryResult['public_id']
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