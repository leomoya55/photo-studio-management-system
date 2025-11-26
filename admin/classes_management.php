<?php
/**
 * Admin Dashboard - Classes Management with Cloudinary Integration
 * Allows Vanessa to manage classes and their images
 */

session_start();
require_once '../config/db_connect.php';
require_once '../config/cloudinary_admin.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit;
}

$cloudinaryAdmin = new CloudinaryAdmin();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'upload_image':
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Validate the image file first
                $validation = $cloudinaryAdmin->validateImageFile($_FILES['image']);
                
                if (!$validation['valid']) {
                    echo json_encode(['success' => false, 'error' => $validation['error']]);
                    exit;
                }
                
                $result = $cloudinaryAdmin->uploadImage(
                    $_FILES['image']['tmp_name'],
                    'classes',
                    $_POST['prefix'] ?? ''
                );
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'No image uploaded']);
            }
            exit;
            
        case 'update_class_image':
            $result = $cloudinaryAdmin->updateClassImage($_POST['class_id'], $_POST['public_id']);
            echo json_encode($result);
            exit;
            
        case 'delete_image':
            $result = $cloudinaryAdmin->deleteImage($_POST['public_id']);
            echo json_encode($result);
            exit;
            
        case 'get_images':
            $result = $cloudinaryAdmin->getImagesFromFolder('classes');
            echo json_encode($result);
            exit;
    }
}

