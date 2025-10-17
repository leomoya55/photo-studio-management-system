<?php
session_start();
require_once 'db_connect.php';

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
$last_name = $_SESSION['last_name'];

// Get user's enrollments
$stmt = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? ORDER BY enrollment_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$enrollments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Legend Dance Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: white;
            padding: 2rem 0;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: -3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .enrollment-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-star me-2"></i>Legend Dance Academy</h1>
                    <p class="mb-0">Panel de Control del Estudiante</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
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
                    <h2 class="text-primary mb-1">¡Hola, <?php echo htmlspecialchars($first_name); ?>!</h2>
                    <p class="text-muted mb-0">Bienvenido a tu panel de control. Aquí puedes ver tus clases inscritas y explorar nuevas opciones.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="clases.html" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Explorar Clases
                    </a>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Stats -->
            <div class="col-md-4 mb-4">
                <div class="stat-card text-center">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                    <h3 class="h4 text-primary"><?php echo $enrollments->num_rows; ?></h3>
                    <p class="text-muted mb-0">Clases Inscritas</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card text-center">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h3 class="h4 text-warning">0</h3>
                    <p class="text-muted mb-0">Clases Completadas</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card text-center">
                    <i class="fas fa-trophy fa-2x text-success mb-2"></i>
                    <h3 class="h4 text-success">Estudiante</h3>
                    <p class="text-muted mb-0">Nivel Actual</p>
                </div>
            </div>
        </div>

        <!-- Enrollments -->
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2 text-primary"></i>Mis Inscripciones
                </h3>
                
                <?php if ($enrollments->num_rows > 0): ?>
                    <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                        <div class="card enrollment-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($enrollment['class_name']); ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo htmlspecialchars($enrollment['class_schedule']); ?>
                                        </p>
                                        <small class="text-muted">
                                            Inscrito el: <?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                            <?php 
                                            $statusText = [
                                                'active' => 'Activa',
                                                'completed' => 'Completada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            echo $statusText[$enrollment['status']]; 
                                            ?>
                                        </span>
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
                            <a href="clases.html" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Explorar Clases
                            </a>
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
                        <a href="clases.html" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-calendar-alt d-block mb-2"></i>
                            Ver Horarios
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="clases.html" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-plus d-block mb-2"></i>
                            Inscribir Clase
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="ubicacion.html" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-map-marker-alt d-block mb-2"></i>
                            Ubicación
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="redes-sociales.html" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-share-alt d-block mb-2"></i>
                            Redes Sociales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php closeConnection($conn); ?>