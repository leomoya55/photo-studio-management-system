<?php
/**
 * Shopping Cart Handler
 * Manages all cart operations with user authentication
 */

session_start();
require_once '../config/db_connect.php';

// Set content type to JSON for AJAX responses
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión para agregar productos al carrito.',
        'requireLogin' => true
    ]);
    exit();
}

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a la base de datos.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            addToCart();
            break;
        case 'remove':
            removeFromCart();
            break;
        case 'update':
            updateQuantity();
            break;
        case 'get':
            getCartItems();
            break;
        case 'count':
            getCartCount();
            break;
        case 'clear':
            clearCart();
            break;
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Acción no válida.'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

function addToCart() {
    global $conn, $user_id;
    
    $product_id = sanitizeInput($_POST['product_id'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (empty($product_id) || $quantity <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Datos de producto inválidos.'
        ]);
        return;
    }
    
    // Try to load product from database first
    $product = null;
    $numeric_id = ctype_digit((string)$product_id) ? (int)$product_id : null;
    if ($numeric_id !== null) {
        $stmtProd = $conn->prepare("SELECT id, name, price, image_url, stock FROM products WHERE is_active = 1 AND id = ?");
        $stmtProd->bind_param("i", $numeric_id);
        if ($stmtProd->execute()) {
            $resProd = $stmtProd->get_result();
            if ($resProd && $resProd->num_rows > 0) {
                $row = $resProd->fetch_assoc();
                // Normalize local image path to root-relative for consistent usage across views
                $img = $row['image_url'] ?? '';
                if ($img && !preg_match('#^https?://#i', $img) && strpos($img, '/') !== 0) {
                    $img = '/' . ltrim($img, '/');
                }
                $product = [
                    'id' => (string)$row['id'],
                    'name' => $row['name'],
                    'price' => (float)$row['price'],
                    'image_url' => $img,
                    'stock' => isset($row['stock']) ? (int)$row['stock'] : null
                ];
            }
        }
        $stmtProd->close();
    }

    // Fallback to legacy JSON file if not found in DB
    if (!$product) {
        $products_file = '../data/products.json';
        if (file_exists($products_file)) {
            $products_data = json_decode(file_get_contents($products_file), true);
            $products_list = isset($products_data['products']) ? $products_data['products'] : $products_data;
            foreach ($products_list as $p) {
                if ($p['id'] == $product_id) {
                    $product = $p;
                    break;
                }
            }
        }
    }

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado.'
        ]);
        return;
    }
    
    // Get or create user's cart
    $cart_id = getUserCart($user_id);
    
    // Check if product already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("is", $cart_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $availableStock = isset($product['stock']) ? (int)$product['stock'] : PHP_INT_MAX; // if null, treat as unlimited
    if ($availableStock <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este producto está agotado.'
        ]);
        $stmt->close();
        return;
    }
    
    if ($result->num_rows > 0) {
        // Update existing item
        $item = $result->fetch_assoc();
        $new_quantity = $item['quantity'] + $quantity;
        if ($new_quantity > $availableStock) {
            $new_quantity = $availableStock;
        }
        
        $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $item['id']);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Producto actualizado en el carrito.',
                'cartCount' => getCartItemCount($cart_id)
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error actualizando el carrito.'
            ]);
        }
        $update_stmt->close();
    } else {
        // Add new item
        if ($quantity > $availableStock) {
            $quantity = $availableStock;
        }
        $insert_stmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, product_id, product_name, product_price, product_image, quantity) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("issdsi", 
            $cart_id, 
            $product['id'], 
            $product['name'], 
            $product['price'], 
            $product['image_url'], 
            $quantity
        );
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Producto agregado al carrito.',
                'cartCount' => getCartItemCount($cart_id)
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error agregando al carrito.'
            ]);
        }
        $insert_stmt->close();
    }
    $stmt->close();
}

function removeFromCart() {
    global $conn, $user_id;
    
    $item_id = (int)($_POST['item_id'] ?? 0);
    
    if ($item_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID de producto inválido.'
        ]);
        return;
    }
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
    $stmt->bind_param("ii", $item_id, $cart_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Producto removido del carrito.',
            'cartCount' => getCartItemCount($cart_id)
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error removiendo producto.'
        ]);
    }
    $stmt->close();
}

function updateQuantity() {
    global $conn, $user_id;
    
    $item_id = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    if ($item_id <= 0 || $quantity < 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Datos inválidos.'
        ]);
        return;
    }
    
    $cart_id = getUserCart($user_id);
    
    if ($quantity == 0) {
        // Remove item if quantity is 0
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
        $stmt->bind_param("ii", $item_id, $cart_id);
    } else {
        // Cap by product stock if available
        // Lookup product_id and available stock
        $getStmt = $conn->prepare("SELECT product_id FROM cart_items WHERE id = ? AND cart_id = ?");
        $getStmt->bind_param("ii", $item_id, $cart_id);
        $getStmt->execute();
        $res = $getStmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $pid = $row['product_id'];
            $pidNumeric = ctype_digit((string)$pid) ? (int)$pid : null;
            if ($pidNumeric !== null) {
                $ps = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $ps->bind_param("i", $pidNumeric);
                $ps->execute();
                $rs = $ps->get_result();
                if ($rs && $info = $rs->fetch_assoc()) {
                    $stock = isset($info['stock']) ? (int)$info['stock'] : PHP_INT_MAX;
                    if ($stock <= 0) { $quantity = 0; }
                    else if ($quantity > $stock) { $quantity = $stock; }
                }
                $ps->close();
            }
        }
        $getStmt->close();

        if ($quantity <= 0) {
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $stmt->bind_param("ii", $item_id, $cart_id);
        } else {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND cart_id = ?");
            $stmt->bind_param("iii", $quantity, $item_id, $cart_id);
        }
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Carrito actualizado.',
            'cartCount' => getCartItemCount($cart_id)
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error actualizando el carrito.'
        ]);
    }
    $stmt->close();
}

function getCartItems() {
    global $conn, $user_id;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("
        SELECT id, product_id, product_name, product_price, product_image, quantity,
               (product_price * quantity) as subtotal
        FROM cart_items 
        WHERE cart_id = ?
        ORDER BY added_at DESC
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['subtotal'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
    
    $stmt->close();
}

function getCartCount() {
    global $conn, $user_id;
    
    $cart_id = getUserCart($user_id);
    $count = getCartItemCount($cart_id);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

function clearCart() {
    global $conn, $user_id;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Carrito vaciado.',
            'cartCount' => 0
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error vaciando el carrito.'
        ]);
    }
    $stmt->close();
}

function getUserCart($user_id) {
    global $conn;
    
    // Try to get existing cart
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $stmt->close();
        return $cart['id'];
    }
    
    $stmt->close();
    
    // Create new cart
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, session_id) VALUES (?, ?)");
    $session_id = session_id();
    $insert_stmt->bind_param("is", $user_id, $session_id);
    $insert_stmt->execute();
    $cart_id = $conn->insert_id;
    $insert_stmt->close();
    
    return $cart_id;
}

function getCartItemCount($cart_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)($row['total'] ?? 0);
}

if (isset($conn)) {
    closeConnection($conn);
}
?>