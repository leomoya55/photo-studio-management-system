<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/paths.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . VIEWS_URL . '/login.php');
    exit();
}

// Set up user session variables
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = 'customer';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/images/favicon.svg">
    <title>Mi Carrito - Vale V Photography</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Vale V Photography monochrome accents -->
    <style>
        /* Monochrome palette */
        :root {
            --bs-primary: #000000;
            --bs-primary-rgb: 0, 0, 0;
            --bs-btn-hover-bg: #111111;
            --bs-btn-active-bg: #111111;
        }
        
        /* Force our primary color */
        .btn-primary {
            background-color: #000000 !important;
            border-color: #000000 !important;
            background-image: linear-gradient(135deg, #000000 0%, #333333 100%) !important;
            color: #ffffff !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #111111 !important;
            border-color: #111111 !important;
            background-image: linear-gradient(135deg, #111111 0%, #444444 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            color: #ffffff !important;
        }
        
        .btn-outline-primary {
            border-color: #111111 !important;
            color: #111111 !important;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: #000000 !important;
            border-color: #000000 !important;
            color: #ffffff !important;
        }
        
        .text-primary {
            color: #111111 !important;
        }
        
        .brand-text {
            font-family: 'Dancing Script', cursive !important;
            color: var(--brand-color) !important;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link:hover {
            color: #111111 !important;
        }
        
        /* User welcome styling */
        .user-welcome {
            color: #111111 !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(61,43,40,0.18);
            border: none;
        }
        
        .dropdown-item:hover {
            background-color: rgba(0, 0, 0, 0.08);
            color: #111111;
        }
        
        .hero-cart {
            background: linear-gradient(135deg, #fff3e1 0%, #f6caa5 100%);
            color: #3d2b28;
            padding: 100px 0 80px;
            margin-top: 76px;
        }

        .hero-cart .text-on-dark {
            color: #3d2b28 !important;
            text-shadow: none;
        }

        .hero-cart .text-on-dark-soft {
            color: #6f4b3a !important;
            text-shadow: none;
        }
        
        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-controls button {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: #f8f9fa;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .quantity-controls button:hover {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: #ffffff;
        }
        
        .quantity-controls input {
            width: 50px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 5px;
        }
        
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cart-empty i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .remove-item {
            color: #dc3545;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remove-item:hover {
            color: #c82333;
            transform: scale(1.1);
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php">
                <span class="brand-text">Vale V Photography</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php">Sesiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/catalogo.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/ubicacion.php">Ubicación</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/contact.php">Contacto</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- User section -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-welcome" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php#clases"><i class="fas fa-calendar me-2"></i>Mis Clases</a></li>
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/cart.php"><i class="fas fa-shopping-cart me-2"></i>Mi Carrito</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-primary" href="login.php">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-cart">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4 text-on-dark">
                        <i class="fas fa-shopping-cart me-3"></i>Mi Carrito
                    </h1>
                    <p class="lead text-on-dark-soft">Revisa tus productos seleccionados y procede al checkout</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div id="cart-items">
                        <!-- Cart items will be loaded here -->
                        <div class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
                            <p class="mt-3 text-muted">Cargando carrito...</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">Resumen del Pedido</h4>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span id="subtotal">₡0</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Envío:</span>
                            <span class="text-success">Gratis</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong id="total" class="text-primary">₡0</strong>
                        </div>
                        
                        <button id="checkout-btn" class="btn btn-primary w-100 py-3 mb-3" disabled>
                            <i class="fas fa-credit-card me-2"></i>
                            Proceder al Checkout
                        </button>
                        
                        <a href="<?php echo VIEWS_URL; ?>/catalogo.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-arrow-left me-2"></i>
                            Continuar Comprando
                        </a>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Compra 100% segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="brand-text">Vale V Photography</h5>
                    <p>Estudio fotográfico donde cada imagen cuenta una historia.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1Czy4E7doQ/?mibextid=wwXIfr" class="text-white me-3" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/valevphotography?igsh=MXZobjc0NWtod2gyMA%3D%3D&utm_source=qr" class="text-white me-3" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@valevstudio" class="text-white me-3" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/50686764740" class="text-white" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                    <p class="mt-2">&copy; 2025 Vale V Photography. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    
    <!-- Cart JS -->
    <script>
        const VIEWS_BASE = '<?php echo VIEWS_URL; ?>';
        // Normalize image URLs: handle http(s), root-relative, backslashes (Windows), or local relative stored paths
        function normalizeImageUrl(url){
            if(!url) return 'https://via.placeholder.com/80?text=IMG';
            url = String(url);
            // Normalize path separators and leading markers before deciding how to resolve
            url = url.replace(/\\/g, '/').trim();
            if(/^https?:\/\//i.test(url) || url.startsWith('//')) return url; // absolute URL (with or without protocol)
            if(url.startsWith('/')) return url; // root-relative already valid
            if(url.startsWith('../')) return url; // already relative to views/
            if(url.startsWith('./')) return '../' + url.replace(/^\.\//, '');
            return '../' + url.replace(/^\/+/, ''); // default: move from views/ to web root
        }
        document.addEventListener('DOMContentLoaded', function() {
            loadCartItems();
        });
        
        function loadCartItems() {
            fetch(VIEWS_BASE + '/cart_handler.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCartItems(data.items, data.total);
                } else {
                    showErrorMessage('Error cargando el carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error de conexión');
            });
        }
        
        function displayCartItems(items, total) {
            const cartItemsContainer = document.getElementById('cart-items');
            
            if (items.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <h3 class="text-muted">Tu carrito está vacío</h3>
                        <p class="text-muted">¡Agrega algunos productos para comenzar!</p>
                        <a href="catalogo.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Explorar Catálogo
                        </a>
                    </div>
                `;
                document.getElementById('checkout-btn').disabled = true;
                document.getElementById('subtotal').textContent = '₡0';
                document.getElementById('total').textContent = '₡0';
                return;
            }
            
            let cartHTML = '';
            items.forEach(item => {
                cartHTML += `
                    <div class="cart-item" data-item-id="${item.id}">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="${normalizeImageUrl(item.product_image)}" alt="${item.product_name}" class="img-fluid rounded" style="max-height: 80px; object-fit: cover;">
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1">${item.product_name}</h5>
                                <p class="text-muted mb-0">₡${parseFloat(item.product_price).toLocaleString('es-CR')}</p>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})" ${item.quantity <= 1 ? 'disabled' : ''}>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${item.id}, this.value)">
                                    <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong>₡${parseFloat(item.subtotal).toLocaleString('es-CR')}</strong>
                            </div>
                            <div class="col-md-1 text-end">
                                <i class="fas fa-trash remove-item" onclick="removeItem(${item.id})" title="Eliminar"></i>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            cartItemsContainer.innerHTML = cartHTML;
            document.getElementById('subtotal').textContent = `₡${parseFloat(total).toLocaleString('es-CR')}`;
            document.getElementById('total').textContent = `₡${parseFloat(total).toLocaleString('es-CR')}`;
            document.getElementById('checkout-btn').disabled = false;
            
            // Add checkout event listener
            document.getElementById('checkout-btn').onclick = () => {
                window.location.href = VIEWS_BASE + '/checkout.php';
            };
        }
        
        function updateQuantity(itemId, quantity) {
            quantity = parseInt(quantity);
            if (quantity < 0) return;
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('item_id', itemId);
            formData.append('quantity', quantity);
            
            fetch(VIEWS_BASE + '/cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCartItems(); // Reload the cart
                    showSuccessMessage(data.message);
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error actualizando cantidad');
            });
        }
        
        function removeItem(itemId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('item_id', itemId);
            
            fetch(VIEWS_BASE + '/cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCartItems(); // Reload the cart
                    showSuccessMessage(data.message);
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error eliminando producto');
            });
        }
        
        function showSuccessMessage(message) {
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
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    closeConnection($conn);
}
?>