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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_post':
                $platform = sanitizeInput($_POST['platform']);
                $caption = sanitizeInput($_POST['caption']);
                $post_date = sanitizeInput($_POST['post_date']);
                $cloud_public_id = isset($_POST['cloud_public_id']) ? trim($_POST['cloud_public_id']) : '';
                
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
                // If no file uploaded but Cloudinary selection was made, use that URL
                if (empty($image_path) && !empty($cloud_public_id)) {
                    $image_path = 'https://res.cloudinary.com/' . getCloudName() . '/image/upload/f_auto,q_auto/' . $cloud_public_id;
                }
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO social_posts (platform, caption, image_url, post_date) VALUES (?, ?, ?, ?)");
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
                
            case 'delete_post':
                $post_id = intval($_POST['post_id']);
                $stmt = $conn->prepare("DELETE FROM social_posts WHERE id = ?");
                $stmt->bind_param("i", $post_id);
                
                if ($stmt->execute()) {
                    $message = 'Post eliminado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el post.';
                    $messageType = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all social posts
$posts_query = "SELECT * FROM social_posts ORDER BY post_date DESC, created_at DESC";
$posts_result = $conn->query($posts_query);

// Fetch Cloudinary images from social_media folder for selection
$cloudImages = $cloudinaryAdmin->getImagesFromFolder('social_media');
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-share-alt text-primary me-2"></i>Gestión de Redes Sociales</h1>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                    </a>
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
                            <input type="hidden" name="cloud_public_id" id="cloud_public_id" value="">
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

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Seleccionar desde Cloudinary (carpeta social_media)</label>
                                    <div class="border rounded p-2" style="max-height:220px; overflow:auto">
                                        <div class="row g-2">
                                            <?php if ($cloudImages['success'] && !empty($cloudImages['images'])): ?>
                                                <?php foreach ($cloudImages['images'] as $img): ?>
                                                    <div class="col-4">
                                                        <img src="<?php echo $cloudinaryAdmin->getOptimizedUrl($img['public_id'], 150, 100); ?>" class="img-fluid border rounded select-cloud-img" data-public-id="<?php echo htmlspecialchars($img['public_id']); ?>" style="cursor:pointer">
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="col-12 text-muted small">No hay imágenes en Cloudinary/social_media</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="form-text">Haz clic en una imagen para seleccionarla.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="social_image" class="form-label">O subir imagen</label>
                                    <input type="file" class="form-control" name="social_image" accept="image/*" onchange="validateFileSize(this, 'social')">
                                    <div class="form-text">Formatos: JPG, PNG, GIF, WEBP. Máx 10MB.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Agregar Post
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Existing Posts -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Posts Actuales</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($post = $posts_result->fetch_assoc()): ?>
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
                                                    </span>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['post_date'])); ?></small>
                                                </div>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
                                                <div class="d-flex gap-2">
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este post?')">
                                                        <input type="hidden" name="action" value="delete_post">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="pickCloudForPost(<?php echo $post['id']; ?>)"><i class="fas fa-camera"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay posts agregados aún. ¡Agrega tu primer post!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const CLOUD_NAME = '<?php echo htmlspecialchars(getCloudName()); ?>';
        // Handle Cloudinary selection in form
        document.querySelectorAll('.select-cloud-img').forEach(img => {
            img.addEventListener('click', () => {
                const id = img.getAttribute('data-public-id');
                document.getElementById('cloud_public_id').value = id;
                img.classList.add('selected-image');
            });
        });

        // Assign Cloudinary image to an existing post quickly
        function pickCloudForPost(postId){
            const publicId = prompt('Public ID en Cloudinary (carpeta social_media/...)');
            if(!publicId) return;
            const url = `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/f_auto,q_auto/${publicId}`;
            fetch('social_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'update_image', id: postId, image_url: url})})
                .then(r=>r.json())
                .then(d=>{ if(d.success){ location.reload(); } else { alert(d.message||'Error'); } })
                .catch(err=> alert('Error: '+err.message));
        }
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