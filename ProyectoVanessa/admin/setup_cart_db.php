<?php
/**
 * Database Setup for Shopping Cart System
 * Creates necessary tables for the cart and order management system
 */

require_once(__DIR__ . '/../config/db_connect.php');

// Check database connection
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

echo "<h2>Setting up Shopping Cart Database Tables...</h2>";

try {
    // Create cart table for persistent cart storage
    $sql_cart = "
    CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_cart)) {
        echo "<p>✓ Tabla 'cart' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'cart': " . $conn->error . "</p>";
    }
    
    // Create cart_items table for storing individual cart items
    $sql_cart_items = "
    CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cart_id INT NOT NULL,
        product_id VARCHAR(100) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        product_image VARCHAR(500),
        quantity INT NOT NULL DEFAULT 1,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cart_id (cart_id),
        INDEX idx_product_id (product_id),
        FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_cart_items)) {
        echo "<p>✓ Tabla 'cart_items' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'cart_items': " . $conn->error . "</p>";
    }
    
    // Create orders table for order management
    $sql_orders = "
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_method ENUM('sinpe', 'transfer', 'card', 'cash') DEFAULT 'sinpe',
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        shipping_address TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_order_number (order_number),
        INDEX idx_status (status),
        INDEX idx_payment_status (payment_status),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_orders)) {
        echo "<p>✓ Tabla 'orders' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'orders': " . $conn->error . "</p>";
    }
    
    // Create order_items table for storing ordered products
    $sql_order_items = "
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id VARCHAR(100) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        product_image VARCHAR(500),
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        INDEX idx_order_id (order_id),
        INDEX idx_product_id (product_id),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_order_items)) {
        echo "<p>✓ Tabla 'order_items' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'order_items': " . $conn->error . "</p>";
    }
    
    // Create order_status_log table for tracking order changes
    $sql_order_log = "
    CREATE TABLE IF NOT EXISTS order_status_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        changed_by INT NOT NULL,
        change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        INDEX idx_order_id (order_id),
        INDEX idx_changed_by (changed_by),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_order_log)) {
        echo "<p>✓ Tabla 'order_status_log' creada/verificada exitosamente</p>";
    } else {
        echo "<p>✗ Error creando tabla 'order_status_log': " . $conn->error . "</p>";
    }
    
    echo "<br><h3>✅ Configuración de base de datos del carrito completada!</h3>";
    echo "<p>El sistema de carrito de compras está listo para usar.</p>";
    echo "<p><strong>Características implementadas:</strong></p>";
    echo "<ul>";
    echo "<li>Carrito persistente por usuario</li>";
    echo "<li>Gestión de productos en el carrito</li>";
    echo "<li>Sistema de órdenes con números únicos</li>";
    echo "<li>Estados de orden y pago</li>";
    echo "<li>Integración con pagos SINPE para Costa Rica</li>";
    echo "<li>Registro de cambios de estado</li>";
    echo "<li>Soporte para múltiples métodos de pago</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>✗ Error durante la configuración: " . $e->getMessage() . "</p>";
}

$conn->close();
?>