<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db_connect.php';

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

// Debug output for horarios (remove in production)
// echo "<!-- HORARIOS DEBUG: userRole = '$userRole', admin_view = " . (isset($_GET['admin_view']) ? $_GET['admin_view'] : 'not set') . ", isLoggedIn = " . ($isLoggedIn ? 'true' : 'false') . ", isAdminView = " . ($isAdminView ? 'true' : 'false') . " -->";

// Get schedules from database (for now we'll use sample data, later we'll create the database structure)
$schedules = [];

// Real schedule data from Legend Academy
$sampleSchedules = [
    'Lunes' => [
        ['time' => '10:00 - 11:00', 'class' => 'Pilates/Funcional', 'instructor' => 'Vanessa Mora'],
        ['time' => '15:00 - 16:00', 'class' => 'Adulto Mayor', 'instructor' => 'Vanessa Mora']
    ],
    'Martes' => [
        ['time' => '15:00 - 16:00', 'class' => 'Pilates/Funcional', 'instructor' => 'Vanessa Mora'],
        ['time' => '18:00 - 19:00', 'class' => 'Latino', 'instructor' => 'Vanessa Mora'],
        ['time' => '19:00 - 20:00', 'class' => 'Pilates/Funcional', 'instructor' => 'Vanessa Mora'],
        ['time' => '20:00 - 21:00', 'class' => 'Cardio Dance', 'instructor' => 'Vanessa Mora']
    ],
    'Miércoles' => [
        ['time' => '10:00 - 11:00', 'class' => 'Pilates/Funcional', 'instructor' => 'Vanessa Mora'],
        ['time' => '15:00 - 16:00', 'class' => 'Pilates/Funcional', 'instructor' => 'Vanessa Mora'],
        ['time' => '20:00 - 21:00', 'class' => 'Cardio Dance', 'instructor' => 'Vanessa Mora']
    ],
    'Jueves' => [
        ['time' => '18:00 - 19:00', 'class' => 'Latino', 'instructor' => 'Vanessa Mora'],
        ['time' => '19:00 - 20:00', 'class' => 'Pilates Step', 'instructor' => 'Vanessa Mora'],
        ['time' => '20:00 - 21:00', 'class' => 'Cardio Dance', 'instructor' => 'Vanessa Mora']
    ],
    'Viernes' => [
        ['time' => '18:00 - 19:00', 'class' => 'Kids', 'instructor' => 'Vanessa Mora'],
        ['time' => '19:00 - 20:00', 'class' => 'Hip Hop Adultos', 'instructor' => 'Vanessa Mora'],
        ['time' => '20:00 - 21:00', 'class' => 'Afro Adultos', 'instructor' => 'Vanessa Mora']
    ],
    'Sábado' => [
        ['time' => '10:30 - 11:30', 'class' => 'Preballet', 'instructor' => 'Vanessa Mora'],
        ['time' => '11:30 - 12:30', 'class' => 'Minikids', 'instructor' => 'Vanessa Mora'],
        ['time' => '14:00 - 15:00', 'class' => 'Kids/Técnica', 'instructor' => 'Vanessa Mora'],
        ['time' => '15:00 - 16:00', 'class' => 'Kids/Coreo', 'instructor' => 'Vanessa Mora'],
        ['time' => '17:00 - 18:00', 'class' => 'Compañía', 'instructor' => 'Vanessa Mora'],
        ['time' => '18:00 - 19:00', 'class' => 'Compañía', 'instructor' => 'Vanessa Mora'],
        ['time' => '19:00 - 20:00', 'class' => 'Compañía', 'instructor' => 'Vanessa Mora'],
        ['time' => '20:00 - 21:00', 'class' => 'Compañía', 'instructor' => 'Vanessa Mora']
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios - Legend Dance Academy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Orange and Black Color Overrides to match other pages -->
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
        
        /* Schedule specific styles */
        .schedule-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 76px;
        }
        
        .day-column {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 0;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .day-header {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .class-slot {
            padding: 15px;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .class-slot:last-child {
            border-bottom: none;
        }
        
        .class-slot:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .class-slot.admin-view {
            cursor: default;
            opacity: 0.8;
        }
        
        .class-slot.admin-view:hover {
            transform: none;
            background-color: #ffffff;
        }
        
        .admin-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .class-time {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .class-name {
            font-weight: 500;
            color: #ff6600;
            margin: 5px 0;
        }
        
        .class-instructor {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
        }
        
        .class-availability {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        

        
        .register-btn {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            border: none;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .register-btn:hover {
            background: linear-gradient(135deg, #e55a00 0%, #ff6600 100%);
            transform: scale(1.05);
            color: white;
        }
        
        .register-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
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
        
        /* Responsive design */
        @media (max-width: 768px) {
            .schedule-hero {
                padding: 80px 0 60px;
            }
            
            .day-column {
                margin-bottom: 15px;
            }
            
            .class-slot {
                padding: 12px;
            }
        }
        

    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>">
                <span class="brand-text">Legend</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo VIEWS_URL; ?>/horarios.php<?php echo $adminViewParam; ?>">Horarios</a>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-welcome" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
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

    <!-- Hero Section -->
    <section class="schedule-hero">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-4">Horarios de Clases</h1>
                    <p class="lead mb-4">Descubre nuestros horarios semanales y reserva tu lugar en las clases que más te gusten. ¡Haz clic en cualquier clase para registrarte!</p>
                </div>

            </div>
        </div>
    </section>

    <!-- Schedule Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <?php foreach ($sampleSchedules as $day => $classes): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="day-column">
                            <div class="day-header">
                                <?php echo $day; ?>
                            </div>
                            <?php foreach ($classes as $class): 
                                $forceAdminView = isset($_GET['admin_view']) && $_GET['admin_view'] == '1';
                            ?>
                                <div class="class-slot <?php echo $forceAdminView ? 'admin-view' : ''; ?>" <?php echo !$forceAdminView ? "onclick=\"registerForClass('" . $class['class'] . "', '" . $day . "', '" . $class['time'] . "')\"" : ''; ?>>
                                    <div class="class-time">
                                        <i class="fas fa-clock me-2"></i><?php echo $class['time']; ?>
                                    </div>
                                    <div class="class-name"><?php echo $class['class']; ?></div>
                                    <div class="class-instructor">
                                        <i class="fas fa-user me-1"></i>Instructora: <?php echo $class['instructor']; ?>
                                    </div>
                                    <div class="class-availability">
                                        <?php if ($forceAdminView): ?>
                                            <span class="badge bg-secondary admin-badge">
                                                <i class="fas fa-eye me-1"></i>Vista Admin
                                            </span>
                                        <?php else: ?>
                                            <button class="btn register-btn">
                                                <i class="fas fa-user-plus me-1"></i>Reservar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Additional Information -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fas fa-exclamation-circle me-2"></i>Información Importante</h5>
                            <p class="card-text">
                                • Las reservas deben realizarse con al menos 2 horas de anticipación<br>
                                • Cancellaciones gratuitas hasta 4 horas antes de la clase<br>
                                • Para más información sobre las clases, visita nuestra sección de <a href="clases.php<?php echo $adminViewParam; ?>" class="text-primary">Clases</a>
                            </p>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <div class="mt-3">
                                    <a href="register.php" class="btn btn-primary me-2">Crear Cuenta</a>
                                    <a href="login.php" class="btn btn-outline-primary">Iniciar Sesión</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        <a href="#" class="disabled" title="Próximamente"><i class="fab fa-youtube"></i></a>
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
    
    <!-- Schedule JS -->
    <script>
        function registerForClass(className, day, time) {
            <?php if (isset($_SESSION['user_id'])): ?>
                // User is logged in, show enrollment confirmation
                showScheduleEnrollmentConfirmation(className, day, time);
            <?php else: ?>
                // User not logged in, redirect to login
                if (confirm('Para registrarte en una clase necesitas tener una cuenta. ¿Deseas iniciar sesión ahora?')) {
                    window.location.href = 'login.php?redirect=horarios.php';
                }
            <?php endif; ?>
        }

        // Add some visual feedback when hovering over class slots
        document.addEventListener('DOMContentLoaded', function() {
            const classSlots = document.querySelectorAll('.class-slot');
            
            classSlots.forEach(slot => {
                slot.addEventListener('mouseenter', function() {
                    this.style.borderLeft = '4px solid #ff6600';
                });
                
                slot.addEventListener('mouseleave', function() {
                    this.style.borderLeft = 'none';
                });
            });
        });

        // Schedule enrollment functions
        function showScheduleEnrollmentConfirmation(className, day, time) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'scheduleEnrollmentModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-calendar-plus me-2"></i>Confirmar Inscripción
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="display-1 text-primary mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h4>${className}</h4>
                                <p class="text-muted">${day} - ${time}</p>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Proceso de inscripción:</strong><br>
                                1. Tu solicitud será enviada a administración<br>
                                2. Recibirás una confirmación por correo<br>
                                3. La instructora Vanessa revisará y aprobará tu solicitud<br>
                                4. Te contactaremos para coordinar el primer día de clase
                            </div>
                            
                            <div class="text-center">
                                <p><strong>¿Estás seguro que deseas inscribirte en este horario?</strong></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="processScheduleEnrollment('${className}', '${day}', '${time}')">
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

        // Process schedule enrollment
        async function processScheduleEnrollment(className, day, time) {
            try {
                // Close the modal first
                const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleEnrollmentModal'));
                modal.hide();
                
                // Show loading state - simple notification
                showScheduleNotification('Procesando inscripción...', 'info');
                
                // Find class ID based on className (this is a simplified approach)
                let classId = getClassIdByName(className);
                
                const response = await fetch('process_enrollment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: classId,
                        action: 'enroll',
                        schedule_details: {
                            day: day,
                            time: time
                        }
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showScheduleNotification(result.message, 'success');
                } else {
                    showScheduleNotification(result.message || 'Error al procesar la inscripción', 'error');
                }
                
            } catch (error) {
                console.error('Enrollment error:', error);
                showScheduleNotification('Error de conexión. Por favor, inténtalo nuevamente.', 'error');
            }
        }

        function getClassIdByName(className) {
            // Simple mapping - in a real app, this would come from the server
            const classMapping = {
                'Ballet Clásico': 1,
                'Danza Contemporánea': 2,
                'Hip Hop': 3,
                'Jazz': 4,
                'Salsa': 5,
                'Bachata': 6,
                'Zumba': 7,
                'Flamenco': 8
            };
            return classMapping[className] || 1;
        }

        function showScheduleNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                             type === 'error' ? 'alert-danger' : 
                             type === 'warning' ? 'alert-warning' : 'alert-info';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert \${alertClass} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                \${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    closeConnection($conn);
}
?>