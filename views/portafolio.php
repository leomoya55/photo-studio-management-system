<?php
session_start();
require_once __DIR__ . '/../config/paths.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $userRole = $_SESSION['role'] ?? 'customer';
}

$isAdminView = isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && $isLoggedIn && $userRole === 'admin';
$adminViewParam = (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') ? '?admin_view=1' : '';

if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && (!$isLoggedIn || $userRole !== 'admin')) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portafolio - Vale V Photography</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <style>
        :root {
            --bs-primary: #000000;
            --bs-primary-rgb: 0, 0, 0;
            --bs-btn-hover-bg: #111111;
            --bs-btn-active-bg: #111111;
        }

        body {
            background-color: #f7f6f4;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background-color: #000000 !important;
            border-color: #000000 !important;
            background-image: linear-gradient(135deg, #000000 0%, #333333 100%) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #111111 !important;
            border-color: #111111 !important;
            background-image: linear-gradient(135deg, #111111 0%, #444444 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .btn-outline-primary {
            color: #111111 !important;
            border-color: #111111 !important;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: #000000 !important;
            border-color: #000000 !important;
            color: #ffffff !important;
        }

        .brand-text {
            font-family: 'Dancing Script', cursive !important;
            color: var(--brand-color) !important;
            font-weight: 700;
        }

        .navbar-nav .nav-link:hover {
            color: #333333 !important;
        }

        .portfolio-hero {
            background: linear-gradient(135deg, #0f172a 0%, #312e81 100%);
            color: #ffffff;
            padding: 140px 0 100px;
            margin-top: 72px;
        }

        .portfolio-hero .lead {
            max-width: 640px;
        }

        .portfolio-sidebar {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
            position: sticky;
            top: 120px;
        }

        .portfolio-sidebar h5 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #141b2b;
        }

        .portfolio-sidebar .nav-link {
            border-radius: 12px;
            margin-bottom: 0.5rem;
            text-align: left;
            color: #374151;
            font-weight: 500;
            padding: 0.65rem 0.85rem;
            border: 1px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .portfolio-sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(17, 24, 39, 0.92) 0%, rgba(55, 48, 163, 0.92) 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 10px 25px rgba(17, 24, 39, 0.15);
        }

        .portfolio-sidebar .nav-link span {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        .portfolio-content {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            min-height: 460px;
        }

        .portfolio-grid {
            margin-top: 0.5rem;
        }

        .portfolio-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .portfolio-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.16);
        }

        .portfolio-image {
            position: relative;
            padding-top: 66%;
            background: #f1f5f9;
        }

        .portfolio-image img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portfolio-card-body {
            padding: 1.4rem 1.5rem 1.6rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
        }

        .portfolio-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .portfolio-meta span i {
            color: #111827;
            margin-right: 0.35rem;
        }

        .portfolio-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #6b7280;
        }

        .portfolio-empty i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #9ca3af;
        }

        @media (max-width: 991px) {
            .portfolio-sidebar {
                position: static;
                top: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>#inicio">
                <span class="brand-text">Vale V Photography</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portfolioNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="portfolioNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/index.php<?php echo $adminViewParam; ?>#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>">Sesiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/catalogo.php<?php echo $adminViewParam; ?>">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/redes-sociales.php<?php echo $adminViewParam; ?>">Redes Sociales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo VIEWS_URL; ?>/ubicacion.php<?php echo $adminViewParam; ?>">Ubicación</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($userName); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/admin.php"><i class="fas fa-cog me-2"></i>Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/dashboard.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo VIEWS_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-outline-primary" href="<?php echo VIEWS_URL; ?>/register.php">Registrarse</a>
                        </li>
                        <li class="nav-item nav-cta">
                            <a class="nav-link btn btn-primary" href="<?php echo VIEWS_URL; ?>/login.php">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($isAdminView): ?>
    <div class="alert alert-info border-0 rounded-0 text-center mb-0" style="background: linear-gradient(90deg, #17a2b8, #20c997); color: white;">
        <div class="container">
            <i class="fas fa-eye me-2"></i>
            <strong>Vista de Administrador</strong> - Estás viendo el portafolio sin opciones de reserva
            <a href="<?php echo ADMIN_URL; ?>/admin.php" class="btn btn-light btn-sm ms-3">
                <i class="fas fa-arrow-left me-1"></i>Volver al Panel Admin
            </a>
        </div>
    </div>
    <?php endif; ?>

    <section class="portfolio-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3 text-on-dark">Portafolio del Estudio</h1>
                    <p class="lead mb-4 text-on-dark-soft">Explora sesiones reales capturadas por nuestro equipo creativo. Navega por categorías para descubrir experiencias personalizadas: desde recuerdos de recién nacidos hasta gender reveal, eventos familiares y producciones artísticas.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?php echo VIEWS_URL; ?>/clases.php<?php echo $adminViewParam; ?>" class="btn btn-light text-dark">
                            <i class="fas fa-calendar-check me-2"></i>Reservar una sesión
                        </a>
                        <a href="https://wa.me/50686764740" target="_blank" rel="noopener" class="btn btn-outline-light">
                            <i class="fab fa-whatsapp me-2"></i>Hablar con el estudio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3">
                    <aside class="portfolio-sidebar">
                        <h5>Colecciones</h5>
                        <div id="portfolioTabs" class="nav flex-column" role="tablist" aria-orientation="vertical">
                            <div class="text-muted small">Cargando sesiones...</div>
                        </div>
                    </aside>
                </div>
                <div class="col-lg-9">
                    <div class="portfolio-content">
                        <div id="portfolioLoading" class="text-center py-5">
                            <div class="spinner-border text-dark" role="status">
                                <span class="visually-hidden">Cargando portafolio...</span>
                            </div>
                            <p class="mt-3 text-muted">Cargando portafolio fotográfico...</p>
                        </div>
                        <div id="portfolioError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="portfolioGrid" class="row g-4 portfolio-grid d-none"></div>
                        <div id="portfolioEmpty" class="portfolio-empty d-none">
                            <i class="far fa-images"></i>
                            <h5>No hay sesiones disponibles en esta categoría</h5>
                            <p class="mb-0">Selecciona otra categoría o contáctanos para una propuesta personalizada.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="brand-text">Vale V Photography</h5>
                    <p>Estudio fotográfico donde cada imagen cuenta una historia.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1Czy4E7doQ/?mibextid=wwXIfr" class="text-white me-3" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/valevphotography?igsh=MXZobjc0NWtod2gyMA%3D%3D&utm_source=qr" class="text-white me-3" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@valevstudio" class="text-white me-3" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/50686764740" class="text-white" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                    <p class="mt-2 mb-0">&copy; 2025 Vale V Photography. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    <?php if ($isLoggedIn && $userRole !== 'admin'): ?>
    <script src="<?php echo ASSETS_URL; ?>/js/user-alerts.js"></script>
    <?php endif; ?>
    <script>
        const portfolioState = {
            sessions: [],
            categories: [],
            active: null
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadPortfolio();
        });

        function slugify(value) {
            const cleaned = (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-');
            const trimmed = cleaned.replace(/^-+|-+$/g, '');
            return trimmed !== '' ? trimmed : 'otras-sesiones';
        }

        function formatCategoryName(value) {
            const text = (value || 'Otras sesiones').toString().trim();
            return text.replace(/\s+/g, ' ').split(' ').map(function(word) {
                if (word.length === 0) {
                    return word;
                }
                return word.charAt(0).toUpperCase() + word.slice(1);
            }).join(' ');
        }

        function escapeHtml(value) {
            return (value === undefined || value === null ? '' : value.toString())
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function hasGalleryImage(session) {
            if (!session) {
                return false;
            }
            const source = session.image || session.image_url || '';
            if (typeof source !== 'string') {
                return false;
            }
            return source.trim() !== '';
        }

        function getCloudinaryImageUrl(imageValue, width, height) {
            const w = width || 600;
            const h = height || 400;
            if (!imageValue) {
                return 'https://via.placeholder.com/' + w + 'x' + h + '?text=Vale+V';
            }

            const cloudName = 'deov2g1ji';
            const transformations = 'w_' + w + ',h_' + h + ',c_fill,f_auto,q_auto,dpr_auto';
            const marker = '/image/upload/';

            if (typeof imageValue === 'string' && /^https?:\/\//i.test(imageValue.trim())) {
                const trimmed = imageValue.trim();
                if (!trimmed.includes(marker)) {
                    return trimmed;
                }

                const parts = trimmed.split(marker);
                if (parts.length < 2) {
                    return trimmed;
                }

                const prefix = parts[0];
                const suffixRaw = parts.slice(1).join(marker);
                const suffix = suffixRaw.replace(/^\/+/, '');

                if (suffix.startsWith('v')) {
                    return prefix + marker + transformations + '/' + suffix;
                }

                const segments = suffix.split('/').filter(Boolean);
                if (!segments.length) {
                    return trimmed;
                }

                segments[0] = transformations;
                return prefix + marker + segments.join('/');
            }

            try {
                const encodedId = String(imageValue)
                    .split('/')
                    .map(function(segment) { return encodeURIComponent(segment); })
                    .join('/');
                return 'https://res.cloudinary.com/' + cloudName + '/image/upload/' + transformations + '/' + encodedId;
            } catch (e) {
                return 'https://via.placeholder.com/' + w + 'x' + h + '?text=Vale+V';
            }
        }

        const classesEndpoint = <?php echo json_encode(url_join(BASE_URL, 'data/get_classes_from_db.php')); ?>;

        async function loadPortfolio() {
            showLoading(true);
            try {
                const response = await fetch(classesEndpoint + '?v=' + Date.now());
                if (!response.ok) {
                    throw new Error('No se pudo cargar el portafolio.');
                }
                const data = await response.json();
                portfolioState.sessions = (Array.isArray(data) ? data : []).filter(hasGalleryImage);
                buildCategories();
                if (!portfolioState.active && portfolioState.categories.length > 0) {
                    portfolioState.active = portfolioState.categories[0].slug;
                }
                renderTabs();
                renderCategory(portfolioState.active);
            } catch (error) {
                showError(error && error.message ? error.message : 'No se pudo cargar el portafolio.');
            } finally {
                showLoading(false);
            }
        }

        function buildCategories() {
            const map = new Map();
            portfolioState.sessions.forEach(function(session) {
                const baseName = session && session.category && session.category.trim() !== '' ? session.category : 'Otras sesiones';
                const slug = slugify(baseName);
                if (!map.has(slug)) {
                    map.set(slug, {
                        slug: slug,
                        name: formatCategoryName(baseName),
                        items: []
                    });
                }
                map.get(slug).items.push(session);
            });

            const sorted = Array.from(map.values()).sort(function(a, b) {
                return a.name.localeCompare(b.name, 'es', { sensitivity: 'base' });
            });

            const desired = ['bebes', 'gender-reveal'];
            const ordered = [{
                slug: 'all',
                name: 'Todas las sesiones',
                items: portfolioState.sessions.slice()
            }];

            desired.forEach(function(target) {
                const index = sorted.findIndex(function(category) { return category.slug === target; });
                if (index >= 0) {
                    ordered.push(sorted[index]);
                    sorted.splice(index, 1);
                } else {
                    ordered.push({
                        slug: target,
                        name: formatCategoryName(target.replace(/-/g, ' ')),
                        items: []
                    });
                }
            });

            sorted.forEach(function(category) {
                if (!ordered.find(function(existing) { return existing.slug === category.slug; })) {
                    ordered.push(category);
                }
            });

            portfolioState.categories = ordered;
            if (!portfolioState.active && ordered.length > 0) {
                portfolioState.active = ordered[0].slug;
            }
        }

        function renderTabs() {
            const container = document.getElementById('portfolioTabs');
            if (!container) {
                return;
            }
            container.innerHTML = '';
            if (portfolioState.categories.length === 0) {
                container.innerHTML = '<div class="text-muted small">No hay categorías disponibles.</div>';
                return;
            }
            portfolioState.categories.forEach(function(category) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'nav-link' + (category.slug === portfolioState.active ? ' active' : '');
                button.dataset.slug = category.slug;
                button.innerHTML = '<span>' + escapeHtml(category.name) + '</span><span>' + category.items.length + '</span>';
                button.addEventListener('click', function() {
                    setActiveCategory(category.slug);
                });
                container.appendChild(button);
            });
        }

        function setActiveCategory(slug) {
            if (!slug) {
                return;
            }
            portfolioState.active = slug;
            document.querySelectorAll('#portfolioTabs .nav-link').forEach(function(link) {
                const isActive = link.dataset.slug === slug;
                link.classList.toggle('active', isActive);
            });
            renderCategory(slug);
        }

        function renderCategory(slug) {
            const grid = document.getElementById('portfolioGrid');
            const emptyState = document.getElementById('portfolioEmpty');
            const errorBox = document.getElementById('portfolioError');
            if (!grid || !emptyState || !errorBox) {
                return;
            }

            errorBox.classList.add('d-none');

            const category = portfolioState.categories.find(function(item) { return item.slug === slug; });
            const sessions = category ? category.items.filter(hasGalleryImage) : [];

            if (!sessions || sessions.length === 0) {
                grid.classList.add('d-none');
                grid.innerHTML = '';
                emptyState.classList.remove('d-none');
                return;
            }

            emptyState.classList.add('d-none');
            grid.classList.remove('d-none');
            grid.innerHTML = sessions.map(renderCard).join('');
        }

        function renderCard(session) {
            const title = session && session.name ? session.name : 'Sesión del Estudio';
            const description = session && session.description ? session.description : '';
            const duration = session && session.duration ? session.duration : 'Por coordinar';
            const schedule = session && session.schedule ? session.schedule : 'Horario por definir';
            const imageUrl = getCloudinaryImageUrl(session && session.image ? session.image : null, 600, 400);

            return '<div class="col-md-6 col-xl-4">'
                + '<div class="portfolio-card">'
                + '<div class="portfolio-image">'
                + '<img src="' + imageUrl + '" alt="' + escapeHtml(title) + '" loading="lazy">'
                + '</div>'
                + '<div class="portfolio-card-body">'
                + '<div>'
                + '<h5 class="mb-2">' + escapeHtml(title) + '</h5>'
                + '<p class="text-muted mb-0">' + escapeHtml(description) + '</p>'
                + '</div>'
                + '<div class="portfolio-meta">'
                + '<span><i class="far fa-clock"></i>' + escapeHtml(duration) + '</span>'
                + '<span><i class="far fa-calendar"></i>' + escapeHtml(schedule) + '</span>'
                + '</div>'
                + '</div>'
                + '</div>'
                + '</div>';
        }

        function showLoading(show) {
            const loading = document.getElementById('portfolioLoading');
            const grid = document.getElementById('portfolioGrid');
            const emptyState = document.getElementById('portfolioEmpty');
            if (!loading || !grid || !emptyState) {
                return;
            }
            if (show) {
                loading.classList.remove('d-none');
                grid.classList.add('d-none');
                emptyState.classList.add('d-none');
            } else {
                loading.classList.add('d-none');
            }
        }

        function showError(message) {
            const errorBox = document.getElementById('portfolioError');
            if (!errorBox) {
                return;
            }
            errorBox.textContent = message;
            errorBox.classList.remove('d-none');
        }
    </script>
</body>
</html>