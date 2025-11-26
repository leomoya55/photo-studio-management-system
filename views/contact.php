<?php
session_start();

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session_manager.php';

$config = require __DIR__ . '/../config/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && (($_SESSION['role'] ?? '') === 'admin');

if ($isAdmin) {
    header('Location: ' . ADMIN_URL . '/admin.php?view=mensajes');
    exit();
}

$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$userName = $isLoggedIn ? trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) : '';
$apiEndpoint = url_join(BASE_URL, 'api/support_messages.php');

if (!function_exists('safeText')) {
    function safeText($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$contactDetails = $config['contact'] ?? [];
$socialLinks = $config['social'] ?? [];
$defaultSubject = isset($_GET['asunto']) ? safeText((string)$_GET['asunto']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/images/favicon.svg">
    <title>Contacto - Vale V Photography</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f5f3;
        }
        #mainNav {
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(8px);
        }
        #mainNav.scrolled {
            background: #ffffff;
        }
        .contact-hero {
            padding-top: 120px;
            padding-bottom: 60px;
            background: linear-gradient(140deg, #111827 0%, #1f2937 65%, #374151 100%);
            color: rgba(255,255,255,0.9);
            position: relative;
            overflow: hidden;
        }
        .contact-hero::after {
            content: '';
            position: absolute;
            inset: -20% 30% auto;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(17,24,39,0) 70%);
            border-radius: 50%;
            opacity: 0.6;
        }
        .contact-hero h1 {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .contact-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
            font-size: 0.85rem;
        }
        .contact-card {
            border: 1px solid rgba(17, 24, 39, 0.06);
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 24px 50px rgba(17, 24, 39, 0.08);
            overflow: hidden;
        }
        .contact-card .card-body {
            padding: 2.25rem;
        }
        .contact-info-card {
            border-radius: 22px;
            background: linear-gradient(180deg, #fff 0%, #f1efee 100%);
            border: 1px solid rgba(17, 24, 39, 0.05);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.65);
        }
        .contact-info-card .icon-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #111827;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
        }
        .form-control, .form-select {
            border-radius: 14px;
            border: 1px solid rgba(17, 24, 39, 0.12);
            padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #111827;
            box-shadow: 0 0 0 0.15rem rgba(17, 24, 39, 0.2);
        }
        .btn-dark {
            border-radius: 14px;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .support-wrapper {
            border-radius: 22px;
            background: #ffffff;
            border: 1px solid rgba(17,24,39,0.06);
            box-shadow: 0 28px 50px rgba(17,24,39,0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .support-thread {
            height: 380px;
            overflow-y: auto;
            padding: 1.75rem;
            background: linear-gradient(180deg, #fbfbfc 0%, #f7f6f5 100%);
        }
        .message-bubble {
            max-width: 80%;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            position: relative;
            box-shadow: 0 12px 26px rgba(17,24,39,0.08);
        }
        .message-bubble.from-user {
            margin-left: auto;
            background: #111827;
            color: rgba(255,255,255,0.9);
            border-bottom-right-radius: 6px;
        }
        .message-bubble.from-admin {
            margin-right: auto;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid rgba(17,24,39,0.08);
            border-bottom-left-radius: 6px;
        }
        .message-meta {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            margin-bottom: 0.35rem;
            opacity: 0.75;
        }
        .message-subject {
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .message-body {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .support-form {
            border-top: 1px solid rgba(17,24,39,0.08);
            background: rgba(255,255,255,0.98);
            padding: 1.75rem;
        }
        .support-status {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.85rem;
            margin-top: 0.75rem;
        }
        .support-status-muted {
            background: rgba(15,23,42,0.08);
            color: #4b5563;
        }
        .support-status-success {
            background: rgba(34,197,94,0.18);
            color: #166534;
        }
        .support-status-warning {
            background: rgba(250,204,21,0.2);
            color: #854d0e;
        }
        .support-status-error {
            background: rgba(248,113,113,0.25);
            color: #b91c1c;
        }
        .support-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            color: #4b5563;
        }
        .support-loading .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.18rem;
        }
        .support-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
        @media (max-width: 991px) {
            .contact-card .card-body {
                padding: 1.75rem;
            }
            .support-thread {
                height: 320px;
                padding: 1.25rem;
            }
            .message-bubble {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
        <div class="container py-2">
            <a class="navbar-brand fw-semibold" href="<?php echo VIEWS_URL; ?>/index.php">
                <i class="fas fa-camera-retro me-2"></i>Vale V Photography
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php">Sesiones</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo VIEWS_URL; ?>/catalogo.php">Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo VIEWS_URL; ?>/contact.php">Contacto</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="btn btn-dark rounded-pill px-3" href="<?php echo VIEWS_URL; ?>/dashboard.php">Mi cuenta</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo VIEWS_URL; ?>/login.php">Ingresar</a></li>
                        <li class="nav-item">
                            <a class="btn btn-dark rounded-pill px-3" href="<?php echo VIEWS_URL; ?>/register.php">Crear cuenta</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="contact-hero">
        <div class="container position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="contact-badge"><i class="fas fa-envelope-open-text"></i> Centro de mensajes</div>
                    <h1 class="display-5 mt-3 mb-3">Estamos listos para ayudarte con tu próxima sesión</h1>
                    <p class="lead text-white-50 mb-4"><?php echo $isLoggedIn ? 'Comparte tus ideas, solicita cambios o consulta sobre tus reservaciones. Nuestro equipo responde desde este mismo panel.' : 'Comparte los detalles de tu proyecto fotográfico y te responderemos en menos de un día hábil.'; ?></p>
                    <div class="d-flex flex-wrap gap-3 text-white-50">
                        <div><i class="fas fa-clock me-2"></i>Atención <?php echo safeText($contactDetails['hours']['monday_friday'] ?? '09:00 - 21:00'); ?></div>
                        <div><i class="fas fa-phone me-2"></i><?php echo safeText($contactDetails['phone'] ?? '+506 8676-4740'); ?></div>
                        <div><i class="fas fa-map-marker-alt me-2"></i><?php echo safeText($contactDetails['address'] ?? 'San José, Costa Rica'); ?></div>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <img src="<?php echo ASSETS_URL; ?>/images/contact-hero-placeholder.jpg" class="img-fluid rounded-4 shadow-lg" alt="Estudio Vale V Photography" onerror="this.style.display='none';">
                </div>
            </div>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <?php if ($isLoggedIn): ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="support-wrapper" data-support-messages data-role="user" data-api="<?php echo safeText($apiEndpoint); ?>" data-user-id="<?php echo $userId; ?>">
                            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                                <div>
                                    <h2 class="h4 mb-0">Tu conversación con el estudio</h2>
                                    <small class="text-muted">Respuestas directas desde el equipo de Vale V Photography</small>
                                </div>
                                <div class="support-loading d-none" data-loading>
                                    <div class="spinner-border" role="status"></div>
                                    <span>Cargando mensajes...</span>
                                </div>
                            </div>
                            <div class="support-thread" data-message-list></div>
                            <div class="support-empty" data-empty-state>
                                <i class="fas fa-comments mb-3" style="font-size: 2rem;"></i>
                                <p class="mb-1">Aún no hay mensajes.</p>
                                <small class="text-muted">Escribe tu primera consulta y nuestro equipo responderá aquí.</small>
                            </div>
                            <div class="support-form">
                                <form data-support-form>
                                    <div class="mb-3">
                                        <label for="supportSubject" class="form-label">Asunto (opcional)</label>
                                        <input type="text" class="form-control" id="supportSubject" name="subject" maxlength="120" value="<?php echo $defaultSubject; ?>" placeholder="Ej. Consulta sobre mi próxima sesión">
                                    </div>
                                    <div class="mb-3">
                                        <label for="supportMessage" class="form-label">Mensaje</label>
                                        <textarea class="form-control" id="supportMessage" name="message" rows="4" maxlength="2000" placeholder="Comparte detalles o preguntas específicas sobre tus sesiones" required></textarea>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <button type="submit" class="btn btn-dark">
                                            <span class="me-2"><i class="fas fa-paper-plane"></i></span>Enviar mensaje
                                        </button>
                                        <div class="support-status support-status-muted d-none" data-support-status></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="contact-info-card h-100 p-4">
                            <h3 class="h5">¿Qué puedo enviar desde aquí?</h3>
                            <p class="text-muted">Utiliza este espacio para coordinar detalles de tu sesión, gestionar cambios o compartir referencias visuales.</p>
                            <ul class="list-unstyled d-grid gap-3">
                                <li class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-calendar-check"></i></div>
                                    <div>
                                        <strong>Reservaciones y pagos</strong>
                                        <p class="text-muted mb-0 small">Confirma fechas, métodos de pago o solicita recordatorios personalizados.</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-lightbulb"></i></div>
                                    <div>
                                        <strong>Ideas creativas</strong>
                                        <p class="text-muted mb-0 small">Comparte opciones de vestuario, locaciones o referencias visuales para tu producción.</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-file-image"></i></div>
                                    <div>
                                        <strong>Postproducción</strong>
                                        <p class="text-muted mb-0 small">Solicita ajustes a tus imágenes, descargas retrato o asesoría sobre impresiones.</p>
                                    </div>
                                </li>
                            </ul>
                            <hr>
                            <p class="small text-muted mb-1"><i class="fas fa-shield-alt me-2"></i>Atención personalizada</p>
                            <p class="small text-muted">Tus mensajes son visibles únicamente por el equipo de Vale V Photography. Recibirás un correo cada vez que respondamos.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="contact-card">
                            <div class="card-body">
                                <h2 class="h4 mb-1">Cuéntanos sobre tu proyecto</h2>
                                <p class="text-muted mb-4">Completa el formulario y te contactaremos para personalizar tu sesión o planificar tu evento fotográfico.</p>
                                <form id="contactForm" novalidate data-endpoint="<?php echo url_join(BASE_URL, 'api/contact_submit.php'); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre completo</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Tu nombre" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Correo electrónico</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@correo.com" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="+506 0000 0000">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tipo_clase" class="form-label">Tipo de sesión</label>
                                            <select class="form-select" id="tipo_clase" name="tipo_clase" required>
                                                <option value="" selected disabled>Selecciona una opción</option>
                                                <option value="Sesión de retrato">Sesión de retrato</option>
                                                <option value="Cobertura de evento">Cobertura de evento</option>
                                                <option value="Marca personal">Marca personal</option>
                                                <option value="Fotografía comercial">Fotografía comercial</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="mensaje" class="form-label">Mensaje</label>
                                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" placeholder="Comparte detalles clave: fecha, locación, estilo, cantidad de participantes" required></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-dark" data-submit-btn>
                                                <span class="me-2"><i class="fas fa-paper-plane"></i></span>Enviar mensaje
                                            </button>
                                            <p class="small text-muted mt-3 mb-0"><i class="fas fa-lock me-2"></i>Tus datos son tratados con confidencialidad y uso exclusivo para contactarte.</p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="contact-info-card h-100 p-4">
                            <h3 class="h5">También puedes encontrarnos</h3>
                            <div class="d-grid gap-3 mt-4">
                                <div class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-phone-alt"></i></div>
                                    <div>
                                        <strong>Llámanos o envía un WhatsApp</strong>
                                        <p class="mb-0 text-muted"><?php echo safeText($contactDetails['phone'] ?? '+506 8676-4740'); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-envelope-open"></i></div>
                                    <div>
                                        <strong>Correo directo</strong>
                                        <p class="mb-0 text-muted"><?php echo safeText($contactDetails['email'] ?? 'info@valevphotography.com'); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                                    <div>
                                        <strong>Visítanos</strong>
                                        <p class="mb-0 text-muted"><?php echo safeText($contactDetails['address'] ?? 'San José, Costa Rica'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <p class="fw-semibold mb-2">Horarios de atención</p>
                            <ul class="list-unstyled text-muted small">
                                <li><i class="fas fa-calendar-day me-2"></i>Lunes a viernes: <?php echo safeText($contactDetails['hours']['monday_friday'] ?? '09:00 - 21:00'); ?></li>
                                <li><i class="fas fa-calendar-week me-2"></i>Sábado: <?php echo safeText($contactDetails['hours']['saturday'] ?? '09:00 - 18:00'); ?></li>
                                <li><i class="fas fa-sun me-2"></i>Domingo: <?php echo safeText($contactDetails['hours']['sunday'] ?? '10:00 - 16:00'); ?></li>
                            </ul>
                            <hr class="my-4">
                            <p class="fw-semibold mb-2">Síguenos para ideas y novedades</p>
                            <div class="d-flex gap-3">
                                <?php if (!empty($socialLinks['instagram'])): ?><a class="text-dark" href="<?php echo safeText($socialLinks['instagram']); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram fa-lg"></i></a><?php endif; ?>
                                <?php if (!empty($socialLinks['facebook'])): ?><a class="text-dark" href="<?php echo safeText($socialLinks['facebook']); ?>" target="_blank" rel="noopener"><i class="fab fa-facebook fa-lg"></i></a><?php endif; ?>
                                <?php if (!empty($socialLinks['youtube'])): ?><a class="text-dark" href="<?php echo safeText($socialLinks['youtube']); ?>" target="_blank" rel="noopener"><i class="fab fa-youtube fa-lg"></i></a><?php endif; ?>
                                <?php if (!empty($socialLinks['tiktok'])): ?><a class="text-dark" href="<?php echo safeText($socialLinks['tiktok']); ?>" target="_blank" rel="noopener"><i class="fab fa-tiktok fa-lg"></i></a><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="py-4 bg-dark text-white-50">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>&copy; <?php echo date('Y'); ?> Vale V Photography. Todos los derechos reservados.</div>
            <div class="d-flex gap-3">
                <a href="<?php echo VIEWS_URL; ?>/catalogo.php" class="text-white-50">Servicios</a>
                <a href="<?php echo VIEWS_URL; ?>/register.php" class="text-white-50">Crear cuenta</a>
                <a href="<?php echo VIEWS_URL; ?>/login.php" class="text-white-50">Acceder</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/support-messages.js"></script>
</body>
</html>