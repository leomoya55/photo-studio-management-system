<?php
// Test database connection
require_once 'db_connect.php';

echo "<h2>🧪 Database Connection Test</h2>";

if ($conn && !$conn->connect_error) {
    echo "<p style='color: green;'>✅ Successfully connected to JawsDB MySQL database!</p>";
    echo "<p><strong>Database:</strong> " . $_ENV['DB_NAME'] . "</p>";
    echo "<p><strong>Host:</strong> " . $_ENV['DB_HOST'] . "</p>";
    echo "<p><strong>Port:</strong> " . $_ENV['DB_PORT'] . "</p>";
    
    // Test query to show existing tables
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<h3>📋 Existing Tables:</h3>";
        if ($result->num_rows > 0) {
            echo "<ul>";
            while($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No tables found. Ready to create new ones!</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Connection failed!</p>";
}

closeConnection($conn);
?>