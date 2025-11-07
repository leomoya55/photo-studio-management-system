<?php
session_start();
require_once __DIR__ . '/../config/paths.php';

// Access control: admin OR secret key OR localhost allow
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? '');
$diagKey = getenv('DIAG_KEY') ?: getenv('CLOUDINARY_DIAG_KEY');
$hasValidKey = isset($_GET['key']) && !empty($diagKey) && hash_equals($diagKey, $_GET['key']);
$allowLocal = (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) && (isset($_GET['allow']) && $_GET['allow'] == '1');

if (!$isAdmin && !$hasValidKey && !$allowLocal) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$candidates = [
    __DIR__ . '/payment_proof_uploads.log',
    __DIR__ . '/../data/payment_proof_uploads.log',
    sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payment_proof_uploads.log',
    __DIR__ . '/student_emails_log.txt',
];

$logs = [];
foreach ($candidates as $path) {
    if (is_file($path)) {
        $logs[$path] = $path;
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cloudinary Proof Upload Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> body { padding: 2rem; } pre { background:#0f172a; color:#e2e8f0; padding:1rem; border-radius:8px; max-height:60vh; overflow:auto;} code { color:#93c5fd; } </style>
</head>
<body>
  <div class="container">
    <h1 class="mb-4">Logs de subida de comprobantes</h1>

    <?php if (empty($logs)): ?>
      <div class="alert alert-warning">No se encontraron archivos de log en las rutas conocidas.</div>
      <ul>
        <li><code><?= htmlspecialchars(__DIR__ . '/payment_proof_uploads.log') ?></code></li>
        <li><code><?= htmlspecialchars(__DIR__ . '/../data/payment_proof_uploads.log') ?></code></li>
        <li><code><?= htmlspecialchars(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payment_proof_uploads.log') ?></code></li>
        <li><code><?= htmlspecialchars(__DIR__ . '/student_emails_log.txt') ?></code></li>
      </ul>
    <?php else: ?>
      <?php foreach ($logs as $path): ?>
        <div class="card mb-3">
          <div class="card-header">Archivo: <code><?= htmlspecialchars($path) ?></code></div>
          <div class="card-body">
            <?php
              $content = @file_get_contents($path);
              if ($content === false) {
                  echo '<div class="alert alert-danger">No se pudo leer este log.</div>';
              } else {
                  // Show last 200 lines
                  $lines = preg_split("/\r?\n/", trim($content));
                  $tail = array_slice($lines, -200);
                  echo '<pre>' . htmlspecialchars(implode("\n", $tail)) . '</pre>';
              }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <a href="<?= ADMIN_URL ?>/admin.php" class="btn btn-secondary">Volver al Panel Admin</a>
  </div>
</body>
</html>
