<?php
/**
 * Database Setup for Enrollment System
 * Creates necessary tables for the enrollment management system
 */

require_once(__DIR__ . '/../config/db_connect.php');

// Check database connection
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

echo "<h2>Setting up Enrollment Database Tables...</h2>";

try {
    // Create enrollments table if it doesn't exist
    $sql_enrollments = "
    CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        class_id INT NOT NULL,
        class_name VARCHAR(255) NOT NULL,
        enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'active', 'inactive', 'rejected') DEFAULT 'pending',
        notes TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_class_id (class_id),
        INDEX idx_status (status),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_enrollments)) {
        echo "<p>✓ Tabla 'enrollments' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'enrollments': " . $conn->error . "</p>";
    }
    
    // Create enrollment status log table for tracking changes
    $sql_status_log = "
    CREATE TABLE IF NOT EXISTS enrollment_status_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        changed_by INT NOT NULL,
        change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        INDEX idx_enrollment_id (enrollment_id),
        INDEX idx_changed_by (changed_by),
        FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_status_log)) {
        echo "<p>✓ Tabla 'enrollment_status_log' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'enrollment_status_log': " . $conn->error . "</p>";
    }
    
    // Check if enrollments table has the correct columns
    $result = $conn->query("DESCRIBE enrollments");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Add missing columns if needed
        $required_columns = [
            'updated_at' => "ALTER TABLE enrollments ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $sql) {
            if (!in_array($column, $columns)) {
                if ($conn->query($sql)) {
                    echo "<p>✓ Columna '$column' añadida a la tabla enrollments</p>";
                } else {
                    echo "<p>✗ Error añadiendo columna '$column': " . $conn->error . "</p>";
                }
            } else {
                echo "<p>✓ Columna '$column' ya existe en enrollments</p>";
            }
        }
    }
    
    // Create some sample enrollments for testing (optional)
    $sample_enrollment_sql = "
    INSERT IGNORE INTO enrollments (user_id, class_id, class_name, status, notes) 
    SELECT 
        u.id,
        1,
        'Ballet Clásico',
        'pending',
        'Inscripción de prueba creada durante configuración'
    FROM users u 
    WHERE u.role = 'customer' 
    LIMIT 1
    ";
    
    if ($conn->query($sample_enrollment_sql)) {
        if ($conn->affected_rows > 0) {
            echo "<p>✓ Inscripción de ejemplo creada</p>";
        } else {
            echo "<p>ℹ No se creó inscripción de ejemplo (ya existe o no hay usuarios)</p>";
        }
    } else {
        echo "<p>✗ Error creando inscripción de ejemplo: " . $conn->error . "</p>";
    }
    
    echo "<br><h3>✅ Configuración de base de datos completada!</h3>";
    echo "<p>El sistema de inscripciones está listo para usar.</p>";
    echo "<p><strong>Características implementadas:</strong></p>";
    echo "<ul>";
    echo "<li>Inscripciones con estado (pendiente, aprobada, activa, inactiva, rechazada)</li>";
    echo "<li>Sistema de aprobación por administrador</li>";
    echo "<li>Registro de cambios de estado</li>";
    echo "<li>Notificaciones por correo electrónico</li>";
    echo "<li>Confirmación de inscripción para usuarios</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>✗ Error durante la configuración: " . $e->getMessage() . "</p>";
}

$conn->close();
?>