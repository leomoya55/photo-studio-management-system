<?php
/**
 * Academia Vanessa - Admin Dashboard
 * Simple admin interface for managing enrollments and contacts
 */

session_start();

// Simple authentication (in production, use proper authentication)
$admin_username = 'admin';
$admin_password = 'vanessa2025'; // Change this password!

// Handle login
if (isset($_POST['login'])) {
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Credenciales incorrectas';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Check if logged in
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// Load data functions
function loadEnrollments() {
    $file = 'data/enrollments.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

function loadContacts() {
    $file = 'logs/contact_submissions.log';
    $contacts = [];
    
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $contact = json_decode($line, true);
            if ($contact) {
                $contacts[] = $contact;
            }
        }
    }
    
    return array_reverse($contacts); // Most recent first
}

function loadClasses() {
    $file = 'data/classes.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

// Get statistics
function getStats() {
    $enrollments = loadEnrollments();
    $contacts = loadContacts();
    
    $stats = [
        'total_enrollments' => count($enrollments),
        'active_enrollments' => count(array_filter($enrollments, function($e) { return $e['status'] === 'active'; })),
        'pending_enrollments' => count(array_filter($enrollments, function($e) { return $e['status'] === 'pending'; })),
        'total_contacts' => count($contacts),
        'recent_contacts' => count(array_filter($contacts, function($c) { 
            return strtotime($c['timestamp']) > strtotime('-7 days'); 
        }))
    ];
    
    return $stats;
}

if ($is_logged_in) {
    $enrollments = loadEnrollments();
    $contacts = loadContacts();
    $classes = loadClasses();
    $stats = getStats();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Academia Legend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-tabs-container {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 15px 25px;
            background: transparent;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #ff6b6b;
            background: white;
            border-color: #dee2e6 #dee2e6 white;
            border-bottom: 2px solid #ff6b6b;
            font-weight: 600;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            color: white;
            border-radius: 15px;
        }
        .stat-card.secondary {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <!-- Login Form -->
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);">
        <div class="card shadow-lg" style="width: 400px;">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h2 class="brand-text" style="font-family: 'Dancing Script', cursive; color: #ff6b6b;">Academia Legend</h2>
                    <p class="text-muted">Panel de Administración</p>
                </div>
                
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Admin Dashboard -->
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-white shadow-sm">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 px-4">
                    <h4 class="mb-0">
                        <i class="fas fa-dance text-primary"></i> Academia Legend - Panel de Administración
                    </h4>
                    <a href="?logout=1" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="nav-tabs-container bg-light border-bottom">
                    <div class="container-fluid">
                        <ul class="nav nav-tabs border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="#dashboard" data-tab="dashboard">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#enrollments" data-tab="enrollments">
                                    <i class="fas fa-users"></i> Inscripciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#contacts" data-tab="contacts">
                                    <i class="fas fa-envelope"></i> Contactos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#classes" data-tab="classes">
                                    <i class="fas fa-calendar"></i> Clases
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="p-4">
                    <!-- Dashboard Tab -->
                    <div id="dashboard" class="tab-content active">
                        <h2 class="mb-4">Dashboard</h2>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <h3><?php echo $stats['total_enrollments']; ?></h3>
                                        <p>Total Inscripciones</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card secondary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check fa-2x mb-2"></i>
                                        <h3><?php echo $stats['active_enrollments']; ?></h3>
                                        <p>Estudiantes Activos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-envelope fa-2x mb-2"></i>
                                        <h3><?php echo $stats['total_contacts']; ?></h3>
                                        <p>Total Contactos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card secondary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <h3><?php echo $stats['pending_enrollments']; ?></h3>
                                        <p>Pendientes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Inscripciones Recientes</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $recent_enrollments = array_slice($enrollments, -5);
                                        foreach (array_reverse($recent_enrollments) as $enrollment): 
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($enrollment['nombre']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $enrollment['timestamp']; ?></small>
                                                </div>
                                                <span class="badge bg-<?php echo $enrollment['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Contactos Recientes</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $recent_contacts = array_slice($contacts, 0, 5);
                                        foreach ($recent_contacts as $contact): 
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($contact['data']['nombre']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $contact['timestamp']; ?></small>
                                                </div>
                                                <small class="text-primary"><?php echo htmlspecialchars($contact['data']['tipo_clase']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enrollments Tab -->
                    <div id="enrollments" class="tab-content">
                        <h2 class="mb-4">Inscripciones</h2>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Clase</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['telefono']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['class_id']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($enrollment['timestamp'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $enrollment['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewDetails('<?php echo $enrollment['id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Contacts Tab -->
                    <div id="contacts" class="tab-content">
                        <h2 class="mb-4">Contactos</h2>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Interés</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($contact['data']['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($contact['data']['email']); ?></td>
                                            <td><?php echo htmlspecialchars($contact['data']['telefono'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($contact['data']['tipo_clase']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($contact['timestamp'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewMessage('<?php echo htmlspecialchars($contact['data']['mensaje']); ?>')">
                                                    <i class="fas fa-envelope-open"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Classes Tab -->
                    <div id="classes" class="tab-content">
                        <h2 class="mb-4">Clases</h2>
                        <div class="row">
                            <?php foreach ($classes as $class): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($class['name']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars(substr($class['description'], 0, 100)) . '...'; ?></p>
                                            <div class="d-flex justify-content-between">
                                                <span class="badge bg-primary"><?php echo $class['level']; ?></span>
                                                <span class="text-primary">$<?php echo $class['price']; ?></span>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">Capacidad: <?php echo $class['capacity']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mensaje de Contacto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageContent">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('[data-tab]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all nav links
                document.querySelectorAll('.nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                
                // Show selected tab
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                this.classList.add('active');
            });
        });
        
        function viewMessage(message) {
            document.getElementById('messageContent').textContent = message;
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        }
        
        function viewDetails(enrollmentId) {
            alert('Función de detalles en desarrollo para inscripción: ' + enrollmentId);
        }
    </script>

<?php endif; ?>

</body>
</html>