// Load classes data from DB (fallback to JSON only if DB unavailable)
$classesData = [];
if (isset($conn) && $conn && !$conn->connect_error) {
    $sql = "SELECT id, name, description, level, duration, schedule, price, image, instructor, capacity, featured FROM classes WHERE active = 1 ORDER BY featured DESC, name ASC";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            // Normalize types
            $row['featured'] = (bool)($row['featured'] ?? 0);
            $row['price'] = (int)($row['price'] ?? 0);
            $row['capacity'] = isset($row['capacity']) ? (int)$row['capacity'] : 0;
            $classesData[] = $row;
        }
        $res->close();
    }
}
if (empty($classesData)) {
    $json = @file_get_contents('../data/classes.json');
    if ($json) { $classesData = json_decode($json, true) ?: []; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <title>Gestión de Clases - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .admin-sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
        }
        
        .admin-main {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .class-card {
            transition: transform 0.2s;
        }
        
        .class-card:hover {
            transform: translateY(-2px);
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .cloudinary-gallery {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cloudinary-image {
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .cloudinary-image:hover {
            transform: scale(1.05);
        }
        
        .selected-image {
            border: 3px solid #ff6600 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar text-white p-0">
                <div class="p-3">
                    <h4 class="text-center">
                        <i class="fas fa-crown me-2"></i>Panel Admin
                    </h4>
                    <hr>
                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link text-white active" href="#classes">
                            <i class="fas fa-dance me-2"></i>Gestión de Clases
                        </a>
                        
                        <a class="nav-link text-white" href="../views/index.php?admin_view=1">
                            <i class="fas fa-eye me-2"></i>Ver Sitio Web
                        </a>
                        <a class="nav-link text-white" href="../views/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 admin-main">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1><i class="fas fa-dance me-3"></i>Gestión de Clases</h1>
                    </div>
                    
                    <!-- Classes Grid -->
                    <div class="row" id="classesGrid">
                        <?php foreach ($classesData as $class): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card class-card h-100">
                                <div class="position-relative">
                                    <?php if (!empty($class['image'])): ?>
                                        <img src="<?php echo $cloudinaryAdmin->getOptimizedUrl($class['image'], 400, 250); ?>" 
                                             class="card-img-top image-preview" 
                                             alt="<?php echo htmlspecialchars($class['name']); ?>">
                                    <?php else: ?>
                                        <div class="image-preview bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <button class="btn btn-sm btn-warning me-1" onclick="editClass('<?php echo $class['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="changeImage('<?php echo $class['id']; ?>', '<?php echo htmlspecialchars($class['name']); ?>')">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($class['name']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($class['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="row g-2 small">
                                        <div class="col-6">
                                            <strong>Nivel:</strong> <?php echo htmlspecialchars($class['level']); ?>
                                        </div>
                                        <div class="col-6">
                                            <strong>Precio:</strong> ₡<?php echo number_format($class['price']); ?>
                                        </div>
                                        <div class="col-6">
                                            <strong>Duración:</strong> <?php echo htmlspecialchars($class['duration']); ?>
                                        </div>
                                        <div class="col-6">
                                            <strong>Capacidad:</strong> <?php echo $class['capacity']; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($class['featured']): ?>
                                        <span class="badge bg-warning mt-2">
                                            <i class="fas fa-star me-1"></i>Destacada
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Image Modal -->
    <div class="modal fade" id="changeImageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera me-2"></i>Cambiar Imagen - <span id="modalClassName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Upload New Image -->
                        <div class="col-md-6">
                            <h6><i class="fas fa-upload me-2"></i>Subir Nueva Imagen</h6>
                            <form id="uploadForm" enctype="multipart/form-data">
                                <input type="hidden" id="uploadClassId" name="class_id">
                                <input type="hidden" name="action" value="upload_image">
                                <input type="hidden" name="prefix" id="uploadPrefix">
                                
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="imageFile" name="image" accept="image/*" required onchange="validateFileSize(this, 'class')">
                                    <div class="form-text">Formatos soportados: JPG, PNG, GIF, WebP. Máximo <strong>10MB</strong>.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" id="uploadBtn">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Subir a Cloudinary
                                </button>
                            </form>
                            
                            <div id="uploadProgress" class="mt-3" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                </div>
                                <small class="text-muted">Subiendo imagen...</small>
                            </div>
                        </div>
                        
                        <!-- Select Existing Image -->
                        <div class="col-md-6">
                            <h6><i class="fas fa-images me-2"></i>Seleccionar Imagen Existente</h6>
                            <div class="cloudinary-gallery">
                                <div class="row g-2" id="cloudinaryImages">
                                    <div class="col-12 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando imágenes...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmImageChange" disabled>
                        <i class="fas fa-check me-2"></i>Aplicar Cambio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const CLOUD_NAME = '<?php echo htmlspecialchars(getCloudName()); ?>';
        let selectedImagePublicId = null;
        let currentClassId = null;

        function changeImage(classId, className) {
            currentClassId = classId;
            document.getElementById('modalClassName').textContent = className;
            document.getElementById('uploadClassId').value = classId;
            document.getElementById('uploadPrefix').value = className.toLowerCase().replace(/\s+/g, '-');
            
            // Load Cloudinary images
            loadCloudinaryImages();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('changeImageModal')).show();
        }

        function loadCloudinaryImages() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_images'
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('cloudinaryImages');
                
                if (data.success && data.images.length > 0) {
                    container.innerHTML = data.images.map(image => `
                        <div class="col-4 mb-2">
                            <img src="${getCloudinaryUrl(image.public_id, 150, 100)}" 
                                 class="img-fluid cloudinary-image border rounded" 
                                 alt="${image.public_id}"
                                 onclick="selectImage('${image.public_id}', this)"
                                 data-public-id="${image.public_id}">
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No hay imágenes disponibles</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading images:', error);
                document.getElementById('cloudinaryImages').innerHTML = '<div class="col-12"><p class="text-danger text-center">Error al cargar imágenes</p></div>';
            });
        }

        function selectImage(publicId, element) {
            // Remove previous selection
            document.querySelectorAll('.cloudinary-image').forEach(img => {
                img.classList.remove('selected-image');
            });
            
            // Add selection to clicked image
            element.classList.add('selected-image');
            selectedImagePublicId = publicId;
            
            // Enable confirm button
            document.getElementById('confirmImageChange').disabled = false;
        }

        function getCloudinaryUrl(publicId, width, height) {
            return `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/w_${width},h_${height},c_fill,f_auto,q_auto/${publicId}`;
        }

        // Handle image upload
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadProgress = document.getElementById('uploadProgress');
            
            uploadBtn.disabled = true;
            uploadProgress.style.display = 'block';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                uploadProgress.style.display = 'none';
                uploadBtn.disabled = false;
                
                if (data.success) {
                    // Auto-select the uploaded image
                    selectedImagePublicId = data.public_id;
                    document.getElementById('confirmImageChange').disabled = false;
                    
                    // Reload cloudinary images to show the new one
                    loadCloudinaryImages();
                    
                    alert('¡Imagen subida exitosamente!');
                } else {
                    alert('Error al subir imagen: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                uploadProgress.style.display = 'none';
                uploadBtn.disabled = false;
                console.error('Upload error:', error);
                alert('Error al subir la imagen');
            });
        });

        // Handle image change confirmation
        document.getElementById('confirmImageChange').addEventListener('click', function() {
            if (!selectedImagePublicId || !currentClassId) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_class_image&class_id=${currentClassId}&public_id=${selectedImagePublicId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Imagen actualizada exitosamente!');
                    location.reload(); // Reload to show changes
                } else {
                    alert('Error al actualizar imagen: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                alert('Error al actualizar la imagen');
            });
        });

        function editClass(classId) {
            // TODO: Implement class editing functionality
            alert('Función de edición en desarrollo');
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