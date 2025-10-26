<?php
// api/webhooks/stream-end.php
// Se ejecuta cuando finaliza una transmisión

header('Content-Type: text/plain');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Models/Event.php';

try {
    $streamKey = $_POST['name'] ?? '';
    
    if (empty($streamKey)) {
        http_response_code(400);
        exit;
    }
    
    $eventModel = new Event();
    $event = $eventModel->findByStreamKey($streamKey);
    
    if (!$event) {
        error_log("Stream ended but event not found: {$streamKey}");
        http_response_code(404);
        exit;
    }
    
    // Marcar evento como finalizado
    $eventModel->endStream($event['id']);
    
    // Log
    error_log("Stream ended for event {$event['id']}: {$event['title']}");
    
    http_response_code(200);
    echo "OK";
    
} catch (Exception $e) {
    error_log("Stream end webhook error: " . $e->getMessage());
    http_response_code(500);
}
