<?php
// Test login simulation
session_start();
require_once 'db_connect.php';

echo "Testing login system...\n";
echo "========================\n";

$email = "juan.perez@test.com";
$password = "test123";

echo "Attempting to login with:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Check user credentials
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role, is_active FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    echo "User found in database:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Role: " . $user['role'] . "\n";
    
    if (!$user['is_active']) {
        echo "\nLOGIN FAILED: Account is disabled\n";
    } elseif (password_verify($password, $user['password'])) {
        echo "\nLOGIN SUCCESS!\n";
        
        // Set session variables (like the real login would do)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        echo "Session variables set:\n";
        echo "user_id: " . $_SESSION['user_id'] . "\n";
        echo "first_name: " . $_SESSION['first_name'] . "\n";
        echo "last_name: " . $_SESSION['last_name'] . "\n";
        echo "email: " . $_SESSION['email'] . "\n";
        echo "role: " . $_SESSION['role'] . "\n";
        
        echo "\nFull name for display: " . $_SESSION['first_name'] . " " . $_SESSION['last_name'] . "\n";
        
        // Test redirect logic
        if ($user['role'] === 'admin') {
            echo "Would redirect to: admin.php\n";
        } else {
            echo "Would redirect to: dashboard.php\n";
        }
        
    } else {
        echo "\nLOGIN FAILED: Invalid password\n";
    }
} else {
    echo "\nLOGIN FAILED: User not found\n";
}

$stmt->close();
$conn->close();
?>