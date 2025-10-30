<?php
// api/webhooks/mercadopago.php
// Webhook para procesar notificaciones de MercadoPago (SDK v3.x)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/payment.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

// Log de la solicitud para debugging
$log_file = __DIR__ . '/../../storage/logs/webhook_mp.log';
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'body' => file_get_contents('php://input')
];
file_put_contents($log_file, json_encode($log_data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

$db = Database::getInstance()->getConnection();
$paymentConfig = PaymentConfig::getInstance();
$mpConfig = $paymentConfig->getMercadoPago();

try {
    // MercadoPago envía notificaciones de tipo "payment"
    if (isset($data['type']) && $data['type'] === 'payment') {
        
        $payment_id = $data['data']['id'] ?? null;
        
        if (!$payment_id) {
            throw new Exception('Payment ID not found');
        }
        
        // Configurar MercadoPago SDK v3.x
        MercadoPagoConfig::setAccessToken($mpConfig['access_token']);
        
        // Crear cliente de pagos
        $client = new PaymentClient();
        
        // Consultar el pago en MercadoPago
        $payment = $client->get($payment_id);
        
        if (!$payment) {
            throw new Exception('Payment not found in MercadoPago');
        }
        
        // Obtener la compra desde nuestra BD usando external_reference
        $external_reference = $payment->external_reference;
        
        $stmt = $db->prepare("
            SELECT p.*, e.created_by as streamer_id
            FROM purchases p
            JOIN events e ON p.event_id = e.id
            WHERE p.transaction_id = ?
        ");
        $stmt->execute([$external_reference]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            throw new Exception('Purchase not found: ' . $external_reference);
        }
        
        // Procesar según el estado del pago
        $status_map = [
            'approved' => 'completed',
            'pending' => 'pending',
            'in_process' => 'pending',
            'rejected' => 'failed',
            'cancelled' => 'failed',
            'refunded' => 'refunded',
            'charged_back' => 'refunded'
        ];
        
        $new_status = $status_map[$payment->status] ?? 'pending';
        
        // Actualizar la compra
        $stmt = $db->prepare("
            UPDATE purchases 
            SET 
                status = ?,
                payment_method = 'mercadopago',
                transaction_id = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $new_status,
            $payment_id, // Guardar el payment_id de MP
            $purchase['id']
        ]);
        
        // Si el pago fue aprobado, registrar las ganancias
        if ($new_status === 'completed') {
            
            // Calcular distribución de ganancias
            $earnings = $paymentConfig->calculateEarnings(
                $purchase['amount'], 
                $purchase['streamer_id']
            );
            
            // Registrar en analytics
            $stmt = $db->prepare("
                INSERT INTO analytics 
                (event_id, user_id, action, details, ip_address, user_agent) 
                VALUES (?, ?, 'payment_confirmed', ?, ?, ?)
            ");
            $stmt->execute([
                $purchase['event_id'],
                $purchase['user_id'],
                json_encode([
                    'payment_id' => $payment_id,
                    'amount' => $purchase['amount'],
                    'currency' => $purchase['currency'],
                    'payment_method' => 'mercadopago',
                    'mp_status' => $payment->status,
                    'earnings' => $earnings
                ]),
                $payment->payer->identification->number ?? 'unknown',
                'MercadoPago Webhook'
            ]);
            
            // TODO: Enviar email de confirmación al usuario
            // TODO: Enviar notificación al streamer
            
            file_put_contents($log_file, "✅ Payment approved: {$payment_id} - Purchase: {$purchase['id']}\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "ℹ️ Payment status: {$payment->status} - Purchase: {$purchase['id']}\n", FILE_APPEND);
        }
        
        // Responder OK a MercadoPago
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Webhook processed']);
        
    } else {
        // Otros tipos de notificaciones (merchant_order, etc.)
        file_put_contents($log_file, "⚠️ Unhandled notification type: " . ($data['type'] ?? 'unknown') . "\n", FILE_APPEND);
        
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Notification received']);
    }
    
} catch (Exception $e) {
    file_put_contents($log_file, "❌ Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}