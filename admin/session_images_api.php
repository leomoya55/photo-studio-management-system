<?php
/**
 * Session Images API
 * Allows admins to attach Cloudinary photos to client accounts and list them for dashboards.
 */

session_start();

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/session_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No hay conexión con la base de datos'
    ]);
    exit;
}

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Inicia sesión para continuar'
    ]);
    exit;
}

function ensureSessionImagesTable(mysqli $conn): void {
    $sql = "CREATE TABLE IF NOT EXISTS user_session_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_label VARCHAR(191) DEFAULT NULL,
        session_date DATE DEFAULT NULL,
        image_url TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_session_date (session_date),
        CONSTRAINT fk_session_photo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

try {
    ensureSessionImagesTable($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($userId <= 0) {
            json_response(['success' => false, 'message' => 'Parámetros inválidos'], 400);
        }

        $requesterId = (int)($_SESSION['user_id'] ?? 0);
        if ($userRole !== 'admin' && $requesterId !== $userId) {
            json_response(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $stmt = $conn->prepare('SELECT id, user_id, session_label, session_date, image_url, created_at FROM user_session_photos WHERE user_id = ? ORDER BY session_date DESC, created_at DESC');
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        json_response([
            'success' => true,
            'data' => $rows
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($userRole !== 'admin') {
            json_response(['success' => false, 'message' => 'Acceso exclusivo para administradores'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            json_response(['success' => false, 'message' => 'Petición inválida'], 400);
        }

        $action = $input['action'] ?? '';
        switch ($action) {
            case 'add':
                $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
                $imageUrl = trim((string)($input['image_url'] ?? ''));
                $sessionLabel = trim((string)($input['session_label'] ?? ''));
                $sessionDate = trim((string)($input['session_date'] ?? ''));

                if ($userId <= 0 || empty($imageUrl)) {
                    json_response(['success' => false, 'message' => 'Faltan datos obligatorios'], 400);
                }

                if ($sessionLabel !== '' && mb_strlen($sessionLabel) > 180) {
                    $sessionLabel = mb_substr($sessionLabel, 0, 180);
                }

                if ($sessionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
                    $sessionDate = '';
                }

                $stmt = $conn->prepare('INSERT INTO user_session_photos (user_id, session_label, session_date, image_url) VALUES (?, ?, ?, ?)');
                if (!$stmt) {
                    throw new Exception('Error preparando inserción: ' . $conn->error);
                }
                $sessionLabelParam = $sessionLabel !== '' ? $sessionLabel : null;
                $sessionDateParam = $sessionDate !== '' ? $sessionDate : null;
                $stmt->bind_param(
                    'isss',
                    $userId,
                    $sessionLabelParam,
                    $sessionDateParam,
                    $imageUrl
                );
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo guardar la foto: ' . $stmt->error);
                }
                $newId = $stmt->insert_id;
                $stmt->close();

                $select = $conn->prepare('SELECT id, user_id, session_label, session_date, image_url, created_at FROM user_session_photos WHERE id = ?');
                $select->bind_param('i', $newId);
                $select->execute();
                $photo = $select->get_result()->fetch_assoc();
                $select->close();

                json_response([
                    'success' => true,
                    'message' => 'Foto registrada',
                    'photo' => $photo
                ]);
                break;

            case 'delete':
                $photoId = isset($input['id']) ? (int)$input['id'] : 0;
                if ($photoId <= 0) {
                    json_response(['success' => false, 'message' => 'ID inválido'], 400);
                }
                $stmt = $conn->prepare('DELETE FROM user_session_photos WHERE id = ?');
                if (!$stmt) {
                    throw new Exception('Error preparando eliminación: ' . $conn->error);
                }
                $stmt->bind_param('i', $photoId);
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo eliminar la foto: ' . $stmt->error);
                }
                $stmt->close();

                json_response([
                    'success' => true,
                    'message' => 'Foto eliminada'
                ]);
                break;

            default:
                json_response(['success' => false, 'message' => 'Acción no soportada'], 400);
        }
    }

    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
} catch (Exception $ex) {
    json_response([
        'success' => false,
        'message' => $ex->getMessage()
    ], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
