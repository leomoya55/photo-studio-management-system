<?php
/**
 * Cloudinary Admin Management Class
 * Allows admin users to upload, manage, and assign images through Cloudinary
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

class CloudinaryAdmin {
    
    private $uploadApi;
    private $adminApi;
    
    public function __construct() {
        $this->uploadApi = new UploadApi();
        $this->adminApi = new AdminApi();
    }
    
    /**
     * Validate image file before upload
     */
    public function validateImageFile($file) {
        $maxSize = 10 * 1024 * 1024; // 10MB limit for Cloudinary free tier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        // Check if file was uploaded without errors
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return [
                'valid' => false,
                'error' => 'No se ha seleccionado ningún archivo.'
            ];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $fileSizeMB = round($file['size'] / 1024 / 1024, 2);
            return [
                'valid' => false,
                'error' => "La imagen es muy grande ({$fileSizeMB}MB). El tamaño máximo permitido es 10MB."
            ];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Formato de imagen no válido. Solo se permiten archivos JPG, PNG, GIF y WebP.'
            ];
        }
        
        // Check if file is actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'error' => 'El archivo no es una imagen válida.'
            ];
        }
        
        return [
            'valid' => true,
            'size_mb' => round($file['size'] / 1024 / 1024, 2),
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $mimeType
        ];
    }

    /**
     * Upload image to Cloudinary from admin dashboard
     */
    public function uploadImage($filePath, $folder = 'classes', $publicIdPrefix = '') {
        try {
            $options = [
                'folder' => $folder,
                'resource_type' => 'image',
                'use_filename' => true,
                'unique_filename' => true,
                'overwrite' => false,
                'quality' => 'auto:best',
                'fetch_format' => 'auto',
                'flags' => 'progressive'
            ];
            
            if (!empty($publicIdPrefix)) {
                $options['public_id'] = $publicIdPrefix . '_' . uniqid();
            }
            
            $result = $this->uploadApi->upload($filePath, $options);
            
            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'bytes' => $result['bytes']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all images from a specific folder
     */
    public function getImagesFromFolder($folder = '', $maxResults = 50) {
        try {
            $params = [
                'type' => 'upload',
                'max_results' => $maxResults
            ];
            
            // Only add prefix if folder is not empty
            if (!empty($folder)) {
                $params['prefix'] = $folder . '/';
            }
            
            $result = $this->adminApi->assets($params);
            
            return [
                'success' => true,
                'images' => $result['resources']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete image from Cloudinary
     */
    public function deleteImage($publicId) {
        try {
            $result = $this->uploadApi->destroy($publicId);
            
            return [
                'success' => $result['result'] === 'ok',
                'result' => $result['result']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate optimized URL for admin preview
     */
    public function getOptimizedUrl($publicId, $width = 300, $height = 200) {
        if (empty($publicId)) {
            return "https://via.placeholder.com/{$width}x{$height}?text=No+Image";
        }
        
        $cloudName = getCloudName();
        $transformations = "w_{$width},h_{$height},c_fill,f_auto,q_auto,dpr_auto";
        
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformations}/{$publicId}";
    }
    
    /**
     * Search for images by tag or name
     */
    public function searchImages($query, $folder = '') {
        try {
            // Use assets method with search parameters instead
            $searchOptions = [
                'type' => 'upload',
                'max_results' => 30
            ];
            
            if (!empty($folder)) {
                $searchOptions['prefix'] = $folder . '/';
            }
            
            $result = $this->adminApi->assets($searchOptions);
            
            // Filter results by query if provided
            if (!empty($query)) {
                $filteredResources = array_filter($result['resources'], function($resource) use ($query) {
                    return stripos($resource['public_id'], $query) !== false;
                });
                $result['resources'] = array_values($filteredResources);
            }
            
            return [
                'success' => true,
                'images' => $result['resources']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update class image in database
     */
    public function updateClassImage($classId, $newPublicId) {
        try {
            // Prefer updating the database; fallback to JSON only if DB unavailable
            require_once __DIR__ . '/db_connect.php';
            if (isset($conn) && $conn && !$conn->connect_error) {
                $stmt = $conn->prepare('UPDATE classes SET image = ?, updated_at = NOW() WHERE id = ?');
                if (!$stmt) {
                    throw new Exception('DB prepare failed: ' . $conn->error);
                }
                $stmt->bind_param('ss', $newPublicId, $classId);
                if (!$stmt->execute()) {
                    throw new Exception('DB execute failed: ' . $stmt->error);
                }
                $stmt->close();
                closeConnection($conn);
                return [
                    'success' => true,
                    'message' => 'Class image updated successfully in database'
                ];
            }

            // Fallback to JSON file update (legacy/local only)
            $classesFile = __DIR__ . '/../data/classes.json';
            if (!file_exists($classesFile)) {
                throw new Exception('No database connection and classes.json not found');
            }
            $classes = json_decode(file_get_contents($classesFile), true);
            foreach ($classes as &$class) {
                if ($class['id'] === $classId) {
                    $class['image'] = $newPublicId;
                    break;
                }
            }
            $result = file_put_contents($classesFile, json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return [
                'success' => $result !== false,
                'message' => $result ? 'Class image updated in JSON' : 'Failed to update class image JSON'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get storage usage statistics
     */
    public function getStorageStats() {
        try {
            $result = $this->adminApi->usage();
            
            return [
                'success' => true,
                'stats' => [
                    'credits_used' => $result['credits']['used'] ?? 0,
                    'credits_limit' => $result['credits']['limit'] ?? 0,
                    'storage_used' => $result['storage']['used'] ?? 0,
                    'storage_limit' => $result['storage']['limit'] ?? 0,
                    'bandwidth_used' => $result['bandwidth']['used'] ?? 0,
                    'bandwidth_limit' => $result['bandwidth']['limit'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>