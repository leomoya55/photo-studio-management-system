<?php
require_once '../config/db_connect.php';
require_once '../config/cloudinary_admin.php';

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
        // Fetch all active social posts
        $endpoint = $_GET['endpoint'] ?? 'posts';
        if ($endpoint === 'cloud_images') {
            $cloud = new CloudinaryAdmin();
            $folder = $_GET['folder'] ?? 'social_media';
            $max = intval($_GET['max'] ?? 60);
            $imgs = $cloud->getImagesFromFolder($folder, $max);
            echo json_encode($imgs);
        } else {
            $platform = $_GET['platform'] ?? null;
            $limit = intval($_GET['limit'] ?? 50);
            
            $sql = "SELECT id, platform, caption, image_url, post_date, created_at, updated_at FROM social_posts WHERE is_active = 1";
            
            if ($platform) {
                $sql .= " AND platform = ?";
            }
            
            $sql .= " ORDER BY post_date DESC, created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            
            if ($platform) {
                $stmt->bind_param("si", $platform, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $posts = [];
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
            
            echo json_encode($posts);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No se recibieron datos válidos');
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $post = $input['post'];
                
                $platform = $post['platform'] ?? 'Instagram';
                $caption = $post['caption'] ?? '';
                $image_url = $post['image_url'] ?? '';
                $post_date = $post['post_date'] ?? date('Y-m-d H:i:s');
                
                $sql = "INSERT INTO social_posts (platform, caption, image_url, post_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $platform, $caption, $image_url, $post_date);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al crear el post: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Post creado exitosamente']);
                break;
                
            case 'edit':
                $postId = $input['id'];
                $post = $input['post'];
                
                $platform = $post['platform'] ?? 'Instagram';
                $caption = $post['caption'] ?? '';
                $post_date = $post['post_date'] ?? date('Y-m-d H:i:s');
                
                $sql = "UPDATE social_posts SET platform = ?, caption = ?, post_date = ?, updated_at = NOW() WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $platform, $caption, $post_date, $postId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar el post: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Post actualizado exitosamente']);
                break;
                
            case 'update_image':
                $postId = $input['id'];
                $imageUrl = $input['image_url'];
                
                $sql = "UPDATE social_posts SET image_url = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $imageUrl, $postId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar la imagen: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Imagen actualizada exitosamente']);
                break;
                
            case 'delete':
                $postId = $input['id'];
                
                // Soft delete
                $sql = "UPDATE social_posts SET is_active = 0, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $postId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar el post: ' . $stmt->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Post eliminado exitosamente']);
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