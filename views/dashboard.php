<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/user_notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
// Clean, safe queries block
$user_profile = [];
if ($stmt = $conn->prepare("SELECT * FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$enrollments = null; $enrollments_count = 0;
if ($stmt = $conn->prepare("\n    SELECT e.*, \n           COUNT(f.id) as feedback_count,\n           AVG(f.performance_rating) as avg_rating,\n           MAX(f.class_date) as last_feedback_date\n    FROM enrollments e \n    LEFT JOIN instructor_feedback f ON e.id = f.enrollment_id \n    WHERE e.user_id = ? \n    GROUP BY e.id \n    ORDER BY e.enrollment_date DESC\n")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $enrollments = $stmt->get_result();
    $enrollments_count = $enrollments ? $enrollments->num_rows : 0;
    $stmt->close();
}

$recent_feedback = null; $feedback_count = 0;
if ($stmt = $conn->prepare("\n    SELECT f.*, e.class_name, e.class_schedule\n    FROM instructor_feedback f\n    JOIN enrollments e ON f.enrollment_id = e.id\n    WHERE f.user_id = ?\n    ORDER BY f.class_date DESC\n    LIMIT 5\n")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_feedback = $stmt->get_result();
    $feedback_count = $recent_feedback ? $recent_feedback->num_rows : 0;
    $stmt->close();
}

$progress_summary = null; $progress_count = 0;
if ($stmt = $conn->prepare("\n    SELECT class_type, skill_level, total_classes_attended, average_rating, goals, achievements\n    FROM user_progress \n    WHERE user_id = ?\n    ORDER BY updated_at DESC\n")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $progress_summary = $stmt->get_result();
    $progress_count = $progress_summary ? $progress_summary->num_rows : 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Legend Dance Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: white;
            padding: 2rem 0 4rem 0;
            position: relative;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: -2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        .header-content {
            margin-bottom: 1.5rem;
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            height: 100%;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            text-align: center;
        }
        .enrollment-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .feedback-card {
            border-left: 4px solid #ff6600;
            margin-bottom: 1rem;
        }
        .status-badge {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .rating-stars {
            color: #ffc107;
        }
        .profile-info-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .profile-info-item:last-child {
            border-bottom: none;
        }
        .edit-profile-btn {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .emergency-contact {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .health-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            text-align: center;
        }
        .enrollment-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .feedback-card {
            border-left: 4px solid #ff6600;
            margin-bottom: 1rem;
        }
        .status-badge {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .rating-stars {
            color: #ffc107;
        }
        .profile-info-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .profile-info-item:last-child {
            border-bottom: none;
        }
        .edit-profile-btn {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .emergency-contact {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .health-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <h1 class="header-title">
                            <i class="fas fa-star me-2"></i>Legend Dance Academy
                        </h1>
                        <p class="header-subtitle">Panel de Control del Estudiante</p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="#notifications" class="btn btn-light btn-lg me-2" title="Notificaciones">
                        <i class="fas fa-bell me-2"></i>
                        <span>Notificaciones</span>
                        <span id="notifCount" class="badge bg-danger ms-2" style="display:none;">0</span>
                    </a>
                    <a href="<?php echo VIEWS_URL; ?>/index.php" class="btn btn-outline-light btn-lg me-2">
                        <i class="fas fa-home me-2"></i>Inicio
                    </a>
                    <a href="<?php echo VIEWS_URL; ?>/logout.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary rounded-circle p-3 me-3">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-primary mb-1">¡Hola, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h2>
                            <p class="text-muted mb-0">Bienvenido a tu panel de control. Gestiona tu perfil, clases y progreso.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo VIEWS_URL; ?>/clases.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Explorar Clases
                    </a>
                </div>
            </div>
        </div>

        <a id="notifications"></a>
        <?php
        // Get and display user notifications
        $notifications = getUserNotifications($user_id, $conn);
        if (!empty($notifications)) {
            echo displayNotifications($notifications);
        }
        ?>

        <div class="row mt-4">
            <!-- Personal Information Card -->
            <div class="col-lg-4 mb-4">
                <div class="info-card position-relative">
                    <button class="btn btn-sm btn-outline-primary edit-profile-btn" onclick="editPersonalInfo()">
                        <i class="fas fa-edit"></i>
                    </button>
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                    
                    <div class="profile-info-item">
                        <small class="text-muted">Peso:</small><br>
                        <strong><?php echo $user_profile['weight'] ? $user_profile['weight'] . ' kg' : 'No especificado'; ?></strong>
                    </div>
                    
                    <div class="profile-info-item">
                        <small class="text-muted">Altura:</small><br>
                        <strong><?php echo $user_profile['height'] ? $user_profile['height'] . ' cm' : 'No especificado'; ?></strong>
                    </div>
                    
                    <div class="profile-info-item">
                        <small class="text-muted">Email:</small><br>
                        <strong><?php echo htmlspecialchars($user_profile['email']); ?></strong>
                    </div>
                    
                    <div class="profile-info-item">
                        <small class="text-muted">Miembro desde:</small><br>
                        <strong><?php echo date('d/m/Y', strtotime($user_profile['created_at'])); ?></strong>
                    </div>

                    <?php if ($user_profile['medical_conditions']): ?>
                    <div class="health-info">
                        <h6 class="text-warning mb-2">
                            <i class="fas fa-heartbeat me-1"></i>Condiciones Médicas
                        </h6>
                        <p class="mb-2 small"><?php echo nl2br(htmlspecialchars($user_profile['medical_conditions'])); ?></p>
                        <button class="btn btn-outline-warning btn-sm w-100" onclick="editMedicalInfo()">
                            <i class="fas fa-edit me-1"></i>Editar Info. Médica
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="health-info text-center">
                        <i class="fas fa-heartbeat fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">No hay información médica registrada</p>
                        <button class="btn btn-outline-warning btn-sm" onclick="editMedicalInfo()">
                            <i class="fas fa-plus me-1"></i>Agregar Info. Médica
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Emergency Contact Card -->
            <div class="col-lg-4 mb-4">
                <div class="info-card">
                    <h5 class="text-danger mb-3">
                        <i class="fas fa-phone-alt me-2"></i>Contacto de Emergencia
                    </h5>
                    
                    <?php if ($user_profile['emergency_contact_name']): ?>
                        <div class="emergency-contact">
                            <div class="profile-info-item">
                                <small class="text-muted">Nombre:</small><br>
                                <strong><?php echo htmlspecialchars($user_profile['emergency_contact_name']); ?></strong>
                            </div>
                            
                            <div class="profile-info-item">
                                <small class="text-muted">Teléfono:</small><br>
                                <strong><?php echo htmlspecialchars($user_profile['emergency_contact_phone']); ?></strong>
                            </div>
                            
                            <div class="profile-info-item">
                                <small class="text-muted">Relación:</small><br>
                                <strong><?php echo htmlspecialchars($user_profile['emergency_contact_relationship']); ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No hay contacto de emergencia registrado</p>
                            <button class="btn btn-outline-danger btn-sm" onclick="editEmergencyContact()">
                                Agregar Contacto
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="col-lg-4 mb-4">
                <div class="info-card">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-chart-line me-2"></i>Estadísticas
                    </h5>
                    
                    <div class="stat-card mb-2">
                        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary"><?php echo (int)($enrollments_count ?? ($enrollments ? $enrollments->num_rows : 0)); ?></h4>
                        <p class="text-muted mb-0">Clases Inscritas</p>
                    </div>
                    
                    <div class="stat-card mb-2">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h4 class="text-warning"><?php echo (int)($feedback_count ?? ($recent_feedback ? $recent_feedback->num_rows : 0)); ?></h4>
                        <p class="text-muted mb-0">Evaluaciones Recibidas</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-trophy fa-2x text-success mb-2"></i>
                            <h4 class="text-primary"><?php echo (int)($enrollments_count ?? ($enrollments ? $enrollments->num_rows : 0)); ?></h4>
                        <p class="text-muted mb-0">Programas en Progreso</p>
                    </div>
                    
                    
                    
                </div>
            </div>
                            <h4 class="text-warning"><?php echo (int)($feedback_count ?? ($recent_feedback ? $recent_feedback->num_rows : 0)); ?></h4>

        <!-- Enrollments and Feedback -->
                    
                    
                    
        <div class="row">
            <!-- My Enrollments -->
                            <h4 class="text-success"><?php echo (int)($progress_count ?? ($progress_summary ? $progress_summary->num_rows : 0)); ?></h4>
                <h3 class="mb-4">
                    <i class="fas fa-list me-2 text-primary"></i>Mis Inscripciones
                </h3>
                
                <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                    <?php $enrollments->data_seek(0); // Reset pointer ?>
                    <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                        <div class="card enrollment-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($enrollment['class_name']); ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo htmlspecialchars($enrollment['class_schedule']); ?>
                                        </p>
                                        <div class="row mt-2">
                                            <div class="col-sm-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-plus me-1"></i>
                                                    Inscrito: <?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Asistencias: <?php echo $enrollment['total_classes_attended'] ?? 0; ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($enrollment['avg_rating']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Calificación promedio:</small>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= round($enrollment['avg_rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-1"><?php echo number_format($enrollment['avg_rating'], 1); ?>/5</span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($enrollment['progress_notes']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Notas de progreso:</small>
                                            <p class="small text-dark mb-0"><?php echo nl2br(htmlspecialchars($enrollment['progress_notes'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4 text-md-end">
                                        <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                            <?php 
                                            $statusText = [
                                                'active' => 'Asistiendo a Clases',
                                                'inactive' => 'No Está Asistiendo',
                                                'pending' => 'Esperando Aprobación',
                                                'rejected' => 'No Aprobado',
                                                'completed' => 'Clase Completada',
                                                'cancelled' => 'Cancelado'
                                            ];
                                            echo $statusText[$enrollment['status']] ?? ucfirst($enrollment['status']); 
                                            ?>
                                        </span>
                                        
                                        <?php 
                                        // Add status-specific action messages
                                        if ($enrollment['status'] === 'inactive'): ?>
                                        <div class="mt-2">
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Contacta con la academia para volver a las clases
                                            </small>
                                        </div>
                                        <?php elseif ($enrollment['status'] === 'pending'): ?>
                                        <div class="mt-2">
                                            <small class="text-info">
                                                <i class="fas fa-clock me-1"></i>
                                                Tu solicitud está siendo revisada
                                            </small>
                                        </div>
                                        <?php elseif ($enrollment['status'] === 'rejected'): ?>
                                        <div class="mt-2">
                                            <small class="text-danger">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Contacta con la academia para más información
                                            </small>
                                        </div>
                                        <?php elseif ($enrollment['status'] === 'completed'): ?>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                ¡Felicidades! Has completado esta clase
                                            </small>
                                        </div>
                                        <?php elseif ($enrollment['status'] === 'cancelled'): ?>
                                        <div class="mt-2">
                                            <small class="text-secondary">
                                                <i class="fas fa-ban me-1"></i>
                                                Esta inscripción fue cancelada
                                            </small>
                                        </div>
                                        <?php elseif ($enrollment['status'] === 'active'): ?>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-play me-1"></i>
                                                ¡Estás asistiendo a esta clase!
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($enrollment['feedback_count'] > 0): ?>
                                        <div class="mt-2">
                                            <small class="text-primary">
                                                <i class="fas fa-comments me-1"></i>
                                                <?php echo $enrollment['feedback_count']; ?> evaluación(es)
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($enrollment['last_feedback_date']): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Última evaluación: <?php echo date('d/m/Y', strtotime($enrollment['last_feedback_date'])); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card enrollment-card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No tienes clases inscritas aún</h5>
                            <p class="text-muted mb-3">¡Explora nuestras clases y encuentra la perfecta para ti!</p>
                            <a href="<?php echo VIEWS_URL; ?>/clases.php" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Explorar Clases
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Instructor Feedback -->
            <div class="col-lg-4">
                <h3 class="mb-4">
                    <i class="fas fa-comments me-2 text-primary"></i>Evaluaciones Recientes
                </h3>
                
                <?php if ($recent_feedback && $recent_feedback->num_rows > 0): ?>
                    <?php while ($feedback = $recent_feedback->fetch_assoc()): ?>
                        <div class="card feedback-card">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-1"><?php echo htmlspecialchars($feedback['class_name']); ?></h6>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($feedback['class_date'])); ?></small>
                                
                                <?php if ($feedback['performance_rating']): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Calificación:</small>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $feedback['performance_rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($feedback['strengths']): ?>
                                <div class="mt-2">
                                    <small class="text-success"><strong>Fortalezas:</strong></small>
                                    <p class="small mb-1"><?php echo htmlspecialchars($feedback['strengths']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($feedback['areas_for_improvement']): ?>
                                <div class="mt-2">
                                    <small class="text-warning"><strong>A mejorar:</strong></small>
                                    <p class="small mb-1"><?php echo htmlspecialchars($feedback['areas_for_improvement']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($feedback['homework_assigned']): ?>
                                <div class="mt-2">
                                    <small class="text-info"><strong>Tarea asignada:</strong></small>
                                    <p class="small mb-0"><?php echo htmlspecialchars($feedback['homework_assigned']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-comment-slash fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No hay evaluaciones recientes</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-bolt me-2 text-primary"></i>Acciones Rápidas
                </h3>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="../views/horarios.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-calendar-alt d-block mb-2"></i>
                            Ver Horarios
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../views/clases.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-plus d-block mb-2"></i>
                            Inscribir Clase
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../views/ubicacion.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-map-marker-alt d-block mb-2"></i>
                            Ubicación
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../views/redes-sociales.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-share-alt d-block mb-2"></i>
                            Redes Sociales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Info Modal (Height & Weight) -->
    <div class="modal fade" id="personalInfoModal" tabindex="-1" aria-labelledby="personalInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="personalInfoModalLabel">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="personalInfoForm" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">
                                        <i class="fas fa-weight me-1"></i>Peso (kg)
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="weight" name="weight" 
                                           value="<?php echo $user_profile['weight']; ?>" placeholder="65.5">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="height" class="form-label">
                                        <i class="fas fa-ruler-vertical me-1"></i>Altura (cm)
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="height" name="height" 
                                           value="<?php echo $user_profile['height']; ?>" placeholder="170.0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Emergency Contact Modal -->
    <div class="modal fade" id="emergencyContactModal" tabindex="-1" aria-labelledby="emergencyContactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="emergencyContactModalLabel">
                        <i class="fas fa-phone-alt me-2"></i>Contacto de Emergencia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="emergencyContactForm" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Esta información será utilizada solo en caso de emergencia.
                        </div>
                        
                        <div class="mb-3">
                            <label for="emergency_contact_name" class="form-label">
                                <i class="fas fa-user me-1"></i>Nombre Completo
                            </label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                   value="<?php echo htmlspecialchars($user_profile['emergency_contact_name']); ?>" 
                                   placeholder="María González Pérez" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="emergency_contact_phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                           value="<?php echo htmlspecialchars($user_profile['emergency_contact_phone']); ?>" 
                                           placeholder="+506 8888-9999" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="emergency_contact_relationship" class="form-label">
                                        <i class="fas fa-heart me-1"></i>Relación
                                    </label>
                                    <select class="form-control" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Madre" <?php echo $user_profile['emergency_contact_relationship'] == 'Madre' ? 'selected' : ''; ?>>Madre</option>
                                        <option value="Padre" <?php echo $user_profile['emergency_contact_relationship'] == 'Padre' ? 'selected' : ''; ?>>Padre</option>
                                        <option value="Esposo/a" <?php echo $user_profile['emergency_contact_relationship'] == 'Esposo/a' ? 'selected' : ''; ?>>Esposo/a</option>
                                        <option value="Hermano/a" <?php echo $user_profile['emergency_contact_relationship'] == 'Hermano/a' ? 'selected' : ''; ?>>Hermano/a</option>
                                        <option value="Hijo/a" <?php echo $user_profile['emergency_contact_relationship'] == 'Hijo/a' ? 'selected' : ''; ?>>Hijo/a</option>
                                        <option value="Amigo/a" <?php echo $user_profile['emergency_contact_relationship'] == 'Amigo/a' ? 'selected' : ''; ?>>Amigo/a</option>
                                        <option value="Otro" <?php echo $user_profile['emergency_contact_relationship'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i>Guardar Contacto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Medical Info Modal -->
    <div class="modal fade" id="medicalInfoModal" tabindex="-1" aria-labelledby="medicalInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="medicalInfoModalLabel">
                        <i class="fas fa-heartbeat me-2"></i>Información Médica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="medicalInfoForm" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Esta información ayuda a nuestros instructores a adaptar las clases según tus necesidades.
                        </div>
                        
                        <div class="mb-3">
                            <label for="medical_conditions" class="form-label">
                                <i class="fas fa-notes-medical me-1"></i>Condiciones Médicas, Alergias o Limitaciones
                            </label>
                            <textarea class="form-control" id="medical_conditions" name="medical_conditions" 
                                      rows="4" placeholder="Describe cualquier condición médica, alergia o limitación física que debamos conocer para adaptar las clases..."><?php echo htmlspecialchars($user_profile['medical_conditions']); ?></textarea>
                            <small class="text-muted">Ejemplo: Asma, lesión en rodilla, alergia al latex, etc.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Guardar Información
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fetch alert count and show badge
        (function(){
            fetch('<?php echo BASE_URL; ?>/api/get_user_alerts.php')
              .then(r=>r.json())
              .then(data=>{
                  const cnt = (data && Array.isArray(data.alerts)) ? data.alerts.length : 0;
                  const el = document.getElementById('notifCount');
                  if (el && cnt > 0) { el.textContent = cnt; el.style.display = 'inline-block'; }
              })
              .catch(()=>{});
        })();

        // Modal functions for different profile sections
        function editPersonalInfo() {
            const modal = new bootstrap.Modal(document.getElementById('personalInfoModal'));
            modal.show();
        }
        
        function editEmergencyContact() {
            const modal = new bootstrap.Modal(document.getElementById('emergencyContactModal'));
            modal.show();
        }
        
        function editMedicalInfo() {
            const modal = new bootstrap.Modal(document.getElementById('medicalInfoModal'));
            modal.show();
        }
        
        // Form submission handlers
        function setupFormHandler(formId, successMessage) {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../views/update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and refresh page
                        const modalElement = this.closest('.modal');
                        bootstrap.Modal.getInstance(modalElement).hide();
                        
                        // Show success message
                        alert(successMessage);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar. Por favor, intenta de nuevo.');
                });
            });
        }
        
        // Setup all form handlers
        setupFormHandler('personalInfoForm', 'Información personal actualizada exitosamente');
        setupFormHandler('emergencyContactForm', 'Contacto de emergencia actualizado exitosamente');
        setupFormHandler('medicalInfoForm', 'Información médica actualizada exitosamente');
    </script>
</body>
</html>

<?php closeConnection($conn); ?>