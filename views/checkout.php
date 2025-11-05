<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/paths.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . VIEWS_URL . '/login.php');
    exit();
}

// Set up user session variables
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = 'customer';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
    $userEmail = $_SESSION['email'];
}

// Get cart items
function getCartItems($user_id, $conn) {
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, ci.id, ci.product_id, ci.product_name, ci.product_price, ci.product_image, ci.quantity,
               (ci.product_price * ci.quantity) as subtotal
        FROM cart c
        JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['subtotal'];
    }
    
    $stmt->close();
    return ['items' => $items, 'total' => $total];
}

$cartData = getCartItems($_SESSION['user_id'], $conn);

// Redirect if cart is empty
if (empty($cartData['items'])) {
    header('Location: catalogo.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Legend Dance Academy</title>
    
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
        .user-welcome {
            color: #333 !important;
            font-weight: 500;
        }
        .user-welcome:hover {
            color: #ff6b35 !important;
        }
        .nav-link {
            color: #333 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #ff6b35 !important;
        }
        .btn-primary {
            background-color: #ff6b35;
            border-color: #ff6b35;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #e55a2b;
            border-color: #e55a2b;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #000;
            transform: translateY(-2px);
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: #fff !important;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .dropdown-item {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #ff6b35;
        }
        .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        .checkout-step {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .step-number {
            background: #ff6600;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .delivery-options {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        
        .delivery-option {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .delivery-option:hover {
            border-color: #ff6600;
            background-color: rgba(255, 102, 0, 0.05);
        }
        
        .delivery-option input[type="radio"]:checked ~ .form-check-label {
            color: #ff6600;
            font-weight: 600;
        }
        
        .delivery-option input[type="radio"]:checked {
            background-color: #ff6600;
            border-color: #ff6600;
        }
        
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover,
        .payment-option.selected {
            border-color: #ff6600;
            background-color: rgba(255, 102, 0, 0.05);
        }
        
        .payment-option.selected {
            background-color: rgba(255, 102, 0, 0.1);
        }
        
        .sinpe-info {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }
        
        .sinpe-info.show {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
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

    <!-- Main Content -->
    <section class="py-5" style="margin-top: 80px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Order Summary -->
                    <div class="checkout-step">
                        <div class="d-flex align-items-center mb-4">
                            <div class="step-number">1</div>
                            <h4 class="mb-0">Resumen del Pedido</h4>
                        </div>
                        
                        <div class="order-summary">
                            <?php foreach ($cartData['items'] as $item): ?>
                            <?php 
                                // Normalize image path for Windows/backslashes and relative paths
                                $img = $item['product_image'] ?? '';
                                if (!empty($img)) {
                                    // Convert backslashes to forward slashes
                                    $img = str_replace('\\', '/', $img);
                                }
                                $isHttp = preg_match('/^https?:\/\//i', $img);
                                $isRoot = (strlen($img) > 0 && $img[0] === '/');
                                if (!$isHttp && !$isRoot && !empty($img)) {
                                    $img = '../' . ltrim($img, '/');
                                }
                                if (empty($img)) {
                                    $img = 'https://via.placeholder.com/50?text=IMG';
                                }
                            ?>
                            <div class="order-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <small class="text-muted d-block">Cantidad: <?php echo $item['quantity']; ?></small>
                                    </div>
                                </div>
                                <strong>₡<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong>
                            </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span class="order-subtotal">₡<?php echo number_format($cartData['total'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span class="order-delivery">Gratis</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong class="text-primary order-total">₡<?php echo number_format($cartData['total'], 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="checkout-step">
                        <div class="d-flex align-items-center mb-4">
                            <div class="step-number">2</div>
                            <h4 class="mb-0">Información del Cliente</h4>
                        </div>
                        
                        <form id="checkout-form" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_SESSION['first_name']); ?>" autocomplete="given-name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_SESSION['last_name']); ?>" autocomplete="family-name" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" autocomplete="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+506 1234-5678" autocomplete="tel" required>
                                </div>
                            </div>
                            
                            <!-- Delivery Options -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Opciones de Entrega</label>
                                <div class="delivery-options">
                                    <div class="delivery-option">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delivery_type" id="pickup" value="pickup" checked>
                                            <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="pickup">
                                                <div>
                                                    <strong>Recoger en Academia</strong>
                                                    <p class="mb-0 text-muted small">Retira tu pedido directamente en Legend Dance Academy</p>
                                                </div>
                                                <span class="badge bg-success">GRATIS</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="delivery-option">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delivery_type" id="delivery" value="delivery">
                                            <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="delivery">
                                                <div>
                                                    <strong>Dirección de Entrega</strong>
                                                    <p class="mb-0 text-muted small">Entrega a domicilio (área cercana a la academia)</p>
                                                </div>
                                                <span class="badge bg-warning text-dark">₡3,000</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delivery Address (hidden by default) -->
                            <div id="delivery-address-section" class="mb-3" style="display: none;">
                                <label for="address" class="form-label">Dirección de Entrega</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Dirección completa para la entrega (solo áreas cercanas a la academia)" autocomplete="street-address"></textarea>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    La entrega está disponible solo para áreas cercanas a Legend Dance Academy
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas Adicionales (Opcional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Instrucciones especiales para la entrega" autocomplete="off"></textarea>
                            </div>
                        </form>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-step">
                        <div class="d-flex align-items-center mb-4">
                            <div class="step-number">3</div>
                            <h4 class="mb-0">Método de Pago</h4>
                        </div>
                        
                        <div class="payment-option selected" data-payment="sinpe">
                            <div class="d-flex align-items-center">
                                <i class="fab fa-whatsapp fa-2x text-success me-3"></i>
                                <div>
                                    <h5 class="mb-1">SINPE Móvil</h5>
                                    <p class="text-muted mb-0">Transferencia instantánea y segura</p>
                                </div>
                                <div class="ms-auto">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                            
                            <div class="sinpe-info show">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-mobile-alt me-2"></i>Datos para la transferencia:</h6>
                                        <p class="mb-2"><strong>Número:</strong> +506 8411-8339</p>
                                        <p class="mb-2"><strong>Nombre:</strong> Vanessa Mora</p>
                                        <p class="mb-3"><strong>Concepto:</strong> Pedido Legend Dance Academy</p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle me-2"></i>Instrucciones:</h6>
                                            <ol class="mb-0 ps-3">
                                                <li>Realiza la transferencia SINPE</li>
                                                <li>Toma captura del comprobante</li>
                                                <li>Súbelo aquí abajo</li>
                                                <li>Confirma tu pedido</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="sinpe_proof" class="form-label">
                                        <strong>Subir comprobante de transferencia:</strong>
                                    </label>
                                    <input type="file" class="form-control" id="sinpe_proof" name="sinpe_proof" 
                                           accept="image/*,.pdf" onchange="validateProofFile(this)">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Formatos permitidos: JPG, PNG, PDF (máx. 5MB)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Final Summary -->
                    <div class="checkout-step sticky-top" style="top: 100px;">
                        <h5 class="mb-4">Resumen Final</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="final-subtotal">₡<?php echo number_format($cartData['total'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <span class="final-delivery">Gratis</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-primary final-total">₡<?php echo number_format($cartData['total'], 0, ',', '.'); ?></strong>
                        </div>
                        
                        <div class="mb-4">
                            <div class="alert alert-success">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Pago 100% seguro con SINPE Móvil</strong>
                            </div>
                        </div>
                        
                        <button id="confirm-order-btn" class="btn btn-success w-100 py-3 mb-3" title="Procesar pedido y realizar pago">
                            <i class="fas fa-check-circle me-2"></i>
                            Confirmar Pedido
                        </button>
                        
                        <a href="<?php echo VIEWS_URL; ?>/cart.php" class="btn btn-outline-primary w-100" title="Regresar al carrito de compras">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Carrito
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                    <p><i class="fas fa-phone me-2"></i> +506 8411-8339</p>
                    <p><i class="fas fa-envelope me-2"></i> info@legenddanceacademy.com</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CLEAN DELIVERY SCRIPT -->
    <script>
        const originalSubtotal = <?php echo $cartData['total']; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up delivery options with subtotal:', originalSubtotal);
            setupDeliveryOptions();
            setupOrderConfirmation();
        });
        
        function setupDeliveryOptions() {
            const pickupRadio = document.getElementById('pickup');
            const deliveryRadio = document.getElementById('delivery');
            const addressSection = document.getElementById('delivery-address-section');
            const addressField = document.getElementById('address');
            
            if (pickupRadio) {
                pickupRadio.addEventListener('change', function() {
                    if (this.checked) {
                        console.log('Pickup selected');
                        if (addressSection) addressSection.style.display = 'none';
                        if (addressField) {
                            addressField.required = false;
                            addressField.value = '';
                        }
                        updateTotals('pickup');
                    }
                });
            }
            
            if (deliveryRadio) {
                deliveryRadio.addEventListener('change', function() {
                    if (this.checked) {
                        console.log('Delivery selected');
                        if (addressSection) addressSection.style.display = 'block';
                        if (addressField) addressField.required = true;
                        updateTotals('delivery');
                    }
                });
            }
        }
        
        function updateTotals(deliveryType) {
            console.log('Updating totals for:', deliveryType);
            
            // Update order summary (left side)
            const deliveryElement = document.querySelector('.order-delivery');
            const totalElement = document.querySelector('.order-total');
            
            // Update final summary (right side)
            const finalDeliveryElement = document.querySelector('.final-delivery');
            const finalTotalElement = document.querySelector('.final-total');
            
            let deliveryCost = 0;
            let deliveryText = 'Gratis';
            
            if (deliveryType === 'delivery') {
                deliveryCost = 3000;
                deliveryText = '₡3.000';
            }
            
            const newTotal = originalSubtotal + deliveryCost;
            const totalText = '₡' + newTotal.toLocaleString('es-CR');
            
            // Update left side
            if (deliveryElement) deliveryElement.textContent = deliveryText;
            if (totalElement) totalElement.textContent = totalText;
            
            // Update right side
            if (finalDeliveryElement) finalDeliveryElement.textContent = deliveryText;
            if (finalTotalElement) finalTotalElement.textContent = totalText;
            
            console.log('Updated - Delivery:', deliveryText, 'Total:', totalText);
        }
        
        function setupOrderConfirmation() {
            const confirmBtn = document.getElementById('confirm-order-btn');
            if (!confirmBtn) return;
            
            confirmBtn.addEventListener('click', function() {
                const form = document.getElementById('checkout-form');
                const formData = new FormData(form);
                
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                const sinpeProof = document.getElementById('sinpe_proof');
                if (!sinpeProof.files.length) {
                    showErrorMessage('Por favor adjunta el comprobante de SINPE');
                    return;
                }
                // Append the SINPE proof file explicitly (input is outside the form)
                formData.append('sinpe_proof', sinpeProof.files[0]);
                
                const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
                const deliveryCost = deliveryType === 'delivery' ? 3000 : 0;
                const totalWithDelivery = originalSubtotal + deliveryCost;
                
                formData.append('payment_method', 'sinpe');
                formData.append('delivery_type', deliveryType);
                formData.append('total_amount', totalWithDelivery);
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                
                fetch('<?php echo VIEWS_URL; ?>/process_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '<?php echo VIEWS_URL; ?>/order_confirmation.php?order=' + data.order_number;
                    } else {
                        showErrorMessage(data.message || 'Error procesando la orden');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check-circle me-2"></i>Confirmar Pedido';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('Error de conexión. Intenta nuevamente.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check-circle me-2"></i>Confirmar Pedido';
                });
            });
        }
        
        function validateProofFile(input) {
            const file = input.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                
                if (!allowedTypes.includes(file.type)) {
                    showErrorMessage('Formato no permitido. Solo JPG, PNG o PDF.');
                    input.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    showErrorMessage('Archivo demasiado grande. Máximo 5MB.');
                    input.value = '';
                    return;
                }
            }
        }
        
        function showErrorMessage(message) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                z-index: 9999;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            toast.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 5000);
        }
    </script>
</body>
</html>

<?php 
if (isset($conn)) {
    $conn->close();
}
?>