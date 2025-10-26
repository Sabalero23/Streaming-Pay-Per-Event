<?php
// api/viewers-count.php
// Devuelve el número de espectadores activos de un evento

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

try {
    if (!isset($_GET['event_id'])) {
        throw new Exception('Event ID requerido');
    }
    
    $eventId = $_GET['event_id'];
    
    $authService = new AuthService();
    $count = $authService->getActiveViewersCount($eventId);
    
    echo json_encode([
        'success' => true,
        'event_id' => $eventId,
        'count' => $count,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
