<?php
// api/heartbeat.php
// API para mantener sesión activa y validar sesión única

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
        echo json_encode([
            'success' => false, 
            'message' => 'Usuario no autorizado'
        ]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el token de sesión sea el actual
    $stmt = $db->prepare("
        SELECT session_token 
        FROM active_sessions 
        WHERE user_id = ? AND event_id = ?
    ");
    $stmt->execute([$user_id, $event_id]);
    $current_session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $session_valid = true;
    
    if (!$current_session) {
        // No hay sesión registrada - crear una nueva
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $device_fingerprint = md5($user_agent . $ip_address);
        
        $stmt = $db->prepare("
            INSERT INTO active_sessions 
            (user_id, event_id, session_token, ip_address, user_agent, device_fingerprint, last_heartbeat) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_heartbeat = NOW()
        ");
        $stmt->execute([$user_id, $event_id, $session_token, $ip_address, $user_agent, $device_fingerprint]);
        
    } else if ($current_session['session_token'] !== $session_token) {
        // El token no coincide - esta sesión fue reemplazada por otra
        $session_valid = false;
        
        error_log("Sesión invalidada - User: $user_id, Event: $event_id, Token esperado: {$current_session['session_token']}, Token recibido: $session_token");
        
    } else {
        // Token válido - actualizar heartbeat
        $stmt = $db->prepare("
            UPDATE active_sessions 
            SET last_heartbeat = NOW() 
            WHERE user_id = ? AND event_id = ? AND session_token = ?
        ");
        $stmt->execute([$user_id, $event_id, $session_token]);
    }
    
    // Obtener número de espectadores activos (últimos 2 minutos)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM active_sessions 
        WHERE event_id = ? 
        AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmt->execute([$event_id]);
    $viewers = $stmt->fetch()['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'session_valid' => $session_valid,
        'viewers' => $viewers,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Error en heartbeat: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en heartbeat: ' . $e->getMessage(),
        'session_valid' => true // En caso de error, no expulsar al usuario
    ]);
}