<?php
session_start();
require_once '../config/paths.php';
require_once '../config/db_connect.php';

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

// Get social media posts
$instagram_posts = [];
$facebook_posts = [];

if ($conn) {
    // Get Instagram posts (latest 6)
    $instagram_query = "SELECT * FROM social_posts WHERE LOWER(TRIM(platform)) = 'instagram' AND (is_active IS NULL OR is_active = 1) ORDER BY post_date DESC, created_at DESC LIMIT 6";
    $instagram_result = $conn->query($instagram_query);
    if ($instagram_result && $instagram_result->num_rows > 0) {
        while ($post = $instagram_result->fetch_assoc()) {
            $instagram_posts[] = $post;
        }
    }
    
    // Get Facebook posts (latest 4)
    $facebook_query = "SELECT * FROM social_posts WHERE LOWER(TRIM(platform)) = 'facebook' AND (is_active IS NULL OR is_active = 1) ORDER BY post_date DESC, created_at DESC LIMIT 4";
    $facebook_result = $conn->query($facebook_query);
    if ($facebook_result && $facebook_result->num_rows > 0) {
        while ($post = $facebook_result->fetch_assoc()) {
            $facebook_posts[] = $post;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Redes Sociales - Vale V Photography</title>
    
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
        /* Brand color overrides for Vale V Photography */
        :root {
            --bs-primary: #000000;
            --bs-primary-rgb: 0, 0, 0;
            --bs-btn-hover-bg: #111111;
            --bs-btn-active-bg: #111111;
        }
        
        /* Force our monochrome primary color */
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
        
        /* Fix any remaining blue/sky blue elements */
        .badge {
            background-color: #111111 !important;
            color: white !important;
        }
        
        .badge-primary {
            background-color: #111111 !important;
            color: white !important;
        }
        
        .badge-secondary {
            background-color: #333333 !important;
            color: white !important;
        }
        
        .badge-info {
            background-color: #111111 !important;
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
            color: #111111 !important;
        }
        
        .bg-info {
            background-color: #111111 !important;
        }
        
        /* Icons and checks */
        .fas.fa-check,
        .fas.fa-check-circle {
            color: #111111 !important;
        }
        
        /* Any remaining blue accents */
        .text-secondary {
            color: #555555 !important;
        }
        
        /* Additional styles for social media page */
        .hero-social {
            background: linear-gradient(135deg, #000000 0%, #3a3a3a 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 76px;
        }
        
        .social-platform {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .social-platform:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .social-platform::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient-primary);
        }
        
        .social-platform.instagram::before {
            background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
        }
        
        .social-platform.facebook::before {
            background: #1877F2;
        }
        
        .social-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .instagram-icon {
            background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
        }
        
        .facebook-icon {
            background: #1877F2;
        }
        
        .whatsapp-icon {
            background: #25D366;
        }
        
        .video-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 1rem;
            border: 2px dashed #bdc3c7;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .video-placeholder:hover {
            background: linear-gradient(135deg, #d5dbdb 0%, #a6acaf 100%);
            transform: scale(1.02);
        }
        
        .video-placeholder::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-placeholder::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-45%, -50%);
            font-size: 2rem;
            color: #666;
            z-index: 2;
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .video-item {
            position: relative;
            cursor: pointer;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .video-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 20px 15px 15px;
            z-index: 2;
        }
        
        .live-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ff4757;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 3;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .stats-counter {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .social-feed {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .feed-post {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .feed-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .feed-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .feed-actions {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            margin-top: 15px;
        }
        
        .feed-action {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .feed-action:hover {
            color: var(--primary-color);
        }
        
        .hashtag {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .hashtag:hover {
            text-decoration: underline;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .gallery-item {
            position: relative;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            border: 2px dashed #bdc3c7;
        }
        
        @media (max-width: 768px) {
            .hero-social {
                padding: 80px 0 60px;
                text-align: center;
            }
            
            .video-grid {
                grid-template-columns: 1fr;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .social-platform {
                padding: 20px;
            }
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
        
        /* Disabled social links for upcoming platforms */
        .social-links a.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .social-links a.disabled:hover {
            opacity: 0.5;
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
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Sesiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/catalogo.php<?php echo $adminViewParam; ?>">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/redes-sociales.php<?php echo $adminViewParam; ?>">Redes Sociales</a>
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
    <section class="hero-social">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-4 text-on-dark">Síguenos en Redes</h1>
                    <p class="lead mb-4 text-on-dark-soft">Mantente al día con las últimas sesiones, lanzamientos especiales y momentos increíbles de nuestra comunidad creativa.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Platforms Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Instagram -->
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="social-platform instagram">
                        <div class="d-flex align-items-center mb-4">
                            <div class="social-icon instagram-icon">
                                <i class="fab fa-instagram"></i>
                            </div>
                            <div class="ms-3">
                                <h4 class="mb-1">Instagram</h4>
                                <p class="mb-0 text-muted">@valevphotography</p>
                            </div>
                        </div>
                        
                        <div id="instagramGallery">
                            <?php if (!empty($instagram_posts)): ?>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($instagram_posts as $index => $post): ?>
                                        <?php if ($index < 6): // Limit to 6 posts ?>
                                            <div class="col-4">
                                                <div class="instagram-post">
                                                    <?php if ($post['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                                             class="img-fluid rounded" 
                                                             style="width: 100%; height: 120px; object-fit: cover;"
                                                             alt="Instagram post"
                                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/300x120?text=No+Image';">
                                                    <?php else: ?>
                                                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 120px;">
                                                            <div class="text-center">
                                                                <i class="fab fa-instagram text-primary fa-2x mb-1"></i>
                                                                <small class="text-muted d-block"><?php echo date('d/m', strtotime($post['post_date'])); ?></small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (strlen($post['caption']) > 0): ?>
                                                        <small class="text-muted mt-1 d-block" style="font-size: 0.7rem; line-height: 1.2;">
                                                            <?php echo substr(htmlspecialchars($post['caption']), 0, 30) . (strlen($post['caption']) > 30 ? '...' : ''); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted">Últimas publicaciones de Instagram</small>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fab fa-instagram fa-3x text-primary mb-3"></i>
                                    <p class="text-muted mb-0">¡Síguenos en Instagram para ver nuestras últimas publicaciones!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="https://www.instagram.com/valevphotography?igsh=MXZobjc0NWtod2gyMA%3D%3D&utm_source=qr" target="_blank" class="btn btn-primary w-100">
                            <i class="fab fa-instagram me-2"></i>Seguir en Instagram
                        </a>
                    </div>
                </div>

                <!-- Facebook -->
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="social-platform facebook">
                        <div class="d-flex align-items-center mb-4">
                            <div class="social-icon facebook-icon">
                                <i class="fab fa-facebook"></i>
                            </div>
                            <div class="ms-3">
                                <h4 class="mb-1">Facebook</h4>
                                <p class="mb-0 text-muted">Vale V Photography</p>
                            </div>
                        </div>
                        
                        <div id="facebookFeed">
                            <?php if (!empty($facebook_posts)): ?>
                                <div class="facebook-posts mb-3">
                                    <?php foreach ($facebook_posts as $index => $post): ?>
                                        <?php if ($index < 3): // Limit to 3 posts for Facebook ?>
                                            <div class="facebook-post mb-3 p-3 bg-light rounded">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="fab fa-facebook text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-1">Vale V Photography</h6>
                                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['post_date'])); ?></small>
                                                        <p class="mt-2 mb-2" style="font-size: 0.9rem;">
                                                            <?php echo nl2br(htmlspecialchars(substr($post['caption'], 0, 150))); ?>
                                                            <?php echo strlen($post['caption']) > 150 ? '...' : ''; ?>
                                                        </p>
                                                        <?php if ($post['image_url']): ?>
                                                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                                                 class="img-fluid rounded mt-2" 
                                                                 style="max-height: 200px; width: 100%; object-fit: cover;"
                                                                 alt="Facebook post image"
                                                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x200?text=No+Image';">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted">Últimas publicaciones de Facebook</small>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fab fa-facebook fa-3x text-primary mb-3"></i>
                                    <p class="text-muted mb-0">¡Síguenos en Facebook para ver nuestras últimas publicaciones!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="https://www.facebook.com/share/1Czy4E7doQ/?mibextid=wwXIfr" target="_blank" class="btn btn-primary w-100">
                            <i class="fab fa-facebook me-2"></i>Seguir en Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- WhatsApp Contact Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="social-icon whatsapp-icon mx-auto mb-4">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h2 class="section-title">¿Tienes preguntas?</h2>
                    <p class="section-subtitle">Contáctanos directamente por WhatsApp para información sobre clases, horarios y promociones especiales.</p>
                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="https://wa.me/1234567890" class="btn btn-success btn-lg">
                            <i class="fab fa-whatsapp me-2"></i>Escribir por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Hashtags -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h3 class="section-title">Únete a la Comunidad</h3>
                    <p class="section-subtitle">Comparte tus momentos favoritos usando nuestros hashtags oficiales</p>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        <span class="badge bg-primary fs-6 p-3 text-white">#ValeVPhotography</span>
                        <span class="badge bg-secondary fs-6 p-3 text-white">#ValeVStudio</span>
                        <span class="badge bg-success fs-6 p-3 text-white">#CapturaConValeV</span>
                        <span class="badge bg-warning fs-6 p-3 text-white">#ValeVFamily</span>
                        <span class="badge bg-info fs-6 p-3 text-white">#ValeVMoments</span>
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
    
    <!-- Clean Social Media Page - No fake content -->
    <script>
        // Only real functionality - no fake posts or notifications
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    closeConnection($conn);
}
?>