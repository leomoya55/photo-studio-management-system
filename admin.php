<?php
/**
 * Academia Legend - Enhanced Admin Dashboard
 * Database-integrated admin interface for managing customers and enrollments
 */

session_start();
require_once 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests for customer management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'delete_customer':
            $customer_id = (int)$_POST['customer_id'];
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
            $stmt->bind_param("i", $customer_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar cliente']);
            }
            $stmt->close();
            exit;
            
        case 'toggle_customer_status':
            $customer_id = (int)$_POST['customer_id'];
            $current_status = $_POST['status'];
            $new_status = ($current_status === 'active' || $current_status === '1') ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'");
            $stmt->bind_param("ii", $new_status, $customer_id);
            
            if ($stmt->execute()) {
                $status_text = $new_status ? 'active' : 'inactive';
                echo json_encode(['success' => true, 'status' => $status_text]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
            }
            $stmt->close();
            exit;
            
        case 'get_customer_details':
            $customer_id = (int)$_POST['customer_id'];
            $stmt = $conn->prepare("
                SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as full_name,
                       u.email, u.phone, u.is_active as status, u.created_at,
                       COUNT(e.id) as total_enrollments,
                       COUNT(CASE WHEN e.status = 'active' THEN 1 END) as active_enrollments
                FROM users u 
                LEFT JOIN enrollments e ON u.id = e.user_id 
                WHERE u.id = ? AND u.role = 'customer'
                GROUP BY u.id
            ");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($customer = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'customer' => $customer]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
            }
            $stmt->close();
            exit;
    }
}

// Load data from database
function getDashboardStats() {
    global $conn;
    
    $stats = [];
    
    // Total customers
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $stats['total_customers'] = $result->fetch_assoc()['count'];
    
    // Active customers
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND is_active = 1");
    $stats['active_customers'] = $result->fetch_assoc()['count'];
    
    // Total enrollments
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments");
    $stats['total_enrollments'] = $result->fetch_assoc()['count'];
    
    // Active enrollments
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
    $stats['active_enrollments'] = $result->fetch_assoc()['count'];
    
    // Recent registrations (last 7 days)
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_registrations'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

function getRecentCustomers($limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) as full_name, email, phone, is_active as status, created_at 
        FROM users 
        WHERE role = 'customer' 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    $stmt->close();
    return $customers;
}

