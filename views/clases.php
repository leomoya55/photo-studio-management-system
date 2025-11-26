<?php
session_start();
require_once '../config/paths.php';
require_once '../config/db_connect.php';
require_once '../config/image_helpers.php';

// Set up user session variables
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
}

// Check if this is admin view mode (admin viewing website without enrollment options)
$isAdminView = isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && $isLoggedIn && $userRole === 'admin';
$adminViewParam = (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? '?admin_view=1' : '';

// If admin_view parameter is set but user is not admin, redirect to login
if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && (!$isLoggedIn || $userRole !== 'admin')) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Debug output (remove in production)
// echo "<!-- DEBUG: userRole = '$userRole', admin_view = " . (isset($_GET['admin_view']) ? $_GET['admin_view'] : 'not set') . ", isLoggedIn = " . ($isLoggedIn ? 'true' : 'false') . ", isAdminView = " . ($isAdminView ? 'true' : 'false') . " -->";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Vale V Photography</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Vale V Photography warm palette -->
    <style>
        /* Monochrome accent overrides */
        :root {
            --bs-primary: #000000;
            --bs-primary-rgb: 0, 0, 0;
            --bs-btn-hover-bg: #111111;
            --bs-btn-active-bg: #111111;
        }
        
        /* Force our monochrome accent */
        .btn-primary {
            background-color: #000000 !important;
            border-color: #000000 !important;
            background-image: linear-gradient(135deg, #000000 0%, #333333 100%) !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #111111 !important;
            border-color: #111111 !important;
            background-image: linear-gradient(135deg, #111111 0%, #444444 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .btn-outline-primary {
            color: #111111 !important;
            border-color: #111111 !important;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: #000000 !important;
            border-color: #000000 !important;
            color: white !important;
        }
        
        .text-primary {
            color: #111111 !important;
        }
        
        .brand-text {
            font-family: 'Dancing Script', cursive !important;
            color: var(--brand-color) !important;
            font-weight: 700;
        }
        
        .section-title {
            color: #000000 !important;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link:hover {
            color: #333333 !important;
        }
        
        body {
            background-color: #fafafa;
        }
        
        .bg-primary {
            background: linear-gradient(135deg, #000000 0%, #333333 100%) !important;
        }
        
        .class-schedule {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 10px;
            margin: 10px 0;
        }
        
        .filter-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }
        
        .filter-tab {
            background: none;
            border: none;
            padding: 15px 25px;
            color: #6c757d;
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            color: var(--primary-color);
        }
        
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--gradient-primary);
        }
        
        .hero-classes {
            background: linear-gradient(135deg, #000000 0%, #3a3a3a 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 76px;
        }
        
        .search-box {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            color: white;
            padding: 12px 25px;
        }
        
        .search-box::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .search-box:focus {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .benefit-item i {
            color: #111111 !important;
            margin-right: 8px;
            font-size: 0.8rem;
        }
        
        .instructor-info {
            display: flex;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .instructor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #333333 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .loading-state {
            text-align: center;
            padding: 60px 0;
            color: #6c757d;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 0;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .hero-classes {
                padding: 80px 0 60px;
                text-align: center;
            }
            
            .filter-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .filter-tab {
                min-width: 120px;
            }
        }
        
        /* Class Card Animation Improvements */
        .class-card {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .class-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .class-category-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: linear-gradient(135deg, #000000 0%, #343434 100%);
            color: #ffffff;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        
        /* User welcome styling */
        .user-welcome {
            color: #111111 !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #111111;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: none;
        }
        
        .dropdown-item:hover {
            background-color: #000000;
            color: white;
        }
        
        /* Schedule Selection Styles */
        .schedule-selection .form-check {
            margin-bottom: 0.75rem;
        }
        
        .schedule-option {
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .schedule-option:hover {
            background: rgba(0, 0, 0, 0.08);
            border-color: #111111 !important;
        }
        
        .form-check-input:checked + .form-check-label .schedule-option {
            background: rgba(0, 0, 0, 0.08);
            border-color: #111111 !important;
            color: #111111;
        }
        
        .form-check-input:checked + .form-check-label .schedule-option .fas {
            color: #111111 !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>">
                <span class="brand-text">Vale V Photography</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Sesiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/catalogo.php<?php echo $adminViewParam; ?>">Catálogo</a>
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
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-welcome" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($userName); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/admin.php"><i class="fas fa-cog me-2"></i>Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-outline-primary" href="<?php echo VIEWS_URL; ?>/register.php">Registrarse</a>
                        </li>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-primary" href="<?php echo VIEWS_URL; ?>/login.php">Iniciar Sesión</a>
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
            <a href="<?php echo ADMIN_URL; ?>/admin.php" class="btn btn-light btn-sm ms-3">
                <i class="fas fa-arrow-left me-1"></i>Volver al Panel Admin
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-classes">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4 text-on-dark">Servicios Fotográficos</h1>
                    <p class="lead mb-4 text-on-dark-soft">Explora sesiones diseñadas para resaltar tu esencia: retratos editoriales, branding para marcas, cobertura de eventos y conceptos creativos personalizados. En nuestro estudio cada proyecto recibe dirección artística dedicada.</p>
                </div>
                <div class="col-lg-4">
                    <div class="position-relative">
                        <input type="text" id="searchClasses" class="form-control search-box" placeholder="Buscar clases...">
                        <i class="fas fa-search position-absolute" style="right: 20px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.7);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Classes Section -->
    <section class="py-5">
        <div class="container">
            <!-- Filter Tabs -->
            <div class="row">
                <div class="col-12 text-center mb-3">
                    <p class="text-muted mb-0">Todas las sesiones son bajo disponibilidad de la fotografa Valeria Vega.</p>
                </div>
                <div class="col-12">
                    <div id="filterTabs" class="filter-tabs d-flex justify-content-center flex-wrap mb-4" aria-label="Filtros por categoría">
                        <div class="text-muted small py-2" data-filter-placeholder>Cargando categorías...</div>
                    </div>
                </div>
            </div>

            <!-- Classes Grid -->
            <div class="row" id="classesGrid">
                <!-- Classes will be loaded here dynamically -->
                <div class="col-12 loading-state">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando clases...</span>
                    </div>
                    <p class="mt-3">Cargando clases disponibles...</p>
                </div>
            </div>

            <!-- No Results -->
            <div class="row d-none" id="noResults">
                <div class="col-12 no-results">
                    <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                    <h4>No se encontraron clases</h4>
                    <p>Intenta con otros términos de búsqueda o selecciona una categoría diferente.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Enrollment CTA -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="section-title">¿Listo para crear imágenes memorables?</h2>
                    <p class="section-subtitle">Agenda tu sesión con nuestro equipo y diseñemos juntos la experiencia ideal para ti, tu marca o tu evento. Cada propuesta incluye dirección creativa y asesoría personalizada.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="#" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus me-2"></i>Registrarse Ahora
                        </a>
                        <a href="ubicacion.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-map-marker-alt me-2"></i>Visítanos
                        </a>
                        <a href="redes-sociales.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-images me-2"></i>Ver Portafolio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                    <h5 class="brand-text mb-0">Vale V Photography</h5>
                    <p class="mb-0">Tu estudio de fotografía de confianza</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1Czy4E7doQ/?mibextid=wwXIfr" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com/valevphotography?igsh=MXZobjc0NWtod2gyMA%3D%3D&utm_source=qr" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/50686764740" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <!-- Classes Page JS -->
    <script>
        // Cloudinary helper function with fallback support
        function getCloudinaryImageUrl(imageValue, width = 400, height = 300) {
            if (!imageValue) {
                return `https://via.placeholder.com/${width}x${height}?text=No+Image`;
            }

            const cloudName = 'deov2g1ji';
            const transformations = `w_${width},h_${height},c_fill,f_auto,q_auto,dpr_auto`;
            const placeholder = `https://via.placeholder.com/${width}x${height}?text=No+Image`;

            const isFullUrl = typeof imageValue === 'string' && /^https?:\/\//i.test(imageValue.trim());
            if (isFullUrl) {
                const trimmed = imageValue.trim();
                const marker = '/image/upload/';
                if (!trimmed.includes(marker)) {
                    return trimmed;
                }

                const parts = trimmed.split(marker);
                if (parts.length < 2) {
                    return trimmed;
                }

                const prefix = parts[0];
                const suffixRaw = parts.slice(1).join(marker);
                const suffix = suffixRaw.replace(/^\/+/, '');
                if (suffix.startsWith('v')) {
                    return `${prefix}${marker}${transformations}/${suffix}`;
                }

                const segments = suffix.split('/').filter(Boolean);
                if (!segments.length) {
                    return trimmed;
                }

                segments[0] = transformations;
                return `${prefix}${marker}${segments.join('/')}`;
            }

            try {
                const encodedId = String(imageValue)
                    .split('/')
                    .map(segment => encodeURIComponent(segment))
                    .join('/');
                return `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${encodedId}`;
            } catch (_e) {
                return placeholder;
            }
        }

        // Enhanced image loading with fallback URLs
        function loadImageWithFallback(imgElement, publicId, width = 400, height = 300) {
            const cloudName = 'deov2g1ji';
            const transformations = `w_${width},h_${height},c_fill,f_auto,q_auto,dpr_auto`;
            
            const fallbackUrls = [
                `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${publicId}`, // Root folder (correct)
                `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/classes/${publicId}`, // With classes folder (old)
                `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${publicId}.jpg`, // With .jpg extension
                `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/classes/${publicId}.jpg`, // Classes with .jpg
                `https://via.placeholder.com/${width}x${height}?text=Image+Loading+Failed` // Final fallback
            ];
            
            let currentIndex = 0;
            
            function tryNextUrl() {
                if (currentIndex < fallbackUrls.length) {
                    imgElement.src = fallbackUrls[currentIndex];
                    currentIndex++;
                } else {
                    console.log(`All image fallbacks exhausted for: ${publicId}`);
                }
            }
            
            imgElement.onerror = tryNextUrl;
            
            // Start loading the first URL
            tryNextUrl();
        }

        // Admin view mode - hide enrollment buttons for admin users
        const isAdminView = <?php echo $isAdminView ? 'true' : 'false'; ?>;
        
        // Use URL parameter for admin view (more reliable)
        const urlAdminView = <?php echo (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? 'true' : 'false'; ?>;
        
        // Use URL-based admin view detection (bypasses session issues)
        const finalAdminView = urlAdminView;
        
        let allClasses = [];
        let filteredClasses = [];
        let currentCategory = 'all';
        let currentSearch = '';
        const classesEndpoint = <?php echo json_encode(url_join(BASE_URL, 'data/get_classes_from_db.php')); ?>;
        const filterTabsContainer = document.getElementById('filterTabs');
        const filterPlaceholder = filterTabsContainer ? filterTabsContainer.querySelector('[data-filter-placeholder]') : null;
        const preferredCategoryOrder = ['bebes', 'familiares navidenos', 'familiares', 'eventos', 'gender reveal'];
        const categoryLabelOverrides = new Map([
            ['bebes', 'Bebés'],
            ['familiares navidenos', 'Familiares Navideños'],
            ['familiares', 'Familiares'],
            ['eventos', 'Eventos'],
            ['gender reveal', 'Gender Reveal'],
            ['otras sesiones', 'Otras sesiones']
        ]);

        // Load classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            loadClasses();
        });

        // Load classes from JSON
        async function loadClasses() {
            try {
                // Add cache-busting parameter to force refresh
                const cacheBuster = new Date().getTime();
                const response = await fetch(`${classesEndpoint}?v=${cacheBuster}`);
                let payload = await response.json();

                if (!Array.isArray(payload)) {
                    console.warn('Unexpected classes payload, expected an array but received:', payload);
                    payload = [];
                }

                allClasses = payload;
                buildCategoryFilters(allClasses);
                filterClasses();
            } catch (error) {
                console.error('Error loading classes:', error);
                showError();
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            if (filterTabsContainer) {
                filterTabsContainer.addEventListener('click', function(event) {
                    const button = event.target.closest('.filter-tab');
                    if (!button) {
                        return;
                    }

                    const selectedCategory = button.getAttribute('data-category');
                    if (!selectedCategory) {
                        return;
                    }

                    if (currentCategory !== selectedCategory) {
                        currentCategory = selectedCategory;
                        updateActiveTab(selectedCategory);
                        filterClasses();
                    } else if (selectedCategory === 'all') {
                        // Allow refreshing the "all" tab to reset search filtering
                        filterClasses();
                    }
                });
            }

            const searchInput = document.getElementById('searchClasses');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    currentSearch = this.value.toLowerCase();
                    filterClasses();
                });
            }
        }

        // Category helpers and filtering
        function normalizeCategory(value) {
            if (!value) return '';
            return value
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function formatCategoryLabel(value) {
            const trimmed = (value || '').toString().trim();
            if (!trimmed) {
                return 'Otras sesiones';
            }
            const replacements = new Map([
                ['bebes', 'Bebés'],
                ['bebés', 'Bebés'],
                ['bebe', 'Bebé'],
                ['bebé', 'Bebé'],
                ['navideno', 'Navideño'],
                ['navideño', 'Navideño'],
                ['navidenos', 'Navideños'],
                ['navideños', 'Navideños'],
                ['navidena', 'Navideña'],
                ['navideña', 'Navideña'],
                ['navidenas', 'Navideñas'],
                ['navideñas', 'Navideñas']
            ]);

            return trimmed
                .split(/\s+/)
                .map(word => {
                    const lower = word.toLowerCase();
                    if (replacements.has(lower)) {
                        return replacements.get(lower);
                    }
                    const capitalized = lower.charAt(0).toUpperCase() + lower.slice(1);
                    return capitalized;
                })
                .join(' ');
        }

        function extractCategoriesFromClass(classItem) {
            const raw = (classItem && typeof classItem.category === 'string') ? classItem.category : '';
            const segments = raw.split(/[,\/|]+/).map(segment => segment.trim()).filter(Boolean);
            const categories = [];
            const seen = new Set();

            if (segments.length === 0) {
                categories.push({
                    slug: 'otras sesiones',
                    label: categoryLabelOverrides.get('otras sesiones') || 'Otras sesiones'
                });
                return categories;
            }

            segments.forEach(segment => {
                const slug = normalizeCategory(segment);
                if (!slug || seen.has(slug)) {
                    return;
                }
                seen.add(slug);
                const label = categoryLabelOverrides.get(slug) || segment;
                categories.push({
                    slug,
                    label: formatCategoryLabel(label)
                });
            });

            return categories;
        }

        function getClassCategorySlugs(classItem) {
            return extractCategoriesFromClass(classItem).map(category => category.slug);
        }

        function updateActiveTab(categorySlug) {
            if (!filterTabsContainer) {
                return;
            }
            filterTabsContainer.querySelectorAll('.filter-tab').forEach(button => {
                button.classList.toggle('active', button.getAttribute('data-category') === categorySlug);
            });
        }

        function buildCategoryFilters(classes) {
            if (!filterTabsContainer) {
                return;
            }

            const categoriesMap = new Map();

            classes.forEach(classItem => {
                extractCategoriesFromClass(classItem).forEach(category => {
                    if (!categoriesMap.has(category.slug)) {
                        categoriesMap.set(category.slug, categoryLabelOverrides.get(category.slug) || category.label);
                    }
                });
            });

            if (categoriesMap.size === 0) {
                categoriesMap.set('otras sesiones', categoryLabelOverrides.get('otras sesiones'));
            }

            const previousCategory = currentCategory;
            filterTabsContainer.innerHTML = '';

            const allButton = document.createElement('button');
            allButton.type = 'button';
            allButton.className = 'filter-tab active';
            allButton.dataset.category = 'all';
            allButton.textContent = 'Todas';
            filterTabsContainer.appendChild(allButton);

            if (filterPlaceholder && filterPlaceholder.parentNode) {
                filterPlaceholder.parentNode.removeChild(filterPlaceholder);
            }

            const workingMap = new Map(categoriesMap);
            const ordered = [];

            preferredCategoryOrder.forEach(slug => {
                if (workingMap.has(slug)) {
                    ordered.push({ slug, label: workingMap.get(slug) });
                    workingMap.delete(slug);
                }
            });

            const remaining = Array.from(workingMap.entries())
                .map(([slug, label]) => ({ slug, label }))
                .sort((a, b) => a.label.localeCompare(b.label, 'es', { sensitivity: 'base' }));

            ordered.push(...remaining);

            ordered.forEach(category => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'filter-tab';
                button.dataset.category = category.slug;
                button.textContent = category.label;
                filterTabsContainer.appendChild(button);
            });

            if (previousCategory !== 'all') {
                const existing = filterTabsContainer.querySelector(`.filter-tab[data-category="${previousCategory}"]`);
                if (existing) {
                    currentCategory = previousCategory;
                    updateActiveTab(previousCategory);
                    return;
                }
            }

            currentCategory = 'all';
            updateActiveTab('all');
        }

        function filterClasses() {
            filteredClasses = allClasses.filter(classItem => {
                const categorySlugs = getClassCategorySlugs(classItem);
                const matchesCategory = currentCategory === 'all' || categorySlugs.includes(currentCategory);

                const nameText = (classItem.name || '').toLowerCase();
                const descriptionText = (classItem.description || '').toLowerCase();
                const instructorText = (classItem.instructor || '').toLowerCase();
                const categoryText = (classItem.category || '').toLowerCase();

                const matchesSearch = currentSearch === '' ||
                    nameText.includes(currentSearch) ||
                    descriptionText.includes(currentSearch) ||
                    instructorText.includes(currentSearch) ||
                    categoryText.includes(currentSearch);

                return matchesCategory && matchesSearch;
            });

            renderClasses();
        }

        // Render classes to the grid
        function renderClasses() {
            const grid = document.getElementById('classesGrid');
            const noResults = document.getElementById('noResults');

            if (!grid || !noResults) {
                return;
            }

            if (filteredClasses.length === 0) {
                grid.innerHTML = '';
                noResults.classList.remove('d-none');
                return;
            }

            noResults.classList.add('d-none');

            grid.innerHTML = filteredClasses.map(classItem => {
                const className = (classItem.name || 'Sesión sin título').trim() || 'Sesión sin título';
                const description = (classItem.description || 'Pronto compartiremos más detalles de esta sesión.').trim();
                const duration = (classItem.duration || 'Por coordinar').trim() || 'Por coordinar';
                const schedule = (classItem.schedule || 'Por coordinar').trim() || 'Por coordinar';
                const instructorName = (classItem.instructor || 'Equipo Vale V').trim() || 'Equipo Vale V';
                const priceValue = Number(classItem.price) || 0;
                const priceLabel = priceValue.toLocaleString('es-CR');
                const categories = extractCategoriesFromClass(classItem);
                const primaryCategory = categories.length ? categories[0].label : 'Sin categoría';

                const imageMarkup = classItem.image
                    ? `<img src="${getCloudinaryImageUrl(classItem.image, 400, 300)}" class="card-img-top" style="height: 200px; object-fit: cover; object-position: center;" alt="${className}">`
                    : `<div class="placeholder-class" style="height: 200px; background: linear-gradient(135deg, #000000 0%, #333333 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${className}</div>`;

                const managementLabel = instructorName === 'Vanessa Mora' ? 'Directora' : 'Fotógrafo/a';

                return `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card class-card">
                        <div class="position-relative">
                            ${imageMarkup}
                            <div class="class-category-badge">${primaryCategory}</div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${className}</h5>
                            </div>

                            <p class="card-text text-muted small mb-3">${description}</p>

                            <div class="class-schedule">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>Duración
                                        </small>
                                        <strong>${duration}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i>Horario
                                        </small>
                                        <strong>${schedule}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="instructor-info">
                                <div class="instructor-avatar">
                                    ${getInitials(instructorName)}
                                </div>
                                <div>
                                    <small class="text-muted d-block">${managementLabel}</small>
                                    <strong style="font-size: 0.9rem;">${instructorName}</strong>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="class-price">
                                    ₡${priceLabel}<small class="text-muted">/mes</small>
                                </div>
                                ${!finalAdminView ? `
                                    <button class="btn btn-primary btn-sm" onclick="enrollInClass('${classItem.id}')">
                                        <i class="fas fa-user-plus me-1"></i>Inscribirse
                                    </button>
                                ` : `
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-eye me-1"></i>Vista Administrador
                                    </span>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        }

        // Get initials for instructor avatar
        function getInitials(name) {
            return ((name || '')
                .split(/\s+/)
                .filter(Boolean)
                .map(word => word.charAt(0))
                .join('')
                .toUpperCase() || 'VV').slice(0, 2);
        }

        // Enroll in class function
        function enrollInClass(classId) {
            const classItem = allClasses.find(c => c.id === classId);
            if (!classItem) {
                showNotification('Error: Clase no encontrada', 'error');
                return;
            }
            
            // Show confirmation dialog
            showEnrollmentConfirmation(classItem);
        }

        // Show enrollment confirmation modal
        function showEnrollmentConfirmation(classItem) {
            // Parse schedules - split by comma and trim
            const schedules = classItem.schedule.split(',').map(s => s.trim()).filter(s => s.length > 0);
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'enrollmentModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-user-plus me-2"></i>Confirmar Inscripción
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <img src="${classItem.image ? getCloudinaryImageUrl(classItem.image, 400, 300) : getCloudinaryImageUrl('default-class', 400, 300)}" 
                                     alt="${classItem.name}" class="img-fluid rounded mb-3" style="max-height: 200px;">
                                <h4>${classItem.name}</h4>
                                <p class="text-muted">${classItem.description}</p>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Categoría:</strong> ${classItem.category || 'Por definir'}
                                </div>
                                <div class="col-md-6">
                                    <strong>Duración:</strong> ${classItem.duration}
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Precio:</strong> ₡${classItem.price.toLocaleString()}/mes
                                </div>
                                <div class="col-md-6">
                                    <strong>Instructor:</strong> ${classItem.instructor}
                                </div>
                            </div>
                            
                            ${schedules.length > 1 ? `
                                <div class="mb-4">
                                    <label class="form-label"><strong>Selecciona tu horario preferido:</strong></label>
                                    <div class="schedule-selection">
                                        ${schedules.map((schedule, index) => `
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="selectedSchedule" 
                                                       id="schedule${index}" value="${schedule}" ${index === 0 ? 'checked' : ''}>
                                                <label class="form-check-label" for="schedule${index}">
                                                    <div class="schedule-option p-2 border rounded">
                                                        <i class="fas fa-clock me-2 text-primary"></i>
                                                        <strong>${schedule}</strong>
                                                    </div>
                                                </label>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : `
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <strong>Horario:</strong> 
                                        <span class="badge bg-primary ms-2">
                                            <i class="fas fa-clock me-1"></i>${classItem.schedule}
                                        </span>
                                        <input type="hidden" name="selectedSchedule" value="${classItem.schedule}">
                                    </div>
                                </div>
                            `}
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Proceso de inscripción:</strong><br>
                                1. Tu solicitud será enviada a administración<br>
                                2. Recibirás una confirmación por correo<br>
                                3. La instructora Vanessa revisará y aprobará tu solicitud<br>
                                4. Te contactaremos para coordinar el primer día de clase
                            </div>
                            
                            <div class="text-center">
                                <p><strong>¿Estás seguro que deseas inscribirte en esta clase?</strong></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="processEnrollment('${classItem.id}')">
                                <i class="fas fa-check me-1"></i>Sí, inscribirme
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }

        // Process enrollment
        async function processEnrollment(classId) {
            try {
                const scheduleInputs = document.querySelectorAll('input[name="selectedSchedule"]');
                let selectedSchedule = '';

                if (scheduleInputs.length > 0) {
                    const checkedInput = document.querySelector('input[name="selectedSchedule"]:checked');
                    const fallbackInput = checkedInput || scheduleInputs[0];
                    selectedSchedule = fallbackInput && fallbackInput.value ? fallbackInput.value : '';
                }

                if (!selectedSchedule) {
                    selectedSchedule = 'Coordinar por WhatsApp';
                }
                
                // Close the modal first
                const modal = bootstrap.Modal.getInstance(document.getElementById('enrollmentModal'));
                modal.hide();
                
                // Show loading state
                showNotification('Procesando inscripción...', 'info');
                
                const response = await fetch('process_enrollment_fixed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: classId,
                        selected_schedule: selectedSchedule,
                        action: 'enroll'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                } else {
                    showNotification(result.message || 'Error al procesar la inscripción', 'error');
                }
                
            } catch (error) {
                console.error('Enrollment error:', error);
                showNotification('Error de conexión. Por favor, inténtalo nuevamente.', 'error');
            }
        }

        // Show error state
        function showError() {
            const grid = document.getElementById('classesGrid');
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4>Error al cargar las clases</h4>
                    <p>No se pudieron cargar las clases. Por favor, intenta recargar la página.</p>
                    <button class="btn btn-primary" onclick="location.reload()">Recargar</button>
                </div>
            `;
        }

        // Add notification system if not already available
        if (typeof showNotification !== 'function') {
            function showNotification(message, type = 'info') {
                // Simple notification implementation
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                `;
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                        <span>${message}</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 5000);
            }
        }
    </script>
</body>
</html>