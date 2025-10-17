<?php
require_once 'session_manager.php';
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php#inicio">
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
                        <a class="nav-link" href="clases.php">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redes-sociales.php">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ubicacion.php">Ubicación</a>
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
                            <a href="clases.php" class="btn btn-primary btn-lg me-3">Ver Clases</a>
                            <a href="register.php" class="btn btn-outline-primary btn-lg">Únete Ahora</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-image logo-contrast-bg">
                            <img src="LegendCR.png" alt="Academia Legend" class="img-fluid rounded shadow">
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
                <div class="col-lg-6 mb-4">
                    <div class="director-image-placeholder">
                        <i class="fas fa-user-tie display-1 text-primary"></i>
                        <p class="mt-3">Foto de Vanessa Mora</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="director-info">
                        <span class="badge bg-primary mb-3">Directora y Fundadora</span>
                        <h2 class="mb-3">Vanessa Mora</h2>
                        <p class="lead mb-4">
                            Con más de 10 años de experiencia en el mundo de la danza, Vanessa Mora es la fundadora y directora de Legend Dance Academy.
                        </p>
                        <p class="mb-4">
                            Su pasión por el baile y su dedicación a la enseñanza han convertido a Legend en un referente de la danza en Costa Rica. Especializada en múltiples disciplinas, desde ritmos latinos hasta técnicas urbanas.
                        </p>
                        <div class="director-stats row">
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">10+</h4>
                                <small class="text-muted">Años de Experiencia</small>
                            </div>
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">200+</h4>
                                <small class="text-muted">Estudiantes</small>
                            </div>
                            <div class="col-4 text-center">
                                <h4 class="text-primary mb-1">9</h4>
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
                    <a href="clases.html" class="btn btn-primary btn-lg">
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
            </div>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h3>Nuestra Misión</h3>
                    <p>
                        En Legend Dance Academy, nos dedicamos a cultivar el amor por la danza en cada uno de nuestros estudiantes. 
                        Creemos que la danza es una forma de expresión que trasciende barreras y une corazones.
                    </p>
                    <h3>Nuestra Visión</h3>
                    <p>
                        Ser la academia de danza líder en Costa Rica, reconocida por la excelencia en la enseñanza, 
                        la innovación en nuestros programas y el desarrollo integral de nuestros estudiantes.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number">10+</span>
                            <p>Años de Experiencia</p>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">200+</span>
                            <p>Estudiantes Activos</p>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">9</span>
                            <p>Disciplinas de Danza</p>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">95%</span>
                            <p>Satisfacción</p>
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
                    <h5 class="brand-text">Legend</h5>
                    <p>Academia de danza donde cada movimiento cuenta una historia.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=100068508182444" class="text-white me-3" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/legendvm.cr/" class="text-white me-3" target="_blank"><i class="fab fa-instagram"></i></a>
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
    <script src="assets/js/script.js"></script>
    
    <!-- Homepage specific JS -->
    <script>
        // Load featured classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedClasses();
        });

        // Load featured classes from JSON
        async function loadFeaturedClasses() {
            try {
                const cacheBuster = new Date().getTime();
                const response = await fetch(`data/classes.json?v=${cacheBuster}`);
                const allClasses = await response.json();
                const featuredClasses = allClasses.filter(classItem => classItem.featured);
                
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
                                <a href="clases.html" class="btn btn-outline-primary btn-sm">Ver Detalles</a>
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

    </script>
</body>
</html>