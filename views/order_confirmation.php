<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/paths.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . VIEWS_URL . '/login.php');
    exit();
}

// Get order number from URL
$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    header('Location: ' . VIEWS_URL . '/catalogo.php');
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.order_number = ? AND o.user_id = ?
    GROUP BY o.id
");
$stmt->bind_param("si", $order_number, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . VIEWS_URL . '/catalogo.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items
$items_stmt = $conn->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$items_stmt->bind_param("i", $order['id']);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}
$items_stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Legend Dance Academy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <style>
        /* Orange and Black Color Overrides */
        :root {
            --bs-primary: #ff6600;
            --bs-primary-rgb: 255, 102, 0;
            --bs-btn-hover-bg: #e55a00;
            --bs-btn-active-bg: #e55a00;
        }
        
        .brand-text {
            font-family: 'Dancing Script', cursive;
            font-weight: 700;
            font-size: 2.2rem;
            color: #ff6b35 !important;
            text-decoration: none;
        }
        .brand-text:hover {
            color: #e55a2b !important;
            text-decoration: none;
        }
        
        /* Force our orange color */
        .btn-primary {
            background-color: #ff6600 !important;
            border-color: #ff6600 !important;
            background-image: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #e55a00 !important;
            border-color: #e55a00 !important;
            background-image: linear-gradient(135deg, #e55a00 0%, #ff6600 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 102, 0, 0.3);
            color: white !important;
        }
        
        .btn-outline-primary {
            border-color: #ff6600 !important;
            color: #ff6600 !important;
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
        
        .navbar-nav .nav-link:hover {
            color: #ff6600 !important;
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
        
        .hero-confirmation {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 76px;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .order-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .payment-info {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .copy-button {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .copy-button:hover {
            background: #1e7e34;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php">
                <span class="brand-text">Legend</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php">Clases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/catalogo.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/ubicacion.php">Ubicación</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/contact.php">Contacto</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- User section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-welcome" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php#clases"><i class="fas fa-calendar me-2"></i>Mis Clases</a></li>
                            <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/cart.php"><i class="fas fa-shopping-cart me-2"></i>Mi Carrito</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-confirmation">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1 class="display-4 fw-bold mb-4">¡Pedido Confirmado!</h1>
                    <p class="lead">Tu pedido ha sido recibido exitosamente</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Confirmation -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Success Message -->
                    <div class="confirmation-card">
                        <h2 class="text-success mb-4">¡Gracias por tu pedido!</h2>
                        <p class="lead mb-4">Hemos recibido tu pedido #<strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
                        <p class="text-muted">Te hemos enviado un email de confirmación a <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong></p>
                        <div class="mt-4">
                            <span class="status-badge status-pending">
                                <i class="fas fa-clock me-2"></i>Estado: Pendiente
                            </span>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div class="order-details">
                        <h4 class="mb-4">Detalles del Pedido</h4>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Número de Pedido:</strong><br><?php echo htmlspecialchars($order['order_number']); ?></p>
                                <p><strong>Fecha:</strong><br><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Cliente:</strong><br><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong>Email:</strong><br><?php echo htmlspecialchars($order['customer_email']); ?></p>
                            </div>
                        </div>

                        <h5 class="mb-3">Productos Pedidos</h5>
                        <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="d-flex align-items-center">
                                <?php 
                                    // Normalize image paths: handle backslashes, absolute URLs, root-relative or local
                                    $img = $item['product_image'] ?? '';
                                    if (!empty($img)) { $img = str_replace('\\', '/', $img); }
                                    $isHttp = preg_match('/^https?:\/\//i', $img);
                                    $isRoot = (strlen($img) > 0 && $img[0] === '/');
                                    if (!$isHttp && !$isRoot && !empty($img)) { $img = '../' . ltrim($img, '/'); }
                                    if (empty($img)) { $img = 'https://via.placeholder.com/60x60?text=IMG'; }
                                ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <small class="text-muted d-block">Cantidad: <?php echo $item['quantity']; ?> × ₡<?php echo number_format($item['product_price'], 0, ',', '.'); ?></small>
                                </div>
                            </div>
                            <strong>₡<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-primary">₡<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <?php if ($order['payment_method'] === 'sinpe'): ?>
                    <div class="order-details">
                        <h4 class="mb-4">Información de Pago</h4>
                        
                        <div class="payment-info">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Pago con SINPE Móvil
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Número de teléfono:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <code id="sinpe-phone">+506 8411-8339</code>
                                        <button class="copy-button" onclick="copyToClipboard('sinpe-phone')">Copiar</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Beneficiario:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <code id="sinpe-name">Vanessa Mora</code>
                                        <button class="copy-button" onclick="copyToClipboard('sinpe-name')">Copiar</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>Monto a transferir:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <code id="sinpe-amount">₡<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></code>
                                        <button class="copy-button" onclick="copyToClipboard('sinpe-amount')">Copiar</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Concepto:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <code id="sinpe-concept">Pedido <?php echo htmlspecialchars($order['order_number']); ?></code>
                                        <button class="copy-button" onclick="copyToClipboard('sinpe-concept')">Copiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong> Una vez realizada la transferencia, nos pondremos en contacto contigo para confirmar el pago y coordinar la entrega.
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Shipping Information -->
                    <div class="order-details">
                        <h4 class="mb-4">Información de Entrega</h4>
                        <p><strong>Dirección:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        <p><strong>Teléfono:</strong><br><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <?php if (!empty($order['notes'])): ?>
                        <p><strong>Notas:</strong><br><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Next Steps -->
                    <div class="order-details">
                        <h4 class="mb-4">Próximos Pasos</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="me-3">
                                        <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6>1. Realiza el pago</h6>
                                        <p class="text-muted">Transfiere el monto total usando SINPE Móvil a la información proporcionada arriba.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="me-3">
                                        <i class="fas fa-phone fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6>2. Confirmación</h6>
                                        <p class="text-muted">Nos pondremos en contacto contigo para confirmar el pago recibido.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="me-3">
                                        <i class="fas fa-box fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6>3. Preparación</h6>
                                        <p class="text-muted">Prepararemos tu pedido con todo el cuidado y atención al detalle.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="me-3">
                                        <i class="fas fa-truck fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6>4. Entrega</h6>
                                        <p class="text-muted">Coordinaremos la entrega en la dirección que proporcionaste.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="<?php echo VIEWS_URL; ?>/catalogo.php" class="btn btn-primary me-3">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Continuar Comprando
                        </a>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supportModal">
                            <i class="fas fa-envelope me-2"></i>
                            Contactar Soporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-headset me-2"></i>Contactar soporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pedido</label>
                        <input type="text" class="form-control" id="supportOrder" value="<?php echo htmlspecialchars($order['order_number']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asunto (opcional)</label>
                        <input type="text" class="form-control" id="supportSubject" placeholder="Ej. Consulta sobre entrega">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea class="form-control" id="supportMessage" rows="5" placeholder="Describe tu consulta o problema" required></textarea>
                        <div class="form-text">Incluiremos tu nombre y correo para que soporte pueda responderte.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnSendSupport">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="text-primary mb-3">Legend Dance Academy</h5>
                    <p>Transformando vidas a través de la danza desde 2008</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-primary mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo VIEWS_URL; ?>/index.php" class="text-white-50">Inicio</a></li>
                        <li><a href="<?php echo VIEWS_URL; ?>/clases.php" class="text-white-50">Clases</a></li>
                        <li><a href="<?php echo VIEWS_URL; ?>/catalogo.php" class="text-white-50">Catálogo</a></li>
                        <li><a href="<?php echo VIEWS_URL; ?>/contact.php" class="text-white-50">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-primary mb-3">Contacto</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i>Calle Principal 123, Ciudad</p>
                    <p><i class="fas fa-phone me-2"></i>+506 8411-8339</p>
                    <p><i class="fas fa-envelope me-2"></i>info@legenddanceacademy.com</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                showSuccessMessage('Copiado al portapapeles');
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showSuccessMessage('Copiado al portapapeles');
            });
        }
        
        function showSuccessMessage(message) {
            // Simple alert for now - you can enhance this with a toast notification
            alert(message);
        }

        // Send support request
        document.addEventListener('DOMContentLoaded', function(){
            const btn = document.getElementById('btnSendSupport');
            if (!btn) return;
            btn.addEventListener('click', async function(){
                const order = document.getElementById('supportOrder').value.trim();
                const subject = document.getElementById('supportSubject').value.trim();
                const message = document.getElementById('supportMessage').value.trim();
                if (!message) { alert('Por favor escribe un mensaje.'); return; }
                btn.disabled = true; btn.innerText = 'Enviando...';
                try {
                    const res = await fetch('<?php echo BASE_URL; ?>/api/send_support.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_number: order, subject, message })
                    }).then(r=>r.json());
                    if (!res.success) throw new Error(res.message || 'No se pudo enviar');
                    alert('Tu mensaje fue enviado. Te responderemos pronto.');
                    const modalEl = document.getElementById('supportModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    document.getElementById('supportSubject').value='';
                    document.getElementById('supportMessage').value='';
                } catch(e){
                    alert('Error: ' + e.message);
                } finally {
                    btn.disabled = false; btn.innerText = 'Enviar';
                }
            });
        });
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    closeConnection($conn);
}
?>