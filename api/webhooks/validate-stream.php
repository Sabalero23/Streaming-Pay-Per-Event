<?php
// api/webhooks/validate-stream.php
// Webhook llamado por Nginx RTMP cuando alguien intenta publicar un stream
// Valida el stream key antes de permitir la transmisión

header('Content-Type: text/plain');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Models/Event.php';

try {
    // Nginx RTMP envía los datos como POST form data
    $streamKey = $_POST['name'] ?? '';
    
    if (empty($streamKey)) {
        // Denegar si no hay stream key
        http_response_code(403);
        echo "Stream key required";
        exit;
    }
    
    $eventModel = new Event();
    $validation = $eventModel->validateForStreaming($streamKey);
    
    if (!$validation['valid']) {
        http_response_code(403);
        echo $validation['message'];
        exit;
    }
    
    // Permitir la transmisión
    http_response_code(200);
    echo "OK";
    
} catch (Exception $e) {
    error_log("Stream validation error: " . $e->getMessage());
    http_response_code(500);
    echo "Internal error";
}
