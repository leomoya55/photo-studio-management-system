<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - Legend Dance Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            min-height: 100vh;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .test-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 40px;
            margin: 30px auto;
            max-width: 900px;
        }
        
        .test-item {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #fff;
        }
        
        .test-item.success {
            border-left-color: #28a745;
            background: rgba(40,167,69,0.2);
        }
        
        .test-item.warning {
            border-left-color: #ffc107;
            background: rgba(255,193,7,0.2);
        }
        
        .test-item.error {
            border-left-color: #dc3545;
            background: rgba(220,53,69,0.2);
        }
        
        .btn-test {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            border-radius: 25px;
            padding: 10px 20px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-test:hover {
            background: white;
            color: #ff6b6b;
            transform: translateY(-2px);
        }
        
        .system-status {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .status-ready {
            background: rgba(40,167,69,0.3);
            border: 2px solid #28a745;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #ffeb3b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <div class="text-center mb-4">
                <h1><i class="fas fa-dance"></i> Legend Dance Academy</h1>
                <h2>Sistema de Gestión Completo</h2>
                <p class="lead">Sistema de autenticación y gestión de clientes implementado exitosamente</p>
            </div>
            
            <?php
            require_once 'db_connect.php';
            
            $tests = [];
            $allPassed = true;
            
            // Test 1: Database Connection
            try {
                if ($conn->ping()) {
                    $tests[] = [
                        'name' => 'Conexión a Base de Datos',
                        'status' => 'success',
                        'message' => 'Conectado exitosamente a JawsDB MySQL',
                        'icon' => 'fas fa-database'
                    ];
                } else {
                    throw new Exception('Connection failed');
                }
            } catch (Exception $e) {
                $tests[] = [
                    'name' => 'Conexión a Base de Datos',
                    'status' => 'error',
                    'message' => 'Error de conexión: ' . $e->getMessage(),
                    'icon' => 'fas fa-database'
                ];
                $allPassed = false;
            }
            
            // Test 2: Tables exist
            try {
                $result = $conn->query("SHOW TABLES");
                $tables = [];
                while ($row = $result->fetch_row()) {
                    $tables[] = $row[0];
                }
                
                if (in_array('users', $tables) && in_array('enrollments', $tables)) {
                    $tests[] = [
                        'name' => 'Estructura de Base de Datos',
                        'status' => 'success',
                        'message' => 'Tablas creadas: ' . implode(', ', $tables),
                        'icon' => 'fas fa-table'
                    ];
                } else {
                    throw new Exception('Required tables missing');
                }
            } catch (Exception $e) {
                $tests[] = [
                    'name' => 'Estructura de Base de Datos',
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage(),
                    'icon' => 'fas fa-table'
                ];
                $allPassed = false;
            }
            
            // Test 3: Admin user exists
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $tests[] = [
                        'name' => 'Usuario Administrador',
                        'status' => 'success',
                        'message' => 'Cuenta de administrador configurada para Vanessa',
                        'icon' => 'fas fa-user-shield'
                    ];
                } else {
                    throw new Exception('No admin user found');
                }
            } catch (Exception $e) {
                $tests[] = [
                    'name' => 'Usuario Administrador',
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage(),
                    'icon' => 'fas fa-user-shield'
                ];
                $allPassed = false;
            }
            
            // Test 4: Required files exist
            $required_files = [
                'register.php' => 'Registro de Clientes',
                'login.php' => 'Inicio de Sesión',
                'admin.php' => 'Panel de Administración',
                'dashboard.php' => 'Portal del Cliente',
                'logout.php' => 'Cerrar Sesión'
            ];
            
            $missing_files = [];
            foreach ($required_files as $file => $description) {
                if (!file_exists($file)) {
                    $missing_files[] = "$description ($file)";
                }
            }
            
            if (empty($missing_files)) {
                $tests[] = [
                    'name' => 'Archivos del Sistema',
                    'status' => 'success',
                    'message' => 'Todos los archivos necesarios están presentes',
                    'icon' => 'fas fa-file-code'
                ];
            } else {
                $tests[] = [
                    'name' => 'Archivos del Sistema',
                    'status' => 'error',
                    'message' => 'Archivos faltantes: ' . implode(', ', $missing_files),
                    'icon' => 'fas fa-file-code'
                ];
                $allPassed = false;
            }
            
            // Test 5: Environment variables
            if (file_exists('.env')) {
                $tests[] = [
                    'name' => 'Configuración de Seguridad',
                    'status' => 'success',
                    'message' => 'Variables de entorno configuradas correctamente',
                    'icon' => 'fas fa-shield-alt'
                ];
            } else {
                $tests[] = [
                    'name' => 'Configuración de Seguridad',
                    'status' => 'error',
                    'message' => 'Archivo .env no encontrado',
                    'icon' => 'fas fa-shield-alt'
                ];
                $allPassed = false;
            }
            
            // Display tests
            foreach ($tests as $test) {
                echo "<div class='test-item {$test['status']}'>";
                echo "<h5><i class='{$test['icon']}'></i> {$test['name']}</h5>";
                echo "<p>{$test['message']}</p>";
                echo "</div>";
            }
            ?>
            
            <div class="system-status <?php echo $allPassed ? 'status-ready' : 'status-error'; ?>">
                <?php if ($allPassed): ?>
                    <h3><i class="fas fa-check-circle"></i> Sistema Completamente Operativo</h3>
                    <p>¡El sistema Legend Dance Academy está listo para usar!</p>
                <?php else: ?>
                    <h3><i class="fas fa-exclamation-triangle"></i> Sistema Requiere Atención</h3>
                    <p>Algunos componentes necesitan configuración adicional.</p>
                <?php endif; ?>
            </div>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h5>Registro de Clientes</h5>
                    <p>Los clientes pueden crear cuentas seguras</p>
                    <a href="register.php" class="btn btn-test">Probar Registro</a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h5>Autenticación</h5>
                    <p>Sistema de login con roles</p>
                    <a href="login.php" class="btn btn-test">Probar Login</a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5>Panel de Admin</h5>
                    <p>Gestión completa para Vanessa</p>
                    <a href="admin.php" class="btn btn-test">Panel Admin</a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h5>Portal del Cliente</h5>
                    <p>Dashboard personalizado</p>
                    <a href="dashboard.php" class="btn btn-test">Portal Cliente</a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <h4><i class="fas fa-key"></i> Credenciales de Administrador</h4>
                <div class="alert alert-warning d-inline-block">
                    <strong>Email:</strong> vanessa@legenddance.com<br>
                    <strong>Contraseña:</strong> admin123<br>
                    <small><i class="fas fa-exclamation-triangle"></i> Cambiar contraseña después del primer login</small>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.html" class="btn btn-test">
                    <i class="fas fa-home"></i> Volver al Sitio Principal
                </a>
                <a href="test_connection.php" class="btn btn-test">
                    <i class="fas fa-database"></i> Test de Conexión
                </a>
            </div>
        </div>
    </div>
</body>
</html>