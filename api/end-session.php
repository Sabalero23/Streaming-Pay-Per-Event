<?php
// api/end-session.php
// Finaliza la sesión de visualización y registra analíticas

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['session_token'])) {
        throw new Exception('Session token requerido');
    }
    
    $sessionToken = $input['session_token'];
    $watchTime = $input['watch_time'] ?? 0;
    
    // Obtener datos de la sesión antes de eliminarla
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "SELECT user_id, event_id FROM active_sessions WHERE session_token = ?"
    );
    $stmt->execute([$sessionToken]);
    $session = $stmt->fetch();
    
    // Finalizar sesión
    $authService = new AuthService();
    $authService->endViewingSession($sessionToken);
    
    // Registrar tiempo de visualización en analíticas
    if ($session && $watchTime > 0) {
        $stmt = $db->prepare(
            "INSERT INTO analytics (event_id, user_id, action, details, ip_address) 
             VALUES (?, ?, 'view_end', ?, ?)"
        );
        $details = json_encode(['watch_time_seconds' => $watchTime]);
        $stmt->execute([
            $session['event_id'],
            $session['user_id'],
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sesión finalizada'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
