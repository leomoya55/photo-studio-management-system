<?php
/**
 * Cloudinary Configuration
 * Add your Cloudinary credentials here
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Tag\ImageTag;

// Load Cloudinary configuration from environment if available
$envCloudinaryUrl   = getenv('CLOUDINARY_URL');
$envCloudName       = getenv('CLOUDINARY_CLOUD_NAME');
$envCloudApiKey     = getenv('CLOUDINARY_API_KEY');
$envCloudApiSecret  = getenv('CLOUDINARY_API_SECRET');

if ($envCloudinaryUrl) {
    // Initialize from full URL
    Configuration::instance($envCloudinaryUrl);
} elseif ($envCloudName && $envCloudApiKey && $envCloudApiSecret) {
    Configuration::instance([
        'cloud' => [
            'cloud_name' => $envCloudName,
            'api_key'    => $envCloudApiKey,
            'api_secret' => $envCloudApiSecret,
        ],
        'url' => ['secure' => true]
    ]);
} else {
    // Fallback to embedded defaults (development only)
    Configuration::instance([
        'cloud' => [
            'cloud_name' => 'deov2g1ji',
            'api_key'    => '679771325397379',
            'api_secret' => '4bHmGQBW7_V_wPHMKorlQWLi0aI',
        ],
        'url' => ['secure' => true]
    ]);
}

// Helper to get current Cloudinary cloud name
function getCloudName() {
    $cfg = Configuration::instance();
    // Newer SDK stores under $cfg->cloud->cloudName
    if (isset($cfg->cloud) && isset($cfg->cloud->cloudName)) {
        return $cfg->cloud->cloudName;
    }
    // Fallback to environment
    return getenv('CLOUDINARY_CLOUD_NAME') ?: 'deov2g1ji';
}

// Fix SSL certificate issue for local development
if (php_sapi_name() === 'cli' || strpos($_SERVER['SERVER_NAME'] ?? '', 'localhost') !== false) {
    // Disable SSL verification for local development only
    $context = stream_context_create([
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
        "http" => [
            "timeout" => 30
        ]
    ]);
    
    // Set cURL options for Cloudinary
    if (function_exists('curl_version')) {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ];
        
        // This will be used by Guzzle HTTP client
        putenv('GUZZLE_CURL_OPTIONS=' . json_encode($curlOptions));
    }
}

// Helper function to generate optimized image URLs
function getCloudinaryImageUrl($publicId, $width = 400, $height = 300, $crop = 'fill') {
    try {
        $cloudName = getCloudName();

        // Check if publicId is empty
        if (empty($publicId)) {
            return "https://via.placeholder.com/{$width}x{$height}?text=No+Image";
        }

        // If the publicId doesn't contain 'classes/' prefix, add it
        if (strpos($publicId, 'classes/') === false && !empty($publicId)) {
            $publicId = 'classes/' . $publicId;
        }

        // Build URL manually for better compatibility
        $transformations = "w_{$width},h_{$height},c_{$crop},f_auto,q_auto";
        $url = "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformations}/{$publicId}";

        return $url;
    } catch (Exception $e) {
        // Fallback to placeholder if Cloudinary fails
        return "https://via.placeholder.com/{$width}x{$height}?text=Image+Not+Found";
    }
}

// Helper function to upload images to Cloudinary
function uploadToCloudinary($filePath, $publicId, $folder = '') {
    try {
        $uploadApi = new UploadApi();
        
        $options = [
            'public_id' => $publicId,
            'overwrite' => true,
            'resource_type' => 'image'
        ];
        
        if (!empty($folder)) {
            $options['folder'] = $folder;
        }
        
        $result = $uploadApi->upload($filePath, $options);
        return $result;
    } catch (Exception $e) {
        error_log("Cloudinary upload error: " . $e->getMessage());
        return false;
    }
}

// Helper function to check if image exists in Cloudinary
function cloudinaryImageExists($publicId) {
    try {
        $url = getCloudinaryImageUrl($publicId, 1, 1);
        return !empty($url) && !strpos($url, 'placeholder');
    } catch (Exception $e) {
        return false;
    }
}