<?php
// api/force_session.php
// API para forzar nueva sesión y cerrar la anterior

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $event_id = $data['event_id'] ?? 0;
    $user_id = $data['user_id'] ?? 0;
    $session_token = $data['session_token'] ?? '';
    
    // Validar que el usuario coincida con la sesión
    if ($user_id != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autorizado']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Forzar nueva sesión (esto eliminará/reemplazará la anterior por el UNIQUE KEY)
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $device_fingerprint = md5($user_agent . $ip_address);
    
    $stmt = $db->prepare("
        INSERT INTO active_sessions 
        (user_id, event_id, session_token, ip_address, user_agent, device_fingerprint, last_heartbeat) 
        VALUES (?, ?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            session_token = VALUES(session_token),
            ip_address = VALUES(ip_address),
            user_agent = VALUES(user_agent),
            device_fingerprint = VALUES(device_fingerprint),
            last_heartbeat = NOW()
    ");
    
    $stmt->execute([$user_id, $event_id, $session_token, $ip_address, $user_agent, $device_fingerprint]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Nueva sesión forzada correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en force_session: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al forzar nueva sesión: ' . $e->getMessage()
    ]);
}