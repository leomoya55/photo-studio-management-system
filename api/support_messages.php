<?php
session_start();

require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$messagesFile = __DIR__ . '/../data/support_messages.json';

if (!file_exists($messagesFile)) {
    file_put_contents($messagesFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function load_messages(string $file): array {
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function save_messages(string $file, array $messages): void {
    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new RuntimeException('No se pudo abrir el archivo de mensajes');
    }
    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear el archivo de mensajes');
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function summarize_threads(array $messages): array {
    $summary = [];
    foreach ($messages as $msg) {
        $uid = (int)($msg['user_id'] ?? 0);
        if ($uid <= 0) {
            continue;
        }
        if (!isset($summary[$uid])) {
            $summary[$uid] = [
                'user_id' => $uid,
                'last_message_at' => $msg['created_at'] ?? null,
                'unread_for_admin' => 0,
                'total_messages' => 0,
            ];
        }
        $summary[$uid]['total_messages']++;
        if (($msg['sender'] ?? 'user') === 'user' && empty($msg['read_by_admin'])) {
            $summary[$uid]['unread_for_admin']++;
        }
        if (!empty($msg['created_at'])) {
            if (!$summary[$uid]['last_message_at'] || $msg['created_at'] > $summary[$uid]['last_message_at']) {
                $summary[$uid]['last_message_at'] = $msg['created_at'];
            }
        }
    }
    ksort($summary);
    return $summary;
}

function total_unread_for_admin(array $summary): int {
    $total = 0;
    foreach ($summary as $row) {
        $total += (int)($row['unread_for_admin'] ?? 0);
    }
    return $total;
}

function get_user_profiles(?mysqli $conn, array $userIds): array {
    $map = [];
    if (!$conn || $conn->connect_error) {
        return $map;
    }
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if (empty($userIds)) {
        return $map;
    }

    $stmt = $conn->prepare('SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return $map;
    }

    foreach ($userIds as $uid) {
        $stmt->bind_param('i', $uid);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = $row['first_name'] ?? '';
                }
                $map[$uid] = [
                    'full_name' => $fullName,
                    'email' => $row['email'] ?? ''
                ];
            }
            if ($res) {
                $res->free();
            }
        }
    }

    $stmt->close();
    return $map;
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = $_SESSION['user_id'] ?? null;

if (!$isAdmin && !$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para acceder a tus mensajes.']);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$adminEmail = $config['email']['admin_email'] ?? 'admin@valevphotography.com';
$studioFrom = $config['email']['from_email'] ?? 'noreply@valevphotography.com';
$studioName = $config['email']['from_name'] ?? 'Vale V Photography';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $targetUserId = $userId;
    if ($isAdmin) {
        $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    }

    $messages = load_messages($messagesFile);
    $summary = summarize_threads($messages);
    $totalUnread = total_unread_for_admin($summary);

    if ($isAdmin && $targetUserId === 0) {
        $profiles = get_user_profiles($conn ?? null, array_keys($summary));
        foreach ($summary as &$row) {
            $uid = $row['user_id'];
            $profile = $profiles[$uid] ?? null;
            $row['user_name'] = $profile['full_name'] ?? null;
            $row['user_email'] = $profile['email'] ?? null;
        }
        unset($row);

        echo json_encode([
            'success' => true,
            'overview' => array_values($summary),
            'total_unread' => $totalUnread
        ]);
        exit;
    }

    if (!$targetUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se especificó el usuario a consultar.']);
        exit;
    }

    $updated = false;
    foreach ($messages as &$record) {
        if ((int)($record['user_id'] ?? 0) !== $targetUserId) {
            continue;
        }
        if ($isAdmin && ($record['sender'] ?? '') === 'user' && empty($record['read_by_admin'])) {
            $record['read_by_admin'] = true;
            $updated = true;
        }
        if (!$isAdmin && ($record['sender'] ?? '') === 'admin' && empty($record['read_by_user'])) {
            $record['read_by_user'] = true;
            $updated = true;
        }
    }
    unset($record);

    if ($updated) {
        try {
            save_messages($messagesFile, $messages);
        } catch (Throwable $persistErr) {
            error_log('Support messages mark-read error: ' . $persistErr->getMessage());
        }
        $summary = summarize_threads($messages);
        $totalUnread = total_unread_for_admin($summary);
    }

    $thread = array_values(array_filter($messages, function ($item) use ($targetUserId) {
        return (int)($item['user_id'] ?? 0) === $targetUserId;
    }));

    usort($thread, function ($a, $b) {
        return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
    });

    $userProfile = $isAdmin ? (get_user_profiles($conn ?? null, [$targetUserId])[$targetUserId] ?? null) : null;

    echo json_encode([
        'success' => true,
        'messages' => $thread,
        'user_id' => $targetUserId,
        'user_name' => $userProfile['full_name'] ?? null,
        'user_email' => $userProfile['email'] ?? null,
        'total_unread' => $totalUnread
    ]);
    exit;
}

// POST: create new message
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

$action = isset($input['action']) ? trim((string)$input['action']) : '';

if ($action !== '') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
        exit;
    }

    if ($action === 'close_thread') {
        $threadUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($threadUserId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Conversación no válida.']);
            exit;
        }

        $messages = load_messages($messagesFile);
        $before = count($messages);
        $messages = array_values(array_filter($messages, function ($item) use ($threadUserId) {
            return (int)($item['user_id'] ?? 0) !== $threadUserId;
        }));

        try {
            save_messages($messagesFile, $messages);
        } catch (Throwable $err) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo finalizar la conversación.']);
            exit;
        }

        $summary = summarize_threads($messages);
        $totalUnread = total_unread_for_admin($summary);

        echo json_encode([
            'success' => true,
            'message' => 'Conversación finalizada.',
            'removed' => max(0, $before - count($messages)),
            'total_unread' => $totalUnread
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no soportada.']);
    exit;
}

$messageBody = trim((string)($input['message'] ?? ''));
$subject = trim((string)($input['subject'] ?? ''));
$channel = trim((string)($input['channel'] ?? 'soporte'));
$targetUserId = $userId;

if ($isAdmin && isset($input['user_id'])) {
    $targetUserId = (int)$input['user_id'];
}

if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario destino no válido.']);
    exit;
}

