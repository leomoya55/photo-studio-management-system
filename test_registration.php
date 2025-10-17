<?php
// Test registration simulation
require_once 'db_connect.php';

echo "Testing registration system...\n";
echo "================================\n";

// Test data
$firstName = "Juan";
$lastName = "Pérez";
$email = "juan.perez@test.com";
$phone = "+506-1234-5678";
$password = "test123";

echo "Test data:\n";
echo "Name: $firstName $lastName\n";
echo "Email: $email\n";
echo "Phone: $phone\n";
echo "Password: $password\n\n";

// Check if email already exists
$checkEmail = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($checkEmail);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Email already exists - deleting for test...\n";
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $deleteStmt->bind_param("s", $email);
    $deleteStmt->execute();
    $deleteStmt->close();
}

// Hash password and insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertUser = "INSERT INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertUser);
$stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $phone);

echo "Attempting to insert user...\n";

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    echo "SUCCESS! User inserted with ID: $userId\n";
    
    // Verify the user was inserted
    $verifyStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $verifyStmt->bind_param("i", $userId);
    $verifyStmt->execute();
    $user = $verifyStmt->get_result()->fetch_assoc();
    
    echo "\nUser data from database:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Phone: " . $user['phone'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Created: " . $user['created_at'] . "\n";
    
    $verifyStmt->close();
    
} else {
    echo "ERROR: " . $stmt->error . "\n";
    echo "SQL: $insertUser\n";
}

$stmt->close();

// Final count
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$count = $result->fetch_assoc()['count'];
echo "\nFinal customer count: $count\n";

$conn->close();
?>
