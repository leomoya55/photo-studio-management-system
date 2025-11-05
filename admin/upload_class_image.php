<?php
// This endpoint is deprecated. Use upload_class_image_cloud.php (Cloudinary) instead.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
http_response_code(410);
echo json_encode([
  'success' => false,
  'message' => 'Deprecated endpoint. Use admin/upload_class_image_cloud.php (Cloudinary).'
]);
?>