<?php
// api/heartbeat.php
// Mantiene la sesión activa y valida que el usuario siga siendo el mismo

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
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $authService = new AuthService();
    $result = $authService->heartbeat($sessionToken, $ipAddress);
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['error']
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
