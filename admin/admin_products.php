<?php
// admin_products.php - Product management for admin
session_start();
require_once '../config/db_connect.php';
require_once '../includes/ImageUploader.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description']);
                // Parse price input handling different formats (15000, 15,000, 15.000)
                $priceInput = $_POST['price'];
                // Remove spaces and handle different thousand separators
                $priceInput = str_replace(' ', '', $priceInput);
                // If it contains both comma and period, assume comma is thousands separator
                if (strpos($priceInput, ',') !== false && strpos($priceInput, '.') !== false) {
                    $priceInput = str_replace(',', '', $priceInput);
                }
                // If it contains comma but no period, check if it's thousands separator
                elseif (strpos($priceInput, ',') !== false && strpos($priceInput, '.') === false) {
                    // If there are more than 2 digits after comma, it's thousands separator
                    $parts = explode(',', $priceInput);
                    if (count($parts) == 2 && strlen($parts[1]) > 2) {
                        $priceInput = str_replace(',', '', $priceInput);
                    }
                }
                // If it contains period but no comma, check if it's thousands separator
                elseif (strpos($priceInput, '.') !== false && strpos($priceInput, ',') === false) {
                    // If there are more than 2 digits after period, it's thousands separator
                    $parts = explode('.', $priceInput);
                    if (count($parts) == 2 && strlen($parts[1]) > 2) {
                        $priceInput = str_replace('.', '', $priceInput);
                    }
                }
                $price = floatval($priceInput);
                $category = sanitizeInput($_POST['category']);
                $featured = isset($_POST['featured']) ? 1 : 0;
                $stock = isset($_POST['stock']) ? max(0, intval($_POST['stock'])) : 0;
                
                // Handle image upload with validation
                $image_path = '';
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    // Validate file size and type before upload
                    $maxSize = 10 * 1024 * 1024; // 10MB
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    
                    // Check file size
                    if ($_FILES['product_image']['size'] > $maxSize) {
                        $fileSizeMB = round($_FILES['product_image']['size'] / 1024 / 1024, 2);
                        $message = "La imagen es muy grande ({$fileSizeMB}MB). El tamaño máximo permitido es 10MB.";
                        $messageType = 'error';
                        break;
                    }
                    
                    // Check file type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        $message = 'Formato de imagen no válido. Solo se permiten archivos JPG, PNG, GIF y WebP.';
                        $messageType = 'error';
                        break;
                    }
                    
                    // Verify it's actually an image
                    $imageInfo = getimagesize($_FILES['product_image']['tmp_name']);
                    if ($imageInfo === false) {
                        $message = 'El archivo no es una imagen válida.';
                        $messageType = 'error';
                        break;
                    }
                    
                    $uploader = new ImageUploader('products');
                    $upload_result = $uploader->uploadImage($_FILES['product_image'], $name);
                    
                    if ($upload_result['success']) {
                        $image_path = $upload_result['filepath'];
                    } else {
                        $message = $upload_result['message'];
                        $messageType = 'error';
                        break;
                    }
                }
                
                // Handle sizes and colors as JSON
                $sizes = isset($_POST['sizes']) ? json_encode(array_filter(array_map('trim', explode(',', $_POST['sizes'])))) : '[]';
                $colors = isset($_POST['colors']) ? json_encode(array_filter(array_map('trim', explode(',', $_POST['colors'])))) : '[]';
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_url, sizes, colors, stock, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdssssii", $name, $description, $price, $category, $image_path, $sizes, $colors, $stock, $featured);
                
                if ($stmt->execute()) {
                    $message = 'Producto agregado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al agregar el producto: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_product':
                $product_id = intval($_POST['product_id']);
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $message = 'Producto eliminado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el producto.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;
                
            case 'toggle_featured':
                $product_id = intval($_POST['product_id']);
                $stmt = $conn->prepare("UPDATE products SET featured = NOT featured WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $message = 'Estado destacado actualizado.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al actualizar el producto.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all products
$products_query = "SELECT * FROM products ORDER BY created_at DESC";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Legend Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card {
            transition: transform 0.2s ease;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
        }
        
        .product-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .product-featured {
            border-left: 4px solid #ff6600;
        }
        
        .card-header {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white !important;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #e55500 0%, #e56f00 100%);
            border: none;
        }
        
        .text-primary {
            color: #ff6600 !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #ff6600;
            box-shadow: 0 0 0 0.2rem rgba(255, 102, 0, 0.25);
        }
        
        .badge-featured {
            background-color: #ff6600 !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-shopping-bag text-primary me-2"></i>Gestión de Productos</h1>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Add New Product Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Agregar Nuevo Producto</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_product">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Nombre del Producto</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Ej: Camiseta Legend Original">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="price" class="form-label">Precio (₡)</label>
                                    <input type="text" class="form-control" name="price" required placeholder="15000 o 15,000">
                                    <div class="form-text">Usar números sin símbolos. Ej: 15000 o 15,000</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="category" class="form-label">Categoría</label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="Ropa">Ropa</option>
                                        <option value="Calzado">Calzado</option>
                                        <option value="Accesorios">Accesorios</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="stock" class="form-label">Stock inicial</label>
                                    <input type="number" class="form-control" name="stock" min="0" step="1" value="0" placeholder="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="Descripción detallada del producto..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="product_image" class="form-label">Imagen del Producto</label>
                                <input type="file" class="form-control" name="product_image" accept="image/*" required onchange="validateFileSize(this, 'product')">
                                <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo <strong>10MB</strong>.</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sizes" class="form-label">Tallas Disponibles</label>
                                    <input type="text" class="form-control" name="sizes" placeholder="XS, S, M, L, XL">
                                    <div class="form-text">Separar con comas. Ej: XS, S, M, L, XL</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="colors" class="form-label">Colores Disponibles</label>
                                    <input type="text" class="form-control" name="colors" placeholder="Negro, Blanco, Rojo">
                                    <div class="form-text">Separar con comas. Ej: Negro, Blanco, Rojo</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="featured">
                                    <label class="form-check-label" for="featured">
                                        Producto Destacado (aparecerá en la página principal)
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Agregar Producto
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Existing Products -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Productos Actuales (<?php echo $products_result ? $products_result->num_rows : 0; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($products_result && $products_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card product-card <?php echo $product['featured'] ? 'product-featured' : ''; ?>">
                                            <?php if ($product['image_url']): ?>
                                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <?php if ($product['featured']): ?>
                                                        <span class="badge badge-featured">Destacado</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category']); ?></p>
                                                <p class="card-text small"><?php echo nl2br(htmlspecialchars(substr($product['description'], 0, 80))); ?><?php echo strlen($product['description']) > 80 ? '...' : ''; ?></p>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-primary">₡<?php echo number_format($product['price'], 0); ?></strong>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $sizes = json_decode($product['sizes'], true);
                                                        if ($sizes && count($sizes) > 0) {
                                                            echo count($sizes) . ' tallas';
                                                        } else {
                                                            echo 'Sin tallas';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="badge <?php echo ((int)($product['stock'] ?? 0) <= 5) ? 'bg-danger' : 'bg-secondary'; ?>">
                                                        Stock: <?php echo (int)($product['stock'] ?? 0); ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_featured">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este producto?')">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay productos agregados aún</h5>
                                <p class="text-muted">¡Agrega tu primer producto usando el formulario de arriba!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Validate file size before upload
         */
        function validateFileSize(input, type = 'image') {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const file = input.files[0];
            
            if (file) {
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                
                if (file.size > maxSize) {
                    alert(`❌ La imagen es muy grande (${fileSizeMB}MB).\n\nTamaño máximo permitido: 10MB\n\nPor favor, selecciona una imagen más pequeña.`);
                    input.value = '';
                    return false;
                }
                
                // Show file info for user feedback
                const fileInfo = document.createElement('div');
                fileInfo.className = 'alert alert-info mt-2';
                fileInfo.innerHTML = `✅ Archivo seleccionado: <strong>${file.name}</strong> (${fileSizeMB}MB)`;
                
                // Remove any existing file info
                const existingInfo = input.parentNode.querySelector('.alert-info');
                if (existingInfo) {
                    existingInfo.remove();
                }
                
                // Add new file info
                input.parentNode.appendChild(fileInfo);
                
                return true;
            }
            return false;
        }
    </script>
</body>
</html>

<?php closeConnection($conn); ?>