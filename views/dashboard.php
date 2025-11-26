<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/user_notifications.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . VIEWS_URL . '/login.php');
    exit();
}

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ' . ADMIN_URL . '/admin.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$firstName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';

if (!function_exists('safeText')) {
    function safeText($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$userProfile = [];
if ($stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1')) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $userProfile = $result->fetch_assoc() ?: [];
        }
    }
    $stmt->close();
}

$sessionBookings = [];
if ($stmt = $conn->prepare(
    'SELECT e.*, 
            COUNT(f.id) AS feedback_count,
            AVG(f.performance_rating) AS avg_rating,
            MAX(f.class_date) AS last_feedback_date
     FROM enrollments e
     LEFT JOIN instructor_feedback f ON e.id = f.enrollment_id
     WHERE e.user_id = ?
     GROUP BY e.id
     ORDER BY e.enrollment_date DESC'
)) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sessionBookings[] = $row;
            }
        }
    }
    $stmt->close();
}

$recentFeedback = [];
if ($stmt = $conn->prepare(
    'SELECT f.class_date,
            f.performance_rating,
            f.strengths,
            f.areas_for_improvement,
            f.general_notes,
            f.homework_assigned,
            e.class_name
     FROM instructor_feedback f
     LEFT JOIN enrollments e ON f.enrollment_id = e.id
     WHERE f.user_id = ?
     ORDER BY f.class_date DESC, f.created_at DESC
     LIMIT 5'
)) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recentFeedback[] = $row;
            }
        }
    }
    $stmt->close();
}

$sessionPhotos = [];
if ($stmt = $conn->prepare(
    'SELECT session_label, session_date, image_url, created_at
     FROM user_session_photos
     WHERE user_id = ?
     ORDER BY session_date DESC, created_at DESC
     LIMIT 6'
)) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sessionPhotos[] = $row;
            }
        }
    }
    $stmt->close();
}

$notifications = getUserNotifications($userId, $conn);
$replaceMap = [
    'clases' => 'sesiones',
    'Clases' => 'Sesiones',
    'clase' => 'sesión',
    'Clase' => 'Sesión',
    'academia' => 'estudio',
    'Academia' => 'Estudio'
];

foreach ($notifications as &$note) {
    foreach (['title', 'message', 'details', 'action'] as $field) {
        if (!empty($note[$field])) {
            $note[$field] = str_replace(array_keys($replaceMap), array_values($replaceMap), $note[$field]);
        }
    }
}
unset($note);
$notificationCount = count($notifications);

$totalSessions = count($sessionBookings);
$confirmedSessions = 0;
$pendingSessions = 0;
$deliveredSessions = 0;
$ratingSum = 0.0;
$ratingCount = 0;
$latestReservation = null;

foreach ($sessionBookings as $booking) {
    $status = $booking['status'] ?? 'pending';
    if (in_array($status, ['active'], true)) {
        $confirmedSessions++;
    }
    if (in_array($status, ['pending', 'inactive'], true)) {
        $pendingSessions++;
    }
    if ($status === 'completed') {
        $deliveredSessions++;
    }

    if (isset($booking['avg_rating']) && $booking['avg_rating'] !== null) {
        $ratingSum += (float)$booking['avg_rating'];
        $ratingCount++;
    }

    if (!empty($booking['enrollment_date'])) {
        $timestamp = strtotime($booking['enrollment_date']);
        if ($timestamp) {
            if ($latestReservation === null || $timestamp > $latestReservation) {
                $latestReservation = $timestamp;
            }
        }
    }
}

$overallRating = $ratingCount > 0 ? round($ratingSum / $ratingCount, 1) : null;
$galleryCount = count($sessionPhotos);

$statusLabels = [
    'pending' => 'Pendiente de confirmación',
    'inactive' => 'Pendiente de pago',
    'active' => 'Confirmada',
    'completed' => 'Entregada',
    'cancelled' => 'Cancelada',
    'rejected' => 'Rechazada'
];

$statusStyles = [
    'pending' => 'warning',
    'inactive' => 'warning',
    'active' => 'info',
    'completed' => 'success',
    'cancelled' => 'secondary',
    'rejected' => 'danger'
];

