<?php
// Admin dashboard bootstrap: session guard, DB connect, quick stats
session_start();
require_once '../config/session_manager.php';
if (!$isLoggedIn || ($userRole !== 'admin')) {
  header('Location: ../views/login.php');
  exit();
}
require_once '../config/db_connect.php';
require_once '../config/cloudinary_config.php';

// Compute top-level stats for dashboard cards
$stats = [
  'total_users' => 0,
  'total_enrollments' => 0,
  'new_users_7d' => 0,
  'new_enrollments_7d' => 0,
];
if ($conn) {
  try {
    // Total active users
    $res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE is_active = 1");
    if ($res) { $row = $res->fetch_assoc(); $stats['total_users'] = (int)($row['c'] ?? 0); }

    // Total enrollments (only for active users)
    $res = $conn->query("SELECT COUNT(*) AS c FROM enrollments e JOIN users u ON e.user_id = u.id AND u.is_active = 1");
    if ($res) { $row = $res->fetch_assoc(); $stats['total_enrollments'] = (int)($row['c'] ?? 0); }

    // New users in last 7 days
    $res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($res) { $row = $res->fetch_assoc(); $stats['new_users_7d'] = (int)($row['c'] ?? 0); }

    // New enrollments in last 7 days (only active users)
    $res = $conn->query("SELECT COUNT(*) AS c FROM enrollments e JOIN users u ON e.user_id = u.id AND u.is_active = 1 WHERE e.enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($res) { $row = $res->fetch_assoc(); $stats['new_enrollments_7d'] = (int)($row['c'] ?? 0); }
  } catch (Exception $e) {
    // Leave defaults if any query fails
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel de Administración - Legend Academy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://res.cloudinary.com" crossorigin>
  <style>
    body { background:#f6f7fb; }
    .brand-gradient { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); }
    .nav-pills .nav-link.active { background:#ff6600; }
    .nav-pills .nav-link { color:#ff6600; }
    .card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); }
    .card-stat { box-shadow:0 8px 24px rgba(0,0,0,.08); border:0; border-radius:16px; }
    .stat-icon { width:48px; height:48px; display:flex; align-items:center; justify-content:center; border-radius:12px; }
    .table thead th { background:#fafafa; }
    .badge-status { border-radius:20px; }
    .sticky-toolbar { position:sticky; top:0; z-index:2; background:#fff; padding:.75rem; border-bottom:1px solid #eee; }
    .btn-gradient { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); border:0; }
    .btn-gradient:hover { background: linear-gradient(135deg, #e55500 0%, #e56f00 100%); }
    .search-input { max-width:360px; }
  </style>
  <!-- PWA/service worker (optional) -->
  <link rel="manifest" href="/manifest.json">
  <script>
    // Optional: register service worker if present
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        try { navigator.serviceWorker.register('/sw.js'); } catch(e) {}
      });
    }
  </script>
  <meta name="theme-color" content="#ff6600" />
</head>
<body>
  <!-- Top bar -->
  <div class="brand-gradient text-white py-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <i class="fas fa-crown fa-lg"></i>
        <div>
          <div class="fw-semibold">Legend Dance Academy</div>
          <small>Panel de Administración</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-light" href="index.php"><i class="fas fa-home me-1"></i> Sitio</a>
        <a class="btn btn-sm btn-outline-light" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Salir</a>
      </div>
    </div>
  </div>

  <div class="container my-4">
    <div class="row g-3">
      <div class="col-lg-3 col-xl-2">
        <div class="card p-2" style="position:sticky; top:1rem;">
          <div class="nav flex-column nav-pills" id="adminTabs" role="tablist" aria-orientation="vertical">
            <button class="nav-link text-start active" id="tab-dashboard" data-bs-toggle="pill" data-bs-target="#pane-dashboard" type="button" role="tab"><i class="fas fa-chart-line me-2"></i> Dashboard</button>
            <button class="nav-link text-start" id="tab-clientes" data-bs-toggle="pill" data-bs-target="#pane-clientes" type="button" role="tab"><i class="fas fa-users me-2"></i> Gestión de clientes</button>
            <button class="nav-link text-start" id="tab-inscripciones" data-bs-toggle="pill" data-bs-target="#pane-inscripciones" type="button" role="tab"><i class="fas fa-list-check me-2"></i> Inscripciones</button>
            <button class="nav-link text-start" id="tab-clases" data-bs-toggle="pill" data-bs-target="#pane-clases" type="button" role="tab"><i class="fas fa-dumbbell me-2"></i> Clases</button>
            <button class="nav-link text-start" id="tab-productos" data-bs-toggle="pill" data-bs-target="#pane-productos" type="button" role="tab"><i class="fas fa-bag-shopping me-2"></i> Productos</button>
            <button class="nav-link text-start" id="tab-redes" data-bs-toggle="pill" data-bs-target="#pane-redes" type="button" role="tab"><i class="fas fa-share-alt me-2"></i> Redes sociales</button>
            <button class="nav-link text-start" id="tab-sesiones" data-bs-toggle="pill" data-bs-target="#pane-sesiones" type="button" role="tab"><i class="fas fa-calendar-day me-2"></i> Sesiones de clase</button>
            <button class="nav-link text-start" id="tab-asistencia" data-bs-toggle="pill" data-bs-target="#pane-asistencia" type="button" role="tab"><i class="fas fa-clipboard-check me-2"></i> Asistencia</button>
            <button class="nav-link text-start" id="tab-feedback" data-bs-toggle="pill" data-bs-target="#pane-feedback" type="button" role="tab"><i class="fas fa-comment-dots me-2"></i> Feedback estudiantes</button>
            <button class="nav-link text-start" id="tab-ordenes" data-bs-toggle="pill" data-bs-target="#pane-ordenes" type="button" role="tab"><i class="fas fa-box-open me-2"></i> Órdenes</button>
            <button class="nav-link text-start" id="tab-pagos" data-bs-toggle="pill" data-bs-target="#pane-pagos" type="button" role="tab"><i class="fas fa-receipt me-2"></i> Registro de Pagos</button>
            <button class="nav-link text-start" id="tab-progreso" data-bs-toggle="pill" data-bs-target="#pane-progreso" type="button" role="tab"><i class="fas fa-chart-simple me-2"></i> Progreso</button>
            <button class="nav-link text-start" id="tab-reportes" data-bs-toggle="pill" data-bs-target="#pane-reportes" type="button" role="tab"><i class="fas fa-file-export me-2"></i> Reportes</button>
          </div>
        </div>
      </div>
      <div class="col-lg-9 col-xl-10">
        <div class="tab-content" id="adminTabsContent">
      <!-- Dashboard -->
      <div class="tab-pane fade show active" id="pane-dashboard" role="tabpanel">
        <div class="row g-3">
          <div class="col-sm-6 col-lg-3"><div class="card card-stat p-3"><div class="d-flex align-items-center justify-content-between"><div><div class="text-muted small">Usuarios</div><div class="fs-4 fw-bold" id="statTotalUsers"><?php echo number_format($stats['total_users']); ?></div></div><div class="stat-icon bg-light text-primary"><i class="fas fa-users"></i></div></div></div></div>
          <div class="col-sm-6 col-lg-3"><div class="card card-stat p-3"><div class="d-flex align-items-center justify-content-between"><div><div class="text-muted small">Inscripciones</div><div class="fs-4 fw-bold" id="statTotalEnrollments"><?php echo number_format($stats['total_enrollments']); ?></div></div><div class="stat-icon bg-light text-success"><i class="fas fa-list-check"></i></div></div></div></div>
          <div class="col-sm-6 col-lg-3"><div class="card card-stat p-3"><div class="d-flex align-items-center justify-content-between"><div><div class="text-muted small">Nuevos (7 días)</div><div class="fs-4 fw-bold" id="statNewUsers7d"><?php echo number_format($stats['new_users_7d']); ?></div></div><div class="stat-icon bg-light text-warning"><i class="fas fa-user-plus"></i></div></div></div></div>
          <div class="col-sm-6 col-lg-3"><div class="card card-stat p-3"><div class="d-flex align-items-center justify-content-between"><div><div class="text-muted small">Inscripciones (7 días)</div><div class="fs-4 fw-bold" id="statNewEnrollments7d"><?php echo number_format($stats['new_enrollments_7d']); ?></div></div><div class="stat-icon bg-light text-danger"><i class="fas fa-plus"></i></div></div></div></div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-lg-8">
            <div class="card card-stat p-3 h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="fas fa-bell me-2 text-warning"></i>Actividades recientes</h5>
                <a class="link-muted small" href="#" onclick="reloadRecent()"><i class="fas fa-rotate"></i> actualizar</a>
              </div>
              <div id="recentActivity" class="small text-muted">Cargando...</div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card card-stat p-3 h-100">
              <h5 class="mb-3"><i class="fas fa-clipboard-list me-2 text-success"></i>Atajos</h5>
              <div class="d-grid gap-2">
                <a href="../admin/classes_management.php" class="btn btn-outline-secondary"><i class="fas fa-dumbbell me-2"></i>Gestionar Clases</a>
                <a href="../admin/admin_products.php" class="btn btn-outline-secondary"><i class="fas fa-bag-shopping me-2"></i>Gestionar Productos</a>
                <a href="../admin/admin_social.php" class="btn btn-outline-secondary"><i class="fas fa-share-alt me-2"></i>Gestionar Redes Sociales</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gestión de clientes -->
      <div class="tab-pane fade" id="pane-clientes" role="tabpanel">
        <div class="sticky-toolbar d-flex justify-content-between align-items-center">
          <div class="input-group search-input">
            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
            <input type="text" id="searchUsers" class="form-control" placeholder="Buscar por nombre o email..." />
          </div>
          <div class="text-muted small" id="usersCount">0 usuarios</div>
        </div>
        <div class="card mt-2">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="usersTableBody"><tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Inscripciones -->
      <div class="tab-pane fade" id="pane-inscripciones" role="tabpanel">
        <div class="sticky-toolbar d-flex flex-wrap gap-2 justify-content-between align-items-center">
          <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small">Filtrar estado:</span>
            <select id="enrollmentStatusFilter" class="form-select form-select-sm" style="width:160px">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Aprobado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div class="text-muted small" id="enrollmentsCount">0 inscripciones</div>
        </div>
        <div class="card mt-2">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>Estudiante</th><th>Clase</th><th>Horario</th><th>Fecha</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="enrollmentsTableBody"><tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Clases -->
      <div class="tab-pane fade" id="pane-clases" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0"><i class="fas fa-dumbbell me-2 text-primary"></i>Clases</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-gradient text-white" data-bs-toggle="modal" data-bs-target="#modalAddClass"><i class="fas fa-plus me-1"></i> Nueva clase</button>
          </div>
        </div>
        <div class="card p-3">
          <div id="classesGrid" class="row g-3">
            <div class="col-12 text-center text-muted py-4">Cargando...</div>
          </div>
        </div>
      </div>

      <!-- Productos -->
      <div class="tab-pane fade" id="pane-productos" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0"><i class="fas fa-bag-shopping me-2 text-primary"></i>Productos</h5>
          <a class="btn btn-sm btn-gradient text-white" href="../admin/admin_products.php"><i class="fas fa-plus me-1"></i> Gestionar</a>
        </div>
        <div class="card">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>Imagen</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Destacado</th><th>Actualizado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="productsTableBody"><tr><td colspan="7" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Redes sociales -->
      <div class="tab-pane fade" id="pane-redes" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0"><i class="fas fa-share-alt me-2 text-primary"></i>Redes Sociales</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="addPost()"><i class="fas fa-plus me-1"></i> Nuevo post</button>
            <a class="btn btn-sm btn-gradient text-white" href="../admin/admin_social.php"><i class="fas fa-gears me-1"></i> Gestionar</a>
          </div>
        </div>
        <div class="card">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>Imagen</th><th>Plataforma</th><th>Fecha</th><th>Texto</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="socialTableBody"><tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Órdenes -->
      <div class="tab-pane fade" id="pane-ordenes" role="tabpanel">
        <div class="sticky-toolbar d-flex flex-wrap gap-2 justify-content-between align-items-center">
          <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small">Estado:</span>
            <select id="ordersStatusFilter" class="form-select form-select-sm" style="width:170px">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Aprobada</option>
              <option value="completed">Completada</option>
              <option value="canceled">Cancelada</option>
            </select>
          </div>
          <div class="input-group search-input">
            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
            <input type="text" id="searchOrders" class="form-control" placeholder="Buscar por cliente o # de orden..." />
          </div>
          <div class="text-muted small" id="ordersCount">0 órdenes</div>
        </div>
        <div class="card mt-2">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Orden</th>
                  <th>Cliente</th>
                  <th>Método</th>
                  <th>Total</th>
                  <th>Estado</th>
                  <th>Comprobante</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="ordersTableBody"><tr><td colspan="8" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sesiones de clase -->
      <div class="tab-pane fade" id="pane-sesiones" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0"><i class="fas fa-calendar-day me-2 text-primary"></i>Sesiones</h5>
          <button class="btn btn-sm btn-gradient text-white" data-bs-toggle="modal" data-bs-target="#modalAddSession"><i class="fas fa-plus me-1"></i> Nueva sesión</button>
        </div>
        <div class="card">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>Fecha</th><th>Clase</th><th>Horario</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
              <tbody id="sessionsTableBody"><tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Asistencia -->
      <div class="tab-pane fade" id="pane-asistencia" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-4">
            <div class="card p-3 h-100">
              <h6 class="mb-2">Seleccionar sesión</h6>
              <select id="attendanceSession" class="form-select mb-2"></select>
              <div class="small text-muted">Elige una sesión para registrar asistencia.</div>
            </div>
          </div>
          <div class="col-lg-8">
            <div class="card p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Marcar asistencia</h6>
                <button class="btn btn-sm btn-outline-secondary" onclick="reloadAttendance()"><i class="fas fa-rotate"></i></button>
              </div>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead><tr><th>Estudiante</th><th>Asistió</th><th>Notas</th><th class="text-end">Guardar</th></tr></thead>
                  <tbody id="attendanceTableBody"><tr><td colspan="4" class="text-center text-muted py-4">Selecciona una sesión</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Feedback estudiantes -->
      <div class="tab-pane fade" id="pane-feedback" role="tabpanel">
        <div class="card p-3">
          <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Estudiante</label><select id="feedbackUser" class="form-select"></select></div>
            <div class="col-md-3"><label class="form-label">Inscripción</label><select id="feedbackEnrollment" class="form-select"></select></div>
            <div class="col-md-3"><label class="form-label">Fecha de clase</label><input type="date" id="feedbackDate" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="col-md-3"><label class="form-label">Calificación (1-10)</label><input type="number" id="feedbackRating" class="form-control" min="1" max="10" value="8"></div>
            <div class="col-md-4"><label class="form-label">Fortalezas</label><textarea id="feedbackStrengths" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">A mejorar</label><textarea id="feedbackImprovements" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Tarea</label><textarea id="feedbackHomework" class="form-control" rows="2"></textarea></div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-gradient text-white" onclick="submitFeedback()"><i class="fas fa-paper-plane me-1"></i> Guardar feedback</button></div>
        </div>
        <div class="card mt-3">
          <div class="table-responsive">
            <table class="table align-middle mb-0"><thead><tr><th>Fecha</th><th>Estudiante</th><th>Clase</th><th>Calificación</th><th>Notas</th></tr></thead><tbody id="feedbackTableBody"><tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr></tbody></table>
          </div>
        </div>
      </div>

      <!-- Registro de Pagos -->
      <div class="tab-pane fade" id="pane-pagos" role="tabpanel">
        <div class="card p-3">
          <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Estudiante</label><select id="paymentUser" class="form-select"></select></div>
            <div class="col-md-3"><label class="form-label">Inscripción (opcional)</label><select id="paymentEnrollment" class="form-select"></select></div>
            <div class="col-md-2"><label class="form-label">Monto (₡)</label><input type="number" id="paymentAmount" class="form-control" min="0" step="1000" placeholder="15000"></div>
            <div class="col-md-2"><label class="form-label">Método</label><select id="paymentMethod" class="form-select"><option value="sinpe" selected>SINPE</option><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option></select></div>
            <div class="col-md-2"><label class="form-label">Fecha</label><input type="date" id="paymentDate" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="col-md-4"><label class="form-label">Referencia</label><input type="text" id="paymentRef" class="form-control" placeholder="SINPE 123456"></div>
            <div class="col-md-8"><label class="form-label">Notas</label><input type="text" id="paymentNotes" class="form-control" placeholder="Detalle del pago (opcional)"></div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-gradient text-white" onclick="submitPayment()"><i class="fas fa-check me-1"></i> Registrar pago</button></div>
        </div>
        <div class="card mt-3"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Fecha</th><th>Estudiante</th><th>Clase</th><th>Método</th><th>Monto</th><th>Grabado por</th></tr></thead><tbody id="paymentsTableBody"><tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr></tbody></table></div></div>
      </div>

      <!-- Progreso estudiantes (placeholder) -->
      <div class="tab-pane fade" id="pane-progreso" role="tabpanel">
        <div class="card p-4 text-center text-muted"><i class="fas fa-person-running fa-2x mb-2"></i><p>Aún estamos definiendo este módulo. Próximamente podrás ver y actualizar el progreso de cada estudiante.</p></div>
      </div>

      <!-- Reportes -->
      <div class="tab-pane fade" id="pane-reportes" role="tabpanel">
        <div class="card p-3">
          <h6 class="mb-3">Exportar datos</h6>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary" onclick="exportCSV('users')"><i class="fas fa-download me-1"></i> Usuarios</button>
            <button class="btn btn-outline-secondary" onclick="exportCSV('enrollments')"><i class="fas fa-download me-1"></i> Inscripciones</button>
            <button class="btn btn-outline-secondary" onclick="exportCSV('payment_records')"><i class="fas fa-download me-1"></i> Pagos</button>
            <button class="btn btn-outline-secondary" onclick="exportCSV('orders')"><i class="fas fa-download me-1"></i> Órdenes</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Add Session -->
  <div class="modal fade" id="modalAddSession" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Nueva sesión</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Clase</label><select id="newSessionClass" class="form-select"></select></div>
          <div class="col-6"><label class="form-label">Fecha</label><input type="date" id="newSessionDate" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
          <div class="col-3"><label class="form-label">Inicio</label><input type="time" id="newSessionStart" class="form-control" value="18:00"></div>
          <div class="col-3"><label class="form-label">Fin</label><input type="time" id="newSessionEnd" class="form-control" value="19:00"></div>
          <div class="col-6"><label class="form-label">Estado</label><select id="newSessionStatus" class="form-select"><option value="scheduled" selected>Programada</option><option value="completed">Completada</option><option value="cancelled">Cancelada</option></select></div>
          
          <div class="col-12"><label class="form-label">Notas</label><input type="text" id="newSessionNotes" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-gradient text-white" onclick="createSession()">Crear sesión</button></div>
    </div></div>
  </div>

  <!-- Modal: Add Class -->
  <div class="modal fade" id="modalAddClass" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nueva clase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" id="newClassName" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Instructor</label><input type="text" id="newClassInstructor" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label">Nivel</label><input type="text" id="newClassLevel" class="form-control" placeholder="Principiante"></div>
          <div class="col-md-4"><label class="form-label">Duración</label><input type="text" id="newClassDuration" class="form-control" placeholder="60 min"></div>
          
          <div class="col-md-4"><label class="form-label">Precio (₡)</label><input type="number" id="newClassPrice" class="form-control" min="0" step="500" placeholder="15000"></div>
          <div class="col-md-4"><label class="form-label">Categoría</label>
            <select id="newClassCategory" class="form-select">
              <option value="Contemporaneo">Contemporaneo</option>
              <option value="Urbano">Urbano</option>
              <option value="Latino">Latino</option>
              <option value="Fitness">Fitness</option>
              <option value="Infantil">Infantil</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Horario</label><input type="text" id="newClassSchedule" class="form-control" placeholder="Lun 6-7pm"></div>
          <div class="col-12"><label class="form-label">Descripción</label><textarea id="newClassDesc" class="form-control" rows="2"></textarea></div>
          <div class="col-12"><label class="form-label">Imagen (opcional)</label><input type="file" id="newClassImageFile" class="form-control" accept="image/*"></div>
          <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" id="newClassFeatured"> <label class="form-check-label" for="newClassFeatured">Destacar clase</label></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-gradient text-white" onclick="createClassFromAdmin()">Crear</button></div>
    </div></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Cloudinary cloud name for building public-id URLs on the client
    const CLOUDINARY_CLOUD = '<?php echo htmlspecialchars(getCloudName(), ENT_QUOTES); ?>';

    const api = {
      users: '../admin/users_api.php?endpoint=users',
      enrollments: '../admin/users_api.php?endpoint=enrollments',
      payments: '../admin/payments_api.php',
      sessions: '../admin/sessions_api.php',
      attendance: '../admin/attendance_api.php',
      feedback: '../admin/feedback_api.php',
      classes: '../admin/get_classes.php',
      products: '../admin/products_api.php',
      social: '../admin/social_api.php',
      orders: '../admin/users_api.php?endpoint=orders',
      deleteEnrollment: '../admin/delete_enrollment.php',
      updateEnrollmentStatus: '../admin/update_enrollment_status.php'
    };

    const fmtMoney = v => new Intl.NumberFormat('es-CR').format(Number(v||0));
    const el = id => document.getElementById(id);
    const isImageUrl = (u) => /\.(png|jpe?g|gif|webp)$/i.test(u||'');
    const isPdfUrl = (u) => /\.pdf$/i.test(u||'') || String(u||'').toLowerCase().includes('application/pdf');
    const extractUrlFromNotes = (notes) => {
      const n = String(notes||'');
      const m = n.match(/https?:\/\/\S+/i);
      return m ? m[0].replace(/[\)\]]+$/,'') : '';
    };

    // Simple caches
    let sessionsCache = [];

    // Map status (English/Spanish) to canonical keys
    function normalizeStatus(st){
      const s = String(st||'').toLowerCase();
      if(['pending','pendiente'].includes(s)) return 'pending';
      if(['approved','aprobada','aprobado'].includes(s)) return 'approved';
      if(['completed','completada','completado'].includes(s)) return 'completed';
      if(['canceled','cancelled','cancelada','cancelado'].includes(s)) return 'canceled';
      return s || 'pending';
    }

    // Recent activity
    async function reloadRecent() {
      try {
        const [enrs, pays, ords] = await Promise.all([
          fetch(api.enrollments).then(r=>r.json()),
          fetch(api.payments).then(r=>r.json()),
          fetch(api.orders).then(r=>r.json())
        ]);
        const events = [];
        (enrs||[]).slice(0,10).forEach(e=>events.push({t:'inscripción', ts: e.enrollment_date, msg: `${e.full_name || (e.first_name+" "+e.last_name)} → ${e.class_name} (${e.status})`}));
        (pays||[]).slice(0,10).forEach(p=>events.push({t:'pago', ts: p.payment_date, msg: `${p.student_name} ₡${fmtMoney(p.amount)} via ${p.payment_method}`}));
        (ords||[]).slice(0,10).forEach(o=>{
          const total = Number(o.total_amount||0) + Number(o.delivery_cost||0);
          events.push({ t:'orden', ts: o.created_at, msg: `#${o.order_number} ${o.customer_name||''} ₡${fmtMoney(total)} (${normalizeStatus(o.status)})` });
        });
        events.sort((a,b)=> (b.ts||'').localeCompare(a.ts||''));
        el('recentActivity').innerHTML = events.slice(0,10).map(ev=>`<div class="d-flex gap-2 align-items-center py-1"><span class="badge bg-light text-dark text-capitalize">${ev.t}</span><span>${ev.msg}</span><span class="ms-auto small text-muted">${ev.ts ? new Date(ev.ts).toLocaleDateString('es-CR') : ''}</span></div>`).join('') || '<span class="text-muted">Sin actividad reciente</span>';
      } catch(e) { el('recentActivity').innerHTML = '<span class="text-danger">Error al cargar</span>'; }
    }

    // Users
    let usersCache = [];
    async function loadUsers() {
      const tbody = el('usersTableBody');
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try { usersCache = await fetch(api.users).then(r=>r.json()) || []; renderUsers(usersCache); }
      catch(e){ tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar usuarios</td></tr>`; }
    }
    function renderUsers(list){
      const tbody = el('usersTableBody');
      if (!Array.isArray(list) || !list.length) { tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Sin usuarios</td></tr>`; return; }
      tbody.innerHTML = list.map(u=>`<tr>
        <td>${(u.full_name||'').trim() || (u.first_name||'')+' '+(u.last_name||'')}</td>
        <td>${u.email||''}</td>
        <td>${u.phone||''}</td>
        <td><span class="badge rounded-pill bg-light text-dark">${u.role||'customer'}</span></td>
        <td>${u.created_at ? new Date(u.created_at).toLocaleDateString('es-CR') : ''}</td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-danger" title="Desactivar usuario" onclick="deleteUser(${u.id})"><i class="fas fa-user-slash"></i></button>
            <button class="btn btn-outline-dark" title="Eliminar permanentemente" onclick="hardDeleteUser(${u.id})"><i class="fas fa-skull"></i></button>
          </div>
        </td>
      </tr>`).join('');
      el('usersCount').textContent = `${list.length} usuario${list.length!==1?'s':''}`;
    }
    async function deleteUser(id){
      if(!confirm('¿Eliminar este cliente? (desactivar cuenta)')) return;
      try{
        const res = await fetch(api.users.replace('?endpoint=users',''),{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({type:'user', action:'delete', user_id:id})}).then(r=>r.json());
        if(!res.success) throw new Error(res.message||'Error');
        await loadUsers();
        // also refresh enrollments so deleted user's enrollments disappear immediately
        await loadEnrollments();
      }catch(e){ alert('No se pudo eliminar: '+e.message); }
    }
    async function hardDeleteUser(id){
      const msg = 'Esta acción eliminará permanentemente al usuario si no tiene registros relacionados. Escribe ELIMINAR para confirmar:';
      const conf = prompt(msg);
      if (conf !== 'ELIMINAR') return;
      try{
        const res = await fetch(api.users.replace('?endpoint=users',''),{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({type:'user', action:'hard_delete', user_id:id})}).then(r=>r.json());
        if(!res.success) throw new Error(res.message||'Error');
        await loadUsers();
        await loadEnrollments();
      }catch(e){ alert('No se pudo eliminar permanentemente: '+e.message); }
    }
    el('searchUsers').addEventListener('input', (e)=>{
      const q = e.target.value.toLowerCase();
      renderUsers(usersCache.filter(u=> (`${u.full_name||u.first_name||''} ${u.last_name||''}`.toLowerCase().includes(q) || (u.email||'').toLowerCase().includes(q)) ));
    });

    // Enrollments
    let enrollmentsCache = [];
    async function loadEnrollments(){
      const tbody = el('enrollmentsTableBody');
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try {
        const data = await fetch(api.enrollments).then(r=>r.json());
        enrollmentsCache = Array.isArray(data) ? data : [];
        renderEnrollments();
      } catch(e) { tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar inscripciones</td></tr>`; }
    }
  function statusBadge(st){ const key=(st||'').toLowerCase() || 'pending'; const m={pending:'secondary', approved:'primary', rejected:'danger'}; const label = key; return `<span class="badge badge-status bg-${m[key]||'light'} ${m[key]?'text-white':'text-dark'} text-capitalize">${label}</span>`; }
    function renderEnrollments(){
      const filter = el('enrollmentStatusFilter').value;
      const tbody = el('enrollmentsTableBody');
      const list = enrollmentsCache.filter(e=> !filter || (e.status||'').toLowerCase()===filter.toLowerCase());
      el('enrollmentsCount').textContent = `${list.length} inscripción${list.length!==1?'es':''}`;
      if(!list.length){ tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Sin inscripciones</td></tr>`; return; }
      tbody.innerHTML = list.map(e=>`<tr>
        <td>${e.full_name || (e.first_name+" "+e.last_name)}</td>
        <td>${e.class_name||''}</td>
        <td class="small">${e.class_schedule||e.selected_schedule||''}</td>
        <td>${e.enrollment_date ? new Date(e.enrollment_date).toLocaleDateString('es-CR') : ''}</td>
        <td>${statusBadge(e.status)}</td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-success" title="Aprobar" onclick="updateEnrollmentStatus(${e.id}, 'approved')"><i class="fas fa-check"></i></button>
            <button class="btn btn-outline-danger" title="Rechazar" onclick="updateEnrollmentStatus(${e.id}, 'rejected')"><i class="fas fa-xmark"></i></button>
            <button class="btn btn-outline-dark" title="Eliminar" onclick="deleteEnrollment(${e.id})"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`).join('');
    }
    async function updateEnrollmentStatus(id, status){
      try{
        const r = await fetch(api.updateEnrollmentStatus,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({enrollment_id:id, status})}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        // Optimistic update in cache
        const idx = enrollmentsCache.findIndex(e=> String(e.id) === String(id));
        if (idx >= 0) enrollmentsCache[idx].status = status;
        await loadEnrollments();
        // Refresh attendance list for selected session so newly approved students appear
        reloadAttendance();
      }catch(e){ alert('No se pudo actualizar: '+e.message); }
    }
    async function deleteEnrollment(id){ if(!confirm('¿Eliminar inscripción? Esta acción no se puede deshacer.')) return; try{ const r=await fetch(api.deleteEnrollment,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({enrollment_id:id})}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadEnrollments(); }catch(e){ alert('No se pudo eliminar: '+e.message);} }

    // Orders
    let ordersCache = [];
  function orderStatusBadge(st){ const m={pending:'secondary', approved:'primary', completed:'success', canceled:'dark'}; const key=normalizeStatus(st); const label = key; return `<span class="badge badge-status bg-${m[key]||'light'} ${m[key]?'text-white':'text-dark'} text-capitalize">${label}</span>`; }
    async function loadOrders(){
      const tbody = el('ordersTableBody');
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try {
        const data = await fetch(api.orders).then(r=>r.json());
        ordersCache = Array.isArray(data) ? data : [];
        renderOrders();
      } catch(e){ tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error al cargar órdenes</td></tr>`; }
    }
    function renderOrders(){
  const filter = normalizeStatus(el('ordersStatusFilter').value||'');
      const q = (el('searchOrders').value||'').toLowerCase();
      let list = ordersCache.slice();
  if (filter) list = list.filter(o=> normalizeStatus(o.status)===filter);
      if (q) list = list.filter(o=> (o.customer_name||'').toLowerCase().includes(q) || (o.order_number||'').toLowerCase().includes(q));
      el('ordersCount').textContent = `${list.length} orden${list.length!==1?'es':''}`;
      const tbody = el('ordersTableBody');
      if (!list.length){ tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Sin órdenes</td></tr>`; return; }
      tbody.innerHTML = list.map(o=>{
        const proof = ((o.payment_proof_url||'').trim()) || extractUrlFromNotes(o.notes||'');
        const proofBtn = proof ? `<button class="btn btn-sm btn-outline-info" onclick="viewProof('${(proof||'').replace(/'/g,"&#39;")}')"><i class="fas fa-eye"></i> Ver</button>` : '<span class="text-muted small">—</span>';
        const total = Number(o.total_amount||0) + Number(o.delivery_cost||0);
        const st = normalizeStatus(o.status);
        const canApprove = st==='pending';
        const canComplete = st==='approved' || st==='pending';
        const canCancel = st==='pending' || st==='approved';
        return `<tr>
          <td>${o.created_at ? new Date(o.created_at).toLocaleDateString('es-CR') : ''}</td>
          <td><div class="fw-semibold">${o.order_number||''}</div><div class="small text-muted">${(o.items||'').slice(0,120)}</div></td>
          <td>${o.customer_name||''}<div class="small text-muted">${o.customer_phone||''}</div></td>
          <td class="text-capitalize">${o.payment_method||''}</td>
          <td>₡${fmtMoney(total)}</td>
          <td>${orderStatusBadge(o.status)}</td>
          <td>${proofBtn}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary" ${canApprove?'':'disabled'} onclick="updateOrderStatus(${o.id}, 'approved')" title="Aprobar"><i class="fas fa-check"></i></button>
              <button class="btn btn-outline-success" ${canComplete?'':'disabled'} onclick="updateOrderStatus(${o.id}, 'completed')" title="Completar"><i class="fas fa-flag-checkered"></i></button>
              <button class="btn btn-outline-danger" ${canCancel?'':'disabled'} onclick="updateOrderStatus(${o.id}, 'canceled')" title="Cancelar"><i class="fas fa-xmark"></i></button>
              <button class="btn btn-danger" onclick="deleteOrder(${o.id})" title="Eliminar"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
      }).join('');
    }
    async function updateOrderStatus(id, status){
      try {
        const r = await fetch(api.users.replace('?endpoint=users',''), { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ type:'order', action:'update_status', order_id: id, status }) }).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        await loadOrders();
      } catch(e){ alert('No se pudo actualizar la orden: '+e.message); }
    }
    async function deleteOrder(id){
      if (!confirm('¿Eliminar esta orden? Esta acción no se puede deshacer.')) return;
      try {
        const r = await fetch(api.users.replace('?endpoint=users',''), { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ type:'order', action:'delete', order_id: id }) }).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        await loadOrders();
      } catch(e){ alert('No se pudo eliminar la orden: '+e.message); }
    }
    function viewProof(url){
      const modal = new bootstrap.Modal(document.getElementById('modalViewProof'));
      const box = document.getElementById('proofViewerBox');
      const u = String(url||'');
      let html = '';
      if (isPdfUrl(u)) {
        html = `<div class="d-flex justify-content-between align-items-center mb-2">
                  <a class="btn btn-sm btn-outline-secondary" href="${u}" target="_blank" rel="noopener">Abrir en nueva pestaña</a>
                </div>
                <iframe src="${u}" style="width:100%;height:70vh;border:0" title="Comprobante"></iframe>`;
      } else {
        html = `<div class="d-flex justify-content-between align-items-center mb-2">
                  <a class="btn btn-sm btn-outline-secondary" href="${u}" target="_blank" rel="noopener">Abrir en nueva pestaña</a>
                </div>
                <div style="overflow:auto; max-height:70vh; text-align:center">
                  <img src="${u}" alt="Comprobante" style="max-width:100%;height:auto;cursor:zoom-in" onclick="window.open('${u}','_blank')">
                </div>`;
      }
      box.innerHTML = html;
      modal.show();
    }
    el('ordersStatusFilter').addEventListener('change', renderOrders);
    el('searchOrders').addEventListener('input', renderOrders);


    // Classes (grid-based)
    let classesCache = [];
    // URL-encode a Cloudinary public_id but preserve folder slashes
    const encodeCloudinaryPublicId = (pid) => encodeURIComponent(pid).replace(/%2F/gi, '/');

    // Build an optimized Cloudinary URL (fill crop) at target size
    const buildCloudinaryUrl = (pid, w=720, h=405) => {
      const publicId = (pid || '').trim().replace(/^\/+/, '');
      if (!publicId) return '';
      const cloud = CLOUDINARY_CLOUD || 'deov2g1ji';
      const encoded = encodeCloudinaryPublicId(publicId);
      return `https://res.cloudinary.com/${cloud}/image/upload/f_auto,q_auto,dpr_auto,c_fill,w_${w},h_${h}/${encoded}`;
    };
    const isHttpUrl = (v) => /^https?:\/\//i.test(v || '');
    const transformCloudinarySecureUrl = (url, w=720, h=405) => {
      const u = (url || '').trim();
      if (!u) return '';
      // Only transform Cloudinary URLs
      if (!/https?:\/\/res\.cloudinary\.com\//i.test(u)) return u;
      // Insert/replace the transformation segment
      return u.replace(/\/image\/upload\/(?:[^/]*\/)?/i, `/image/upload/f_auto,q_auto,dpr_auto,c_fill,w_${w},h_${h}/`);
    };
    const resolveClassImage = (imageVal) => {
      const v = (imageVal || '').trim();
      if (!v) return '';
      if (v.startsWith('http://') || v.startsWith('https://')) return v; // already full URL
      // Heuristic: if there are spaces, likely the last token is the actual public_id
      const candidate = v.includes(' ') ? v.split(/\s+/).filter(Boolean).pop() : v;
      return buildCloudinaryUrl(candidate, 720, 405);
    };

    // Fallback loader attempts a couple of alternative URL shapes before giving up
    window.fallbackClassImage = (imgEl) => {
      try {
        const tries = Number(imgEl.getAttribute('data-tries') || '0');
        if (tries > 2) { // final fallback
          imgEl.onerror = null;
          imgEl.src = 'https://via.placeholder.com/720x405?text=Clase';
          return;
        }
        const val = (imgEl.getAttribute('data-imgval') || '').trim();
        const isUrl = imgEl.getAttribute('data-isurl') === '1';
        let nextSrc = '';
        if (!val) {
          nextSrc = 'https://via.placeholder.com/720x405?text=Clase';
        } else if (isUrl) {
          // Try untransformed original URL on first failure
          if (tries === 0) {
            nextSrc = val;
          } else if (tries === 1) {
            // Try transformed again but different size
            nextSrc = transformCloudinarySecureUrl(val, 840, 473);
          } else {
            nextSrc = 'https://via.placeholder.com/720x405?text=Clase';
          }
        } else {
          // Treat as public id or local path, try multiple heuristics
          const candidates = [];
          const parts = val.split(/\s+/).filter(Boolean);
          const lastToken = parts.length ? parts[parts.length - 1] : val;
          // 1) last token as-is
          candidates.push(buildCloudinaryUrl(lastToken, 720, 405));
          // 2) last token but prefixed with classes/
          candidates.push(buildCloudinaryUrl(`classes/${lastToken}`, 720, 405));
          // 3) original full value as-is (may include folder)
          candidates.push(buildCloudinaryUrl(val, 720, 405));
          // 4) last token with spaces replaced by underscore
          candidates.push(buildCloudinaryUrl(lastToken.replace(/\s+/g,'_'), 720, 405));
          // 5) local relative path attempt (legacy)
          candidates.push(val.startsWith('/') ? `..${val}` : `../${val}`);
          nextSrc = candidates[Math.min(tries, candidates.length-1)];
        }
        imgEl.setAttribute('data-tries', String(tries + 1));
        imgEl.src = nextSrc || 'https://via.placeholder.com/720x405?text=Clase';
      } catch (e) {
        imgEl.onerror = null;
        imgEl.src = 'https://via.placeholder.com/720x405?text=Clase';
      }
    };
    async function loadClasses(){
      const grid = document.getElementById('classesGrid');
      grid.innerHTML = `<div class="col-12 text-center text-muted py-4">Cargando...</div>`;
      try {
        const data = await fetch(api.classes).then(r=>r.json());
        classesCache = Array.isArray(data)? data: [];
        if(!classesCache.length){ grid.innerHTML = `<div class="col-12 text-center text-muted py-4">Sin clases</div>`; return; }
        grid.innerHTML = classesCache.map((c, i)=>{
          const val = (c.image || '').trim();
          let src360, src720, src1080;
          if (!val) {
            src360 = 'https://via.placeholder.com/360x203?text=Clase';
            src720 = 'https://via.placeholder.com/720x405?text=Clase';
            src1080 = 'https://via.placeholder.com/1080x608?text=Clase';
          } else if (isHttpUrl(val)) {
            // Full secure URL (likely Cloudinary) -> inject transforms
            src360 = transformCloudinarySecureUrl(val, 360, 203);
            src720 = transformCloudinarySecureUrl(val, 720, 405);
            src1080 = transformCloudinarySecureUrl(val, 1080, 608);
          } else {
            // Public ID -> build Cloudinary URL using last token if spaces exist
            const token = val.includes(' ') ? val.split(/\s+/).filter(Boolean).pop() : val;
            src360 = buildCloudinaryUrl(token, 360, 203);
            src720 = buildCloudinaryUrl(token, 720, 405);
            src1080 = buildCloudinaryUrl(token, 1080, 608);
          }
      const sizes = "(min-width:1200px) 33vw, (min-width:992px) 33vw, (min-width:768px) 50vw, 100vw";
      const priority = i < 3 ? 'high' : 'auto';
      const safeVal = (c.image||'').trim();
      const imgTag = `<img 
        src="${src720}"
        srcset="${src360} 360w, ${src720} 720w, ${src1080} 1080w"
        sizes="${sizes}"
        loading="lazy" decoding="async" fetchpriority="${priority}"
        data-imgval="${safeVal.replace(/"/g,'&quot;')}" data-isurl="${isHttpUrl(safeVal) ? '1' : '0'}" data-tries="0"
        onerror="fallbackClassImage(this)"
        class="card-img-top" style="height:200px;object-fit:cover;">`;
          return `<div class="col-md-6 col-lg-4"><div class="card h-100">`
            + `<div class="position-relative">${imgTag}<div class="position-absolute top-0 end-0 p-2">`
            + `<button class="btn btn-sm btn-info me-1" title="Cambiar imagen" data-id="${c.id}" data-name="${(c.name||'').replace(/"/g,'&quot;')}" onclick="openClassImageModal(this)"><i class="fas fa-camera"></i></button>`
            + `<button class="btn btn-sm btn-warning me-1" title="Editar" data-id="${c.id}" onclick="openEditClass(this)"><i class="fas fa-pencil"></i></button>`
            + `<button class="btn btn-sm btn-danger" title="Eliminar" onclick="deleteClass('${c.id}')"><i class="fas fa-trash"></i></button>`
            + `</div></div>`
            + `<div class="card-body">`
            + `<h6 class="mb-1">${c.name}</h6>`
            + `<div class="text-muted small">${c.level||''} • ₡${fmtMoney(c.price)}</div>`
            + `${c.featured? '<span class="badge bg-warning text-dark mt-2"><i class="fas fa-star me-1"></i>Destacada</span>':''}`
            + `</div></div></div>`;
        }).join('');
        const sel = el('newSessionClass');
        sel.innerHTML = classesCache.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
        // If there's a selected class, prefill times from its schedule/duration
        if (sel && sel.value) {
          prefillSessionFromClassId(sel.value);
        }
      } catch(e) { grid.innerHTML = `<div class="col-12 text-center text-danger py-4">Error al cargar clases</div>`; }
    }

    function openClassImageModal(a){
      let btn = a; let id = btn?.dataset?.id; let name = btn?.dataset?.name || '';
      if(!id && typeof a === 'string'){ id = a; }
      document.getElementById('uploadClassId').value = id || '';
      document.getElementById('uploadClassName').textContent = name;
      document.getElementById('uploadClassFile').value = '';
      new bootstrap.Modal(document.getElementById('modalUploadClassImage')).show();
    }

    function openEditClass(btn){
      const id = btn.dataset.id;
      const cls = (classesCache||[]).find(x=> String(x.id) === String(id));
      if(!cls){ alert('Clase no encontrada'); return; }
      el('editClassId').value = cls.id;
      el('editClassName').value = cls.name||'';
      el('editClassInstructor').value = cls.instructor||'';
      el('editClassLevel').value = cls.level||'';
      el('editClassDuration').value = cls.duration||'';
      
      el('editClassPrice').value = cls.price||0;
      // Ensure category select contains current value; if not, append it
      const catSel = el('editClassCategory');
      const currentCat = cls.category||'';
      if (currentCat && !Array.from(catSel.options).some(o=> o.value === currentCat)){
        const opt = document.createElement('option');
        opt.value = currentCat; opt.textContent = currentCat; catSel.appendChild(opt);
      }
      catSel.value = currentCat || 'Contemporaneo';
      el('editClassSchedule').value = cls.schedule||'';
      el('editClassDesc').value = cls.description||'';
      el('editClassFeatured').checked = !!cls.featured;
      new bootstrap.Modal(document.getElementById('modalEditClass')).show();
    }

    async function updateClassFromAdmin(){
      const id = el('editClassId').value;
      const payload = { action: 'edit', id, class: {
        name: el('editClassName').value.trim(),
        instructor: el('editClassInstructor').value.trim(),
        level: el('editClassLevel').value.trim() || 'Principiante',
        duration: el('editClassDuration').value.trim() || '60 min',
        
        price: Number(el('editClassPrice').value||0),
  category: el('editClassCategory').value.trim() || 'Contemporaneo',
        schedule: el('editClassSchedule').value.trim() || '',
        description: el('editClassDesc').value.trim() || '',
        featured: el('editClassFeatured').checked,
        benefits: []
      }};
      try{
        const r = await fetch('../admin/save_classes.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalEditClass')).hide();
        await loadClasses();
      }catch(e){ alert('No se pudo actualizar la clase: '+e.message); }
    }

    async function deleteClass(id){
      if(!confirm('¿Eliminar esta clase?')) return;
      try{
        const r = await fetch('../admin/save_classes.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'delete', id })}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        await loadClasses();
      }catch(e){ alert('No se pudo eliminar la clase: '+e.message); }
    }

    // Products
    async function loadProducts(){
      const tbody = el('productsTableBody');
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try{
        const data = await fetch(api.products).then(r=>r.json());
        const list = Array.isArray(data)? data: [];
        if(!list.length){ tbody.innerHTML = `<tr><td colspan=\"7\" class=\"text-center text-muted py-4\">Sin productos</td></tr>`; return; }
        tbody.innerHTML = list.map(p=>{
          const img = p.image_url ? (p.image_url.startsWith('http') ? p.image_url : ('../'+p.image_url)) : '';
          return `<tr>
            <td>${img ? `<img src="${img}" style="width:80px;height:60px;object-fit:cover;border-radius:8px">` : ''}</td>
            <td>${p.name}</td>
            <td>${p.category||''}</td>
            <td>₡${fmtMoney(p.price)}</td>
            <td>${p.featured? '<i class="fas fa-star text-warning"></i>':''}</td>
            <td>${p.updated_at ? new Date(p.updated_at).toLocaleDateString('es-CR'): ''}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-info me-1" title="Cambiar imagen" onclick="changeProductImage(${p.id})"><i class="fas fa-camera"></i></button>
              <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="openEditProduct(${p.id})"><i class="fas fa-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i></button>
            </td>
          </tr>`;
        }).join('');
      } catch(e){ tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar productos</td></tr>`; }
    }
    async function openEditProduct(id){
      try {
        const data = await fetch(api.products).then(r=>r.json());
        const prod = (data||[]).find(x=> String(x.id) === String(id));
        if(!prod){ alert('Producto no encontrado'); return; }
        // Populate modal fields
        el('editProductId').value = prod.id;
        el('editProductName').value = prod.name || '';
        el('editProductPrice').value = prod.price || 0;
        // Category select: ensure current exists
        const catSel = el('editProductCategory');
        const currentCat = prod.category || '';
        if (currentCat && !Array.from(catSel.options).some(o=> o.value === currentCat)){
          const opt = document.createElement('option');
          opt.value = currentCat; opt.textContent = currentCat; catSel.appendChild(opt);
        }
        catSel.value = currentCat || 'Ropa';
        el('editProductDescription').value = prod.description || '';
        // Sizes/colors are arrays; show as comma-separated
        try {
          const sizesArr = Array.isArray(prod.sizes) ? prod.sizes : (prod.sizes ? JSON.parse(prod.sizes) : []);
          el('editProductSizes').value = (sizesArr||[]).join(', ');
        } catch(_) { el('editProductSizes').value = ''; }
        try {
          const colorsArr = Array.isArray(prod.colors) ? prod.colors : (prod.colors ? JSON.parse(prod.colors) : []);
          el('editProductColors').value = (colorsArr||[]).join(', ');
        } catch(_) { el('editProductColors').value = ''; }
        el('editProductFeatured').checked = !!prod.featured;
        el('editProductStock').value = typeof prod.stock === 'number' ? prod.stock : (prod.stock ? parseInt(prod.stock,10) : 0);
        new bootstrap.Modal(document.getElementById('modalEditProduct')).show();
      } catch(e){ alert('Error cargando producto: '+e.message); }
    }

    async function updateProductFromAdmin(){
      const id = el('editProductId').value;
      const sizes = (el('editProductSizes').value||'').split(',').map(s=>s.trim()).filter(Boolean);
      const colors = (el('editProductColors').value||'').split(',').map(s=>s.trim()).filter(Boolean);
      const payload = { action:'edit', id, product:{
        name: el('editProductName').value.trim(),
        description: el('editProductDescription').value.trim(),
        price: Number(el('editProductPrice').value||0),
        category: el('editProductCategory').value.trim() || 'Ropa',
        sizes, colors,
        stock: Math.max(0, parseInt(el('editProductStock').value||'0',10)),
        featured: el('editProductFeatured').checked
      } };
      try {
        const r = await fetch(api.products,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalEditProduct')).hide();
        await loadProducts();
      } catch(e){ alert('No se pudo guardar el producto: '+e.message); }
    }
    async function deleteProduct(id){ if(!confirm('¿Eliminar (desactivar) este producto?')) return; try{ const r=await fetch(api.products,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadProducts(); }catch(e){ alert('No se pudo eliminar: '+e.message);} }
    // Open product image upload modal
    function changeProductImage(id){
      const input = document.getElementById('uploadProductId');
      input.value = id;
      const fileInput = document.getElementById('uploadProductFile');
      fileInput.value = '';
      new bootstrap.Modal(document.getElementById('modalUploadProductImage')).show();
    }

    // Social posts
    async function loadSocial(){
      const tbody = el('socialTableBody'); tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try{
        const posts = await fetch(api.social).then(r=>r.json()); const list = Array.isArray(posts)? posts: [];
        if(!list.length){ tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Sin posts</td></tr>`; return; }
        tbody.innerHTML = list.map(p=>{
          const img = p.image_url ? (p.image_url.startsWith('http')? p.image_url : ('../'+p.image_url)) : '';
          return `<tr>
            <td>${img ? `<img src="${img}" style="width:80px;height:60px;object-fit:cover;border-radius:8px">` : ''}</td>
            <td class="text-capitalize">${p.platform||''}</td>
            <td>${p.post_date ? new Date(p.post_date).toLocaleDateString('es-CR') : ''}</td>
            <td class="small">${(p.caption||'').replaceAll('\n','<br>')}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-info me-1" title="Cambiar imagen" onclick="changePostImage(${p.id})"><i class="fas fa-camera"></i></button>
              <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="editPost(${p.id})"><i class="fas fa-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deletePost(${p.id})"><i class="fas fa-trash"></i></button>
            </td>
          </tr>`;
        }).join('');
      }catch(e){ tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error al cargar posts</td></tr>`; }
    }
    async function editPost(id){ try{ const posts=await fetch(api.social).then(r=>r.json()); const p=(posts||[]).find(x=>x.id==id); if(!p){ alert('Post no encontrado'); return; } const platform=prompt('Plataforma (facebook/instagram)', p.platform||'instagram'); if(platform===null) return; const post_date=prompt('Fecha (YYYY-MM-DD)', (p.post_date||'').slice(0,10)); if(post_date===null) return; const caption=prompt('Texto del post', p.caption||''); if(caption===null) return; const payload={action:'edit', id, post:{platform:platform.toLowerCase(), caption, post_date}}; const r=await fetch(api.social,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadSocial(); }catch(e){ alert('No se pudo editar: '+e.message);} }
    async function deletePost(id){ if(!confirm('¿Eliminar este post?')) return; try{ const r=await fetch(api.social,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadSocial(); }catch(e){ alert('No se pudo eliminar: '+e.message);} }
    // Open social post image upload modal
    function changePostImage(id){
      document.getElementById('uploadPostId').value = id;
      document.getElementById('uploadPostFile').value = '';
      new bootstrap.Modal(document.getElementById('modalUploadPostImage')).show();
    }
  async function addPost(){ const platform=prompt('Plataforma (facebook/instagram)','instagram'); if(platform===null) return; const post_date=prompt('Fecha (YYYY-MM-DD)', new Date().toISOString().slice(0,10)); if(post_date===null) return; const caption=prompt('Texto del post',''); if(caption===null) return; const publicId=prompt('Public ID de Cloudinary (opcional)'); const image_url= publicId? `https://res.cloudinary.com/${CLOUDINARY_CLOUD}/image/upload/f_auto,q_auto/${publicId}`: ''; try{ const r=await fetch(api.social,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'add', post:{platform: platform.toLowerCase(), caption, image_url, post_date}})}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadSocial(); }catch(e){ alert('No se pudo crear el post: '+e.message);} }

    // Sessions
    async function loadSessions(){
      const tbody = el('sessionsTableBody'); tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try{
        const data=await fetch(api.sessions).then(r=>r.json());
        const list=Array.isArray(data)? data: [];
        sessionsCache = list;
        if(!list.length){ tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Sin sesiones</td></tr>`; return; }
        tbody.innerHTML = list.map(s=>`<tr><td>${s.session_date ? new Date(s.session_date).toLocaleDateString('es-CR'): ''}</td><td>${s.class_name||''}</td><td>${(s.start_time||'').slice(0,5)} - ${(s.end_time||'').slice(0,5)}</td><td><span class="badge bg-light text-dark text-capitalize">${s.status}</span></td><td class="text-end"><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" title="Completar" onclick="updateSession(${s.id}, 'completed')"><i class="fas fa-flag-checkered"></i></button><button class="btn btn-outline-danger" title="Eliminar" onclick="deleteSession(${s.id})"><i class="fas fa-trash"></i></button></div></td></tr>`).join('');
        const sel=el('attendanceSession');
        sel.innerHTML='<option value="">Seleccionar...</option>'+list.map(s=>`<option value="${s.id}">${(s.session_date||'').slice(0,10)} - ${s.class_name||''} ${(s.start_time||'').slice(0,5)}</option>`).join('');
      }catch(e){ tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error al cargar sesiones</td></tr>`; }
    }
  async function createSession(){ const payload={ action:'add', class_id: (el('newSessionClass').value||'').trim(), session_date: el('newSessionDate').value, start_time: el('newSessionStart').value, end_time: el('newSessionEnd').value, status: el('newSessionStatus').value, notes: el('newSessionNotes').value || ''}; if(!payload.class_id||!payload.session_date||!payload.start_time||!payload.end_time){ alert('Completa Clase, Fecha, Inicio y Fin'); return; } try{ const r=await fetch(api.sessions,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); bootstrap.Modal.getInstance(document.getElementById('modalAddSession')).hide(); await loadSessions(); }catch(e){ alert('No se pudo crear la sesión: '+e.message);} }
    async function updateSession(id,status){ try{ const r=await fetch(api.sessions,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'update', id, status })}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadSessions(); }catch(e){ alert('No se pudo actualizar: '+e.message);} }
    async function deleteSession(id){ if(!confirm('¿Eliminar sesión?')) return; try{ const r=await fetch(api.sessions,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'delete', id })}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadSessions(); }catch(e){ alert('No se pudo eliminar: '+e.message);} }

    // --- Prefill session start/end from selected class schedule/duration ---
    function parseDurationMinutes(text){
      if(!text) return 60;
      const m = String(text).match(/(\d{1,3})\s*min/i);
      if(m) return Math.max(1, parseInt(m[1],10));
      return 60;
    }
    function to24h(h, m, ampm){
      h = Number(h); m = Number(m||0);
      if (ampm){
        const ap = ampm.toLowerCase();
        if(ap === 'pm' && h < 12) h += 12;
        if(ap === 'am' && h === 12) h = 0;
      }
      // clamp
      h = (h>=0 && h<=23) ? h : 0; m = (m>=0 && m<=59) ? m : 0;
      return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
    }
    function addMinutes(timeHHMM, minutes){
      const [hh,mm] = timeHHMM.split(':').map(n=>parseInt(n,10));
      const total = hh*60 + mm + (minutes||0);
      const nh = Math.floor((total%1440+1440)%1440 / 60);
      const nm = ((total%60)+60)%60;
      return `${String(nh).padStart(2,'0')}:${String(nm).padStart(2,'0')}`;
    }
    function extractTimesFromSchedule(schedule, durationText){
      if(!schedule){ return { start: '', end: '' }; }
      let s = String(schedule).toLowerCase();
      // unify separators
      s = s.replace(/[–—]/g,'-').replace(/\s+a\s+/g,'-').replace(/\s+hasta\s+/g,'-');
      // find up to two time mentions
      const re = /(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/g;
      const times = [];
      let m;
      while ((m = re.exec(s)) && times.length < 2){
        times.push({ h: m[1], min: m[2]||'0', ap: m[3]||'' });
      }
      if(!times.length){
        return { start: '', end: '' };
      }
      // If only one has am/pm, propagate to the other
      if(times.length === 2){
        if(times[0].ap && !times[1].ap) times[1].ap = times[0].ap;
        if(times[1].ap && !times[0].ap) times[0].ap = times[1].ap;
      }
      const start = to24h(times[0].h, times[0].min, times[0].ap);
      let end = '';
      if(times.length > 1){
        end = to24h(times[1].h, times[1].min, times[1].ap);
      } else {
        // Derive end using duration
        end = addMinutes(start, parseDurationMinutes(durationText));
      }
      return { start, end };
    }
    function prefillSessionFromClassId(classId){
      const cls = (classesCache||[]).find(c=> String(c.id) === String(classId));
      if(!cls) return;
      const schedule = cls.schedule || '';
      const duration = cls.duration || '';
      const { start, end } = extractTimesFromSchedule(schedule, duration);
      if (start) el('newSessionStart').value = start;
      if (end) el('newSessionEnd').value = end;
    }

    // Attendance
    el('attendanceSession').addEventListener('change', async (e)=>{
      const sessionId = e.target.value;
      const tbody = el('attendanceTableBody');
      if(!sessionId){ tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Selecciona una sesión</td></tr>`; return; }
      try{
        // Find selected session to determine class name
        const sess = (sessionsCache||[]).find(s=> String(s.id) === String(sessionId));
        const className = (sess?.class_name || '').trim().toLowerCase();
        // Ensure we have enrollments cached
        if (!Array.isArray(enrollmentsCache) || !enrollmentsCache.length) {
          const data = await fetch(api.enrollments).then(r=>r.json());
          enrollmentsCache = Array.isArray(data)? data: [];
        }
        // Filter to currently registered students for this class
        const allowed = new Set(['active','approved','accepted']);
        const enrolled = (enrollmentsCache||[]).filter(e=>{
          const cls = String(e.class_name||'').trim().toLowerCase();
          const st = String(e.status||'').trim().toLowerCase();
          return cls === className && allowed.has(st);
        });
        // Deduplicate by user_id
        const byUser = new Map();
        for (const eRec of enrolled){ if(!byUser.has(eRec.user_id)) byUser.set(eRec.user_id, eRec); }
        const list = Array.from(byUser.values());
        if (!list.length){
          tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No hay estudiantes inscritos en esta clase. Aprueba inscripciones para registrar asistencia.</td></tr>`;
          return;
        }
        tbody.innerHTML = list.map(eRec=>{
          const full = (eRec.full_name || ((eRec.first_name||'')+' '+(eRec.last_name||''))).trim();
          const rowId = `att_${eRec.user_id}`;
          return `<tr id="${rowId}">
            <td>${full}</td>
            <td><input type="checkbox" class="form-check-input" /></td>
            <td><input type="text" class="form-control form-control-sm" placeholder="Notas"></td>
            <td class="text-end">
              <div class="d-flex justify-content-end align-items-center gap-2">
                <small class="text-danger d-none" id="${rowId}_err"></small>
                <button class="btn btn-sm btn-outline-primary" onclick="saveAttendance(${sessionId}, ${eRec.user_id}, '${rowId}')"><i class="fas fa-save"></i></button>
              </div>
            </td>
          </tr>`;
        }).join('');
      }catch(e){
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Error al cargar inscritos</td></tr>`;
      }
    });
    async function saveAttendance(sessionId,userId,rowId){
      const row=document.getElementById(rowId);
      const errEl=document.getElementById(`${rowId}_err`);
      if(errEl){ errEl.textContent=''; errEl.classList.add('d-none'); }
      const attended=row.querySelector('input[type=checkbox]').checked?1:0;
      const notes=row.querySelector('input[type=text]').value||'';
      try{
        const r=await fetch(api.attendance,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'add', session_id:Number(sessionId), user_id:Number(userId), attended, notes })}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        row.classList.add('table-success');
        setTimeout(()=> row.classList.remove('table-success'), 1200);
      }catch(e){
        if(errEl){ errEl.textContent = e.message; errEl.classList.remove('d-none'); setTimeout(()=>{ errEl.classList.add('d-none'); }, 5000); }
        else { alert('No se pudo guardar asistencia: '+e.message); }
      }
    }
    function reloadAttendance(){ const ev=new Event('change'); el('attendanceSession').dispatchEvent(ev); }

    // Feedback
    async function loadFeedback(){
      const tbody=el('feedbackTableBody');
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>`;
      try{
        const data=await fetch(api.feedback).then(r=>r.json());
        const list=Array.isArray(data)? data: [];
        if(!list.length){ tbody.innerHTML = `<tr><td colspan=\"5\" class=\"text-center text-muted py-4\">Sin feedback</td></tr>`; return; }
        const attMap = { present:'Asistió', absent:'Ausente', late:'Tarde', excused:'Justificada' };
        const esc = (s)=> String(s||'').replace(/[&<>"']/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]));
        tbody.innerHTML = list.map(f=>{
          const notesParts = [];
          if (f.strengths) notesParts.push(`<strong>Fortalezas:</strong> ${esc(f.strengths)}`);
          if (f.areas_for_improvement) notesParts.push(`<strong>A mejorar:</strong> ${esc(f.areas_for_improvement)}`);
          if (f.homework_assigned) notesParts.push(`<strong>Tarea:</strong> ${esc(f.homework_assigned)}`);
          const att = attMap[(f.attendance_status||'').toLowerCase()] || '';
          const notesHtml = notesParts.length ? notesParts.join(' · ') : (esc(f.general_notes||''));
          const rating = (f.performance_rating? `${f.performance_rating}/10` : '');
          return `<tr>
            <td>${f.class_date?new Date(f.class_date).toLocaleDateString('es-CR'):''}</td>
            <td>${esc(f.student_name||'')}</td>
            <td>${esc(f.class_name||'')}</td>
            <td>${rating}</td>
            <td class="small">${att ? `<span class="badge bg-light text-dark me-1">${att}</span>`:''}${notesHtml || '<span class="text-muted">(Sin notas)</span>'}</td>
          </tr>`;
        }).join('');
      }catch(e){
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error al cargar feedback</td></tr>`;
      }
    }
    async function populateUsersAndEnrollmentsForForms(){ try{ const [users,enrs]=await Promise.all([ fetch(api.users).then(r=>r.json()), fetch(api.enrollments).then(r=>r.json()) ]); const usersSel=el('feedbackUser'); const payUserSel=el('paymentUser'); usersSel.innerHTML=(users||[]).map(u=>`<option value="${u.id}">${(u.full_name||'').trim() || (u.first_name||'')+' '+(u.last_name||'')}</option>`).join(''); payUserSel.innerHTML=usersSel.innerHTML; const enrSel=el('feedbackEnrollment'); const payEnrSel=el('paymentEnrollment'); const options=['<option value="">(Opcional)</option>'].concat((enrs||[]).map(e=>`<option value="${e.id}">${(e.class_name||'')} - ${(e.full_name||e.first_name+' '+e.last_name)} (${(e.status||'')})</option>`)); enrSel.innerHTML=options.join(''); payEnrSel.innerHTML=options.join(''); }catch(e){} }
    async function submitFeedback(){ try{ const payload={ action:'add', user_id:Number(el('feedbackUser').value||0), enrollment_id:Number(el('feedbackEnrollment').value||0), class_date: el('feedbackDate').value, performance_rating:Number(el('feedbackRating').value||0), strengths: el('feedbackStrengths').value||'', areas_for_improvement: el('feedbackImprovements').value||'', general_notes:'', homework_assigned: el('feedbackHomework').value||'' }; const r=await fetch(api.feedback,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadFeedback(); alert('Feedback guardado'); }catch(e){ alert('No se pudo guardar feedback: '+e.message);} }

    // Payments
    async function loadPayments(){ const tbody=el('paymentsTableBody'); tbody.innerHTML = `<tr><td colspan=\"6\" class=\"text-center text-muted py-4\">Cargando...</td></tr>`; try{ const data=await fetch(api.payments).then(r=>r.json()); const list=Array.isArray(data)? data: []; if(!list.length){ tbody.innerHTML = `<tr><td colspan=\"6\" class=\"text-center text-muted py-4\">Sin pagos</td></tr>`; return; } tbody.innerHTML=list.map(p=>`<tr><td>${p.payment_date?new Date(p.payment_date).toLocaleDateString('es-CR'):''}</td><td>${p.student_name||''}</td><td>${p.class_name||'Pago General'}</td><td class="text-capitalize">${p.payment_method||''}</td><td>₡${fmtMoney(p.amount)}</td><td>${p.recorded_by_name||''}</td></tr>`).join(''); }catch(e){ tbody.innerHTML = `<tr><td colspan=\"6\" class=\"text-center text-danger py-4\">Error al cargar pagos</td></tr>`; } }
    async function submitPayment(){ try{ const payload={ action:'add', enrollment_id:Number(el('paymentEnrollment').value||0), user_id:Number(el('paymentUser').value||0), amount:Number(el('paymentAmount').value||0), payment_method: el('paymentMethod').value, payment_status:'completed', payment_date: el('paymentDate').value, reference_number: el('paymentRef').value||'', notes: el('paymentNotes').value||'' }; if(!payload.user_id || !payload.amount){ alert('Selecciona estudiante y monto'); return; } const r=await fetch(api.payments,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json()); if(!r.success) throw new Error(r.message||'Error'); await loadPayments(); alert('Pago registrado'); }catch(e){ alert('No se pudo registrar el pago: '+e.message);} }

    // CSV export
    async function exportCSV(type){ let url; if(type==='users') url=api.users; else if(type==='enrollments') url=api.enrollments; else if(type==='payment_records') url=api.payments; else if(type==='orders') url='../admin/users_api.php?endpoint=orders'; else return; try{ const data=await fetch(url).then(r=>r.json()); if(!Array.isArray(data)||!data.length){ alert('Sin datos para exportar'); return; } const cols=Object.keys(data[0]); const csv=[cols.join(',')].concat(data.map(row=> cols.map(k=> (`${(row[k]??'')}`.replaceAll('"','""')) ).map(v=>`"${v}"`).join(','))).join('\n'); const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=`${type}-${new Date().toISOString().slice(0,10)}.csv`; a.click(); }catch(e){ alert('No se pudo exportar: '+e.message);} }

    // Init
    document.addEventListener('DOMContentLoaded', async ()=>{
      // Safety: if a modal backdrop or modal-open class gets stuck, clean it up
      const cleanupModalArtifacts = () => {
        try {
          document.querySelectorAll('.modal-backdrop').forEach(el=> el.remove());
          document.body.classList.remove('modal-open');
          document.body.style.removeProperty('overflow');
          document.body.style.removeProperty('paddingRight');
        } catch(_){}
      };
      // Clean on tab switches and general clicks inside admin content
      document.getElementById('adminTabs')?.addEventListener('click', cleanupModalArtifacts);
      document.getElementById('adminTabsContent')?.addEventListener('click', (e)=>{
        // If clicks fall through to content, ensure no hidden overlay is blocking inputs
        cleanupModalArtifacts();
      });

      reloadRecent();
      await Promise.all([
        loadUsers(),
        loadEnrollments(),
        loadClasses(),
        loadProducts(),
        loadSocial(),
        loadSessions(),
        loadFeedback(),
        loadPayments(),
        loadOrders(),
        populateUsersAndEnrollmentsForForms(),
      ]);

      // Hook up class change in "Nueva sesión" modal to prefill times
      const sessionClassSel = document.getElementById('newSessionClass');
      if (sessionClassSel) {
        sessionClassSel.addEventListener('change', (e)=> prefillSessionFromClassId(e.target.value));
      }

      // When modal opens, prefill based on current selection
      const addSessionModal = document.getElementById('modalAddSession');
      if (addSessionModal) {
        addSessionModal.addEventListener('shown.bs.modal', ()=>{
          const sel = document.getElementById('newSessionClass');
          if (sel && sel.value) prefillSessionFromClassId(sel.value);
        });
      }
    });
  </script>

  <!-- Modal: View Proof -->
  <div class="modal fade" id="modalViewProof" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Comprobante SINPE</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="proofViewerBox" class="text-center"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Upload Class Image -->
  <div class="modal fade" id="modalUploadClassImage" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-camera me-2"></i>Actualizar imagen de clase - <span id="uploadClassName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formUploadClassImage" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" id="uploadClassId" name="class_id">
            <div class="mb-3">
              <label class="form-label">Selecciona una imagen</label>
              <input type="file" class="form-control" id="uploadClassFile" name="image" accept="image/*" required>
              <div class="form-text">JPG, PNG, WebP. Máx 10MB.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-gradient text-white">Subir</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Edit Product -->
  <div class="modal fade" id="modalEditProduct" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-pencil me-2"></i>Editar producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editProductId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre</label>
              <input type="text" id="editProductName" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Precio (₡)</label>
              <input type="number" id="editProductPrice" class="form-control" min="0" step="500">
            </div>
            <div class="col-md-3">
              <label class="form-label">Stock</label>
              <input type="number" id="editProductStock" class="form-control" min="0" step="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoría</label>
              <select id="editProductCategory" class="form-select">
                <option value="Ropa">Ropa</option>
                <option value="Calzado">Calzado</option>
                <option value="Accesorios">Accesorios</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea id="editProductDescription" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tallas (coma separadas)</label>
              <input type="text" id="editProductSizes" class="form-control" placeholder="XS, S, M, L, XL">
            </div>
            <div class="col-md-6">
              <label class="form-label">Colores (coma separadas)</label>
              <input type="text" id="editProductColors" class="form-control" placeholder="Negro, Blanco, Rojo">
            </div>
            <div class="col-12 form-check ms-2">
              <input class="form-check-input" type="checkbox" id="editProductFeatured">
              <label class="form-check-label" for="editProductFeatured">Destacar producto</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-gradient text-white" onclick="updateProductFromAdmin()">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Edit Class -->
  <div class="modal fade" id="modalEditClass" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-pencil me-2"></i>Editar clase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="editClassId">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" id="editClassName" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Instructor</label><input type="text" id="editClassInstructor" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label">Nivel</label><input type="text" id="editClassLevel" class="form-control"></div>
          <div class="col-md-4"><label class="form-label">Duración</label><input type="text" id="editClassDuration" class="form-control"></div>
          
          <div class="col-md-4"><label class="form-label">Precio (₡)</label><input type="number" id="editClassPrice" class="form-control" min="0" step="500"></div>
          <div class="col-md-4"><label class="form-label">Categoría</label>
            <select id="editClassCategory" class="form-select">
              <option value="Contemporaneo">Contemporaneo</option>
              <option value="Urbano">Urbano</option>
              <option value="Latino">Latino</option>
              <option value="Fitness">Fitness</option>
              <option value="Infantil">Infantil</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Horario</label><input type="text" id="editClassSchedule" class="form-control"></div>
          <div class="col-12"><label class="form-label">Descripción</label><textarea id="editClassDesc" class="form-control" rows="2"></textarea></div>
          <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" id="editClassFeatured"> <label class="form-check-label" for="editClassFeatured">Destacar clase</label></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-gradient text-white" onclick="updateClassFromAdmin()">Guardar</button></div>
    </div></div>
  </div>

  <script>
    // Create class from admin modal (with optional image upload)
    async function createClassFromAdmin(){
      const name = el('newClassName').value.trim();
      const instructor = el('newClassInstructor').value.trim();
      if(!name || !instructor){ alert('Nombre e instructor son requeridos'); return; }
      let imageUrl = '';
      const imgInput = document.getElementById('newClassImageFile');
      if (imgInput && imgInput.files && imgInput.files.length) {
        const fd = new FormData();
        fd.append('image', imgInput.files[0]);
        fd.append('prefix', name.toLowerCase());
        try{
          const up = await fetch('../admin/upload_class_image_cloud.php', { method:'POST', body: fd }).then(r=>r.json());
          if(!up.success) throw new Error(up.message||'Error subiendo imagen');
          imageUrl = up.image_url || '';
        }catch(err){ alert('No se pudo subir la imagen: '+err.message); return; }
      }
      const payload = {
        action: 'add',
        class: {
          name,
          instructor,
          level: el('newClassLevel').value.trim() || 'Principiante',
          duration: el('newClassDuration').value.trim() || '60 min',
          
          price: Number(el('newClassPrice').value||0),
          category: el('newClassCategory').value.trim() || 'Contemporaneo',
          schedule: el('newClassSchedule').value.trim() || '',
          description: el('newClassDesc').value.trim() || '',
          featured: el('newClassFeatured').checked,
          benefits: [],
          image: imageUrl
        }
      };
      try{
        const r = await fetch('../admin/save_classes.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)}).then(r=>r.json());
        if(!r.success) throw new Error(r.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalAddClass')).hide();
        await loadClasses();
      }catch(e){ alert('No se pudo crear la clase: '+e.message); }
    }

    // Handle update image for existing class
    document.getElementById('formUploadClassImage').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.currentTarget);
      try{
        const res = await fetch('../admin/upload_class_image_cloud.php', { method:'POST', body: fd }).then(r=>r.json());
        if(!res.success) throw new Error(res.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalUploadClassImage')).hide();
        await loadClasses();
      }catch(err){ alert('No se pudo subir la imagen: '+err.message); }
    });
  </script>
  

  <!-- Modal: Upload Product Image -->
  <div class="modal fade" id="modalUploadProductImage" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-camera me-2"></i>Actualizar imagen de producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formUploadProductImage" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" id="uploadProductId" name="product_id">
            <div class="mb-3">
              <label class="form-label">Selecciona una imagen</label>
              <input type="file" class="form-control" id="uploadProductFile" name="image" accept="image/*" required>
              <div class="form-text">JPG, PNG, WebP. Máx 10MB.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-gradient text-white">Subir</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Upload Social Post Image -->
  <div class="modal fade" id="modalUploadPostImage" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-camera me-2"></i>Actualizar imagen del post</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formUploadPostImage" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" id="uploadPostId" name="post_id">
            <div class="mb-3">
              <label class="form-label">Selecciona una imagen</label>
              <input type="file" class="form-control" id="uploadPostFile" name="social_media" accept="image/*,video/*" required>
              <div class="form-text">Imágenes (JPG, PNG, WebP, GIF, máx 10MB) o Videos (MP4/MOV/AVI, máx 60s, 100MB).</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-gradient text-white">Subir</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Handle product image upload
    document.getElementById('formUploadProductImage').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const form = e.currentTarget;
      const fd = new FormData(form);
      try{
        const res = await fetch('../admin/upload_product_image.php', { method:'POST', body: fd }).then(r=>r.json());
        if(!res.success) throw new Error(res.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalUploadProductImage')).hide();
        await loadProducts();
      }catch(err){ alert('No se pudo subir la imagen: '+err.message); }
    });

    // Handle social post image upload (update existing post)
    document.getElementById('formUploadPostImage').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.currentTarget);
      try{
        const res = await fetch('../admin/upload_social_image_update.php', { method:'POST', body: fd }).then(r=>r.json());
        if(!res.success) throw new Error(res.message||'Error');
        bootstrap.Modal.getInstance(document.getElementById('modalUploadPostImage')).hide();
        await loadSocial();
      }catch(err){ alert('No se pudo subir la imagen: '+err.message); }
    });
  </script>
</body>
</html>
<?php closeConnection($conn); ?>