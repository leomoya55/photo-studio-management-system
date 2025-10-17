<?php
require_once 'db_connect.php';

echo "<h2>🗄️ Database Setup for Legend Dance Academy</h2>";

// Create users table
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
)";

if ($conn->query($createUsersTable) === TRUE) {
    echo "<p style='color: green;'>✅ Users table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating users table: " . $conn->error . "</p>";
}

// Create enrollments table for class registrations
$createEnrollmentsTable = "
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    class_schedule VARCHAR(50) NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($createEnrollmentsTable) === TRUE) {
    echo "<p style='color: green;'>✅ Enrollments table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating enrollments table: " . $conn->error . "</p>";
}

// Insert Vanessa as admin (she can change password later)
$adminEmail = 'vanessa@legenddance.com';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT); // Temporary password

$checkAdmin = "SELECT id FROM users WHERE email = '$adminEmail'";
$result = $conn->query($checkAdmin);

if ($result->num_rows == 0) {
    $insertAdmin = "
    INSERT INTO users (first_name, last_name, email, password, role, phone) 
    VALUES ('Vanessa', 'Mora', '$adminEmail', '$adminPassword', 'admin', '+506-1234-5678')";
    
    if ($conn->query($insertAdmin) === TRUE) {
        echo "<p style='color: blue;'>👑 Admin user 'Vanessa Mora' created!</p>";
        echo "<p><strong>Email:</strong> vanessa@legenddance.com</p>";
        echo "<p><strong>Temporary Password:</strong> admin123</p>";
        echo "<p style='color: orange;'>⚠️ Please change this password immediately!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating admin user: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>👑 Admin user already exists!</p>";
}

echo "<hr>";
echo "<h3>🔗 Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='test_connection.php'>Test Database Connection</a></li>";
echo "<li><a href='register.php'>Customer Registration</a></li>";
echo "<li><a href='login.php'>User Login</a></li>";
echo "<li><a href='admin.php'>Admin Dashboard</a></li>";
echo "</ul>";

closeConnection($conn);
?>