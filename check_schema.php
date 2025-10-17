<?php
require_once 'db_connect.php';

echo "Users table schema:\n";
echo "===================\n";

$result = $conn->query('DESCRIBE users');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . ($row['Default'] ?? 'no default') . "\n";
}

$conn->close();
?>