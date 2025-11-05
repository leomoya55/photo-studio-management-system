<?php
session_start();
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
    <title>Clases - Legend Dance Academy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
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
        
        /* Level badges for classes - Use proper class selectors */
        .class-level-badge {
            background-color: #333333 !important;
            color: white !important;
        }
        
        /* Category badges */
        .class-category-badge {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white !important;
        }
        
        /* Price styling */
        .class-price {
            color: #ff6600 !important;
        }
        
        /* Instructor avatar (VM initials) */
        .instructor-avatar {
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
        
        /* Additional styles for classes page */
        .class-card {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .class-category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white !important;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .class-level-badge {
            background: #333333 !important;
            color: white !important;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .class-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6600 !important;
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
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
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
            color: #ff6600 !important;
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
            background: rgba(255, 102, 0, 0.1);
            border-color: #ff6600 !important;
        }
        
        .form-check-input:checked + .form-check-label .schedule-option {
            background: rgba(255, 102, 0, 0.1);
            border-color: #ff6600 !important;
            color: #ff6600;
        }
        
        .form-check-input:checked + .form-check-label .schedule-option .fas {
            color: #ff6600 !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php<?php echo $adminViewParam; ?>">
                <span class="brand-text">Legend</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php<?php echo $adminViewParam; ?>#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="clases.php<?php echo $adminViewParam; ?>">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="horarios.php<?php echo $adminViewParam; ?>">Horarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.php<?php echo $adminViewParam; ?>">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redes-sociales.php<?php echo $adminViewParam; ?>">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ubicacion.php<?php echo $adminViewParam; ?>">Ubicación</a>
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
                                    <li><a class="dropdown-item" href="../admin/admin.php"><i class="fas fa-cog me-2"></i>Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-outline-primary px-3 me-2" href="register.php">Registrarse</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn-primary text-white px-3" href="login.php">Iniciar Sesión</a>
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
    <section class="hero-classes">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Nuestras Clases</h1>
                    <p class="lead mb-4">Descubre la clase perfecta para ti. Desde ballet clásico hasta hip hop, pilates y mucho más. En Legend tenemos opciones para todos los niveles y edades.</p>
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
                <div class="col-12">
                    <div class="filter-tabs d-flex justify-content-center mb-4">
                        <button class="filter-tab active" data-category="all">Todas</button>
                        <button class="filter-tab" data-category="Contemporaneo">Contemporáneo</button>
                        <button class="filter-tab" data-category="Urbano">Urbano</button>
                        <button class="filter-tab" data-category="Latino">Latino</button>
                        <button class="filter-tab" data-category="Fitness">Fitness</button>
                        <button class="filter-tab" data-category="Infantil">Infantil</button>
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
                    <h2 class="section-title">¿Listo para comenzar?</h2>
                    <p class="section-subtitle">Únete a Legend Dance Academy y descubre tu pasión por la danza. Ofrecemos clases de prueba gratuitas para nuevos estudiantes.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="#" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus me-2"></i>Registrarse Ahora
                        </a>
                        <a href="ubicacion.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-map-marker-alt me-2"></i>Visítanos
                        </a>
                        <a href="redes-sociales.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-play me-2"></i>Ver Videos
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

    <!-- Simple Call to Action -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1">¿Encontraste tu clase ideal?</h5>
                    <p class="text-muted mb-0">Consulta horarios disponibles y reserva tu lugar.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="horarios.php<?php echo $adminViewParam; ?>" class="btn btn-primary">
                        <i class="fas fa-clock me-2"></i>Ver Horarios
                    </a>
                </div>
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
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.tiktok.com/@studiolegend.cr" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/50684118339" target="_blank"><i class="fab fa-whatsapp"></i></a>
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
        function getCloudinaryImageUrl(publicIdOrUrl, width = 400, height = 300) {
            if (!publicIdOrUrl) {
                return `https://via.placeholder.com/${width}x${height}?text=No+Image`;
            }
            // If it's already a full URL (e.g., secure_url), just return it
            if (typeof publicIdOrUrl === 'string' && /^https?:\/\//i.test(publicIdOrUrl)) {
                return publicIdOrUrl;
            }
            const cloudName = 'deov2g1ji';
            const transformations = `w_${width},h_${height},c_fill,f_auto,q_auto,dpr_auto`;
            
            // Images are in root with unique IDs, no folder prefix needed
            const encodedId = encodeURIComponent(publicIdOrUrl);
            return `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${encodedId}`;
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

        // Load classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses();
            setupEventListeners();
        });

        // Load classes from JSON
        async function loadClasses() {
            try {
                // Add cache-busting parameter to force refresh
                const cacheBuster = new Date().getTime();
                const response = await fetch(`../data/get_classes_from_db.php?v=${cacheBuster}`);
                allClasses = await response.json();
                filteredClasses = [...allClasses];
                renderClasses();
            } catch (error) {
                console.error('Error loading classes:', error);
                showError();
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Category filters
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active tab
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter classes
                    currentCategory = this.getAttribute('data-category');
                    filterClasses();
                });
            });

            // Search functionality
            const searchInput = document.getElementById('searchClasses');
            searchInput.addEventListener('input', function() {
                currentSearch = this.value.toLowerCase();
                filterClasses();
            });
        }

        // Filter classes based on category and search
        function normalizeCategory(value) {
            if (!value) return '';
            return value
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, ''); // remove accents
        }

        function filterClasses() {
            filteredClasses = allClasses.filter(classItem => {
                const matchesCategory = currentCategory === 'all' || normalizeCategory(classItem.category) === normalizeCategory(currentCategory);
                const matchesSearch = currentSearch === '' || 
                    classItem.name.toLowerCase().includes(currentSearch) ||
                    classItem.description.toLowerCase().includes(currentSearch) ||
                    classItem.instructor.toLowerCase().includes(currentSearch) ||
                    classItem.level.toLowerCase().includes(currentSearch);
                
                return matchesCategory && matchesSearch;
            });
            
            renderClasses();
        }

        // Render classes to the grid
        function renderClasses() {
            const grid = document.getElementById('classesGrid');
            const noResults = document.getElementById('noResults');
            
            if (filteredClasses.length === 0) {
                grid.innerHTML = '';
                noResults.classList.remove('d-none');
                return;
            }
            
            noResults.classList.add('d-none');
            
            grid.innerHTML = filteredClasses.map(classItem => `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card class-card">
                        <div class="position-relative">
                            ${classItem.image ? 
                                `<img src="${getCloudinaryImageUrl(classItem.image, 400, 300)}" class="card-img-top" style="height: 200px; object-fit: cover; object-position: center;" alt="${classItem.name}">` : 
                                `<div class="placeholder-class" style="height: 200px; background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${classItem.name}</div>`
                            }
                            <div class="class-category-badge">${classItem.category}</div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${classItem.name}</h5>
                                <span class="class-level-badge">${classItem.level}</span>
                            </div>
                            
                            <p class="card-text text-muted small mb-3">${classItem.description}</p>
                            
                            <div class="class-schedule">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>Duración
                                        </small>
                                        <strong>${classItem.duration}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i>Horario
                                        </small>
                                        <strong>${classItem.schedule}</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="benefits-list mt-3">
                                <small class="text-muted d-block mb-2">Beneficios:</small>
                                ${classItem.benefits.slice(0, 3).map(benefit => `
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <small>${benefit}</small>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="instructor-info">
                                <div class="instructor-avatar">
                                    ${getInitials(classItem.instructor)}
                                </div>
                                <div>
                                    <small class="text-muted d-block">${classItem.instructor === 'Vanessa Mora' ? 'Directora' : 'Instructor'}</small>
                                    <strong style="font-size: 0.9rem;">${classItem.instructor}</strong>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="class-price">
                                    ₡${classItem.price.toLocaleString()}<small class="text-muted">/mes</small>
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
            `).join('');
        }

        // Get initials for instructor avatar
        function getInitials(name) {
            return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
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
                                    <strong>Nivel:</strong> ${classItem.level}
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
                // Get selected schedule
                const selectedScheduleInput = document.querySelector('input[name="selectedSchedule"]:checked') || 
                                            document.querySelector('input[name="selectedSchedule"]');
                const selectedSchedule = selectedScheduleInput ? selectedScheduleInput.value : '';
                
                if (!selectedSchedule) {
                    showNotification('Por favor selecciona un horario', 'error');
                    return;
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