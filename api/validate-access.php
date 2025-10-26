<?php
// api/validate-access.php
// Valida que el usuario tenga acceso al evento y crea una sesión de visualización

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Models/Event.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

try {
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['event_id']) || !isset($input['access_token'])) {
        throw new Exception('Faltan parámetros requeridos');
    }
    
    $eventId = $input['event_id'];
    $accessToken = $input['access_token'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Validar token de acceso
    $authService = new AuthService();
    $tokenValidation = $authService->validateEventAccessToken($accessToken);
    
    if (!$tokenValidation['valid']) {
        throw new Exception('Token de acceso inválido o expirado');
    }
    
    $tokenData = $tokenValidation['data'];
    $userId = $tokenData['user_id'];
    
    // Verificar que el evento coincida
    if ($tokenData['event_id'] != $eventId) {
        throw new Exception('El token no corresponde a este evento');
    }
    
    // Verificar que el usuario tenga una compra válida
    $paymentService = new PaymentService();
    if (!$paymentService->canAccessEvent($userId, $eventId)) {
        throw new Exception('No tienes acceso a este evento');
    }
    
    // Obtener información del evento
    $eventModel = new Event();
    $event = $eventModel->findById($eventId);
    
    if (!$event) {
        throw new Exception('Evento no encontrado');
    }
    
    // Verificar que el evento esté en vivo
    if ($event['status'] !== 'live') {
        throw new Exception('El evento aún no ha comenzado o ya finalizó');
    }
    
    // Crear sesión de visualización (control de 1 dispositivo)
    try {
        $sessionToken = $authService->startViewingSession(
            $userId,
            $eventId,
            $ipAddress,
            $userAgent
        );
    } catch (Exception $e) {
        // Si ya hay una sesión activa en otro dispositivo
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => 'DEVICE_CONFLICT'
        ]);
        exit;
    }
    
    // Obtener información del usuario para watermark
    $userModel = new User();
    $user = $userModel->findById($userId);
    
    // Registrar en analíticas
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "INSERT INTO analytics (event_id, user_id, action, ip_address, user_agent) 
         VALUES (?, ?, 'view_start', ?, ?)"
    );
    $stmt->execute([$eventId, $userId, $ipAddress, $userAgent]);
    
    // Obtener URL HLS
    $hlsUrl = $eventModel->getHlsUrl($eventId);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'session_token' => $sessionToken,
        'hls_url' => $hlsUrl,
        'event' => [
            'id' => $event['id'],
            'title' => $event['title'],
            'status' => $event['status'],
            'enable_chat' => $event['enable_chat'],
            'enable_dvr' => $event['enable_dvr']
        ],
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'ip' => $ipAddress
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
