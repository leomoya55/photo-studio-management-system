<?php
/**
 * Cloudinary Helper Functions for Views
 * Include this in your view files to generate optimized Cloudinary URLs
 */

// Include Cloudinary config
require_once __DIR__ . '/cloudinary_config.php';

/**
 * Generate Cloudinary URL for class images with fallback options
 */
function getClassImageUrl($publicId, $width = 400, $height = 300) {
    if (empty($publicId)) {
        return "https://via.placeholder.com/{$width}x{$height}?text=No+Image";
    }

    $cloudName = getCloudName();
    $publicId = trim($publicId);

    $encodedId = implode('/', array_map('rawurlencode', explode('/', $publicId)));

    if ($width <= 0 && $height <= 0) {
        $transformations = 'f_auto,q_auto,dpr_auto';
    } else {
        $pieces = [];
        if ($width > 0) {
            $pieces[] = "w_{$width}";
        }
        if ($height > 0) {
            $pieces[] = "h_{$height}";
        }
        $pieces[] = ($width > 0 && $height > 0) ? 'c_fill' : 'c_limit';
        $pieces[] = 'f_auto';
        $pieces[] = 'q_auto';
        $pieces[] = 'dpr_auto';
        $transformations = implode(',', $pieces);
    }

    return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformations}/{$encodedId}";
}

/**
 * Generate Cloudinary URL for profile images (like Vanessa's photo)
 */
function getProfileImageUrl($publicId, $width = 400, $height = 300) {
    if (empty($publicId)) {
        return "https://via.placeholder.com/{$width}x{$height}?text=No+Image";
    }

    $cloudName = getCloudName();
    $publicId = trim($publicId);

    $encodedId = implode('/', array_map('rawurlencode', explode('/', $publicId)));

    if ($width <= 0 && $height <= 0) {
        $transformations = 'f_auto,q_auto,dpr_auto';
    } else {
        $pieces = [];
        if ($width > 0) {
            $pieces[] = "w_{$width}";
        }
        if ($height > 0) {
            $pieces[] = "h_{$height}";
        }
        $pieces[] = ($width > 0 && $height > 0) ? 'c_fill' : 'c_limit';
        $pieces[] = 'f_auto';
        $pieces[] = 'q_auto';
        $pieces[] = 'dpr_auto';
        $transformations = implode(',', $pieces);
    }

    return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformations}/{$encodedId}";
}

/**
 * Generate responsive Cloudinary URLs for different screen sizes
 */
function getResponsiveClassImage($publicId) {
    $cloudName = getCloudName();
    
    return [
        'mobile' => "https://res.cloudinary.com/{$cloudName}/image/upload/w_300,h_225,c_fill,f_auto,q_auto,dpr_auto/{$publicId}",
        'tablet' => "https://res.cloudinary.com/{$cloudName}/image/upload/w_500,h_375,c_fill,f_auto,q_auto,dpr_auto/{$publicId}",
        'desktop' => "https://res.cloudinary.com/{$cloudName}/image/upload/w_800,h_600,c_fill,f_auto,q_auto,dpr_auto/{$publicId}"
    ];
}

/**
 * Check if we should use Cloudinary or fallback to local images
 */
function shouldUseCloudinary() {
    // Always use Cloudinary in production
    return true;
    
    // Alternative: Check if we're in production
    // return isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost';
}

/**
 * Smart image URL generator - uses Cloudinary if available, local if not
 */
function getImageUrl($imageData, $width = 400, $height = 300) {
    // If imageData starts with http, it's already a full URL
    if (strpos($imageData, 'http') === 0) {
        return $imageData;
    }
    
    // If it contains 'assets/', it's a local path - convert to Cloudinary
    if (strpos($imageData, 'assets/') === 0) {
        // Extract filename and convert to public ID
        $filename = basename($imageData);
        $publicId = strtolower(str_replace([' ', '.jpg', '.jpeg', '.png', '.gif'], ['', '', '', '', ''], $filename));
        return getClassImageUrl($publicId, $width, $height);
    }
    
    // Otherwise, assume it's already a Cloudinary public ID
    // If width and height are 0, return original size
    if ($width == 0 && $height == 0) {
        $cloudName = getCloudName();
        return "https://res.cloudinary.com/{$cloudName}/image/upload/f_auto,q_auto/{$imageData}";
    }
    
    return getClassImageUrl($imageData, $width, $height);
}
?>