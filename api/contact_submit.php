<?php
/**
 * Vale V Photography - Public Contact Form Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$emailConfig = $config['email'] ?? [];
$pathsConfig = $config['paths'] ?? [];

$studioFrom = $emailConfig['from_email'] ?? 'info@valevphotography.com';
$studioName = $emailConfig['from_name'] ?? 'Vale V Photography';
$studioInbox = $emailConfig['admin_email'] ?? $studioFrom;

// Collect raw input
$data = $_POST;

$required = ['nombre', 'email', 'tipo_clase', 'mensaje'];
$errors = [];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $errors[] = "El campo '{$field}' es obligatorio";
    }
}

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email no es válido';
}

if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $data['telefono'])) {
    $errors[] = 'El teléfono no es válido';
}

$messageBody = trim($data['mensaje'] ?? '');
if (strlen($messageBody) < 10) {
    $errors[] = 'El mensaje debe tener al menos 10 caracteres';
}
if (strlen($messageBody) > 1000) {
    $errors[] = 'El mensaje no puede exceder los 1000 caracteres';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Errores de validación: ' . implode(', ', $errors)
    ]);
    exit;
}

$payload = [];
foreach ($data as $key => $value) {
    $payload[$key] = htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

$timestamp = date('Y-m-d H:i:s');
$customerSubject = 'Confirmación de consulta - Vale V Photography';
$adminSubject = 'Nueva consulta del sitio web - Vale V Photography';

$customerHtml = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Contacto Vale V Photography</title>"
    ."<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;}"
    .".wrap{max-width:620px;margin:0 auto;border:1px solid #eee;border-radius:18px;overflow:hidden;}"
    .".hero{background:linear-gradient(135deg,#000 0%,#3a3a3a 100%);color:#fff;padding:26px;text-align:center;}"
    .".content{padding:26px;background:#f9f9f9;}"
    .".footer{padding:18px;text-align:center;font-size:12px;color:#666;background:#fff;}</style></head><body>"
    ."<div class='wrap'><div class='hero'><h1>Gracias por tu mensaje</h1><p>Vale V Photography</p></div>"
    ."<div class='content'><p>Hola <strong>{$payload['nombre']}</strong>,</p>"
    ."<p>Recibimos tu consulta sobre <strong>{$payload['tipo_clase']}</strong>. Nuestro equipo se pondrá en contacto contigo en las próximas 24 horas.</p>"
    ."<h3>Resumen</h3><ul style='padding-left:18px'>"
    ."<li><strong>Email:</strong> {$payload['email']}</li>"
    .(!empty($payload['telefono']) ? "<li><strong>Teléfono:</strong> {$payload['telefono']}</li>" : '')
    ."</ul><p style='margin-top:14px'><strong>Tu mensaje:</strong></p>"
    ."<div style='background:#fff;padding:18px;border-left:4px solid #000;border-radius:10px'>{$payload['mensaje']}</div>"
    ."<p style='margin-top:20px'>Seguinos en redes para ver proyectos recientes y promociones exclusivas.</p></div>"
    ."<div class='footer'>Vale V Photography<br>{$config['contact']['address']}<br>Teléfono: {$config['contact']['phone']}<br>Email: {$studioFrom}</div></div></body></html>";

$adminHtml = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Nueva consulta recibida</title>"
    ."<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;background:#f5f5f7;padding:0;margin:0;}"
    .".card{max-width:680px;margin:24px auto;background:#fff;border-radius:18px;border:1px solid #e1e1e8;overflow:hidden;}"
    .".head{background:#2c3e50;color:#fff;padding:24px;text-align:center;}"
    ."table{width:100%;border-collapse:collapse;margin:18px 0;}th,td{padding:10px;border:1px solid #e8e8f0;text-align:left;}"
    ."th{background:#f0f3f8;font-weight:600;}</style></head><body>"
    ."<div class='card'><div class='head'><h2>Nueva consulta</h2><p>{$timestamp}</p></div><div style='padding:26px'>"
    ."<table><tr><th>Nombre</th><td>{$payload['nombre']}</td></tr>"
    ."<tr><th>Email</th><td>{$payload['email']}</td></tr>"
    ."<tr><th>Teléfono</th><td>" . (!empty($payload['telefono']) ? $payload['telefono'] : 'No proporcionado') . "</td></tr>"
    ."<tr><th>Interés</th><td>{$payload['tipo_clase']}</td></tr></table>"
    ."<h3 style='margin-top:24px'>Mensaje</h3><div style='background:#f9f9fb;border-radius:12px;padding:18px;border-left:4px solid #000;'>{$payload['mensaje']}</div>"
    ."<p style='margin-top:22px;font-size:13px;color:#555'>Responder en menos de 24 horas mantiene la experiencia del estudio.</p>"
    ."</div></div></body></html>";

// Simple mail headers
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: ' . sprintf('%s <%s>', $studioName, $studioFrom),
    'Reply-To: ' . sprintf('%s <%s>', $payload['nombre'], $payload['email'])
];
$headersString = implode("\r\n", $headers);

$customerSent = @mail($payload['email'], $customerSubject, $customerHtml, $headersString);
$adminSent = @mail($studioInbox, $adminSubject, $adminHtml, $headersString);

// Write log
try {
    $logPath = $pathsConfig['contact_logs'] ?? 'logs/contact_submissions.log';
    $absoluteLog = __DIR__ . '/../' . ltrim($logPath, '/');
    if (!is_dir(dirname($absoluteLog))) {
        mkdir(dirname($absoluteLog), 0755, true);
    }
    $logEntry = json_encode([
        'timestamp' => $timestamp,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $payload,
        'customer_sent' => $customerSent,
        'admin_sent' => $adminSent
    ], JSON_UNESCAPED_UNICODE);
    file_put_contents($absoluteLog, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
} catch (Throwable $logError) {
    error_log('Contact log write failed: ' . $logError->getMessage());
}

if ($customerSent && $adminSent) {
    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado exitosamente! Te contactaremos pronto.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje. Por favor, inténtalo nuevamente.']);
}