function getAllCustomers() {
    global $conn;
    
    $result = $conn->query("
        SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
               u.email, u.phone, u.is_active as status, u.created_at,
               COUNT(e.id) as total_enrollments,
               COUNT(CASE WHEN e.status = 'active' THEN 1 END) as active_enrollments
        FROM users u 
        LEFT JOIN enrollments e ON u.id = e.user_id 
        WHERE u.role = 'customer' 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ");
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    return $customers;
}

function getRecentEnrollments($limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email 
        FROM enrollments e 
        JOIN users u ON e.user_id = u.id 
        ORDER BY e.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
    
    $stmt->close();
    return $enrollments;
}

function getAllEnrollments() {
    global $conn;
    
    $result = $conn->query("
        SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email, u.phone 
        FROM enrollments e 
        JOIN users u ON e.user_id = u.id 
        ORDER BY e.created_at DESC
    ");
    
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
    
    return $enrollments;
}

// Get data for dashboard
$stats = getDashboardStats();
$recent_customers = getRecentCustomers();
$recent_enrollments = getRecentEnrollments();
$all_customers = getAllCustomers();
$all_enrollments = getAllEnrollments();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Academia Legend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --legend-red: #ff6b6b;
            --legend-teal: #4ecdc4;
            --legend-dark: #2c3e50;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--legend-red) 0%, var(--legend-teal) 100%);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 25px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-icon.customers { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.enrollments { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.active { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.recent { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        
        .table-card .card-header {
            background: var(--legend-red);
            color: white;
            border: none;
            padding: 20px 25px;
        }
        
        .btn-legend {
            background: linear-gradient(135deg, var(--legend-red) 0%, var(--legend-teal) 100%);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-legend:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
            color: white;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--legend-red) 0%, var(--legend-teal) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
                position: fixed;
                z-index: 1000;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar position-fixed">
        <div class="p-4">
            <h4 class="mb-4">
                <i class="fas fa-dance"></i>
                Academia Legend
            </h4>
            <p class="small mb-4">Panel de Administración - Vanessa</p>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" data-section="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a class="nav-link" href="#" data-section="customers">
                <i class="fas fa-users"></i>
                Gestión de Clientes
            </a>
            <a class="nav-link" href="#" data-section="enrollments">
                <i class="fas fa-user-plus"></i>
                Inscripciones
            </a>
            <a class="nav-link" href="#" data-section="classes">
                <i class="fas fa-calendar-alt"></i>
                Clases
            </a>
            <a class="nav-link" href="#" data-section="reports">
                <i class="fas fa-chart-bar"></i>
                Reportes
            </a>
        </nav>
        
        <div class="position-absolute bottom-0 w-100 p-4">
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> Vanessa
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-home"></i> Ver Sitio</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard Principal</h2>
                <span class="text-muted">Última actualización: <?php echo date('d/m/Y H:i'); ?></span>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-icon customers mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_customers']; ?></h3>
                            <p class="text-muted mb-0">Total Clientes</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-icon active mx-auto">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['active_customers']; ?></h3>
                            <p class="text-muted mb-0">Clientes Activos</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-icon enrollments mx-auto">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['total_enrollments']; ?></h3>
                            <p class="text-muted mb-0">Total Inscripciones</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-icon recent mx-auto">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $stats['recent_registrations']; ?></h3>
                            <p class="text-muted mb-0">Nuevos (7 días)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card table-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Clientes Recientes</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <?php foreach ($recent_customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="customer-avatar me-3">
                                                        <?php echo strtoupper(substr($customer['full_name'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($customer['full_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="status-badge status-<?php echo $customer['status'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $customer['status'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card table-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Inscripciones Recientes</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($enrollment['full_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($enrollment['class_name']); ?></small>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Customer Management Section -->
        <div id="customers" class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gestión de Clientes</h2>
                <button class="btn btn-legend" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-user-plus"></i> Añadir Cliente
                </button>
            </div>
            
            <div class="card table-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Lista de Clientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="customersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Contacto</th>
                                    <th>Inscripciones</th>
                                    <th>Estado</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                <?php echo strtoupper(substr($customer['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($customer['full_name']); ?></h6>
                                                <small class="text-muted">ID: <?php echo $customer['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <small class="d-block"><?php echo htmlspecialchars($customer['email']); ?></small>
                                            <small class="text-muted"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $customer['active_enrollments']; ?> activa(s)</span>
                                        <br>
                                        <small class="text-muted"><?php echo $customer['total_enrollments']; ?> total</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $customer['status'] ? 'active' : 'inactive'; ?>" id="status-<?php echo $customer['id']; ?>">
                                            <?php echo $customer['status'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewCustomerDetails(<?php echo $customer['id']; ?>)" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="toggleCustomerStatus(<?php echo $customer['id']; ?>, '<?php echo $customer['status'] ? 'active' : 'inactive'; ?>')" title="Cambiar Estado">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['full_name']); ?>')" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enrollments Section -->
        <div id="enrollments" class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gestión de Inscripciones</h2>
            </div>
            
            <div class="card table-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Lista de Inscripciones</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Clase</th>
                                    <th>Contacto</th>
                                    <th>Estado</th>
                                    <th>Fecha Inscripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_enrollments as $enrollment): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                <?php echo strtoupper(substr($enrollment['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($enrollment['full_name']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollment['class_name']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <small class="d-block"><?php echo htmlspecialchars($enrollment['email']); ?></small>
                                            <small class="text-muted"><?php echo htmlspecialchars($enrollment['phone'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                            <?php echo ucfirst($enrollment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($enrollment['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" title="Marcar Completado">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Classes Section -->
        <div id="classes" class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gestión de Clases</h2>
                <button class="btn btn-legend">
                    <i class="fas fa-plus"></i> Nueva Clase
                </button>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Esta sección estará disponible en la próxima actualización para gestionar las clases y horarios.
            </div>
        </div>
        
        <!-- Reports Section -->
        <div id="reports" class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reportes y Estadísticas</h2>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card table-card">
                        <div class="card-header">
                            <h5 class="mb-0">Resumen Mensual</h5>
                        </div>
                        <div class="card-body">
                            <p>Nuevos clientes este mes: <strong><?php echo $stats['recent_registrations']; ?></strong></p>
                            <p>Inscripciones activas: <strong><?php echo $stats['active_enrollments']; ?></strong></p>
                            <p>Total de clientes: <strong><?php echo $stats['total_customers']; ?></strong></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card table-card">
                        <div class="card-header">
                            <h5 class="mb-0">Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-legend mb-2 w-100">
                                <i class="fas fa-download"></i> Exportar Lista de Clientes
                            </button>
                            <button class="btn btn-outline-secondary mb-2 w-100">
                                <i class="fas fa-envelope"></i> Enviar Newsletter
                            </button>
                            <button class="btn btn-outline-info w-100">
                                <i class="fas fa-chart-bar"></i> Generar Reporte Completo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Añadir Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-info">
                        <i class="fas fa-info-circle"></i>
                        Los clientes pueden registrarse directamente en la página de registro. 
                        Esta función manual estará disponible en futuras actualizaciones.
                    </p>
                    <a href="register.php" class="btn btn-legend" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ir a Página de Registro
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation functionality
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Hide all sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Remove active class from all nav links
                document.querySelectorAll('.nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                
                // Show selected section
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
                this.classList.add('active');
            });
        });
        
        // Customer management functions
        function viewCustomerDetails(customerId) {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_customer_details&customer_id=${customerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const customer = data.customer;
                    document.getElementById('customerDetailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Información Personal</h6>
                                <p><strong>Nombre:</strong> ${customer.full_name}</p>
                                <p><strong>Email:</strong> ${customer.email}</p>
                                <p><strong>Teléfono:</strong> ${customer.phone || 'N/A'}</p>
                                <p><strong>Fecha de registro:</strong> ${new Date(customer.created_at).toLocaleDateString('es-ES')}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Estadísticas</h6>
                                <p><strong>Estado:</strong> <span class="status-badge status-${customer.status}">${customer.status}</span></p>
                                <p><strong>Total inscripciones:</strong> ${customer.total_enrollments}</p>
                                <p><strong>Inscripciones activas:</strong> ${customer.active_enrollments}</p>
                            </div>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('customerDetailsModal')).show();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los detalles del cliente');
            });
        }
        
        function toggleCustomerStatus(customerId, currentStatus) {
            if (confirm('¿Estás segura de que quieres cambiar el estado de este cliente?')) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_customer_status&customer_id=${customerId}&status=${currentStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusElement = document.getElementById(`status-${customerId}`);
                        statusElement.className = `status-badge status-${data.status}`;
                        statusElement.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        
                        // Update the onclick attribute for the toggle button
                        const toggleButton = document.querySelector(`button[onclick*="toggleCustomerStatus(${customerId}"]`);
                        if (toggleButton) {
                            toggleButton.setAttribute('onclick', `toggleCustomerStatus(${customerId}, '${data.status}')`);
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar el estado del cliente');
                });
            }
        }
        
        function deleteCustomer(customerId, customerName) {
            if (confirm(`¿Estás segura de que quieres eliminar permanentemente al cliente "${customerName}"? Esta acción no se puede deshacer.`)) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_customer&customer_id=${customerId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Refresh the page to update the customer list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el cliente');
                });
            }
        }
        
        // Auto-refresh dashboard every 30 seconds when on dashboard tab
        setInterval(function() {
            if (document.getElementById('dashboard').classList.contains('active')) {
                // Optional: Add subtle refresh indicator or update stats
                console.log('Dashboard auto-refresh check');
            }
        }, 30000);
    </script>

</body>
</html>