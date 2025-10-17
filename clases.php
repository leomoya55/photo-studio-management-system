<?php
require_once 'session_manager.php';
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
        
        /* Schedule Section Styles */
        .schedule-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .day-schedule {
            border-left: 4px solid #ff6600;
            padding-left: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .day-header h4 {
            background: linear-gradient(135deg, #ff6600, #ff8533);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        
        .classes-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .class-item:hover {
            background: linear-gradient(135deg, rgba(255, 102, 0, 0.05), rgba(255, 133, 51, 0.05));
            border-color: #ff6600;
            transform: translateX(5px);
        }
        
        .class-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .class-time {
            font-weight: 700;
            color: #ff6600;
            background: rgba(255, 102, 0, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .schedule-container {
                padding: 1.5rem;
            }
            
            .class-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .class-time {
                align-self: flex-end;
            }
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
                        <a class="nav-link active" href="clases.html">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.html">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redes-sociales.html">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ubicacion.html">Ubicación</a>
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
                                    <li><a class="dropdown-item" href="admin.php"><i class="fas fa-cog me-2"></i>Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Mi Dashboard</a></li>
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

    <!-- Horarios Section -->
    <section id="horarios" class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-5">
                        <h2 class="display-5 fw-bold text-dark mb-3">
                            <i class="fas fa-calendar-alt text-primary me-3"></i>
                            Horarios de Clases
                        </h2>
                        <p class="lead text-muted">Consulta nuestros horarios semanales y encuentra el momento perfecto para tu clase</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="schedule-container">
                        
                        <!-- Martes -->
                        <div class="day-schedule mb-4">
                            <div class="day-header">
                                <h4 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>MARTES
                                </h4>
                            </div>
                            <div class="classes-list">
                                <div class="class-item">
                                    <span class="class-name">Pilates/Funcional</span>
                                    <span class="class-time">3:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Kids/Técnica</span>
                                    <span class="class-time">6:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Kids/Coreo</span>
                                    <span class="class-time">7:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Cardio/Step</span>
                                    <span class="class-time">8:00 P.M</span>
                                </div>
                            </div>
                        </div>

                        <!-- Miércoles -->
                        <div class="day-schedule mb-4">
                            <div class="day-header">
                                <h4 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>MIÉRCOLES
                                </h4>
                            </div>
                            <div class="classes-list">
                                <div class="class-item">
                                    <span class="class-name">Pilates/Funcional</span>
                                    <span class="class-time">10:00 A.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Pilates/Funcional</span>
                                    <span class="class-time">3:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Latino</span>
                                    <span class="class-time">6:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Pilates/Funcional</span>
                                    <span class="class-time">7:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Cardio Dance</span>
                                    <span class="class-time">8:00 P.M</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jueves -->
                        <div class="day-schedule mb-4">
                            <div class="day-header">
                                <h4 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>JUEVES
                                </h4>
                            </div>
                            <div class="classes-list">
                                <div class="class-item">
                                    <span class="class-name">Latino</span>
                                    <span class="class-time">6:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Pilates/Step</span>
                                    <span class="class-time">7:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Cardio Dance</span>
                                    <span class="class-time">8:00 P.M</span>
                                </div>
                            </div>
                        </div>

                        <!-- Viernes -->
                        <div class="day-schedule mb-4">
                            <div class="day-header">
                                <h4 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>VIERNES
                                </h4>
                            </div>
                            <div class="classes-list">
                                <div class="class-item">
                                    <span class="class-name">House Adultos</span>
                                    <span class="class-time">6:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Hip Hop Adultos</span>
                                    <span class="class-time">7:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Afro Adultos</span>
                                    <span class="class-time">8:00 P.M</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sábado -->
                        <div class="day-schedule mb-4">
                            <div class="day-header">
                                <h4 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>SÁBADO
                                </h4>
                            </div>
                            <div class="classes-list">
                                <div class="class-item">
                                    <span class="class-name">Preballet</span>
                                    <span class="class-time">10:30 A.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Minikids</span>
                                    <span class="class-time">11:30 A.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Kids/Técnica</span>
                                    <span class="class-time">2:00 P.M</span>
                                </div>
                                <div class="class-item">
                                    <span class="class-name">Kids/Coreo</span>
                                    <span class="class-time">3:00 P.M</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="alert alert-info d-inline-block">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Los horarios pueden variar durante días festivos. Consulta con nosotros para confirmaciones.
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
                        <button class="filter-tab" data-category="Contemporáneo">Contemporáneo</button>
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
                        <a href="ubicacion.html" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-map-marker-alt me-2"></i>Visítanos
                        </a>
                        <a href="redes-sociales.html" class="btn btn-outline-primary btn-lg">
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
    
    <!-- Classes Page JS -->
    <script>
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
                const response = await fetch(`data/classes.json?v=${cacheBuster}`);
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
        function filterClasses() {
            filteredClasses = allClasses.filter(classItem => {
                const matchesCategory = currentCategory === 'all' || classItem.category === currentCategory;
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
                            <div class="placeholder-class"></div>
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
                                            <i class="fas fa-users me-1"></i>Capacidad
                                        </small>
                                        <strong>${classItem.capacity} personas</strong>
                                    </div>
                                    <div class="col-12 mt-2">
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
                                <button class="btn btn-primary btn-sm" onclick="enrollInClass('${classItem.id}')">
                                    <i class="fas fa-user-plus me-1"></i>Inscribirse
                                </button>
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
            if (classItem) {
                // Show success message
                showNotification(`¡Te has inscrito en ${classItem.name}! Te contactaremos pronto.`, 'success');
                
                // Here you would typically send the enrollment data to your backend
                console.log('Enrolling in class:', classItem);
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