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
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Orange and Black Color Overrides to match index.html -->
    <style>
        /* Orange and Black Color Overrides */
        :root {
            --bs-primary: #ff6600;
            --bs-primary-rgb: 255, 102, 0;
            --bs-btn-hover-bg: #e55a00;
            --bs-btn-active-bg: #e55a00;
        }
        
        /* Force our orange color */
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
        
        .btn-outline-primary {
            color: #ff6600 !important;
            border-color: #ff6600 !important;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: #ff6600 !important;
            border-color: #ff6600 !important;
            color: white !important;
        }
        
        .text-primary {
            color: #ff6600 !important;
        }
        
        .brand-text {
            font-family: 'Dancing Script', cursive !important;
            color: #ff6600 !important;
            font-weight: 700;
        }
        
        .section-title {
            color: #000000 !important;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link:hover {
            color: #ff6600 !important;
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
        
        /* Level badges for classes */
        .badge:contains("Principiante"),
        .badge:contains("Intermedio"), 
        .badge:contains("Avanzado") {
            background-color: #333333 !important;
            color: white !important;
        }
        
        /* Fix any sky blue colors */
        .text-info {
            color: #ff6600 !important;
        }
        
        .bg-info {
            background-color: #ff6600 !important;
        }
        
        /* Icons and checks */
        .fas.fa-check,
        .fas.fa-check-circle {
            color: #ff6600 !important;
        }
        
        /* Any remaining blue accents */
        .text-secondary {
            color: #333333 !important;
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
        
        .product-image {
            height: 250px;
            background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 1rem;
            border: 2px dashed #bdc3c7;
            position: relative;
            overflow: hidden;
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--gradient-primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .product-rating {
            color: #ffc107;
            font-size: 0.9rem;
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
        
        .cart-summary {
            position: fixed;
            top: 50%;
            right: -300px;
            transform: translateY(-50%);
            width: 280px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
            padding: 20px;
        }
        
        .cart-summary.show {
            right: 20px;
        }
        
        .cart-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            border: none;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1001;
            transition: all 0.3s ease;
        }
        
        .cart-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
        
        .cart-counter {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .size-selector {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .size-option {
            width: 35px;
            height: 35px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .size-option:hover,
        .size-option.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .color-selector {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .color-option {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .color-option.active {
            border-color: var(--primary-color);
            transform: scale(1.2);
        }
        
        .color-option.active::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            transition: all 0.3s ease;
            z-index: 2;
        }
        
        .wishlist-btn:hover,
        .wishlist-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .loading-state {
            text-align: center;
            padding: 60px 0;
        }
        
        @media (max-width: 768px) {
            .hero-catalog {
                padding: 80px 0 60px;
                text-align: center;
            }
            
            .cart-summary {
                right: -100vw;
                width: 90vw;
                max-width: 300px;
            }
            
            .cart-summary.show {
                right: 5vw;
            }
            
            .category-filter {
                font-size: 0.9rem;
                padding: 6px 15px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="brand-text">Legend</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clases.html">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="catalogo.html">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redes-sociales.html">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ubicacion.html">Ubicación</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link btn-outline-primary" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                        <div class="category-filter" data-category="Ropa">Ropa</div>
                        <div class="category-filter" data-category="Calzado">Calzado</div>
                        <div class="category-filter" data-category="Accesorios">Accesorios</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <div class="row" id="productsGrid">
                <!-- Products will be loaded here dynamically -->
                <div class="col-12 loading-state">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando productos...</span>
                    </div>
                    <p class="mt-3">Cargando catálogo de productos...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Toggle Button -->
    <button class="cart-toggle" onclick="toggleCart()">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-counter" id="cartCounter">0</span>
    </button>

    <!-- Cart Summary -->
    <div class="cart-summary" id="cartSummary">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Carrito</h5>
            <button class="btn-close" onclick="toggleCart()"></button>
        </div>
        <div id="cartItems">
            <p class="text-muted text-center">Tu carrito está vacío</p>
        </div>
        <div class="border-top pt-3 mt-3" id="cartFooter" style="display: none;">
            <div class="d-flex justify-content-between mb-3">
                <strong>Total: <span id="cartTotal">$0</span></strong>
            </div>
            <button class="btn btn-primary w-100 mb-2" onclick="proceedToCheckout()">
                <i class="fas fa-credit-card me-2"></i>Proceder al Pago
            </button>
            <button class="btn btn-outline-secondary w-100" onclick="clearCart()">
                <i class="fas fa-trash me-2"></i>Vaciar Carrito
            </button>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrarse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="confirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <!-- Catalog Page JS -->
    <script>
        let allProducts = [];
        let filteredProducts = [];
        let cart = [];
        let wishlist = [];
        let currentCategory = 'all';
        let currentSearch = '';

        // Sample products data
        const sampleProducts = [
            {
                id: 'shirt-legend-001',
                name: 'Camiseta Legend Original',
                description: 'Camiseta oficial de la academia con logo bordado. 100% algodón.',
                price: 25,
                category: 'Ropa',
                image: '',
                sizes: ['XS', 'S', 'M', 'L', 'XL'],
                colors: ['#000000', '#ffffff', '#ff6b6b', '#4ecdc4'],
                rating: 4.8,
                featured: true
            },
            {
                id: 'shoes-ballet-001',
                name: 'Zapatillas de Ballet Clásico',
                description: 'Zapatillas profesionales de ballet en cuero suave para niños y adultos.',
                price: 45,
                category: 'Calzado',
                image: '',
                sizes: ['28', '30', '32', '34', '36', '38', '40'],
                colors: ['#f8d7da', '#ffffff', '#000000'],
                rating: 4.9,
                featured: true
            },
            {
                id: 'shoes-hip-hop-001',
                name: 'Sneakers Hip Hop Pro',
                description: 'Zapatillas especiales para hip hop con suela antideslizante.',
                price: 65,
                category: 'Calzado',
                image: '',
                sizes: ['35', '36', '37', '38', '39', '40', '41', '42'],
                colors: ['#000000', '#ffffff', '#ff6b6b'],
                rating: 4.7,
                featured: false
            },
            {
                id: 'leggings-dance-001',
                name: 'Leggings de Danza',
                description: 'Leggings elásticos perfectos para cualquier estilo de danza.',
                price: 35,
                category: 'Ropa',
                image: '',
                sizes: ['XS', 'S', 'M', 'L', 'XL'],
                colors: ['#000000', '#4ecdc4', '#ff6b6b'],
                rating: 4.6,
                featured: true
            },
            {
                id: 'bag-dance-001',
                name: 'Bolsa de Danza Legend',
                description: 'Bolsa espaciosa con compartimentos para zapatos y accesorios.',
                price: 40,
                category: 'Accesorios',
                image: '',
                sizes: ['Única'],
                colors: ['#000000', '#ff6b6b'],
                rating: 4.5,
                featured: false
            },
            {
                id: 'tutu-ballet-001',
                name: 'Tutú Clásico Infantil',
                description: 'Tutú tradicional de ballet para presentaciones y clases.',
                price: 55,
                category: 'Ropa',
                image: '',
                sizes: ['4', '6', '8', '10', '12'],
                colors: ['#f8d7da', '#ffffff', '#e6f3ff'],
                rating: 4.8,
                featured: false
            },
            {
                id: 'water-bottle-001',
                name: 'Botella de Agua Legend',
                description: 'Botella deportiva con logo de la academia, 750ml.',
                price: 15,
                category: 'Accesorios',
                image: '',
                sizes: ['750ml'],
                colors: ['#4ecdc4', '#ff6b6b', '#000000'],
                rating: 4.3,
                featured: false
            },
            {
                id: 'hoodie-legend-001',
                name: 'Sudadera Legend',
                description: 'Sudadera con capucha y logo bordado, perfecta para el calentamiento.',
                price: 50,
                category: 'Ropa',
                image: '',
                sizes: ['XS', 'S', 'M', 'L', 'XL'],
                colors: ['#000000', '#4ecdc4', '#ffffff'],
                rating: 4.7,
                featured: true
            }
        ];

        // Load products on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            setupEventListeners();
            loadCartFromStorage();
        });

        // Load products
        function loadProducts() {
            allProducts = [...sampleProducts];
            filteredProducts = [...allProducts];
            renderProducts();
        }

        // Setup event listeners
        function setupEventListeners() {
            // Category filters
            document.querySelectorAll('.category-filter').forEach(filter => {
                filter.addEventListener('click', function() {
                    document.querySelectorAll('.category-filter').forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    currentCategory = this.getAttribute('data-category');
                    filterProducts();
                });
            });

            // Search functionality
            const searchInput = document.getElementById('searchProducts');
            searchInput.addEventListener('input', function() {
                currentSearch = this.value.toLowerCase();
                filterProducts();
            });
        }

        // Filter products
        function filterProducts() {
            filteredProducts = allProducts.filter(product => {
                const matchesCategory = currentCategory === 'all' || product.category === currentCategory;
                const matchesSearch = currentSearch === '' || 
                    product.name.toLowerCase().includes(currentSearch) ||
                    product.description.toLowerCase().includes(currentSearch) ||
                    product.category.toLowerCase().includes(currentSearch);
                
                return matchesCategory && matchesSearch;
            });
            
            renderProducts();
        }

        // Render products
        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            
            if (filteredProducts.length === 0) {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No se encontraron productos</h4>
                        <p>Intenta con otros términos de búsqueda o selecciona una categoría diferente.</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = filteredProducts.map(product => `
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card product-card">
                        <div class="position-relative">
                            <div class="product-image">
                                ${product.name}
                            </div>
                            ${product.featured ? '<div class="product-badge">Destacado</div>' : ''}
                            <button class="wishlist-btn ${wishlist.includes(product.id) ? 'active' : ''}" onclick="toggleWishlist('${product.id}')">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-2">${product.name}</h6>
                            <p class="card-text small text-muted mb-2">${product.description}</p>
                            
                            <div class="product-rating mb-2">
                                ${generateStars(product.rating)}
                                <span class="ms-1">(${product.rating})</span>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted d-block">Tallas disponibles:</small>
                                <div class="size-selector" id="sizes-${product.id}">
                                    ${product.sizes.map(size => `
                                        <div class="size-option" onclick="selectSize('${product.id}', '${size}')">${size}</div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block">Colores:</small>
                                <div class="color-selector" id="colors-${product.id}">
                                    ${product.colors.map(color => `
                                        <div class="color-option" style="background-color: ${color}" onclick="selectColor('${product.id}', '${color}')"></div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price">$${product.price}</span>
                                <button class="btn btn-primary btn-sm" onclick="addToCart('${product.id}')">
                                    <i class="fas fa-cart-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Generate star rating
        function generateStars(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 !== 0;
            let stars = '';
            
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star"></i>';
            }
            
            if (hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            }
            
            return stars;
        }

        // Select size
        function selectSize(productId, size) {
            const sizeSelector = document.getElementById(`sizes-${productId}`);
            sizeSelector.querySelectorAll('.size-option').forEach(option => {
                option.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Select color
        function selectColor(productId, color) {
            const colorSelector = document.getElementById(`colors-${productId}`);
            colorSelector.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Add to cart
        function addToCart(productId) {
            const product = allProducts.find(p => p.id === productId);
            const sizeSelector = document.getElementById(`sizes-${productId}`);
            const colorSelector = document.getElementById(`colors-${productId}`);
            
            const selectedSize = sizeSelector.querySelector('.size-option.active');
            const selectedColor = colorSelector.querySelector('.color-option.active');
            
            if (!selectedSize) {
                showNotification('Por favor selecciona una talla', 'warning');
                return;
            }
            
            if (!selectedColor) {
                showNotification('Por favor selecciona un color', 'warning');
                return;
            }
            
            const cartItem = {
                ...product,
                selectedSize: selectedSize.textContent,
                selectedColor: selectedColor.style.backgroundColor,
                quantity: 1,
                cartId: `${productId}-${selectedSize.textContent}-${selectedColor.style.backgroundColor}`
            };
            
            const existingItem = cart.find(item => item.cartId === cartItem.cartId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push(cartItem);
            }
            
            updateCartUI();
            saveCartToStorage();
            showNotification(`${product.name} agregado al carrito`, 'success');
        }

        // Toggle wishlist
        function toggleWishlist(productId) {
            const index = wishlist.indexOf(productId);
            if (index > -1) {
                wishlist.splice(index, 1);
                showNotification('Producto removido de favoritos', 'info');
            } else {
                wishlist.push(productId);
                showNotification('Producto agregado a favoritos', 'success');
            }
            
            // Update button state
            const btn = event.target.closest('.wishlist-btn');
            btn.classList.toggle('active');
        }

        // Toggle cart
        function toggleCart() {
            const cartSummary = document.getElementById('cartSummary');
            cartSummary.classList.toggle('show');
        }

        // Update cart UI
        function updateCartUI() {
            const cartCounter = document.getElementById('cartCounter');
            const cartItems = document.getElementById('cartItems');
            const cartFooter = document.getElementById('cartFooter');
            const cartTotal = document.getElementById('cartTotal');
            
            cartCounter.textContent = cart.reduce((total, item) => total + item.quantity, 0);
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-muted text-center">Tu carrito está vacío</p>';
                cartFooter.style.display = 'none';
                return;
            }
            
            cartItems.innerHTML = cart.map(item => `
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <small class="text-muted">Talla: ${item.selectedSize}</small><br>
                        <small class="text-muted">Cantidad: ${item.quantity}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">$${item.price * item.quantity}</div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('${item.cartId}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = `$${total}`;
            cartFooter.style.display = 'block';
        }

        // Remove from cart
        function removeFromCart(cartId) {
            cart = cart.filter(item => item.cartId !== cartId);
            updateCartUI();
            saveCartToStorage();
            showNotification('Producto removido del carrito', 'info');
        }

        // Clear cart
        function clearCart() {
            cart = [];
            updateCartUI();
            saveCartToStorage();
            showNotification('Carrito vaciado', 'info');
        }

        // Proceed to checkout
        function proceedToCheckout() {
            if (cart.length === 0) {
                showNotification('Tu carrito está vacío', 'warning');
                return;
            }
            
            showNotification('¡Gracias por tu compra! Te contactaremos pronto.', 'success');
            clearCart();
            toggleCart();
        }

        // Save cart to localStorage
        function saveCartToStorage() {
            localStorage.setItem('legendCart', JSON.stringify(cart));
        }

        // Load cart from localStorage
        function loadCartFromStorage() {
            const savedCart = localStorage.getItem('legendCart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateCartUI();
            }
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} position-fixed`;
            notification.style.cssText = `
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9999;
                min-width: 300px;
                text-align: center;
            `;
            notification.innerHTML = `
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
</body>
</html>