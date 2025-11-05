<?php
/**
 * Path Configuration for Legend Academy
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
if ($envBaseUrl !== false && $envBaseUrl !== '') {
    $envBaseUrl = rtrim($envBaseUrl, '/');
    define('BASE_URL', $envBaseUrl === '' ? '/' : $envBaseUrl);
} else {
    // Local default (XAMPP): project in subfolder
    define('BASE_URL', '/ProyectoVanessa');
}
// Derived URLs
define('ASSETS_URL', BASE_URL . '/assets');
define('ADMIN_URL', BASE_URL . '/admin');
define('VIEWS_URL', BASE_URL . '/views');
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

// Auto-load essential configurations
includeConfig('db_connect.php');
includeConfig('session_manager.php');

?>