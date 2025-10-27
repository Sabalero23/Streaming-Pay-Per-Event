<?php
// api/chat.php - VERSIÓN SIN MBSTRING (Compatible con todos los servidores)
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE));
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->exec("SET NAMES utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexión'], JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_messages';
$event_id = intval($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

// Verificar acceso al evento
$stmt = $db->prepare("SELECT COUNT(*) as has_access FROM purchases WHERE user_id = ? AND event_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id'], $event_id]);
$access = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$access || $access['has_access'] == 0) {
    http_response_code(403);
    die(json_encode(['error' => 'No tienes acceso a este evento'], JSON_UNESCAPED_UNICODE));
}

// Obtener usuario
$stmt = $db->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    die(json_encode(['error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE));
}

// Función para contar caracteres UTF-8 sin mbstring
function utf8_strlen($str) {
    return strlen(utf8_decode($str));
}

switch ($action) {
    case 'send_message':
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            http_response_code(400);
            die(json_encode(['error' => 'Mensaje vacío'], JSON_UNESCAPED_UNICODE));
        }
        
        // Validar longitud (usar strlen para bytes, no caracteres)
        if (strlen($message) > 2000) { // 500 caracteres * 4 bytes max por emoji
            http_response_code(400);
            die(json_encode(['error' => 'Mensaje muy largo'], JSON_UNESCAPED_UNICODE));
        }
        
        // Anti-spam
        $stmt = $db->prepare("SELECT created_at FROM chat_messages WHERE user_id = ? AND event_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id'], $event_id]);
        $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastMessage && (time() - strtotime($lastMessage['created_at'])) < 2) {
            http_response_code(429);
            die(json_encode(['error' => 'Espera un momento'], JSON_UNESCAPED_UNICODE));
        }
        
        // Insertar mensaje
        $stmt = $db->prepare("INSERT INTO chat_messages (event_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $result = $stmt->execute([$event_id, $_SESSION['user_id'], $message]);
        
        if (!$result) {
            http_response_code(500);
            die(json_encode(['error' => 'Error al enviar mensaje'], JSON_UNESCAPED_UNICODE));
        }
        
        $messageId = $db->lastInsertId();
        
        // Determinar badge de rol
        $roleBadge = '';
        if ($user['role'] === 'admin') $roleBadge = 'ADMIN';
        elseif ($user['role'] === 'moderator') $roleBadge = 'MOD';
        elseif ($user['role'] === 'streamer') $roleBadge = 'STREAMER';
        
        die(json_encode([
            'success' => true,
            'message' => 'Mensaje enviado',
            'data' => [
                'id' => $messageId,
                'user_name' => htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'created_at' => date('Y-m-d H:i:s'),
                'time_formatted' => date('H:i'),
                'is_own' => true,
                'role' => $user['role'],
                'role_badge' => $roleBadge
            ]
        ], JSON_UNESCAPED_UNICODE));
        
    case 'get_messages':
        $since_id = intval($_GET['since_id'] ?? 0);
        $limit = 50;
        
        if ($since_id > 0) {
            $stmt = $db->prepare("
                SELECT cm.id, cm.user_id, cm.message, cm.created_at, u.full_name as user_name, u.role
                FROM chat_messages cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.event_id = ? AND cm.id > ? AND cm.is_moderated = 0
                ORDER BY cm.created_at ASC
                LIMIT ?
            ");
            $stmt->execute([$event_id, $since_id, $limit]);
        } else {
            $stmt = $db->prepare("
                SELECT cm.id, cm.user_id, cm.message, cm.created_at, u.full_name as user_name, u.role
                FROM chat_messages cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.event_id = ? AND cm.is_moderated = 0
                ORDER BY cm.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$event_id, $limit]);
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($since_id == 0) {
            $messages = array_reverse($messages);
        }
        
        foreach ($messages as &$msg) {
            $msg['is_own'] = ($msg['user_id'] == $_SESSION['user_id']);
            $msg['message'] = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
            $msg['user_name'] = htmlspecialchars($msg['user_name'], ENT_QUOTES, 'UTF-8');
            $msg['time_formatted'] = date('H:i', strtotime($msg['created_at']));
            
            // Determinar badge de rol
            $roleBadge = '';
            if ($msg['role'] === 'admin') $roleBadge = 'ADMIN';
            elseif ($msg['role'] === 'moderator') $roleBadge = 'MOD';
            elseif ($msg['role'] === 'streamer') $roleBadge = 'STREAMER';
            
            $msg['role_badge'] = $roleBadge;
        }
        
        die(json_encode([
            'success' => true,
            'messages' => $messages,
            'count' => count($messages)
        ], JSON_UNESCAPED_UNICODE));
        
    case 'delete_message':
        if (!in_array($user['role'], ['admin', 'moderator'])) {
            http_response_code(403);
            die(json_encode(['error' => 'No tienes permisos'], JSON_UNESCAPED_UNICODE));
        }
        
        $message_id = intval($_POST['message_id'] ?? 0);
        $stmt = $db->prepare("UPDATE chat_messages SET is_moderated = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        
        die(json_encode(['success' => true, 'message' => 'Mensaje eliminado'], JSON_UNESCAPED_UNICODE));
        
    default:
        http_response_code(400);
        die(json_encode(['error' => 'Acción no válida'], JSON_UNESCAPED_UNICODE));
}