<?php
// api/webhooks/mercadopago.php
// Webhook para recibir notificaciones de pago de MercadoPago

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Services/PaymentService.php';

// Log de webhook para debugging
$logFile = __DIR__ . '/../../storage/logs/mercadopago_webhooks.log';
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'body' => file_get_contents('php://input')
];
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

try {
    // MercadoPago envía los datos como query parameters
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_GET;
    } else {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
    }
    
    if (empty($data)) {
        throw new Exception('No data received');
    }
    
    // Validar que sea una notificación válida
    if (!isset($data['type'])) {
        throw new Exception('Invalid notification');
    }
    
    $paymentService = new PaymentService();
    $result = $paymentService->handleMercadoPagoWebhook($data);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Unknown error']);
    }
    
} catch (Exception $e) {
    error_log("MercadoPago webhook error: " . $e->getMessage());
    
    // Siempre responder 200 a MercadoPago para evitar reintentos
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