if ($messageBody === '' || mb_strlen($messageBody) < 3) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Por favor escribe un mensaje más detallado.']);
    exit;
}

if (mb_strlen($messageBody) > 2000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede superar los 2000 caracteres.']);
    exit;
}

if ($subject === '') {
    $subject = 'Mensaje desde el portal Vale V Photography';
}

$messages = load_messages($messagesFile);
$nextId = empty($messages) ? 1 : ((int)max(array_column($messages, 'id')) + 1);

$senderType = $isAdmin ? 'admin' : 'user';

$entry = [
    'id' => $nextId,
    'user_id' => $targetUserId,
    'sender' => $senderType,
    'subject' => $subject,
    'message' => $messageBody,
    'channel' => $channel ?: 'soporte',
    'created_at' => date('c'),
    'read_by_admin' => $senderType === 'admin',
    'read_by_user' => $senderType === 'user'
];

$messages[] = $entry;

try {
    save_messages($messagesFile, $messages);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el mensaje.']);
    exit;
}

$summary = summarize_threads($messages);
$totalUnread = total_unread_for_admin($summary);

// Notify recipient via email
try {
    if ($senderType === 'user') {
        $fullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        $userEmail = $_SESSION['email'] ?? '';
        $headline = 'Nuevo mensaje de ' . ($fullName ?: 'Cliente');
        $body = '<p><strong>Cliente:</strong> ' . htmlspecialchars($fullName ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</p>'
               .'<p><strong>Email:</strong> ' . htmlspecialchars($userEmail ?: 'No proporcionado', ENT_QUOTES, 'UTF-8') . '</p>'
               .'<p><strong>Mensaje:</strong></p>'
               .'<div style="background:#f9f9fb;border-radius:12px;padding:16px;border-left:4px solid #000;">'
               . nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'))
               .'</div>'
               .'<p style="margin-top:18px;font-size:13px;color:#555">Responde desde el panel de mensajes internos.</p>';
        $subjectLine = 'Nuevo mensaje de cliente - Vale V Photography';
        send_branded_email($adminEmail, $subjectLine, $headline, $body, 'Nuevo mensaje', '#0d6efd');
    } else {
        // Admin response -> send to user if possible
        $stmt = $conn->prepare('SELECT first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $targetUserId);
            $stmt->execute();
            $res = $stmt->get_result();
            $userRow = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($userRow && !empty($userRow['email'])) {
                $fullName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? ''));
                $headline = 'Has recibido una respuesta del estudio';
                $body = '<p>Hola ' . htmlspecialchars($fullName ?: 'cliente', ENT_QUOTES, 'UTF-8') . ',</p>'
                       .'<p>El equipo de Vale V Photography respondió a tu mensaje:</p>'
                       .'<div style="background:#f9f9fb;border-radius:12px;padding:16px;border-left:4px solid #000;">'
                       . nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'))
                       .'</div>'
                       .'<p style="margin-top:18px;font-size:13px;color:#555">Puedes continuar la conversación desde tu panel de cliente.</p>';
                $statusLabel = 'Mensaje del estudio';
                send_branded_email($userRow['email'], 'Respuesta del estudio - Vale V Photography', $headline, $body, $statusLabel, '#111827');
            }
        }
    }
} catch (Throwable $notifyError) {
    error_log('Support message email error: ' . $notifyError->getMessage());
}

if (isset($conn) && $conn) {
    closeConnection($conn);
}

echo json_encode(['success' => true, 'entry' => $entry, 'total_unread' => $totalUnread]);
