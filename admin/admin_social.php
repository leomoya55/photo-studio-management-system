<?php
// admin_social.php - Social media post management for admin
session_start();
require_once '../config/db_connect.php';
require_once '../config/cloudinary_admin.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

if (!function_exists('extractCloudinaryPublicId')) {
    function extractCloudinaryPublicId($url) {
        if (strpos($url, 'res.cloudinary.com') === false) {
            return '';
        }
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        if (empty($path)) {
            return '';
        }
        $segments = explode('/', ltrim($path, '/'));
        $uploadIndex = array_search('upload', $segments);
        if ($uploadIndex === false) {
            return '';
        }
        $publicParts = array_slice($segments, $uploadIndex + 1);
        if (!$publicParts) {
            return '';
        }
        if (isset($publicParts[0]) && preg_match('/^v\d+$/', $publicParts[0])) {
            array_shift($publicParts);
        }
        if (!$publicParts) {
            return '';
        }
        $publicWithExt = implode('/', $publicParts);
        return preg_replace('/\.[^\.]+$/', '', $publicWithExt);
    }
}
    
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($value) {
        return htmlspecialchars(trim((string)($value ?? '')), ENT_QUOTES, 'UTF-8');
    }
}

$message = '';
$messageType = '';
$cloudinaryAdmin = new CloudinaryAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_post':
            $platform = strtolower(sanitizeInput($_POST['platform']));
            if (!in_array($platform, ['facebook', 'instagram'], true)) {
                $message = 'Plataforma seleccionada no es valida.';
                $messageType = 'error';
                break;
            }
            $caption = sanitizeInput($_POST['caption']);
            $post_date = sanitizeInput($_POST['post_date']);

            $image_path = '';
            if (isset($_FILES['social_image']) && $_FILES['social_image']['error'] === UPLOAD_ERR_OK) {
                $validation = $cloudinaryAdmin->validateImageFile($_FILES['social_image']);
                if (!$validation['valid']) {
                    $message = $validation['error'];
                    $messageType = 'error';
                    break;
                }

                $upload = $cloudinaryAdmin->uploadImage(
                    $_FILES['social_image']['tmp_name'],
                    'social_media',
                    $platform . '_' . date('Y-m-d')
                );

                if (!($upload['success'] ?? false)) {
                    $message = 'Error al subir a Cloudinary: ' . ($upload['error'] ?? 'desconocido');
                    $messageType = 'error';
                    break;
                }

                $image_path = $upload['secure_url'];
            }

            $stmt = $conn->prepare("INSERT INTO social_posts (platform, caption, image_url, post_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->bind_param('ssss', $platform, $caption, $image_path, $post_date);

            if ($stmt->execute()) {
                $message = 'Post agregado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error al agregar el post: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
            break;

        case 'delete_post':
            $post_id = intval($_POST['post_id']);

            $imageUrl = '';
            $lookup = $conn->prepare('SELECT image_url FROM social_posts WHERE id = ?');
            $lookup->bind_param('i', $post_id);
            if ($lookup->execute()) {
                $result = $lookup->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    $imageUrl = $row['image_url'] ?? '';
                }
            }
            $lookup->close();

            if ($imageUrl && ($publicId = extractCloudinaryPublicId($imageUrl))) {
                $cloudinaryAdmin->deleteImage($publicId);
            }

            $stmt = $conn->prepare('DELETE FROM social_posts WHERE id = ?');
            $stmt->bind_param('i', $post_id);

            if ($stmt->execute()) {
                $message = 'Post eliminado permanentemente.';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar el post: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
            break;
    }
}

$posts_query = "SELECT * FROM social_posts ORDER BY post_date DESC, created_at DESC";
$posts_result = $conn->query($posts_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Redes Sociales - Vale V Photography Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .social-post-card { transition: transform 0.2s ease; }
        .social-post-card:hover { transform: translateY(-2px); }
        .social-post-card img { height: 200px; object-fit: cover; }
        .card-header { background: linear-gradient(135deg, #000000 0%, #333333 100%) !important; color: white !important; border: none; }
        .btn-primary { background: linear-gradient(135deg, #000000 0%, #222222 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #111111 0%, #333333 100%); border: none; }
        .text-primary { color: #111111 !important; }
        .form-control:focus, .form-select:focus { border-color: #111111; box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.3); }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h1 class="mb-0"><i class="fas fa-share-alt text-primary me-2"></i>Gestion de Redes Sociales</h1>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Agregar Nuevo Post</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="platform" class="form-label">Plataforma</label>
                                    <select class="form-select" name="platform" required>
                                        <option value="">Seleccionar plataforma</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="instagram">Instagram</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="post_date" class="form-label">Fecha del Post</label>
                                    <input type="date" class="form-control" name="post_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="caption" class="form-label">Texto del Post</label>
                                <textarea class="form-control" name="caption" rows="3" placeholder="Escribe el texto del post..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="social_image" class="form-label">Subir imagen (opcional)</label>
                                <input type="file" class="form-control" name="social_image" accept="image/*" onchange="validateFileSize(this)">
                                <div class="form-text">Formatos aceptados: JPG, PNG, GIF o WEBP. Maximo 10MB.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Agregar Post
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Posts registrados</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($post = $posts_result->fetch_assoc()): ?>
                                    <?php $isActive = !isset($post['is_active']) || (int)$post['is_active'] === 1; ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <?php if (!empty($post['image_url'])): ?>
                                                <img src="<?php echo strpos($post['image_url'], 'http') === 0 ? htmlspecialchars($post['image_url']) : '../' . htmlspecialchars($post['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Imagen del post">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge bg-<?php echo $post['platform'] === 'facebook' ? 'primary' : 'danger'; ?>">
                                                        <i class="fab fa-<?php echo $post['platform']; ?> me-1"></i>
                                                        <?php echo ucfirst($post['platform']); ?>
                                                    </span>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['post_date'])); ?></small>
                                                </div>
                                                <?php if (!$isActive): ?>
                                                    <span class="badge bg-secondary mb-2">Inactivo</span>
                                                <?php endif; ?>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar permanentemente este post? Esta accion no se puede deshacer.');">
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay posts registrados aun. Agrega tu primer post!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateFileSize(input) {
            const maxSize = 10 * 1024 * 1024;
            const file = input.files[0];
            if (!file) {
                return false;
            }
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            if (file.size > maxSize) {
                alert(`La imagen es muy grande (${fileSizeMB}MB).\n\nTamano maximo permitido: 10MB\n\nPor favor, selecciona una imagen mas pequena.`);
                input.value = '';
                return false;
            }

            const fileInfo = document.createElement('div');
            fileInfo.className = 'alert alert-info mt-2';
            fileInfo.innerHTML = `Archivo seleccionado: <strong>${file.name}</strong> (${fileSizeMB}MB)`;
            const existingInfo = input.parentNode.querySelector('.alert-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            input.parentNode.appendChild(fileInfo);
            return true;
        }
    </script>
</body>
</html>

<?php closeConnection($conn); ?>
