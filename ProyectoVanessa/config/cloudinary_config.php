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

// Helper to fetch env values from multiple sources
function env_value($key) {
    $v = getenv($key);
    if ($v !== false && $v !== null && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return null;
}

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
    $name = env_value('CLOUDINARY_CLOUD_NAME');
    if ($name) return $name;
    $url = env_value('CLOUDINARY_URL');
    if ($url) {
        $p = parse_cloudinary_url($url);
        if ($p && !empty($p['cloud_name'])) return $p['cloud_name'];
    }
    return 'deov2g1ji';
}

$cloudName = env_value('CLOUDINARY_CLOUD_NAME');
$apiKey    = env_value('CLOUDINARY_API_KEY');
$apiSecret = env_value('CLOUDINARY_API_SECRET');
$url       = env_value('CLOUDINARY_URL');

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

// Set safe curl options (timeout) for all environments
if (function_exists('curl_version')) {
    $curlOptions = [
        CURLOPT_TIMEOUT => 45,
    ];
    // Merge if already defined elsewhere
    $existing = getenv('GUZZLE_CURL_OPTIONS');
    if ($existing) {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) { $curlOptions = $decoded + $curlOptions; }
    }
    putenv('GUZZLE_CURL_OPTIONS=' . json_encode($curlOptions));
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

/**
 * Get the folder used for payment proofs (allow override via env)
 */
function getPaymentProofFolder() {
    $folder = env_value('CLOUDINARY_PAYMENT_PROOFS_FOLDER');
    if ($folder && trim($folder) !== '') {
        return trim($folder);
    }
    return 'payment_proofs';
}

/**
 * Upload a payment/SINPE proof to Cloudinary, auto-selecting resource_type.
 * Throws Exception on failure so callers can enforce mandatory proof presence.
 *
 * @param string $tmpPath      Temporary file path from PHP upload
 * @param string $orderNumber  Raw order number (e.g. ORD-20241107-ABC123)
 * @param string $mime         Detected MIME type (image/jpeg, application/pdf, etc.)
 * @return array               Cloudinary upload result (includes secure_url, public_id, resource_type)
 * @throws Exception
 */
function uploadPaymentProof($tmpPath, $orderNumber, $mime) {
    if (!is_string($tmpPath) || !is_readable($tmpPath)) {
        throw new Exception('Archivo temporal del comprobante no accesible.');
    }
    // Decide resource_type
    $resourceType = 'image';
    if (is_string($mime)) {
        if (stripos($mime, 'application/pdf') !== false) {
            $resourceType = 'raw';
        } elseif (stripos($mime, 'image/') === 0) {
            $resourceType = 'image';
        } else {
            $resourceType = 'auto';
        }
    }
    // Sanitize public_id (avoid slashes and spaces)
    $publicId = 'order_' . preg_replace('/[^A-Za-z0-9_-]/','', (string)$orderNumber);
    $folder = getPaymentProofFolder();
    try {
        $uploadApi = new UploadApi();
        $res = $uploadApi->upload($tmpPath, [
            'folder' => $folder,
            'public_id' => $publicId,
            'resource_type' => $resourceType,
            'overwrite' => true
        ]);
        if (!is_array($res) || empty($res['secure_url'])) {
            throw new Exception('Respuesta de Cloudinary inválida para el comprobante.');
        }
        return $res;
    } catch (Exception $e) {
        throw new Exception('Cloudinary upload error (comprobante): ' . $e->getMessage());
    }
}