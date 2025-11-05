<?php
/**
 * File Upload Helper for Legend Academy Admin Panel
 * Handles secure image uploads for products and social media
 */

class ImageUploader {
    private $upload_dir;
    private $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $max_size = 5 * 1024 * 1024; // 5MB
    
    public function __construct($category = 'general') {
        $this->upload_dir = '../assets/images/' . $category . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function uploadImage($file, $custom_name = null) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error en la carga del archivo.'];
        }
        
        // Check file size
        if ($file['size'] > $this->max_size) {
            return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB.'];
        }
        
        // Get file extension
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        // Check file type
        if (!in_array($extension, $this->allowed_types)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido. Use: ' . implode(', ', $this->allowed_types)];
        }
        
        // Generate unique filename
        if ($custom_name) {
            $filename = $this->sanitizeFilename($custom_name) . '.' . $extension;
        } else {
            $filename = uniqid('img_') . '_' . time() . '.' . $extension;
        }
        
        $target_path = $this->upload_dir . $filename;
        
        // Check if file is actually an image
        if (!getimagesize($file['tmp_name'])) {
            return ['success' => false, 'message' => 'El archivo no es una imagen válida.'];
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Return relative path for database storage
            $relative_path = 'assets/images/' . basename($this->upload_dir) . '/' . $filename;
            return [
                'success' => true, 
                'message' => 'Imagen subida exitosamente.',
                'filepath' => $relative_path,
                'filename' => $filename
            ];
        } else {
            return ['success' => false, 'message' => 'Error al mover el archivo.'];
        }
    }
    
    public function deleteImage($filepath) {
        if (file_exists('../' . $filepath)) {
            unlink('../' . $filepath);
            return true;
        }
        return false;
    }
    
    private function sanitizeFilename($filename) {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $filename);
        return substr($filename, 0, 50); // Limit length
    }
    
    public function getUploadedImages() {
        $images = [];
        if (is_dir($this->upload_dir)) {
            $files = scandir($this->upload_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $this->isImageFile($file)) {
                    $images[] = [
                        'filename' => $file,
                        'path' => 'assets/images/' . basename($this->upload_dir) . '/' . $file,
                        'size' => filesize($this->upload_dir . $file),
                        'modified' => filemtime($this->upload_dir . $file)
                    ];
                }
            }
        }
        return $images;
    }
    
    private function isImageFile($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->allowed_types);
    }
}
?>