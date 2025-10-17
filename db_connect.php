<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection using environment variables
$servername = $_ENV['DB_HOST'];
$username   = $_ENV['DB_USER'];
$password   = $_ENV['DB_PASS'];
$dbname     = $_ENV['DB_NAME'];
$port       = $_ENV['DB_PORT'];

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to prevent issues with special characters
    $conn->set_charset("utf8");
    
    // Success message for testing (remove in production)
    // echo "Connected successfully to JawsDB MySQL database!";
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
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
    return $conn->real_escape_string($data);
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>