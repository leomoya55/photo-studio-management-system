<?php
/**
 * Path Configuration for Vale V Photography
 * Defines paths for the new layered architecture
 */

// Define root path
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', dirname(__FILE__) . '/..');

// Define directory paths
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('DATA_PATH', ROOT_PATH . '/data');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Define URL paths (for web access)
// Allow override via environment for deployment (e.g., Heroku)
$envBaseUrl = getenv('APP_BASE_URL');
// Heroku detection: if running on Heroku and no APP_BASE_URL set, default to root ('')
$isHeroku = getenv('DYNO') !== false || (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'herokuapp.com') !== false);

function normalize_base_url($val) {
    $val = trim((string)$val);
    if ($val === '' || $val === '/') {
        // Represent site root as empty string to avoid generating double slashes like //assets
        return '';
    }
    // If absolute URL provided, strip only trailing slash
    if (preg_match('#^https?://#i', $val)) {
        return rtrim($val, '/');
    }
    // Otherwise treat as path prefix; ensure it starts with single leading slash and has no trailing slash
    $val = '/' . ltrim($val, '/');
    return rtrim($val, '/');
}

if ($envBaseUrl !== false && $envBaseUrl !== '') {
    define('BASE_URL', normalize_base_url($envBaseUrl));
} else {
    if ($isHeroku) {
        define('BASE_URL', '');
    } else {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
        $appRoot = str_replace('\\', '/', rtrim(ROOT_PATH, '/'));

        $docLower = strtolower($documentRoot);
        $appLower = strtolower($appRoot);

        if ($documentRoot && strncmp($appLower, $docLower, strlen($docLower)) === 0) {
            $relative = substr($appRoot, strlen($documentRoot));
            define('BASE_URL', normalize_base_url($relative));
        } else {
            $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
            $scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']) : '';

            $baseCandidate = '';
            if ($scriptName !== '' && $scriptFilename !== '') {
                $scriptDirUrl = str_replace('\\', '/', dirname($scriptName));
                $scriptDirFs = str_replace('\\', '/', dirname($scriptFilename));
                $appLowerWithSep = rtrim(strtolower($appRoot), '/') . '/';
                $scriptFsLower = strtolower($scriptDirFs) . '/';

                if ($appRoot && strncmp($scriptFsLower, $appLowerWithSep, strlen($appLowerWithSep)) === 0) {
                    $relativeDir = trim(str_replace($appRoot, '', $scriptDirFs), '/');
                    if ($relativeDir !== '') {
                        $suffix = '/' . $relativeDir;
                        if ($suffix !== '/' && strlen($scriptDirUrl) >= strlen($suffix) && substr($scriptDirUrl, -strlen($suffix)) === $suffix) {
                            $baseCandidate = substr($scriptDirUrl, 0, -strlen($suffix));
                        } else {
                            $baseCandidate = $scriptDirUrl;
                        }
                    } else {
                        $baseCandidate = $scriptDirUrl;
                    }
                } else {
                    $baseCandidate = $scriptDirUrl;
                }
            }

            if ($baseCandidate === '.' || $baseCandidate === './') {
                $baseCandidate = '';
            }

            if ($baseCandidate === '' || $baseCandidate === null) {
                $baseCandidate = '/' . trim(basename($appRoot));
            }

            define('BASE_URL', normalize_base_url($baseCandidate));
        }
    }
}

// Safe URL join to avoid accidental double slashes
function url_join($base, $segment) {
    $b = rtrim($base, '/');
    $s = ltrim((string)$segment, '/');
    if ($b === '' && $s === '') return '';
    if ($b === '') return '/' . $s;
    if ($s === '') return $b;
    return $b . '/' . $s;
}

// Derived URLs
define('ASSETS_URL', url_join(BASE_URL, 'assets'));
define('ADMIN_URL', url_join(BASE_URL, 'admin'));
define('VIEWS_URL', url_join(BASE_URL, 'views'));
// (ASSETS_URL and ADMIN_URL are defined above, kept for backward compatibility)

// Helper function to include files from different layers
function includeConfig($filename) {
    return include_once CONFIG_PATH . '/' . $filename;
}

function includeModel($filename) {
    return include_once MODELS_PATH . '/' . $filename;
}

function includeController($filename) {
    return include_once CONTROLLERS_PATH . '/' . $filename;
}

function includeView($filename) {
    return include_once VIEWS_PATH . '/' . $filename;
}

function includeHelper($filename) {
    return include_once INCLUDES_PATH . '/' . $filename;
}

// Auto-load essential configurations in the global scope to expose shared variables
require_once CONFIG_PATH . '/db_connect.php';
require_once CONFIG_PATH . '/session_manager.php';

?>