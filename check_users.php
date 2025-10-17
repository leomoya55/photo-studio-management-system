<?php
require_once 'db_connect.php';

echo "Database Test Results:\n";
echo "======================\n";

// Check users table
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "Customer count: $count\n";
} else {
    echo "Error checking customers: " . $conn->error . "\n";
}

// Check recent registrations
$result = $conn->query("SELECT first_name, last_name, email, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 5");
if ($result) {
    echo "\nRecent registrations:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['first_name']} {$row['last_name']} ({$row['email']}) - {$row['created_at']}\n";
    }
} else {
    echo "Error checking recent registrations: " . $conn->error . "\n";
}

$conn->close();
?>