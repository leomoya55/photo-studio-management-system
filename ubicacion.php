<?php
require_once 'session_manager.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubicación - Legend Dance Academy</title>
    
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
        
        /* Orange accents for location page elements */
        .address-icon {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
        }
        
        .schedule-table th {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
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
        
        /* Additional styles for location page */
        .hero-location {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }
        
        .location-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .location-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .address-icon {
            background: var(--gradient-primary);
        }
        
        .phone-icon {
            background: var(--gradient-secondary);
        }
        
        .schedule-icon {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        
        .transport-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .map-container {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .map-placeholder {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            position: relative;
        }
        
        .map-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.95);
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .coordinate-display {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: white;
        }
        
        .coordinate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .coordinate-item:last-child {
            margin-bottom: 0;
        }
        
        .copy-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .schedule-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .schedule-table table {
            margin: 0;
        }
        
        .schedule-table th {
            background: var(--gradient-primary);
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
        }
        
        .schedule-table td {
            padding: 12px 15px;
            border-color: #f8f9fa;
        }
        
        .schedule-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .parking-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .parking-spot {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
        }
        
        .parking-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .landmarks {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .landmark-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .landmark-item:last-child {
            border-bottom: none;
        }
        
        .landmark-icon {
            width: 30px;
            height: 30px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 0.9rem;
        }
        
        .transport-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .transport-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .transport-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .bus-icon {
            background: #28a745;
        }
        
        .metro-icon {
            background: #007bff;
        }
        
        .car-icon {
            background: #ffc107;
            color: #333;
        }
        
        .walk-icon {
            background: #17a2b8;
        }
        
        @media (max-width: 768px) {
            .hero-location {
                padding: 80px 0 60px;
                text-align: center;
            }
            
            .map-placeholder {
                height: 300px;
                font-size: 1rem;
            }
            
            .map-overlay {
                position: relative;
                top: auto;
                left: auto;
                margin: 20px;
                background: rgba(255,255,255,0.9);
                color: #333;
            }
            
            .coordinate-display {
                background: rgba(0,0,0,0.1);
                color: #333;
                border-color: #dee2e6;
            }
            
            .transport-options {
                grid-template-columns: repeat(2, 1fr);
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
                        <a class="nav-link" href="clases.html">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.html">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redes-sociales.html">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ubicacion.html">Ubicación</a>
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
    <section class="hero-location">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-4">Visítanos</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1964.5!2d-84.054269!3d9.918461!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zOcKwNTUnMDYuNSJOIDg0wrAwMycxNS40Ilc!5e0!3m2!1ses!2scr!4v1696532400000!5m2!1ses!2scr"
                            width="100%" 
                            height="400" 
                            style="border:0; border-radius: 15px;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                        <div class="map-overlay">
                            <strong>Legend Dance Academy</strong><br>
                            <small>75 metros norte de correos<br>Zapote, San José, Costa Rica</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <!-- Address -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="location-card">
                        <div class="location-icon address-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Dirección</h5>
                        <p class="text-muted mb-3">75 metros norte de correos de Costa Rica<br>Zapote<br>San José, Costa Rica<br>C.P. 10101</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="openGoogleMaps()">
                            <i class="fas fa-directions me-1"></i>Cómo llegar
                        </button>
                    </div>
                </div>

                <!-- Phone -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="location-card">
                        <div class="location-icon phone-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Teléfonos</h5>
                        <p class="text-muted mb-3">
                            <strong>Principal:</strong><br>
                            <a href="tel:+50684118339" class="text-decoration-none">+506 8411-8339</a><br><br>
                            <strong>WhatsApp:</strong><br>
                            <a href="https://wa.me/50684118339" class="text-decoration-none">+506 8411-8339</a>
                        </p>
                        <button class="btn btn-outline-success btn-sm" onclick="openWhatsApp()">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </button>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="location-card">
                        <div class="location-icon schedule-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5>Horarios</h5>
                        <div class="small text-muted mb-3">
                            <strong>Martes:</strong> 3:00 PM - 8:00 PM<br>
                            <strong>Miércoles:</strong> 10:00 AM - 8:00 PM<br>
                            <strong>Jueves:</strong> 6:00 PM - 8:00 PM<br>
                            <strong>Viernes:</strong> 6:00 PM - 8:00 PM<br>
                            <strong>Sábados:</strong> 10:30 AM - 3:00 PM<br><br>
                            <small class="text-primary">*Horarios detallados disponibles</small>
                        </div>
                        <a href="clases.html#horarios" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-calendar me-1"></i>Ver horarios
                        </a>
                    </div>
                </div>

                <!-- Transportation -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="location-card">
                        <div class="location-icon transport-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <h5>Navegación</h5>
                        <p class="text-muted mb-3">
                            <strong>Waze:</strong> Navegación GPS en tiempo real<br>
                            <strong>Uber:</strong> Solicitar viaje con destino prefijado<br>
                            <strong>Transporte público:</strong> Rutas disponibles
                        </p>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-primary btn-sm" onclick="openWaze()">
                                <i class="fab fa-waze me-1"></i>Waze
                            </button>
                            <button class="btn btn-outline-dark btn-sm" onclick="openUber()">
                                <i class="fas fa-car me-1"></i>Uber
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Parking Information -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="parking-info">
                        <h4 class="mb-4"><i class="fas fa-parking me-2"></i>Información de Estacionamiento</h4>
                        
                        <div class="parking-spot">
                            <div class="parking-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div>
                                <strong>Estacionamiento Propio</strong><br>
                                <small class="text-muted">20 espacios disponibles • $20/hora • Entrada por Calle Lateral Norte</small>
                            </div>
                        </div>
                        
                        <div class="parking-spot">
                            <div class="parking-icon">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div>
                                <strong>Motocicletas</strong><br>
                                <small class="text-muted">10 espacios • $10/hora • Área techada</small>
                            </div>
                        </div>
                        
                        <div class="parking-spot">
                            <div class="parking-icon">
                                <i class="fas fa-bicycle"></i>
                            </div>
                            <div>
                                <strong>Bicicletas</strong><br>
                                <small class="text-muted">Estacionamiento gratuito • Área segura • 15 espacios</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="landmarks">
                        <h5 class="mb-4"><i class="fas fa-landmark me-2"></i>Referencias cercanas</h5>
                        
                        <div class="landmark-item">
                            <div class="landmark-icon">
                                <i class="fas fa-hospital"></i>
                            </div>
                            <div>
                                <strong>Hospital General</strong><br>
                                <small class="text-muted">2 cuadras al norte</small>
                            </div>
                        </div>
                        
                        <div class="landmark-item">
                            <div class="landmark-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <strong>Centro Comercial Zapote</strong><br>
                                <small class="text-muted">1 cuadra al sur</small>
                            </div>
                        </div>
                        
                        <div class="landmark-item">
                            <div class="landmark-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <strong>Universidad Central</strong><br>
                                <small class="text-muted">5 cuadras al este</small>
                            </div>
                        </div>
                        
                        <div class="landmark-item">
                            <div class="landmark-icon">
                                <i class="fas fa-tree"></i>
                            </div>
                            <div>
                                <strong>Parque Central</strong><br>
                                <small class="text-muted">3 cuadras al oeste</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="mb-4">¿Tienes dudas sobre cómo llegar?</h2>
                    <p class="lead mb-4">Nuestro equipo está listo para ayudarte con indicaciones detalladas y resolver cualquier pregunta sobre nuestra ubicación.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <button class="btn btn-light btn-lg" onclick="openWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </button>
                        <button class="btn btn-outline-light btn-lg" onclick="callPhone()">
                            <i class="fas fa-phone me-2"></i>Llamar Ahora
                        </button>
                        <button class="btn btn-outline-light btn-lg" onclick="openGoogleMaps()">
                            <i class="fas fa-map me-2"></i>Ver en Mapa
                        </button>
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
    
    <!-- Location Page JS -->
    <script>
        // Location coordinates
        const academyLocation = {
            latitude: 9.918461,
            longitude: -84.054269,
            address: "75 metros norte de correos de Costa Rica, Zapote, San José, Costa Rica",
            name: "Legend Dance Academy"
        };

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification(`Copiado: ${text}`, 'success');
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification(`Copiado: ${text}`, 'success');
            });
        }

        // Open Google Maps
        function openGoogleMaps() {
            const url = `https://www.google.com/maps?q=${academyLocation.latitude},${academyLocation.longitude}`;
            window.open(url, '_blank');
        }

        // Open Waze
        function openWaze() {
            const url = `https://waze.com/ul?q=${academyLocation.latitude},${academyLocation.longitude}&navigate=yes`;
            window.open(url, '_blank');
        }

        // Open Uber
        function openUber() {
            // Detect if on mobile device
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            if (isMobile) {
                // On mobile, use the app deep link
                const destination = encodeURIComponent("Legend Dance Academy, 75 metros norte de correos, Zapote, San José, Costa Rica");
                const lat = academyLocation.latitude;
                const lng = academyLocation.longitude;
                const mobileUrl = `uber://?action=setPickup&pickup=my_location&dropoff[formatted_address]=${destination}&dropoff[latitude]=${lat}&dropoff[longitude]=${lng}`;
                window.location.href = mobileUrl;
            } else {
                // On desktop, show instructions and copy address
                const address = "Legend Dance Academy, 75 metros norte de correos, Zapote, San José, Costa Rica";
                copyToClipboard(address);
                showNotification('Dirección copiada al portapapeles. Abre Uber y pega la dirección como destino.', 'success');
                
                // Also open Uber website
                setTimeout(() => {
                    window.open('https://uber.com', '_blank');
                }, 1500);
            }
        }

        // Open WhatsApp
        function openWhatsApp() {
            const phoneNumber = '50684118339';
            const message = encodeURIComponent('Hola, me gustaría obtener información sobre Legend Dance Academy y cómo llegar.');
            const url = `https://wa.me/${phoneNumber}?text=${message}`;
            window.open(url, '_blank');
        }

        // Call phone
        function callPhone() {
            window.location.href = 'tel:+50684118339';
        }

        // Show schedule details
        function showScheduleDetails() {
            document.querySelector('.schedule-table').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        // Show transport details
        function showTransportDetails() {
            document.querySelector('.transport-options').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        // Get user location and show distance
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;
                    const distance = calculateDistance(userLat, userLon, academyLocation.latitude, academyLocation.longitude);
                    
                    showNotification(`Estás a ${distance.toFixed(1)} km de Legend Dance Academy`, 'info');
                }, function(error) {
                    showNotification('No se pudo obtener tu ubicación', 'warning');
                });
            } else {
                showNotification('Tu navegador no soporta geolocalización', 'warning');
            }
        }

        // Calculate distance between two coordinates
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the Earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const d = R * c; // Distance in km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI/180);
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Page initialized
        });
    </script>
</body>
</html>