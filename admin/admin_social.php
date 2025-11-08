<?php
// admin_social.php - Social media post management for admin
session_start();
require_once '../config/db_connect.php';
require_once '../config/cloudinary_admin.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

$message = '';
$messageType = '';
$cloudinaryAdmin = new CloudinaryAdmin();

$showAllPosts = isset($_GET['view']) && $_GET['view'] === 'all';
$archivedCount = 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_post':
                $platform = sanitizeInput($_POST['platform']);
                $caption = sanitizeInput($_POST['caption']);
                $post_date = sanitizeInput($_POST['post_date']);
                
                // Handle image upload with validation
                $image_path = '';
                if (isset($_FILES['social_image']) && $_FILES['social_image']['error'] === UPLOAD_ERR_OK) {
                    // Use Cloudinary directly for admin social uploads
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
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO social_posts (platform, caption, image_url, post_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
                $stmt->bind_param("ssss", $platform, $caption, $image_path, $post_date);
                
                if ($stmt->execute()) {
                    $message = 'Post agregado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al agregar el post: ' . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
                break;
                
            case 'archive_post':
                $post_id = intval($_POST['post_id']);
                $stmt = $conn->prepare("UPDATE social_posts SET is_active = 0, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $post_id);

                if ($stmt->execute()) {
                    $message = 'Post archivado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al archivar el post.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;

            case 'restore_post':
                $post_id = intval($_POST['post_id']);
                $stmt = $conn->prepare("UPDATE social_posts SET is_active = 1, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $post_id);

                if ($stmt->execute()) {
                    $message = 'Post restaurado y visible nuevamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al restaurar el post.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;

            case 'delete_post':
                $post_id = intval($_POST['post_id']);
                $stmt = $conn->prepare("DELETE FROM social_posts WHERE id = ?");
                $stmt->bind_param("i", $post_id);

                if ($stmt->execute()) {
                    $message = 'Post eliminado permanentemente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el post definitivamente.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

if ($conn) {
    $archivedResult = $conn->query("SELECT COUNT(*) AS total FROM social_posts WHERE is_active = 0");
    if ($archivedResult && $row = $archivedResult->fetch_assoc()) {
        $archivedCount = (int) ($row['total'] ?? 0);
    }
}

// Get social posts (active by default, all when requested)
$posts_query = "SELECT * FROM social_posts";
if (!$showAllPosts) {
    $posts_query .= " WHERE (is_active IS NULL OR is_active = 1)";
}
$posts_query .= " ORDER BY post_date DESC, created_at DESC";
$posts_result = $conn->query($posts_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Redes Sociales - Legend Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .social-post-card {
            transition: transform 0.2s ease;
        }
        
        .social-post-card:hover {
            transform: translateY(-2px);
        }
        
        .social-post-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .platform-facebook {
            border-left: 4px solid #1877F2;
        }
        
        .platform-instagram {
            border-left: 4px solid #E4405F;
        }
        
        .card-header {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%) !important;
            color: white !important;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #e55500 0%, #e56f00 100%);
            border: none;
        }
        
        .text-primary {
            color: #ff6600 !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #ff6600;
            box-shadow: 0 0 0 0.2rem rgba(255, 102, 0, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h1 class="mb-0"><i class="fas fa-share-alt text-primary me-2"></i>Gestión de Redes Sociales</h1>
                    <div class="d-flex gap-2">
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                        </a>
                        <a href="admin_social.php<?php echo $showAllPosts ? '' : '?view=all'; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-filter me-1"></i><?php echo $showAllPosts ? 'Ver solo activos' : 'Ver todo'; ?><?php if (!$showAllPosts && $archivedCount > 0) { echo ' (' . $archivedCount . ')'; } ?>
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add New Post Form -->
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
                                <input type="file" class="form-control" name="social_image" accept="image/*" onchange="validateFileSize(this, 'social')">
                                <div class="form-text">Formatos aceptados: JPG, PNG, GIF o WEBP. Máximo 10MB.</div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Agregar Post
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Existing Posts -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Posts <?php echo $showAllPosts ? 'registrados' : 'activos'; ?></h5>
                        <?php if ($showAllPosts && $archivedCount > 0): ?>
                            <span class="badge bg-secondary">Archivados: <?php echo $archivedCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($post = $posts_result->fetch_assoc()): ?>
                                    <?php 
                                        $isActive = !isset($post['is_active']) || (int)$post['is_active'] === 1;
                                        $statusBadge = $isActive ? '' : '<span class="badge bg-secondary ms-2">Archivado</span>';
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <?php if ($post['image_url']): ?>
                                                <img src="<?php echo strpos($post['image_url'],'http')===0 ? htmlspecialchars($post['image_url']) : '../'.htmlspecialchars($post['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge bg-<?php echo $post['platform'] === 'facebook' ? 'primary' : 'danger'; ?>">
                                                        <i class="fab fa-<?php echo $post['platform']; ?> me-1"></i>
                                                        <?php echo ucfirst($post['platform']); ?>
                                                    </span><?php echo $statusBadge; ?>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['post_date'])); ?></small>
                                                </div>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
                                                <div class="d-flex gap-2">
                                                    <?php if ($isActive): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Archivar este post? Dejará de mostrarse en el sitio público.')">
                                                            <input type="hidden" name="action" value="archive_post">
                                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Archivar">
                                                                <i class="fas fa-box-archive"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="restore_post">
                                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restaurar">
                                                                <i class="fas fa-rotate-left"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este post? Esta acción no se puede deshacer.')">
                                                            <input type="hidden" name="action" value="delete_post">
                                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar permanentemente">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay posts <?php echo $showAllPosts ? 'registrados' : 'activos'; ?></h5>
                                <?php if (!$showAllPosts && $archivedCount > 0): ?>
                                    <p class="text-muted">Todos los posts están archivados. Usa "Ver todo" para gestionarlos o restaurarlos.</p>
                                <?php else: ?>
                                    <p class="text-muted">¡Agrega tu primer post desde el formulario superior!</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Validate file size before upload
         */
        function validateFileSize(input, type = 'image') {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const file = input.files[0];
            
            if (file) {
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                
                if (file.size > maxSize) {
                    alert(`❌ La imagen es muy grande (${fileSizeMB}MB).\n\nTamaño máximo permitido: 10MB\n\nPor favor, selecciona una imagen más pequeña.`);
                    input.value = '';
                    return false;
                }
                
                // Show file info for user feedback
                const fileInfo = document.createElement('div');
                fileInfo.className = 'alert alert-info mt-2';
                fileInfo.innerHTML = `✅ Archivo seleccionado: <strong>${file.name}</strong> (${fileSizeMB}MB)`;
                
                // Remove any existing file info
                const existingInfo = input.parentNode.querySelector('.alert-info');
                if (existingInfo) {
                    existingInfo.remove();
                }
                
                // Add new file info
                input.parentNode.appendChild(fileInfo);
                
                return true;
            }
            return false;
        }
    </script>
</body>
</html>

<?php closeConnection($conn); ?>