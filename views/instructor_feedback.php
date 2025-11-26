<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is admin/instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

// Get all active enrollments for feedback
$stmt = $conn->prepare("
    SELECT e.*, u.first_name, u.last_name, u.email
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    WHERE e.status = 'active'
    ORDER BY e.class_name, u.first_name, u.last_name
");
$stmt->execute();
$enrollments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/images/favicon.svg">
    <title>Evaluaciones de Estudiantes - Vale V Photography</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .instructor-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
        }
        .feedback-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .student-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .rating-input {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .star-rating {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating:hover,
        .star-rating.active {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="instructor-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-clipboard-check me-2"></i>Evaluaciones de Estudiantes</h1>
                    <p class="mb-0">Panel de evaluación para instructores</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="/ProyectoVanessa/admin/admin.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                    <a href="../views/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-users me-2 text-primary"></i>Estudiantes Activos
                </h3>
                
                <?php if ($enrollments->num_rows > 0): ?>
                    <?php 
                    $current_class = '';
                    while ($enrollment = $enrollments->fetch_assoc()): 
                        if ($current_class !== $enrollment['class_name']):
                            if ($current_class !== '') echo '</div>'; // Close previous class section
                            $current_class = $enrollment['class_name'];
                    ?>
                        <h4 class="text-primary mt-4 mb-3">
                            <i class="fas fa-music me-2"></i><?php echo htmlspecialchars($current_class); ?>
                        </h4>
                        <div class="row">
                    <?php endif; ?>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card feedback-card">
                            <div class="card-body">
                                <div class="student-info">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($enrollment['email']); ?>
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Inscrito: <?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?>
                                    </small>
                                </div>
                                
                                <form class="feedback-form" data-enrollment-id="<?php echo $enrollment['id']; ?>" data-user-id="<?php echo $enrollment['user_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha de la clase</label>
                                        <input type="date" class="form-control" name="class_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Asistencia</label>
                                        <select class="form-control" name="attendance_status" required>
                                            <option value="present">Presente</option>
                                            <option value="late">Tardía</option>
                                            <option value="absent">Ausente</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Calificación de rendimiento</label>
                                        <div class="rating-input">
                                            <input type="hidden" name="performance_rating" value="">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star-rating" data-rating="<?php echo $i; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2 rating-text">Selecciona una calificación</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Fortalezas</label>
                                        <textarea class="form-control" name="strengths" rows="2" 
                                                  placeholder="¿Qué hizo bien el estudiante hoy?"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Áreas a mejorar</label>
                                        <textarea class="form-control" name="areas_for_improvement" rows="2" 
                                                  placeholder="¿En qué puede mejorar el estudiante?"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tarea asignada</label>
                                        <textarea class="form-control" name="homework_assigned" rows="2" 
                                                  placeholder="Ejercicios o práctica para realizar en casa..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notas generales</label>
                                        <textarea class="form-control" name="general_notes" rows="2" 
                                                  placeholder="Comentarios adicionales..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-save me-1"></i>Guardar Evaluación
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php endwhile; ?>
                    </div> <!-- Close last class section -->
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay estudiantes activos</h5>
                            <p class="text-muted">Los estudiantes aparecerán aquí cuando se inscriban a clases.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.star-rating').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                const container = this.closest('.rating-input');
                const input = container.querySelector('input[name="performance_rating"]');
                const text = container.querySelector('.rating-text');
                
                input.value = rating;
                text.textContent = `${rating}/5 estrellas`;
                
                // Update star display
                container.querySelectorAll('.star-rating').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        // Form submission
        document.querySelectorAll('.feedback-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('enrollment_id', this.dataset.enrollmentId);
                formData.append('user_id', this.dataset.userId);
                formData.append('instructor_id', <?php echo $_SESSION['user_id']; ?>);
                
                fetch('../views/save_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Evaluación guardada exitosamente');
                        this.reset();
                        // Reset star rating
                        this.querySelectorAll('.star-rating').forEach(s => s.classList.remove('active'));
                        this.querySelector('.rating-text').textContent = 'Selecciona una calificación';
                        this.querySelector('input[name="performance_rating"]').value = '';
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al guardar la evaluación');
                });
            });
        });
    </script>
</body>
</html>

<?php closeConnection($conn); ?>