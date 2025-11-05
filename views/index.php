<?php
session_start();
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../config/image_helpers.php');

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legend - Academia de Danza</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - MUST be after Bootstrap to override -->
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
        
        .hero-title {
            color: #000000 !important;
            font-weight: 700;
        }
        
        .hero-subtitle {
            color: #ff6600 !important;
            font-weight: 600;
        }
        
        .section-title {
            color: #000000 !important;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link:hover {
            color: #ff6600 !important;
        }
        
        .logo-contrast-bg {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 2rem;
            border-radius: 20px;
            border: 3px solid #ff6600;
            box-shadow: 0 15px 35px rgba(255, 102, 0, 0.15);
        }
        
        .hero-section {
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
        }
        
        body {
            background-color: #fafafa;
        }
        
        /* Social links in footer */
        .social-links a {
            background-color: #ff6600 !important;
            color: white !important;
        }
        
        .social-links a:hover {
            background-color: #e55a00 !important;
            color: white !important;
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
    </style>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php#inicio">
                <span class="brand-text">Legend</span>
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
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/horarios.php<?php echo $adminViewParam; ?>">Horarios</a>
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
            <a href="<?php echo ADMIN_URL; ?>/admin.php" class="btn btn-light btn-sm ms-3">
                <i class="fas fa-arrow-left me-1"></i>Volver al Panel Admin
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section - Inicio -->
    <section id="inicio" class="hero-section">
        <div class="hero-content">
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6">
                        <h1 class="hero-title">Bienvenidos a <span class="text-primary">Legend</span></h1>
                        <p class="hero-subtitle">Donde cada movimiento cuenta una historia</p>
                        <p class="hero-description">Descubre tu pasión por la danza en nuestra academia. Ofrecemos clases para todos los niveles y edades en un ambiente profesional y acogedor.</p>
                        <div class="hero-buttons">
                            <a href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>" class="btn btn-primary btn-lg me-3">Ver Clases</a>
                            <a href="<?php echo VIEWS_URL; ?>/register.php" class="btn btn-outline-primary btn-lg">Únete Ahora</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-image logo-contrast-bg">
                            <img src="<?php echo getImageUrl('LegendCR_vjqteo', 0, 0); ?>" alt="Academia Legend" class="img-fluid rounded shadow">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Director Section - Moved to top for immediate credibility -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 pe-lg-4">
                    <div class="director-image me-3">
                        <img src="<?php echo getProfileImageUrl('vanessainicio_hzvwrl', 500, 600); ?>" alt="Vanessa Mora - Directora y Fundadora de Legend Dance Academy" class="img-fluid rounded-4 shadow-lg">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="director-info">
                        <span class="badge bg-primary mb-3">Directora y Fundadora</span>
                        <h2 class="mb-3">Vanessa Mora</h2>
                        <p class="lead mb-4">
                            Profesora de Filosofía y Administración Pública de la Universidad de Costa Rica y la UTAC.
                        </p>
                        <p class="mb-4">
                            Bailarina profesional con más de 10 años de experiencia. Representante como bailarina y coreógrafa del país en países como USA, México y Honduras en campeonatos de Hip Hop, danza urbana y latina. Actualmente directora de la Academia Legend en Zapote y gestora cultural del Ministerio de Cultura y el Teatro Nacional. Con experiencia de 7 años como juez de eventos como el FEA.
                        </p>
                        <div class="director-stats row">
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">10+</h4>
                                <small class="text-muted">Años de Experiencia</small>
                            </div>
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">100+</h4>
                                <small class="text-muted">Estudiantes</small>
                            </div>
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">14</h4>
                                <small class="text-muted">Disciplinas</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-muted mb-2"><em>"El baile es más que movimiento, es expresión del alma"</em></p>
                            <strong>- Vanessa Mora</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Clases Section -->
    <section id="clases" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Clases Destacadas</h2>
                <p class="section-subtitle">Descubre algunas de nuestras clases más populares</p>
            </div>
            <div class="row" id="featuredClassesGrid">
                <!-- Featured classes will be loaded here -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando clases destacadas...</span>
                    </div>
                    <p class="mt-3">Cargando clases destacadas...</p>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-th-large me-2"></i>Ver Todas las Clases
                    </a>
                    <p class="mt-3 text-muted">Explora nuestra amplia variedad: Ballet, Hip Hop, Contemporáneo, Pilates, Salsa, Flamenco y mucho más.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="nosotros" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Acerca de Legend</h2>
                <p class="section-subtitle">Más que una academia, una familia</p>
                <p class="mb-4">
                    Legend es un proyecto artístico de Costa Rica que busca dar una formación integral a bailarines. Llevamos más de cuatro años de proveer herramientas formativas técnicas, teóricas y prácticas a bailarines y artistas desde tempranas edades hasta adultos mayores con metodologías modernas y efectivas.
                </p>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h3>Nuestra Misión</h3>
                    <p>
                        Promover el desarrollo integral humano de nuestro estudiantado, mediante herramientas pedagógicas efectivas y personal altamente calificado.
                    </p>
                    <h3>Nuestra Visión</h3>
                    <p>
                        Ser referentes en Costa Rica y el mundo de la creación de espacios creativos, seguros e innovadores para el desarrollo de habilidades, competencias y talentos de la expresión artística, deportiva de nuestro estudiantado.
                    </p>
                </div>
                <div class="col-lg-6 mb-4">
                    <h3>Nuestra Ética y Valores</h3>
                    <p>
                        Seguimos preceptos acorde a una ética de valores, desligada de todo tipo de discriminación, violencia, bullying, creando espacios seguros y confiables para el estudiantado.
                    </p>
                    <h3>Métodos o Herramientas Empleadas</h3>
                    <ul>
                        <li>Método neuroeducativo</li>
                        <li>Blended Learning</li>
                        <li>Herramientas Montessori</li>
                        <li>Pedagogía Waldorf</li>
                        <li>Pedagogía Reggio Emilia</li>
                        <li>Técnica Graham</li>
                        <li>Filosofía para niñ@s</li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="brand-text">Legend</h5>
                    <p>Academia de danza donde cada movimiento cuenta una historia.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=100068508182444" class="text-white me-3" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/legendvm.cr/" class="text-white me-3" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@studiolegend.cr" class="text-white me-3" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/50684118339" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                    <p class="mt-2">&copy; 2025 Legend. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    <!-- User Alert System (only for logged-in users) -->
    <?php if ($isLoggedIn && $userRole !== 'admin'): ?>
    <script src="<?php echo ASSETS_URL; ?>/js/user-alerts.js"></script>
    <?php endif; ?>
    
    <!-- Homepage specific JS -->
    <script>
        // Admin view detection for JavaScript
        const adminViewParam = <?php echo (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? '"?admin_view=1"' : '""'; ?>;
        
        // Cloudinary helper function for featured classes
        function getCloudinaryImageUrl(publicId, width = 400, height = 300) {
            if (!publicId) {
                return `https://via.placeholder.com/${width}x${height}?text=No+Image`;
            }
            const cloudName = 'deov2g1ji';
            const transformations = `w_${width},h_${height},c_fill,f_auto,q_auto,dpr_auto`;
            
            // Images are in root with unique IDs
            return `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${publicId}`;
        }
        
        // Load featured classes on page load
        // Global variables
        let featuredClasses = [];
        let allClasses = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedClasses();
        });

        // Load featured classes from JSON
        async function loadFeaturedClasses() {
            try {
                const cacheBuster = new Date().getTime();
                const response = await fetch(`../data/classes.json?v=${cacheBuster}`);
                allClasses = await response.json();
                featuredClasses = allClasses.filter(classItem => classItem.featured);
                
                renderFeaturedClasses(featuredClasses);
            } catch (error) {
                console.error('Error loading featured classes:', error);
                showFeaturedClassesError();
            }
        }

        // Render featured classes
        function renderFeaturedClasses(classes) {
            const grid = document.getElementById('featuredClassesGrid');
            
            if (classes.length === 0) {
                grid.innerHTML = `
                    <div class="col-12 text-center">
                        <p class="text-muted">No hay clases destacadas disponibles en este momento.</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = classes.slice(0, 6).map(classItem => `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm card-hover">
                        ${classItem.image ? 
                            `<img src="${getCloudinaryImageUrl(classItem.image, 400, 250)}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${classItem.name}">` : 
                            `<div class="placeholder-class" style="height: 200px; background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${classItem.name}</div>`
                        }
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${classItem.name}</h5>
                                <span class="badge bg-secondary">${classItem.level}</span>
                            </div>
                            
                            <p class="card-text text-muted flex-grow-1">${classItem.description}</p>
                            
                            <div class="class-info mb-3">
                                <small class="d-block text-muted">
                                    <i class="fas fa-clock me-1"></i>${classItem.duration} • 
                                    <i class="fas fa-users me-1"></i>${classItem.capacity} personas
                                </small>
                                <small class="d-block text-muted">
                                    <i class="fas fa-calendar me-1"></i>${classItem.schedule}
                                </small>
                                <small class="d-block text-muted">
                                    <i class="fas fa-user me-1"></i>${classItem.instructor}
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">
                                    ₡${classItem.price.toLocaleString()}<small class="text-muted">/mes</small>
                                </span>
                                <button class="btn btn-outline-primary btn-sm" onclick="showClassDetails('${classItem.id}')">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Show error state for featured classes
        function showFeaturedClassesError() {
            const grid = document.getElementById('featuredClassesGrid');
            grid.innerHTML = `
                <div class="col-12 text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                    <p class="text-muted">Error al cargar las clases destacadas.</p>
                    <button class="btn btn-primary" onclick="loadFeaturedClasses()">Reintentar</button>
                </div>
            `;
        }

        // Show class details modal
        function showClassDetails(classId) {
            // Find the class from the featuredClasses array
            const classItem = featuredClasses.find(c => c.id === classId);
            if (!classItem) {
                alert('Error: Clase no encontrada');
                return;
            }
            
            showClassDetailsModal(classItem);
        }

        // Show class details modal
        function showClassDetailsModal(classItem) {
            // Parse schedules - split by comma and trim
            const schedules = classItem.schedule.split(',').map(s => s.trim()).filter(s => s.length > 0);
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'classDetailsModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-info-circle me-2"></i>Detalles de la Clase
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
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-signal text-primary me-2"></i>
                                        <strong>Nivel:</strong> <span class="ms-2 badge bg-secondary">${classItem.level}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <strong>Duración:</strong> <span class="ms-2">${classItem.duration}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tag text-primary me-2"></i>
                                        <strong>Precio:</strong> <span class="ms-2 text-success fw-bold">₡${classItem.price.toLocaleString()}/mes</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <strong>Instructor:</strong> <span class="ms-2">${classItem.instructor}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        <strong>Capacidad:</strong> <span class="ms-2">${classItem.capacity} personas</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-child text-primary me-2"></i>
                                        <strong>Edad:</strong> <span class="ms-2">${classItem.ageGroup || 'Todos'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <strong>Horarios disponibles:</strong>
                                </div>
                                <div class="schedule-display">
                                    ${schedules.map(schedule => `
                                        <span class="badge bg-primary me-2 mb-2 p-2">
                                            <i class="fas fa-clock me-1"></i>${schedule}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                            
                            ${classItem.benefits && classItem.benefits.length > 0 ? `
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-star text-primary me-2"></i>
                                        <strong>Beneficios de esta clase:</strong>
                                    </div>
                                    <div class="row">
                                        ${classItem.benefits.map(benefit => `
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <small>${benefit}</small>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>¿Quieres inscribirte?</strong><br>
                                Visita nuestra página de clases para ver todos los horarios disponibles y completar tu inscripción.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cerrar
                            </button>
                            <a href="clases.php${adminViewParam}" class="btn btn-primary">
                                <i class="fas fa-th-large me-1"></i>Ver Todas las Clases
                            </a>
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

        // Helper function to generate Cloudinary image URLs (matching clases.php)
        function getCloudinaryImageUrl(publicId, width = 400, height = 300) {
            const cloudName = 'deov2g1ji';
            const transformations = `w_${width},h_${height},c_fill,f_auto,q_auto`;
            return `https://res.cloudinary.com/${cloudName}/image/upload/${transformations}/${publicId}`;
        }

    </script>
</body>
</html>