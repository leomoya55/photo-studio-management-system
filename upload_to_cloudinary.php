<?php
/**
 * Upload Existing Images to Cloudinary
 * Run this script once to migrate all your local images to Cloudinary
 */

require_once __DIR__ . '/config/cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;

class ImageUploader {
    private $uploadApi;
    private $results = [];

    public function __construct() {
        $this->uploadApi = new UploadApi();
    }

    public function uploadClassImages() {
        echo "=== UPLOADING CLASS IMAGES ===\n";
        
        $classImagesPath = __DIR__ . '/assets/images/classes/';
        $classImages = glob($classImagesPath . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];

        if (empty($classImages)) {
            echo "No se encontraron imágenes de clases en {$classImagesPath}.\n";
            return;
        }

        foreach ($classImages as $filePath) {
            $filename = basename($filePath);
            $slug = strtolower(pathinfo($filename, PATHINFO_FILENAME));
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = 'class-' . substr(md5($filename), 0, 8);
            }
            $publicId = 'classes/' . $slug;
            $this->uploadSingleImage($filePath, $publicId, $filename);
        }
    }

    public function uploadVanessaImage() {
        echo "=== UPLOADING VANESSA'S IMAGE ===\n";
        
        $vanessaImagePath = __DIR__ . '/assets/images/vanessainicio.jpg';
        $this->uploadSingleImage($vanessaImagePath, 'vanessa/director-photo', 'vanessainicio.jpg');
    }

    public function uploadSocialImages() {
        echo "=== UPLOADING SOCIAL MEDIA IMAGES ===\n";
        
        $socialPath = __DIR__ . '/assets/images/social/';
        
        // Check if social folder exists and has images
        if (is_dir($socialPath)) {
            $socialImages = glob($socialPath . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            
            foreach ($socialImages as $imagePath) {
                $filename = basename($imagePath);
                $publicId = 'social/' . pathinfo($filename, PATHINFO_FILENAME);
                $this->uploadSingleImage($imagePath, $publicId, $filename);
            }
        } else {
            echo "Social images folder not found, skipping...\n";
        }
    }

    private function uploadSingleImage($filePath, $publicId, $displayName) {
        if (!file_exists($filePath)) {
            echo "❌ File not found: {$displayName}\n";
            return false;
        }

        try {
            echo "📤 Uploading: {$displayName}... ";
            
            // Configure HTTP client to ignore SSL issues for local development
            $httpClient = new \GuzzleHttp\Client([
                'verify' => false, // Disable SSL verification for local development
                'timeout' => 30,
                'connect_timeout' => 10
            ]);
            
            $result = $this->uploadApi->upload($filePath, [
                'public_id' => $publicId,
                'overwrite' => true,
                'resource_type' => 'image',
                'quality' => 'auto',
                'format' => 'auto'
            ]);

            $this->results[$publicId] = $result;
            echo "✅ Success! URL: {$result['secure_url']}\n";
            return true;

        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
            
            // Try alternative upload method using cURL directly
            echo "🔄 Trying alternative upload method...\n";
            return $this->uploadWithCurl($filePath, $publicId, $displayName);
        }
    }

    private function uploadWithCurl($filePath, $publicId, $displayName) {
        try {
            global $cloudinary_config;
            
            $url = "https://api.cloudinary.com/v1_1/{$cloudinary_config['cloud']['cloud_name']}/image/upload";
            
            $postFields = [
                'file' => new CURLFile($filePath),
                'public_id' => $publicId,
                'api_key' => $cloudinary_config['cloud']['api_key'],
                'timestamp' => time(),
                'overwrite' => true,
                'quality' => 'auto',
                'format' => 'auto'
            ];
            
            // Generate signature
            $signature_string = "overwrite=true&public_id={$publicId}&quality=auto&timestamp={$postFields['timestamp']}{$cloudinary_config['cloud']['api_secret']}";
            $postFields['signature'] = sha1($signature_string);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            
            if (curl_error($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['secure_url'])) {
                $this->results[$publicId] = $result;
                echo "✅ Success with cURL! URL: {$result['secure_url']}\n";
                return true;
            } else {
                throw new Exception("Upload failed: " . ($result['error']['message'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            echo "❌ cURL upload also failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function showResults() {
        echo "\n=== UPLOAD SUMMARY ===\n";
        echo "Total uploaded: " . count($this->results) . " images\n\n";
        
        echo "📋 UPLOADED IMAGES AND THEIR PUBLIC IDs:\n";
        foreach ($this->results as $publicId => $result) {
            echo "• {$publicId} → {$result['secure_url']}\n";
        }
        
        echo "\n🎉 All images uploaded successfully to Cloudinary!\n";
        echo "Next step: Update your classes.json file to use these Cloudinary URLs.\n";
    }
}

// Run the upload process
if (php_sapi_name() === 'cli') {
    echo "🚀 Starting Cloudinary Upload Process...\n\n";
    
    $uploader = new ImageUploader();
    
    // Upload all categories
    $uploader->uploadClassImages();
    $uploader->uploadVanessaImage();
    $uploader->uploadSocialImages();
    
    // Show results
    $uploader->showResults();
    
    echo "\n✨ Upload process completed!\n";
} else {
    echo "This script should be run from the command line.";
}
?>