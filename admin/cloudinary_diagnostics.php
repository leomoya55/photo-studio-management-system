<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/cloudinary_config.php';

use Cloudinary\Api\Upload\UploadApi;

// Restrict access: admin OR secret key OR localhost with allow=1
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? '');
$diagKey = getenv('DIAG_KEY') ?: getenv('CLOUDINARY_DIAG_KEY');
$hasValidKey = isset($_GET['key']) && !empty($diagKey) && hash_equals($diagKey, $_GET['key']);
$allowLocal = (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) && (isset($_GET['allow']) && $_GET['allow'] == '1');

if (!$isAdmin && !$hasValidKey && !$allowLocal) {
  http_response_code(403);
  echo 'Acceso denegado. Inicia sesión como admin, o usa ?key=SECRET (configura DIAG_KEY), o en localhost usa ?allow=1';
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
  'has_api_key' => (getenv('CLOUDINARY_API_KEY') || (!empty($_ENV['CLOUDINARY_API_KEY'])) || (!empty($_SERVER['CLOUDINARY_API_KEY']))) ? 'yes' : 'no',
  'has_api_secret' => (getenv('CLOUDINARY_API_SECRET') || (!empty($_ENV['CLOUDINARY_API_SECRET'])) || (!empty($_SERVER['CLOUDINARY_API_SECRET']))) ? 'yes' : 'no',
  'has_url' => (getenv('CLOUDINARY_URL') || (!empty($_ENV['CLOUDINARY_URL'])) || (!empty($_SERVER['CLOUDINARY_URL']))) ? 'yes' : 'no',
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

// PDF tests (tiny 1-page PDF base64)
$pdfBase64 = 'data:application/pdf;base64,JVBERi0xLjQKJcTLz9MKMSAwIG9iago8PC9UeXBlIC9DYXRhbG9nL1BhZ2VzIDIgMCBSPj4KZW5kb2JqCjIgMCBvYmoKPDwvVHlwZSAvUGFnZXMvQ291bnQgMS9LaWRzIFszIDAgUl0+PgplbmRvYmoKMyAwIG9iago8PC9UeXBlIC9QYWdlL1BhcmVudCAyIDAgUi9NZWRpYUJveCBbMCAwIDYxMiA3OTJdL1Jlc291cmNlcyA8PC9Qcm9jU2V0IDQgMCBSPj4vQ29udGVudHMgNSAwIFI+PgplbmRvYmoKNCAwIG9iago8PC9UeXBlIC9Qcm9jU2V0L1Jlc291cmNlcyA8PC9Gb250IDw8L0YxIDYgMCBSPj4+Pj4KZW5kb2JqCjYgMCBvYmoKPDwvVHlwZSAvRm9udC9TdWJ0eXBlIC9UeXBlMS9CYXNlRm9udCAvSGVsdmV0aWNhPj4KZW5kb2JqCjUgMCBvYmoKPDwvTGVuZ3RoIDYzPj4Kc3RyZWFtCkJUCi9GMSAxMiBUZgovVGYgMjQgVGYKMTUwIDcwMCBUZAooSG9sYSBQQ0YpIFQKRVQKZW5kc3RyZWFtCmVuZG9iagp4cmVmCjAgNwowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDAxMTAgMDAwMDAgbiAKMDAwMDAwMDA2MCAwMDAwMCBuIAowMDAwMDAwMTg2IDAwMDAwIG4gCjAwMDAwMDAyNzUgMDAwMDAgbiAKMDAwMDAwMDM4NiAwMDAwMCBuIAowMDAwMDAwNTY3IDAwMDAwIG4gCnRyYWlsZXIKPDwvU2l6ZSA3L1Jvb3QgMSAwIFIvSW5mbyA4IDAgUi9JRCBbPDE3OTk1YTkzOGFhYTZmY2Y4Y2MxZGI0NDAwYzZiZGRmZT4KPDE3OTk1YTkzOGFhYTZmY2Y4Y2MxZGI0NDAwYzZiZGRmZT5dPj4Kc3RhcnR4cmVmCjY4NQolJUVPRg==';

$pdfImage = [ 'success' => false, 'message' => '', 'public_id' => null ];
try {
  $uploader = new UploadApi();
  $pid = 'diagnostics/pdf_img_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6);
  $res = $uploader->upload($pdfBase64, [
    'public_id' => $pid,
    'resource_type' => 'image',
    'overwrite' => true,
    'folder' => 'diagnostics'
  ]);
  $pdfImage['success'] = true;
  $pdfImage['public_id'] = $res['public_id'] ?? null;
} catch (Throwable $e) {
  $pdfImage['message'] = $e->getMessage();
}

$pdfRaw = [ 'success' => false, 'message' => '', 'public_id' => null ];
try {
  $uploader = new UploadApi();
  $pid = 'diagnostics/pdf_raw_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6);
  $res = $uploader->upload($pdfBase64, [
    'public_id' => $pid,
    'resource_type' => 'raw',
    'overwrite' => true,
    'folder' => 'diagnostics'
  ]);
  $pdfRaw['success'] = true;
  $pdfRaw['public_id'] = $res['public_id'] ?? null;
} catch (Throwable $e) {
  $pdfRaw['message'] = $e->getMessage();
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
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
            <li class="text-muted small">Nota: ahora se detecta en getenv(), $_ENV y $_SERVER</li>
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

      <div class="card mt-3">
        <div class="card-header">Prueba de PDF</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6>PDF como image</h6>
              <?php if ($pdfImage['success']): ?>
                <div class="alert alert-success">OK</div>
                <p>public_id: <code><?= htmlspecialchars($pdfImage['public_id']) ?></code></p>
              <?php else: ?>
                <div class="alert alert-danger">FAIL</div>
                <pre class="mb-0" style="white-space:pre-wrap; word-wrap:break-word;"><?= htmlspecialchars($pdfImage['message'] ?: 'Sin mensaje') ?></pre>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <h6>PDF como raw</h6>
              <?php if ($pdfRaw['success']): ?>
                <div class="alert alert-success">OK</div>
                <p>public_id: <code><?= htmlspecialchars($pdfRaw['public_id']) ?></code></p>
              <?php else: ?>
                <div class="alert alert-danger">FAIL</div>
                <pre class="mb-0" style="white-space:pre-wrap; word-wrap:break-word;"><?= htmlspecialchars($pdfRaw['message'] ?: 'Sin mensaje') ?></pre>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <a href="<?= ADMIN_URL ?>/admin.php" class="btn btn-secondary">Volver al Panel Admin</a>
      </div>
    </div>
  </body>
 </html>
