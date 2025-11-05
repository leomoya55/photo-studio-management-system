<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db_connect.php';

// Set up user session variables
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
}

// Check if this is admin view mode and preserve admin_view parameter
$adminViewParam = (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? '?admin_view=1' : '';

// Load products from database (active only)
$products = [];
$categories = [];

if ($conn && !$conn->connect_error) {
    $sql = "SELECT id, name, description, price, category, image_url, stock, featured FROM products WHERE is_active = 1 ORDER BY featured DESC, created_at DESC";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            // Normalize types
            $row['price'] = (float)$row['price'];
            $row['featured'] = (bool)$row['featured'];
            $row['stock'] = isset($row['stock']) ? (int)$row['stock'] : null;
            $products[] = $row;
            if (!empty($row['category']) && !in_array($row['category'], $categories)) {
                $categories[] = $row['category'];
            }
        }
        $res->free();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Legend Dance Academy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Orange and Black Color Overrides -->
    <style>
        :root {
            --bs-primary: #ff6600;
            --bs-primary-rgb: 255, 102, 0;
        }
        
        .btn-primary {
            background-color: #ff6600 !important;
            border-color: #ff6600 !important;
            background-image: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #e55a00 !important;
            border-color: #e55a00 !important;
            background-image: linear-gradient(135deg, #e55a00 0%, #ff6600 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 102, 0, 0.3);
        }
        
        .text-primary {
            color: #ff6600 !important;
        }
        
        .hero-catalog {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 76px;
        }
        
        .category-filter {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 25px;
            color: white;
            padding: 8px 20px;
            margin: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .category-filter.active,
        .category-filter:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
        }
        
        .search-catalog {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            color: white;
            padding: 12px 25px;
        }
        
        .search-catalog::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .search-catalog:focus {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        }
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .size-option, .color-option {
            display: inline-block;
            margin: 2px;
            cursor: pointer;
        }
        
        .size-option {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .size-option:hover,
        .size-option.active {
            background-color: #ff6600;
            color: white;
            border-color: #ff6600;
        }
        
        .color-option {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #ddd;
            position: relative;
        }
        
        .color-option:hover,
        .color-option.active {
            border-color: #ff6600;
            transform: scale(1.1);
        }
        
        body {
            background-color: #fafafa;
        }
        
        .bg-primary {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
        }
        
        /* Fix any remaining blue/sky blue elements */
        .badge {
            background-color: #ff6600 !important;
            color: white !important;
        }
        
        .badge-primary {
            background-color: #ff6600 !important;
            color: white !important;
        }
        
        .badge-secondary {
            background-color: #333333 !important;
            color: white !important;
        }
        
        .badge-info {
            background-color: #ff6600 !important;
            color: white !important;
        }
        
        /* Additional styles for catalog page */
        .product-card {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        /* User welcome styling */
        .user-welcome {
            color: #ff6600 !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255, 102, 0, 0.1);
            border: 1px solid #ff6600;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: none;
        }
        
        .dropdown-item:hover {
            background-color: #ff6600;
            color: white;
        }
        
        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 9999;
            min-width: 300px;
        }
        
        .toast-notification.show {
            transform: translateX(0);
        }
        
        .toast-notification.success {
            border-left: 4px solid #28a745;
            color: #28a745;
        }
        
        .toast-notification.error {
            border-left: 4px solid #dc3545;
            color: #dc3545;
        }
        
        .toast-notification i {
            font-size: 18px;
        }
        
        /* Floating Cart */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(255, 102, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            font-size: 24px;
        }
        
        .floating-cart:hover {
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 102, 0, 0.5);
        }
        
        .floating-cart .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            display: none;
        }
        
        .floating-cart.cart-animate {
            animation: cartBounce 0.3s ease;
        }
        
        @keyframes cartBounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>">
                <span class="brand-text">Legend</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php<?php echo $adminViewParam; ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/horarios.php<?php echo $adminViewParam; ?>">Horarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/catalogo.php<?php echo $adminViewParam; ?>">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/redes-sociales.php<?php echo $adminViewParam; ?>">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/ubicacion.php<?php echo $adminViewParam; ?>">Ubicación</a>
                    </li>
                </ul>
                
                <!-- User Navigation -->
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-welcome" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a class="dropdown-item" href="../admin/admin.php"><i class="fas fa-cog me-2"></i>Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-outline-primary px-3 me-2" href="<?php echo VIEWS_URL; ?>/register.php">Registrarse</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn-primary text-white px-3" href="<?php echo VIEWS_URL; ?>/login.php">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1'): ?>
    <!-- Admin View Indicator -->
    <div class="alert alert-info border-0 rounded-0 text-center mb-0" style="background: linear-gradient(90deg, #17a2b8, #20c997); color: white;">
        <div class="container">
            <i class="fas fa-eye me-2"></i>
            <strong>Vista de Administrador</strong> - Vanessa, estás viendo el sitio web sin opciones de inscripción
            <a href="../admin/admin.php" class="btn btn-light btn-sm ms-3">
                <i class="fas fa-arrow-left me-1"></i>Volver al Panel Admin
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-catalog">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Catálogo Legend</h1>
                    <p class="lead mb-4">Descubre nuestra colección exclusiva de ropa de danza, calzado, accesorios y más. Todo lo que necesitas para brillar en cada paso.</p>
                </div>
                <div class="col-lg-4">
                    <div class="position-relative">
                        <input type="text" id="searchProducts" class="form-control search-catalog" placeholder="Buscar productos...">
                        <i class="fas fa-search position-absolute" style="right: 20px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.7);"></i>
                    </div>
                    <div class="mt-3">
                        <div class="category-filter active" data-category="all">Todos</div>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="category-filter" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="category-filter" data-category="Ropa">Ropa</div>
                            <div class="category-filter" data-category="Calzado">Calzado</div>
                            <div class="category-filter" data-category="Accesorios">Accesorios</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <div class="row" id="productsGrid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4 product-item" data-category="<?php echo htmlspecialchars($product['category']); ?>">
                            <div class="card product-card h-100">
                                <?php if (isset($product['featured']) && $product['featured']): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning text-dark">Destacado</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($product['stock']) && $product['stock'] <= 5 && $product['stock'] > 0): ?>
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-danger">¡Pocos disponibles!</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($product['image_url']) && $product['image_url']): ?>
                                    <?php 
                                        $img = $product['image_url'];
                                        $isHttp = preg_match('/^https?:\/\//i', $img);
                                        $isRoot = strpos($img, '/') === 0;
                                        // If it's a local relative path like assets/..., prefix ../ because we're under views/
                                        if (!$isHttp && !$isRoot) { $img = '../' . ltrim($img, '/'); }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img); ?>" 
                                         class="card-img-top" 
                                         style="height: 250px; object-fit: cover;"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 250px;">
                                        <div class="text-center">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="text-muted small">Sin imagen</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <button class="btn btn-sm btn-outline-danger wishlist-btn" onclick="toggleWishlist('<?php echo htmlspecialchars($product['id']); ?>')">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <p class="card-text flex-grow-1"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                    
                                    <div class="mb-3">
                                        <?php if (isset($product['size']) && $product['size']): ?>
                                            <small class="text-muted d-block mb-1">Talla disponible:</small>
                                            <span class="size-option active"><?php echo htmlspecialchars($product['size']); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($product['color']) && $product['color']): ?>
                                            <small class="text-muted d-block mb-1 mt-2">Color:</small>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($product['color']); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($product['brand']) && $product['brand']): ?>
                                            <small class="text-muted d-block mb-1 mt-1"><i class="fas fa-copyright me-1"></i><?php echo htmlspecialchars($product['brand']); ?></small>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($product['stock']) && $product['stock'] !== null): ?>
                                            <small class="d-block mb-2">
                                                <i class="fas fa-boxes me-1 text-muted"></i>Stock: 
                                                <span class="<?php echo $product['stock'] <= 5 ? 'text-danger fw-bold' : 'text-success'; ?>">
                                                    <?php echo $product['stock']; ?> disponible<?php echo $product['stock'] == 1 ? '' : 's'; ?>
                                                </span>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="h5 mb-0 text-primary">₡<?php echo number_format($product['price'], 0); ?></span>
                                        <?php if (isset($product['stock']) && (int)$product['stock'] === 0): ?>
                                            <span class="badge bg-dark">Agotado</span>
                                        <?php elseif (isset($product['stock']) && (int)$product['stock'] === 1): ?>
                                            <button class="btn btn-warning" onclick="addToCart('<?php echo htmlspecialchars($product['id']); ?>')">
                                                <i class="fas fa-shopping-cart me-1"></i>¡Último disponible!
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-primary" onclick="addToCart('<?php echo htmlspecialchars($product['id']); ?>')">
                                                <i class="fas fa-shopping-cart me-1"></i>Agregar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay productos disponibles</h4>
                        <p class="text-muted">Nuestro catálogo estará disponible pronto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="brand-text mb-0">Legend</h5>
                    <p class="mb-0">Tu academia de danza de confianza</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=100068508182444" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com/legendvm.cr/" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="disabled" title="Próximamente"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.tiktok.com/@studiolegend.cr" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/50684118339" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Cart -->
    <?php if ($isLoggedIn): ?>
    <a href="cart.php" class="floating-cart" title="Ver carrito">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">0</span>
    </a>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <!-- Clean Catalog JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupFilters();
            loadCartCount();
        });
        
        function loadCartCount() {
            <?php if ($isLoggedIn): ?>
            fetch('cart_handler.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.count);
                }
            })
            .catch(error => console.error('Error loading cart count:', error));
            <?php endif; ?>
        }

        function setupFilters() {
            // Category filters
            document.querySelectorAll('.category-filter').forEach(filter => {
                filter.addEventListener('click', function() {
                    document.querySelectorAll('.category-filter').forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    const category = this.getAttribute('data-category');
                    filterProducts(category);
                });
            });

            // Search functionality
            const searchInput = document.getElementById('searchProducts');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    searchProducts(searchTerm);
                });
            }
        }

        function filterProducts(category) {
            const products = document.querySelectorAll('.product-item');
            products.forEach(product => {
                const productCategory = product.getAttribute('data-category');
                if (category === 'all' || productCategory === category) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        function searchProducts(searchTerm) {
            const products = document.querySelectorAll('.product-item');
            products.forEach(product => {
                const productText = product.textContent.toLowerCase();
                if (productText.includes(searchTerm)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        function selectSize(element) {
            const parent = element.closest('.product-card');
            if (parent) {
                parent.querySelectorAll('.size-option').forEach(option => option.classList.remove('active'));
                element.classList.add('active');
            }
        }

        function selectColor(element) {
            const parent = element.closest('.product-card');
            if (parent) {
                parent.querySelectorAll('.color-option').forEach(option => option.classList.remove('active'));
                element.classList.add('active');
            }
        }

        function addToCart(productId) {
            // Check if user is logged in
            <?php if (!$isLoggedIn): ?>
            showLoginAlert();
            return;
            <?php endif; ?>
            
            // Show loading state
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
            
            // Send AJAX request to cart handler
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${encodeURIComponent(productId)}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    updateCartCount(data.cartCount);
                    
                    // Show success animation
                    btn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
                    btn.classList.add('btn-success');
                    btn.classList.remove('btn-primary');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.add('btn-primary');
                        btn.classList.remove('btn-success');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    if (data.requireLogin) {
                        showLoginAlert();
                    } else {
                        showErrorMessage(data.message);
                    }
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error agregando producto al carrito');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        function showLoginAlert() {
            if (confirm('Debes iniciar sesión para agregar productos al carrito. ¿Te gustaría iniciar sesión ahora?')) {
                window.location.href = 'login.php';
            }
        }
        
        function showSuccessMessage(message) {
            // Create and show success toast
            const toast = document.createElement('div');
            toast.className = 'toast-notification success';
            toast.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        
        function showErrorMessage(message) {
            // Create and show error toast
            const toast = document.createElement('div');
            toast.className = 'toast-notification error';
            toast.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        
        function updateCartCount(count) {
            const cartBadge = document.querySelector('.cart-count');
            if (cartBadge) {
                cartBadge.textContent = count;
                cartBadge.style.display = count > 0 ? 'block' : 'none';
                
                // Animate cart icon
                const cartIcon = document.querySelector('.floating-cart');
                if (cartIcon) {
                    cartIcon.classList.add('cart-animate');
                    setTimeout(() => {
                        cartIcon.classList.remove('cart-animate');
                    }, 300);
                }
            }
        }

        function toggleWishlist(productId) {
            const btn = event.target.closest('.wishlist-btn');
            if (btn) {
                btn.classList.toggle('active');
                alert(btn.classList.contains('active') ? 'Agregado a favoritos' : 'Removido de favoritos');
            }
        }
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    closeConnection($conn);
}
?>