<?php
/**
 * Cloudinary Configuration (env-first)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// Optionally load .env locally
try {
    if (class_exists('Dotenv\\Dotenv')) {
        $env = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $env->safeLoad();
    }
} catch (Throwable $e) {}

function parse_cloudinary_url($url) {
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) return null;
    return [
        'cloud_name' => $parts['host'],
        'api_key'    => $parts['user'] ?? '',
        'api_secret' => $parts['pass'] ?? ''
    ];
}

function getCloudName() {
    $name = getenv('CLOUDINARY_CLOUD_NAME');
    if ($name) return $name;
    $url = getenv('CLOUDINARY_URL');
    if ($url) {
        $p = parse_cloudinary_url($url);
        if ($p && !empty($p['cloud_name'])) return $p['cloud_name'];
    }
    return 'deov2g1ji';
}

$cloudName = getenv('CLOUDINARY_CLOUD_NAME');
$apiKey    = getenv('CLOUDINARY_API_KEY');
$apiSecret = getenv('CLOUDINARY_API_SECRET');
$url       = getenv('CLOUDINARY_URL');

if ($url && (!$cloudName || !$apiKey || !$apiSecret)) {
    $p = parse_cloudinary_url($url);
    if ($p) {
        $cloudName = $cloudName ?: ($p['cloud_name'] ?? null);
        $apiKey    = $apiKey    ?: ($p['api_key'] ?? null);
        $apiSecret = $apiSecret ?: ($p['api_secret'] ?? null);
    }
}

if (!$cloudName || !$apiKey || !$apiSecret) {
    $cloudName = $cloudName ?: 'deov2g1ji';
    $apiKey    = $apiKey    ?: '679771325397379';
    $apiSecret = $apiSecret ?: '4bHmGQBW7_V_wPHMKorlQWLi0aI';
}

Configuration::instance([
    'cloud' => [
        'cloud_name' => $cloudName,
        'api_key'    => $apiKey,
        'api_secret' => $apiSecret
    ],
    'url' => ['secure' => true]
]);

// Relax SSL locally only
if (php_sapi_name() === 'cli' || strpos($_SERVER['SERVER_NAME'] ?? '', 'localhost') !== false) {
    if (function_exists('curl_version')) {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ];
        putenv('GUZZLE_CURL_OPTIONS=' . json_encode($curlOptions));
    }
}

function getCloudinaryImageUrl($publicId, $width = 400, $height = 300, $crop = 'fill') {
    try {
        $cloudName = getCloudName();
        if (empty($publicId)) {
            return "https://via.placeholder.com/{$width}x{$height}?text=No+Image";
        }
        $transformations = "w_{$width},h_{$height},c_{$crop},f_auto,q_auto";
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformations}/{$publicId}";
    } catch (Exception $e) {
        return "https://via.placeholder.com/{$width}x{$height}?text=Image+Not+Found";
    }
}

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
        return $uploadApi->upload($filePath, $options);
    } catch (Exception $e) {
        error_log('Cloudinary upload error: ' . $e->getMessage());
        return false;
    }
}

function cloudinaryImageExists($publicId) {
    try {
        $url = getCloudinaryImageUrl($publicId, 1, 1);
        return !empty($publicId) && strpos($url, 'placeholder') === false;
    } catch (Exception $e) {
        return false;
    }
}