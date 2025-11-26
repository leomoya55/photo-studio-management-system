<?php
session_start();
require_once '../config/db_connect.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = 'Todos los campos obligatorios deben ser completados.';
        $messageType = 'error';
    } elseif (!validateEmail($email)) {
        $message = 'Por favor ingresa un email válido.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Las contraseñas no coinciden.';
        $messageType = 'error';
    } else {
        // Check if email already exists
        $checkEmail = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Este email ya está registrado. <a href="login.php">Iniciar sesión</a>';
            $messageType = 'error';
        } else {
            // Hash password and insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Try extended insert to ensure is_active and timestamps are set for dashboard stats
            $ok = false; $stmt = null; $insertUser = '';
            $insertExtended = "INSERT INTO users (first_name, last_name, email, password, phone, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'customer', 1, NOW(), NOW())";
            if ($stmt = $conn->prepare($insertExtended)) {
                $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $phone);
                $ok = $stmt->execute();
                if(!$ok){ $err1 = $stmt->error; }
                $stmt->close();
                $insertUser = $insertExtended;
            }
            if (!$ok) {
                // Fallback to legacy minimal insert
                $insertLegacy = "INSERT INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertLegacy);
                if ($stmt) {
                    $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $phone);
                    $ok = $stmt->execute();
                    $insertUser = $insertLegacy;
                    $stmt->close();
                }
            }
            
            if ($ok) {
                // Get the new user's ID for auto-login
                $newUserId = $conn->insert_id;
                
                // Auto-login the user
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer'; // Default role for new users
                $_SESSION['is_active'] = 1;
                
                // Redirect to homepage
                header('Location: index.php');
                exit();
            } else {
                $message = 'Error en el registro. Por favor intenta de nuevo.';
                $messageType = 'error';
            }
        }
        if (isset($stmt) && $stmt) { $stmt->close(); }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Vale V Photography</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-logo h2 {
            color: #000000;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .alert {
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #111111 0%, #444444 100%);
            transform: translateY(-2px);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="brand-logo">
                <h2><i class="fas fa-star"></i> Vale V Photography</h2>
                <p class="text-muted">Crea tu cuenta para inscribirte en nuestras clases</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user me-1"></i>Nombre *
                        </label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user me-1"></i>Apellidos *
                        </label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-1"></i>Email *
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone me-1"></i>Teléfono
                    </label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                           placeholder="+506-1234-5678">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Contraseña *
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           minlength="6" required>
                    <small class="text-muted">Mínimo 6 caracteres</small>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Confirmar Contraseña *
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                </button>
            </form>

            <div class="text-center">
                <p class="mb-2">¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none">Iniciar Sesión</a></p>
                <p><a href="index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Volver al inicio
                </a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

<?php closeConnection($conn); ?>