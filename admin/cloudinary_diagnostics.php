<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;

// Restrict to admins only
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$isAdmin) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

function can_write_path($path) {
    $test = $path . '.write_test_' . uniqid() . '.tmp';
    $ok = @file_put_contents($test, date('c'));
    if ($ok !== false) { @unlink($test); return true; }
    return false;
}

$cloudName = getCloudName();
$diag = [
    'cloud_name' => $cloudName,
    // presence only (do not expose values)
    'has_api_key' => getenv('CLOUDINARY_API_KEY') ? 'yes' : 'no',
    'has_api_secret' => getenv('CLOUDINARY_API_SECRET') ? 'yes' : 'no',
    'has_url' => getenv('CLOUDINARY_URL') ? 'yes' : 'no',
    'log_paths' => [
        'admin_log_writable' => can_write_path(__DIR__ . '/payment_proof_uploads.log') ? 'yes' : 'no',
        'data_log_writable' => can_write_path(__DIR__ . '/../data/payment_proof_uploads.log') ? 'yes' : 'no',
        'student_log_writable' => can_write_path(__DIR__ . '/student_emails_log.txt') ? 'yes' : 'no',
    ],
];

// Create a tiny 1x1 PNG (base64)
$pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAjMBgX8hQIsAAAAASUVORK5CYII=';

$uploadResult = [ 'success' => false, 'message' => '', 'public_id' => null, 'secure_url' => null ];
try {
    $uploader = new UploadApi();
    $pid = 'diagnostics/diag_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6);
    $res = $uploader->upload($pngBase64, [
        'public_id' => $pid,
        'resource_type' => 'image',
        'overwrite' => true,
        'folder' => 'diagnostics'
    ]);
    $uploadResult['success'] = true;
    $uploadResult['public_id'] = $res['public_id'] ?? null;
    $uploadResult['secure_url'] = $res['secure_url'] ?? null;
} catch (Throwable $e) {
    $uploadResult['message'] = $e->getMessage();
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Diagnóstico Cloudinary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { padding: 2rem; } code { user-select: all; } </style>
  </head>
  <body>
    <div class="container">
      <h1 class="mb-4">Diagnóstico Cloudinary</h1>

      <div class="card mb-3">
        <div class="card-header">Configuración detectada</div>
        <div class="card-body">
          <ul class="mb-0">
            <li>Cloud name: <strong><?= htmlspecialchars($diag['cloud_name']) ?></strong></li>
            <li>API Key presente: <strong><?= $diag['has_api_key'] ?></strong></li>
            <li>API Secret presente: <strong><?= $diag['has_api_secret'] ?></strong></li>
            <li>CLOUDINARY_URL presente: <strong><?= $diag['has_url'] ?></strong></li>
          </ul>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Permisos de escritura de logs</div>
        <div class="card-body">
          <ul class="mb-0">
            <li>admin/payment_proof_uploads.log: <strong><?= $diag['log_paths']['admin_log_writable'] ?></strong></li>
            <li>data/payment_proof_uploads.log: <strong><?= $diag['log_paths']['data_log_writable'] ?></strong></li>
            <li>admin/student_emails_log.txt: <strong><?= $diag['log_paths']['student_log_writable'] ?></strong></li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Prueba de subida 1x1 PNG</div>
        <div class="card-body">
          <?php if ($uploadResult['success']): ?>
            <div class="alert alert-success">Subida OK</div>
            <p>public_id: <code><?= htmlspecialchars($uploadResult['public_id']) ?></code></p>
            <?php if (!empty($uploadResult['secure_url'])): ?>
              <p>URL: <a href="<?= htmlspecialchars($uploadResult['secure_url']) ?>" target="_blank">abrir</a></p>
              <img src="<?= htmlspecialchars($uploadResult['secure_url']) ?>" alt="diag" style="max-width:200px;border:1px solid #ddd;border-radius:6px;" />
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-danger">Fallo en la subida</div>
            <pre class="mb-0" style="white-space:pre-wrap; word-wrap:break-word;"><?= htmlspecialchars($uploadResult['message'] ?: 'Sin mensaje') ?></pre>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4">
        <a href="<?= ADMIN_URL ?>/admin.php" class="btn btn-secondary">Volver al Panel Admin</a>
      </div>
    </div>
  </body>
 </html>
