<?php
session_start();
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../config/image_helpers.php');

// Determine session state for navbar rendering
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $userRole = $_SESSION['role'] ?? 'customer';
}

// Preserve admin preview mode when applicable
$adminViewParam = (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? '?admin_view=1' : '';

if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && (!$isLoggedIn || $userRole !== 'admin')) {
    header('Location: ' . VIEWS_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vale V Photography - Estudio Creativo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - MUST be after Bootstrap to override -->
    <style>
        /* Vale V Photography monochrome accents */
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
        
        .hero-title {
            color: #3d2b28 !important;
            font-weight: 700;
        }
        
        .hero-highlight {
            color: var(--brand-color, #000000) !important;
            text-shadow: none;
        }
        
        .hero-subtitle {
            color: var(--brand-color, #000000) !important;
            font-weight: 600;
            text-shadow: none;
        }
        
        .section-title {
            color: #3d2b28 !important;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link:hover {
            color: #111111 !important;
        }
        
        .logo-contrast-bg {
            background: linear-gradient(135deg, #fff7f1 0%, #ffe8d6 100%);
            padding: 1.25rem;
            border-radius: 20px;
            border: 3px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 360px;
        }

        .logo-contrast-bg img {
            width: 100%;
            max-width: 460px;
            height: 100%;
            object-fit: contain;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #fff9f2 0%, #ffeede 100%);
        }
        
        body {
            background-color: #fff9f2;
        }
        
        /* Social links in footer */
        .social-links a {
            background-color: transparent !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
        }
        
        .social-links a:hover {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.85) !important;
            color: #ffffff !important;
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
    </style>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>#inicio">
                <span class="brand-text">Vale V Photography</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Sesiones</a>
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
                            <a class="nav-link btn btn-outline-primary" href="<?php echo VIEWS_URL; ?>/register.php" role="button">Registrarse</a>
                        </li>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-primary" href="<?php echo VIEWS_URL; ?>/login.php" role="button">Iniciar Sesión</a>
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
            <strong>Vista de Administrador</strong> - Valeria, estás viendo el sitio web sin opciones de inscripción
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
                        <h1 class="hero-title">Bienvenidos a <span class="hero-highlight">Vale V Photography</span></h1>
                        <p class="hero-subtitle">Donde cada imagen cuenta una historia</p>
                        <p class="hero-description">Creamos experiencias fotográficas personalizadas para retratos, marcas y eventos, cuidando cada detalle para reflejar tu esencia.</p>
                        <div class="hero-buttons">
                            <a href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>" class="btn btn-primary btn-lg me-3">Ver Sesiones</a>
                            <a href="<?php echo VIEWS_URL; ?>/portafolio.php" class="btn btn-outline-primary btn-lg">Ver Portafolio</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-image logo-contrast-bg">
                               <img src="<?php echo getImageUrl('inicio/Logo_Photo_zwmkyh', 900, 0); ?>" alt="Vale V Photography" class="img-fluid rounded shadow" fetchpriority="high"
                                   onerror="this.onerror=null; this.src='<?php echo getImageUrl('Logo_Photo_zwmkyh', 900, 0); ?>';">
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
                    <img src="<?php echo getProfileImageUrl('inicio/PHOTO-2025-11-23-21-14-55_nfhhxp', 500, 600); ?>" alt="Valeria Vega - Directora y Fundadora de Vale V Photography" class="img-fluid rounded-4 shadow-lg"
                        onerror="this.onerror=null; this.src='<?php echo getProfileImageUrl('PHOTO-2025-11-23-21-14-55_nfhhxp', 500, 600); ?>';">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="director-info">
                        <span class="badge bg-primary mb-3">Directora y Fundadora</span>
                        <h2 class="mb-3">Valeria Vega</h2>
                        <p class="lead mb-4">
                            Fundadora y directora creativa de Vale V Photography.
                        </p>
                        <p class="mb-4">
                            Fotógrafa profesional con más de 10 años de experiencia documentando historias para artistas, emprendedores y familias. Lidera el estudio Vale V Photography en Zapote, integrando dirección artística, producción y edición de alto nivel para crear imágenes con propósito.
                        </p>
                        <div class="director-stats row justify-content-center">
                            <div class="col-12 col-md-6 text-center">
                                <h4 class="mb-1 text-dark">10+</h4>
                                <small class="text-dark">Años de Experiencia</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-muted mb-2"><em>"La fotografía captura la esencia de cada historia"</em></p>
                            <strong>- Valeria Vega</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sesiones Section -->
    <section id="clases" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Sesiones Destacadas</h2>
                <p class="section-subtitle">Descubre algunas de nuestras sesiones más solicitadas</p>
            </div>
            <div class="row" id="featuredClassesGrid">
                <!-- Featured classes will be loaded here -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando sesiones destacadas...</span>
                    </div>
                    <p class="mt-3">Cargando sesiones destacadas...</p>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-th-large me-2"></i>Ver Todas las Sesiones
                    </a>
                    <p class="mt-3 text-muted">Explora nuestras sesiones personalizadas: branding, lifestyle, retratos artísticos, eventos y mucho más.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="nosotros" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Acerca de Vale V Photography</h2>
                <p class="section-subtitle">Más que un estudio, una experiencia</p>
                <p class="mb-4">
                    Vale V Photography es un estudio creativo en Costa Rica dedicado a capturar historias auténticas. Desde sesiones editoriales hasta proyectos corporativos, combinamos dirección artística, producción y acompañamiento integral para generar imágenes memorables.
                </p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="bg-white shadow-sm rounded-4 p-4 text-center">
                        <h3 class="mb-3">Romanos 15:13 (NTV)</h3>
                        <p class="fs-4 mb-0">"El Dios de esperanza los llene de alegria y paz"</p>
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
                        <a href="https://wa.me/50686764740" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                    <p class="mt-2">&copy; 2025 Vale V Photography. Todos los derechos reservados.</p>
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
        
        // Load featured classes on page load
        // Global variables
        let featuredClasses = [];
        let allClasses = [];
        const featuredClassesEndpoint = <?php echo json_encode(url_join(BASE_URL, 'data/get_classes_from_db.php')); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedClasses();
        });

        // Load featured classes from JSON
        async function loadFeaturedClasses() {
            try {
                const cacheBuster = new Date().getTime();
                const apiUrl = `${featuredClassesEndpoint}?v=${cacheBuster}`;
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const payload = await response.json();
                if (Array.isArray(payload)) {
                    allClasses = payload;
                } else if (payload && payload.error) {
                    throw new Error(payload.message || 'No se pudieron cargar las sesiones');
                } else {
                    allClasses = [];
                }
                featuredClasses = allClasses.filter(classItem => {
                    if (!classItem) {
                        return false;
                    }
                    const flag = classItem.featured;
                    return Boolean(flag) || Number(flag) === 1;
                }).map((item, index) => ({
                    ...item,
                    id: item && item.id ? item.id : (item && item.slug ? item.slug : `session-${index}`)
                }));

                if (featuredClasses.length === 0 && Array.isArray(allClasses) && allClasses.length > 0) {
                    featuredClasses = allClasses.slice(0, 6).map((item, index) => ({
                        ...item,
                        id: item && item.id ? item.id : (item && item.slug ? item.slug : `session-${index}`)
                    }));
                }

                renderFeaturedClasses(featuredClasses);
            } catch (error) {
                console.error('Error loading featured sessions:', error);
                showFeaturedClassesError();
            }
        }

        // Render featured classes
        function renderFeaturedClasses(classes) {
            const grid = document.getElementById('featuredClassesGrid');
            if (!grid) {
                return;
            }

            if (!Array.isArray(classes) || classes.length === 0) {
                grid.innerHTML = `
                    <div class="col-12 text-center">
                        <p class="text-muted">No hay sesiones destacadas disponibles en este momento.</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = classes.slice(0, 6).map((classItem, index) => {
                const name = classItem?.name || 'Sesión fotográfica';
                const description = classItem?.description || 'Agenda tu experiencia personalizada con Vale V Photography.';
                const levelValue = (classItem?.level || '').toString().trim();
                const categoryValueRaw = (classItem?.category || '').toString().trim();
                const formatLabel = (label) => {
                    if (!label) {
                        return '';
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
                    return label
                        .split(/\s+/)
                        .map(word => {
                            const lower = word.toLowerCase();
                            if (replacements.has(lower)) {
                                return replacements.get(lower);
                            }
                            return lower.charAt(0).toUpperCase() + lower.slice(1);
                        })
                        .join(' ');
                };
                const categoryValue = formatLabel(categoryValueRaw);
                const showLevel = levelValue && levelValue.toLowerCase() !== 'principiante';
                const badgeLabel = formatLabel(showLevel ? levelValue : categoryValue);
                const badgeMarkup = badgeLabel ? `<span class="badge bg-secondary">${badgeLabel}</span>` : '';
                const priceValue = Number(classItem?.price ?? 0);
                const priceLabel = priceValue > 0 ? `₡${priceValue.toLocaleString('es-CR')}` : 'Cotización personalizada';
                const duration = classItem?.duration ? `<i class="fas fa-clock me-1"></i>${classItem.duration}` : '';
                const capacity = classItem?.capacity ? `<i class="fas fa-users me-1"></i>${classItem.capacity} personas` : '';
                const schedule = classItem?.schedule ? `<i class="fas fa-calendar me-1"></i>${classItem.schedule}` : '';
                const instructor = classItem?.instructor ? `<i class="fas fa-user me-1"></i>${classItem.instructor}` : '';
                const infoLines = [
                    duration && capacity ? `${duration} • ${capacity}` : (duration || capacity),
                    schedule,
                    instructor
                ].filter(Boolean).map(line => `<small class="d-block text-muted">${line}</small>`).join('');
                const classId = String(classItem?.id ?? `session-${index}`);
                const imageMarkup = classItem?.image
                    ? `<img src="${getCloudinaryImageUrl(classItem.image, 400, 250)}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${name}">`
                    : `<div class="placeholder-class" style="height: 200px; background: linear-gradient(135deg, #000000 0%, #333333 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${name}</div>`;

                return `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm card-hover">
                        ${imageMarkup}
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${name}</h5>
                                ${badgeMarkup}
                            </div>
                            <p class="card-text text-muted flex-grow-1">${description}</p>
                            <div class="class-info mb-3">
                                ${infoLines || '<small class="d-block text-muted">Agenda personalizada</small>'}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">${priceLabel}</span>
                                <button class="btn btn-outline-primary btn-sm" onclick="showClassDetails('${classId.replace(/'/g, "\\'")}')">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        // Show error state for featured classes
        function showFeaturedClassesError() {
            const grid = document.getElementById('featuredClassesGrid');
            grid.innerHTML = `
                <div class="col-12 text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                    <p class="text-muted">Error al cargar las sesiones destacadas.</p>
                    <button class="btn btn-primary" onclick="loadFeaturedClasses()">Reintentar</button>
                </div>
            `;
        }

        // Show class details modal
        function showClassDetails(classId) {
            // Find the class from the featuredClasses array
            const classItem = featuredClasses.find(c => c.id === classId);
            if (!classItem) {
                alert('Error: Sesión no encontrada');
                return;
            }
            
            showClassDetailsModal(classItem);
        }

        // Show class details modal
        function showClassDetailsModal(classItem) {
            const scheduleSource = classItem?.schedule ?? '';
            const schedules = Array.isArray(scheduleSource)
                ? scheduleSource
                : String(scheduleSource).split(',').map(s => s.trim()).filter(Boolean);
            const priceValue = Number(classItem?.price ?? 0);
            const priceLabel = priceValue > 0 ? `₡${priceValue.toLocaleString('es-CR')}` : 'Cotización personalizada';
            const levelLabel = classItem?.level || classItem?.category || 'Personalizada';
            const durationLabel = classItem?.duration || 'Sesión adaptable';
            const instructorLabel = classItem?.instructor || 'Equipo Vale V Photography';
            const capacityLabel = classItem?.capacity ? `${classItem.capacity} personas` : 'Cupos limitados';
            const ageGroupLabel = classItem?.ageGroup || classItem?.age_group || 'Todas las edades';
            const benefitsList = Array.isArray(classItem?.benefits) ? classItem.benefits.filter(Boolean) : [];

            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'classDetailsModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-info-circle me-2"></i>Detalles de la Sesión
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <img src="${classItem?.image ? getCloudinaryImageUrl(classItem.image, 400, 300) : getCloudinaryImageUrl('default-class', 400, 300)}" 
                                     alt="${classItem?.name || 'Sesión fotográfica'}" class="img-fluid rounded mb-3" style="max-height: 200px;">
                                <h4>${classItem?.name || 'Sesión fotográfica'}</h4>
                                <p class="text-muted">${classItem?.description || 'Creamos experiencias fotográficas hechas a tu medida.'}</p>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-signal text-primary me-2"></i>
                                        <strong>Estilo:</strong> <span class="ms-2 badge bg-secondary">${levelLabel}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <strong>Duración estimada:</strong> <span class="ms-2">${durationLabel}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tag text-primary me-2"></i>
                                        <strong>Inversión:</strong> <span class="ms-2 text-success fw-bold">${priceLabel}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <strong>Fotógrafa:</strong> <span class="ms-2">${instructorLabel}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        <strong>Cupos sugeridos:</strong> <span class="ms-2">${capacityLabel}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-child text-primary me-2"></i>
                                        <strong>Ideal para:</strong> <span class="ms-2">${ageGroupLabel}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <strong>Agenda sugerida:</strong>
                                </div>
                                <div class="schedule-display">
                                    ${schedules.length ? schedules.map(schedule => `
                                        <span class="badge bg-primary me-2 mb-2 p-2">
                                            <i class="fas fa-clock me-1"></i>${schedule}
                                        </span>
                                    `).join('') : '<span class="badge bg-secondary p-2">Coordinamos la agenda contigo</span>'}
                                </div>
                            </div>
                            
                            ${benefitsList.length ? `
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-star text-primary me-2"></i>
                                        <strong>Beneficios de esta sesión:</strong>
                                    </div>
                                    <div class="row">
                                        ${benefitsList.map(benefit => `
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
                                <strong>¿Quieres reservar?</strong><br>
                                Visita nuestra página de sesiones para coordinar tu experiencia y asegurar la fecha ideal.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cerrar
                            </button>
                            <a href="clases.php${adminViewParam}" class="btn btn-primary">
                                <i class="fas fa-th-large me-1"></i>Ver Todas las Sesiones
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

    </script>
</body>
</html>