<?php
// Database connection configuration with environment variables (Heroku/JawsDB ready)

// Optionally load .env for local development
try {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('Dotenv\\Dotenv')) {
            $env = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $env->safeLoad();
        }
    }
} catch (Throwable $e) {
    // Ignore dotenv load errors in production
}

// Parse a MySQL URL like mysql://user:pass@host:port/dbname
function parse_mysql_url($url) {
    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) {
        return null;
    }
    $db = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
    return [
        'host' => $parts['host'] ?? 'localhost',
        'user' => $parts['user'] ?? '',
        'pass' => $parts['pass'] ?? '',
        'db'   => $db,
        'port' => isset($parts['port']) ? intval($parts['port']) : 3306,
    ];
}

// Prefer a single DATABASE URL first, then individual env vars, then fallbacks
$dbConfig = null;

$candidateUrls = [
    getenv('JAWSDB_URL') ?: null,
    getenv('CLEARDB_DATABASE_URL') ?: null,
    getenv('DATABASE_URL') ?: null,
    getenv('MYSQL_URL') ?: null,
    getenv('MYSQL_CONNECTION_STRING') ?: null,
];

foreach ($candidateUrls as $url) {
    if ($url && stripos($url, 'mysql://') === 0) {
        $dbConfig = parse_mysql_url($url);
        if ($dbConfig) { break; }
    }
}

if (!$dbConfig) {
    $dbConfig = [
        'host' => getenv('DB_HOST') ?: 'a5s42n4idx9husyc.cbetxkdyhwsb.us-east-1.rds.amazonaws.com',
        'user' => getenv('DB_USER') ?: 'et7gg46ymnqkcl7o',
        'pass' => getenv('DB_PASS') ?: 'yhadmlhk2xsg4838',
        'db'   => getenv('DB_NAME') ?: 'yw3j1zpy0fzc7474',
        'port' => getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306,
    ];
}

try {
    // Create connection
    $conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db'], $dbConfig['port']);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Set charset to prevent issues with special characters
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log('Database connection error: ' . $e->getMessage());
    $conn = null; // Set to null for error handling
    // Don't die here, let individual pages handle the error
}

// Function to close connection when needed
function closeConnection($connection) {
    if ($connection && !$connection->connect_error) {
        $connection->close();
    }
}

// Function to sanitize input data
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    // Check if connection exists before using real_escape_string
    if ($conn && !$conn->connect_error) {
        return $conn->real_escape_string($data);
    }

    // Fallback sanitization if no database connection
    return addslashes($data);
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>