function formatDate(?string $date): string
{
    if (!$date) {
        return 'Sin definir';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return safeText($date);
    }
    return date('d/m/Y', $timestamp);
}

function isImageAsset(?string $url): bool
{
    if (!$url) {
        return false;
    }
    $path = parse_url($url, PHP_URL_PATH);
    if (!$path) {
        return false;
    }
    return (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $path) || str_contains($url, '/image/upload/');
}

$memberSince = $userProfile['created_at'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Vale V Photography</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <style>
        body {
            background: #f8f6f4;
            font-family: 'Poppins', sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.6px;
        }
        .dashboard-hero {
            background: linear-gradient(135deg, #111827 0%, #312e81 100%);
            color: #fff;
            border-radius: 24px;
            padding: 2.75rem;
            position: relative;
            overflow: visible;
            z-index: 1;
        }
        .dashboard-hero::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 360px;
            height: 360px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            filter: blur(0.5px);
            pointer-events: none;
            z-index: 0;
        }
        .dashboard-hero .row {
            position: relative;
            z-index: 1;
        }
        .dashboard-hero h1 {
            color: rgba(255, 255, 255, 0.85);
        }
        .hero-badge {
            background: rgba(255,255,255,0.18);
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.4rem;
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border: 1px solid rgba(17, 24, 39, 0.05);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
        }
        .section-title {
            font-weight: 600;
            color: #141b2b;
            margin-bottom: 1.5rem;
        }
        .session-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(20, 27, 43, 0.08);
            box-shadow: 0 14px 28px rgba(20,27,43,0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            gap: 0.75rem;
        }
        .status-badge {
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .photo-card {
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 12px 28px rgba(17, 24, 39, 0.18);
        }
        .photo-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }
        .photo-card .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(17,24,39,0) 0%, rgba(17,24,39,0.65) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1.1rem;
            gap: 0.4rem;
        }
        .photo-card.file-card {
            background: linear-gradient(135deg, #000000 0%, #2f2f2f 100%);
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.75rem 1.5rem;
            gap: 0.75rem;
        }
        .photo-card.file-card .file-card-icon {
            font-size: 2.75rem;
            color: rgba(255,255,255,0.85);
        }
        .photo-card.file-card h6 {
            color: #ffffff;
        }
        .photo-card.file-card small {
            color: rgba(255,255,255,0.7);
        }
        .photo-card.file-card .file-card-actions a {
            font-weight: 600;
        }
        .feedback-card {
            border-radius: 16px;
            border: 1px solid rgba(17, 24, 39, 0.06);
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.08);
        }
        .quick-actions a {
            border-radius: 16px;
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid rgba(17, 24, 39, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .quick-actions a:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.12);
        }
        .tips-list li {
            margin-bottom: 0.6rem;
        }
        .navbar .dropdown-menu {
            z-index: 1100;
        }
        .navbar {
            position: relative;
            z-index: 2000;
        }
        @media (max-width: 767px) {
            .dashboard-hero {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php">
                <i class="fas fa-camera-retro me-2 text-primary"></i>Vale V Photography
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNav" aria-controls="dashboardNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="dashboardNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item text-muted small d-flex align-items-center gap-2">
                        <span>Hola, <?php echo safeText(trim($firstName . ' ' . $lastName)); ?></span>
                        <span id="notifCountInline" class="badge bg-danger rounded-pill<?php echo $notificationCount ? '' : ' d-none'; ?>" data-server-count="<?php echo $notificationCount; ?>" title="Alertas nuevas"><?php echo $notificationCount ?: ''; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php">Reservar nueva sesión</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell me-1"></i>Notificaciones
                            <span id="notifCount" class="badge bg-danger rounded-pill ms-1<?php echo $notificationCount ? '' : ' d-none'; ?>" data-server-count="<?php echo $notificationCount; ?>"><?php echo $notificationCount ?: ''; ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notifDropdown" style="min-width: 320px;">
                            <div class="dropdown-header fw-semibold">Actualizaciones recientes</div>
                            <div id="notifList" class="list-group list-group-flush" data-has-items="<?php echo $notificationCount ? '1' : '0'; ?>">
                                <?php if ($notificationCount === 0): ?>
                                    <div class="list-group-item text-center text-muted small py-3">Sin notificaciones nuevas</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $note): ?>
                                        <?php
                                            $level = $note['level'] ?? 'info';
                                            $icon = 'fa-circle-info';
                                            $tone = 'text-primary';
                                            if ($level === 'warning') {
                                                $icon = 'fa-triangle-exclamation';
                                                $tone = 'text-warning';
                                            } elseif ($level === 'success') {
                                                $icon = 'fa-check-circle';
                                                $tone = 'text-success';
                                            }
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="fas <?php echo $icon; ?> <?php echo $tone; ?> mt-1"></i>
                                                <div>
                                                    <div class="fw-semibold small"><?php echo safeText($note['title'] ?? 'Actualización'); ?></div>
                                                    <div class="small text-muted mb-1"><?php echo $note['message'] ?? ''; ?></div>
                                                    <?php if (!empty($note['details'])): ?>
                                                        <div class="small text-muted"><?php echo safeText($note['details']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($note['action'])): ?>
                                                        <div class="small fw-semibold"><?php echo safeText($note['action']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-footer text-center small py-2 bg-light">
                                <a class="text-decoration-none" href="<?php echo VIEWS_URL; ?>/contact.php">¿Necesitas ayuda? Escríbenos</a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-dark btn-sm" href="<?php echo VIEWS_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <section class="dashboard-hero mb-5">
            <div class="row">
                <div class="col-xl-8 col-lg-9">
                    <span class="hero-badge">
                        <i class="far fa-sun"></i> Tu experiencia fotográfica
                    </span>
                    <h1 class="mt-3 mb-2">Hola <?php echo safeText($firstName); ?>, tus recuerdos están en buenas manos.</h1>
                    <p class="lead mb-4">
                        Revisa tus sesiones confirmadas, descarga tus galerías y encuentra inspiración para tu próximo proyecto con Vanessa.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if ($memberSince): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark rounded-pill">
                                    <i class="far fa-calendar-check me-1"></i>Cliente desde <?php echo formatDate($memberSince); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($totalSessions > 0): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark rounded-pill">
                                    <i class="fas fa-camera me-1"></i><?php echo $totalSessions; ?> sesión<?php echo $totalSessions === 1 ? '' : 'es'; ?> registrad<?php echo $totalSessions === 1 ? 'a' : 'as'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5">
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-camera"></i></div>
                        <div>
                            <span class="text-muted small text-uppercase">Sesiones totales</span>
                            <h3 class="mt-1 mb-0"><?php echo $totalSessions; ?></h3>
                            <small class="text-muted">Incluye confirmadas y entregadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <span class="text-muted small text-uppercase">Pendientes</span>
                            <h3 class="mt-1 mb-0"><?php echo $pendingSessions; ?></h3>
                            <small class="text-muted">En espera de confirmación o pago</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-gift"></i></div>
                        <div>
                            <span class="text-muted small text-uppercase">Sesiones entregadas</span>
                            <h3 class="mt-1 mb-0"><?php echo $deliveredSessions; ?></h3>
                            <small class="text-muted">Listas para descargar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-star"></i></div>
                        <div>
                            <span class="text-muted small text-uppercase">Valoración promedio</span>
                            <h3 class="mt-1 mb-0"><?php echo $overallRating !== null ? $overallRating : '—'; ?><?php echo $overallRating !== null ? '/10' : ''; ?></h3>
                            <small class="text-muted">Basado en tus notas fotográficas</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($notifications)): ?>
            <section class="mb-5">
                <h2 class="section-title">Recordatorios del estudio</h2>
                <?php foreach ($notifications as $note): ?>
                    <?php
                        $level = $note['level'] ?? 'info';
                        $alertClass = $level === 'warning' ? 'alert-warning' : ($level === 'success' ? 'alert-success' : 'alert-info');
                    ?>
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <div class="d-flex gap-3">
                            <div class="fs-4 mt-1">
                                <?php echo $level === 'warning' ? '⚠️' : ($level === 'success' ? '✨' : 'ℹ️'); ?>
                            </div>
                            <div>
                                <h5 class="mb-1"><?php echo safeText($note['title']); ?></h5>
                                <p class="mb-1"><?php echo $note['message']; ?></p>
                                <?php if (!empty($note['details'])): ?>
                                    <small class="text-muted d-block mb-1"><?php echo $note['details']; ?></small>
                                <?php endif; ?>
                                <?php if (!empty($note['action'])): ?>
                                    <small class="fw-semibold text-dark d-block"><?php echo $note['action']; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Tus sesiones fotográficas</h2>
                <?php if ($totalSessions > 0): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo VIEWS_URL; ?>/clases.php">
                        <i class="fas fa-plus me-1"></i> Agendar otra sesión
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($totalSessions === 0): ?>
                <div class="session-card text-center">
                    <i class="fas fa-camera-retro fa-3x text-muted mb-3"></i>
                    <h5 class="mb-2">Aún no has reservado sesiones</h5>
                    <p class="text-muted mb-4">Cuando agendes tu primera sesión, aquí podrás dar seguimiento a cada detalle.</p>
                    <a class="btn btn-dark" href="<?php echo VIEWS_URL; ?>/clases.php">
                        <i class="fas fa-calendar-plus me-2"></i>Explorar sesiones disponibles
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($sessionBookings as $booking): ?>
                        <?php
                            $status = $booking['status'] ?? 'pending';
                            $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                            $statusStyle = $statusStyles[$status] ?? 'secondary';
                            $sessionName = $booking['class_name'] ?: 'Sesión sin título';
                            $schedule = $booking['selected_schedule'] ?: ($booking['class_schedule'] ?? 'Horario por definir');
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="session-card h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?php echo safeText($sessionName); ?></h5>
                                        <span class="badge bg-<?php echo $statusStyle; ?> bg-opacity-10 text-<?php echo $statusStyle; ?> status-badge">
                                            <i class="fas fa-circle me-2" style="font-size: 0.5rem;"></i><?php echo safeText($statusLabel); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted small">
                                    <p class="mb-2"><i class="far fa-calendar me-2"></i><?php echo safeText($schedule); ?></p>
                                    <?php if (!empty($booking['enrollment_date'])): ?>
                                        <p class="mb-2"><i class="far fa-clock me-2"></i>Reservada el <?php echo formatDate($booking['enrollment_date']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($booking['progress_notes'])): ?>
                                    <div class="border rounded-3 p-3 bg-light">
                                        <small class="text-uppercase text-muted">Notas del estudio</small>
                                        <p class="mb-0 mt-1"><?php echo nl2br(safeText($booking['progress_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-auto pt-2 small text-muted">
                                    <?php if (!empty($booking['avg_rating'])): ?>
                                        <span class="d-block mb-1">
                                            <i class="fas fa-star text-warning me-2"></i>Calificación promedio: <?php echo number_format((float)$booking['avg_rating'], 1); ?>/10
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($booking['feedback_count'])): ?>
                                        <span class="d-block">
                                            <i class="fas fa-comments text-primary me-2"></i><?php echo (int)$booking['feedback_count']; ?> nota<?php echo (int)$booking['feedback_count'] === 1 ? '' : 's'; ?> del fotógrafo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Tus recuerdos listos</h2>
                <?php if ($galleryCount > 0): ?>
                    <span class="text-muted small">Última actualización: <?php echo formatDate($sessionPhotos[0]['session_date'] ?? $sessionPhotos[0]['created_at'] ?? null); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($galleryCount === 0): ?>
                <div class="alert alert-light border text-muted" role="alert">
                    <i class="fas fa-image me-2"></i>Cuando tus galerías estén listas, podrás descargarlas desde aquí mismo.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($sessionPhotos as $photo): ?>
                        <?php
                            $galleryUrl = $photo['image_url'] ?? '';
                            $isImage = isImageAsset($galleryUrl);
                            $sessionLabel = $photo['session_label'] ?? '';
                            $dateValue = $photo['session_date'] ?? $photo['created_at'] ?? null;
                            $displayDate = $dateValue ? formatDate($dateValue) : null;
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="photo-card<?php echo $isImage ? '' : ' file-card'; ?>">
                                <?php if ($isImage): ?>
                                    <img src="<?php echo safeText($galleryUrl); ?>" alt="Galería fotográfica" loading="lazy">
                                    <div class="overlay">
                                        <?php if (!empty($sessionLabel)): ?>
                                            <h6 class="mb-0"><?php echo safeText($sessionLabel); ?></h6>
                                        <?php endif; ?>
                                        <?php if (!empty($displayDate)): ?>
                                            <small class="text-white-50"><?php echo safeText($displayDate); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-folder-open file-card-icon"></i>
                                    <?php if (!empty($sessionLabel)): ?>
                                        <h6 class="mb-1"><?php echo safeText($sessionLabel); ?></h6>
                                    <?php endif; ?>
                                    <?php if (!empty($displayDate)): ?>
                                        <small class="d-block mb-2"><?php echo safeText($displayDate); ?></small>
                                    <?php endif; ?>
                                    <div class="file-card-actions">
                                        <a class="btn btn-light btn-sm" href="<?php echo safeText($galleryUrl); ?>" download>
                                            <i class="fas fa-download me-1"></i>Descargar
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="row g-4 mb-5">
            <div class="col-lg-7">
                <h2 class="section-title">Notas del fotógrafo</h2>
                <?php if (empty($recentFeedback)): ?>
                    <div class="alert alert-light border text-muted" role="alert">
                        <i class="far fa-sticky-note me-2"></i>Cuando recibas comentarios del equipo, aparecerán aquí para que prepares tu próxima sesión.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recentFeedback as $feedback): ?>
                            <div class="card feedback-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 text-primary">
                                                <?php echo safeText($feedback['class_name'] ?? 'Sesión fotográfica'); ?>
                                            </h6>
                                            <?php if (!empty($feedback['class_date'])): ?>
                                                <small class="text-muted">Realizada el <?php echo formatDate($feedback['class_date']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($feedback['performance_rating'])): ?>
                                            <span class="badge bg-dark-subtle text-dark"><i class="fas fa-star text-warning me-1"></i><?php echo (int)$feedback['performance_rating']; ?>/10</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($feedback['strengths'])): ?>
                                        <p class="mb-1"><strong>Lo que brilló:</strong> <?php echo safeText($feedback['strengths']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($feedback['areas_for_improvement'])): ?>
                                        <p class="mb-1"><strong>Para la próxima:</strong> <?php echo safeText($feedback['areas_for_improvement']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($feedback['general_notes'])): ?>
                                        <p class="mb-1 text-muted"><?php echo safeText($feedback['general_notes']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($feedback['homework_assigned'])): ?>
                                        <p class="mb-0"><strong>Preparación sugerida:</strong> <?php echo safeText($feedback['homework_assigned']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <h2 class="section-title">Inspiración del estudio</h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <ul class="list-unstyled tips-list mb-4">
                            <li><i class="fas fa-lightbulb text-warning me-2"></i>Trae dos cambios de ropa que contrasten entre sí.</li>
                            <li><i class="fas fa-leaf text-success me-2"></i>Descansa bien la noche anterior para lucir fresco/a.</li>
                            <li><i class="fas fa-music text-primary me-2"></i>Comparte una playlist que represente el mood que buscas.</li>
                            <li><i class="fas fa-map-marker-alt text-danger me-2"></i>Verifica la ubicación y estacionamiento del estudio con anticipación.</li>
                        </ul>
                        <a class="btn btn-dark w-100" href="<?php echo VIEWS_URL; ?>/contact.php">
                            <i class="fas fa-envelope-open-text me-2"></i>Coordinar detalles con Valeria 
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <h2 class="section-title">Acciones rápidas</h2>
            <div class="row g-3 quick-actions">
                <div class="col-md-3">
                    <a href="<?php echo VIEWS_URL; ?>/clases.php" class="text-decoration-none text-dark bg-white">
                        <i class="fas fa-calendar-plus fa-lg"></i>
                        <span class="fw-semibold">Agendar sesión</span>
                        <small class="text-muted text-center">Explora retratos, branding y lifestyle</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo VIEWS_URL; ?>/update_profile.php" class="text-decoration-none text-dark bg-white">
                        <i class="fas fa-user-edit fa-lg"></i>
                        <span class="fw-semibold">Actualizar perfil</span>
                        <small class="text-muted text-center">Contacto, redes y preferencias</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo VIEWS_URL; ?>/contact.php?canal=cliente" class="text-decoration-none text-dark bg-white">
                        <i class="fas fa-comments fa-lg"></i>
                        <span class="fw-semibold">Mensaje a Valeria</span>
                        <small class="text-muted text-center">Coordina ideas y solicitudes desde aquí</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo VIEWS_URL; ?>/ubicacion.php" class="text-decoration-none text-dark bg-white">
                        <i class="fas fa-map-marked-alt fa-lg"></i>
                        <span class="fw-semibold">Cómo llegar</span>
                        <small class="text-muted text-center">Dirección, horarios y estacionamiento</small>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            var badge = document.getElementById('notifCount');
            var inlineBadge = document.getElementById('notifCountInline');
            var list = document.getElementById('notifList');
            var dropdownToggle = document.getElementById('notifDropdown');
            var userOpenedDropdown = false;

            var setBadgeCount = function(count, force){
                var displayCount = (!force && userOpenedDropdown) ? 0 : count;
                [badge, inlineBadge].forEach(function(el){
                    if (!el) { return; }
                    el.setAttribute('data-server-count', count);
                    if (displayCount > 0) {
                        el.textContent = displayCount;
                        el.classList.remove('d-none');
                    } else {
                        el.textContent = '';
                        el.classList.add('d-none');
                    }
                });
            };

            var buildDetail = function(alert){
                if (!alert || !alert.details) {
                    return '';
                }
                if (typeof alert.details === 'string') {
                    return `<div class="small text-muted">${alert.details}</div>`;
                }
                if (Array.isArray(alert.details) && alert.details.length) {
                    var first = alert.details[0];
                    if (first.class_name && first.schedule) {
                        return `<div class="small text-muted">${first.class_name} · ${first.schedule}</div>`;
                    }
                    if (first.order_number) {
                        return `<div class="small text-muted">Orden #${first.order_number}</div>`;
                    }
                }
                return '';
            };

            var showEmpty = function(){
                if (list) {
                    list.innerHTML = '<div class="list-group-item text-center text-muted small py-3">Sin notificaciones nuevas</div>';
                    list.dataset.hasItems = '0';
                }
                setBadgeCount(0, true);
            };

            var markAsRead = function(){
                userOpenedDropdown = true;
                setBadgeCount(0, true);
            };

            if (dropdownToggle) {
                dropdownToggle.addEventListener('show.bs.dropdown', markAsRead);
            }

            fetch('<?php echo BASE_URL; ?>/api/get_user_alerts.php')
                .then(function(response){ return response.json(); })
                .then(function(payload){
                    var alerts = payload && Array.isArray(payload.alerts) ? payload.alerts : [];
                    if (!alerts.length) {
                        showEmpty();
                        return;
                    }
                    if (list) {
                        var html = alerts.map(function(alert){
                            var icon = 'fa-circle-info';
                            var tone = 'text-primary';
                            switch (alert.type) {
                                case 'payment_required':
                                case 'enrollment_rejected':
                                    icon = 'fa-triangle-exclamation';
                                    tone = 'text-warning';
                                    break;
                                case 'enrollment_approved':
                                case 'order_update':
                                    icon = 'fa-check-circle';
                                    tone = 'text-success';
                                    break;
                            }
                            var detail = buildDetail(alert);
                            return `<div class="list-group-item"><div class="d-flex align-items-start gap-2"><i class="fas ${icon} ${tone} mt-1"></i><div><div class="fw-semibold small">${alert.title || 'Actualización'}</div><div class="small text-muted mb-1">${alert.message || ''}</div>${detail}</div></div></div>`;
                        }).join('');
                        list.innerHTML = html;
                        list.dataset.hasItems = '1';
                    }
                    setBadgeCount(alerts.length);
                })
                .catch(function(){
                    if (!list || list.dataset.hasItems === '1') {
                        return;
                    }
                    showEmpty();
                });
        })();
    </script>
</body>
</html>

<?php closeConnection($conn); ?>
