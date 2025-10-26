<?php
// src/Services/PaymentService.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Event.php';

use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

class PaymentService {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../../config/payment.php';
        $this->db = Database::getInstance()->getConnection();
        
        // Configurar MercadoPago SDK
        if ($this->config['default_provider'] === 'mercadopago') {
            SDK::setAccessToken($this->config['mercadopago']['access_token']);
        }
    }
    
    // Crear preferencia de pago en MercadoPago
    public function createMercadoPagoPreference($userId, $eventId) {
        $eventModel = new Event();
        $userModel = new User();
        
        $event = $eventModel->findById($eventId);
        $user = $userModel->findById($userId);
        
        if (!$event) {
            throw new Exception("Evento no encontrado");
        }
        
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }
        
        // Verificar si ya compró el evento
        if ($userModel->hasAccessToEvent($userId, $eventId)) {
            throw new Exception("Ya tienes acceso a este evento");
        }
        
        // Crear item
        $item = new Item();
        $item->title = $event['title'];
        $item->description = substr($event['description'] ?? '', 0, 255);
        $item->quantity = 1;
        $item->unit_price = (float) $event['price'];
        $item->currency_id = $event['currency'];
        
        // Crear preferencia
        $preference = new Preference();
        $preference->items = [$item];
        
        // URLs de retorno
        $preference->back_urls = [
            'success' => $this->config['mercadopago']['success_url'],
            'failure' => $this->config['mercadopago']['failure_url'],
            'pending' => $this->config['mercadopago']['pending_url']
        ];
        $preference->auto_return = 'approved';
        
        // Metadata
        $preference->external_reference = $this->generateTransactionId($userId, $eventId);
        $preference->metadata = [
            'user_id' => $userId,
            'event_id' => $eventId,
            'email' => $user['email']
        ];
        
        // Información del comprador
        $preference->payer = [
            'name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => ['number' => $user['phone'] ?? '']
        ];
        
        // Notification URL (webhook)
        $preference->notification_url = $this->config['mercadopago']['notification_url'];
        
        // Guardar preferencia
        $preference->save();
        
        // Crear registro de compra pendiente
        $this->createPendingPurchase(
            $userId,
            $eventId,
            $preference->external_reference,
            $event['price'],
            $event['currency']
        );
        
        return [
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point, // URL de pago desktop
            'sandbox_init_point' => $preference->sandbox_init_point // URL de pago sandbox
        ];
    }
    
    // Generar ID de transacción único
    private function generateTransactionId($userId, $eventId) {
        return 'TXN_' . $userId . '_' . $eventId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }
    
    // Crear compra pendiente
    private function createPendingPurchase($userId, $eventId, $transactionId, $amount, $currency) {
        $sql = "INSERT INTO purchases 
                (user_id, event_id, transaction_id, payment_method, amount, currency, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $eventId,
            $transactionId,
            'mercadopago',
            $amount,
            $currency
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // Webhook de MercadoPago
    public function handleMercadoPagoWebhook($data) {
        // Validar que sea una notificación de pago
        if (!isset($data['type']) || $data['type'] !== 'payment') {
            return ['success' => false, 'message' => 'Tipo de notificación no soportado'];
        }
        
        // Obtener información del pago
        $paymentId = $data['data']['id'];
        
        try {
            $payment = \MercadoPago\Payment::find_by_id($paymentId);
            
            if (!$payment) {
                throw new Exception("Pago no encontrado");
            }
            
            // Buscar la compra en nuestra base de datos
            $externalReference = $payment->external_reference;
            $sql = "SELECT * FROM purchases WHERE transaction_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$externalReference]);
            $purchase = $stmt->fetch();
            
            if (!$purchase) {
                throw new Exception("Compra no encontrada");
            }
            
            // Actualizar estado según el status del pago
            switch ($payment->status) {
                case 'approved':
                    $this->approvePurchase($purchase['id'], $paymentId);
                    break;
                    
                case 'rejected':
                case 'cancelled':
                    $this->rejectPurchase($purchase['id']);
                    break;
                    
                case 'refunded':
                    $this->refundPurchase($purchase['id']);
                    break;
                    
                case 'pending':
                case 'in_process':
                    // Mantener como pendiente
                    break;
            }
            
            return ['success' => true, 'status' => $payment->status];
            
        } catch (Exception $e) {
            error_log("Error processing MercadoPago webhook: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Aprobar compra
    private function approvePurchase($purchaseId, $paymentId) {
        $sql = "SELECT * FROM purchases WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
        $purchase = $stmt->fetch();
        
        if (!$purchase) {
            throw new Exception("Compra no encontrada");
        }
        
        // Generar token de acceso
        $authService = new AuthService();
        $accessToken = $authService->generateEventAccessToken(
            $purchase['user_id'],
            $purchase['event_id'],
            $purchaseId
        );
        
        // Actualizar compra
        $sql = "UPDATE purchases 
                SET status = 'completed', access_token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accessToken, $purchaseId]);
        
        // Registrar en analíticas
        $this->logPurchaseAnalytics($purchase['user_id'], $purchase['event_id'], 'purchase_completed');
        
        // Enviar email de confirmación
        $this->sendPurchaseConfirmationEmail($purchase['user_id'], $purchase['event_id'], $accessToken);
        
        return true;
    }
    
    // Rechazar compra
    private function rejectPurchase($purchaseId) {
        $sql = "UPDATE purchases SET status = 'failed' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
    }
    
    // Reembolsar compra
    private function refundPurchase($purchaseId) {
        $sql = "UPDATE purchases SET status = 'refunded' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
        
        // Revocar acceso
        $sql = "UPDATE purchases SET access_token = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
    }
    
    // Enviar email de confirmación de compra
    private function sendPurchaseConfirmationEmail($userId, $eventId, $accessToken) {
        $userModel = new User();
        $eventModel = new Event();
        
        $user = $userModel->findById($userId);
        $event = $eventModel->findById($eventId);
        
        if (!$user || !$event) {
            return;
        }
        
        $watchUrl = getenv('APP_URL') . "/watch/{$eventId}?token={$accessToken}";
        $scheduledDate = date('d/m/Y H:i', strtotime($event['scheduled_start']));
        
        $subject = "Confirmación de compra - {$event['title']}";
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .button { background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; 
                             border-radius: 5px; display: inline-block; margin: 20px 0; }
                    .info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>¡Compra Confirmada!</h1>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>{$user['full_name']}</strong>,</p>
                        <p>Tu compra se ha procesado exitosamente. Ya tienes acceso al evento:</p>
                        
                        <div class='info'>
                            <h3>{$event['title']}</h3>
                            <p><strong>Fecha programada:</strong> {$scheduledDate}</p>
                            <p><strong>Precio pagado:</strong> {$event['currency']} {$event['price']}</p>
                        </div>
                        
                        <p>Podrás ver el evento cuando esté en vivo. Te enviaremos un email cuando comience.</p>
                        
                        <center>
                            <a href='{$watchUrl}' class='button'>Acceder al Evento</a>
                        </center>
                        
                        <p><strong>Importante:</strong></p>
                        <ul>
                            <li>Este enlace es personal e intransferible</li>
                            <li>Solo puedes ver desde un dispositivo a la vez</li>
                            <li>El acceso expira 30 días después de la fecha programada</li>
                        </ul>
                    </div>
                    <div class='footer'>
                        <p>Este es un correo automático, por favor no responder.</p>
                        <p>&copy; 2025 Tu Plataforma de Streaming</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@tu-dominio.com\r\n";
        
        mail($user['email'], $subject, $message, $headers);
    }
    
    // Registrar analítica de compra
    private function logPurchaseAnalytics($userId, $eventId, $action) {
        $sql = "INSERT INTO analytics (event_id, user_id, action, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $eventId,
            $userId,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // Obtener compras del usuario
    public function getUserPurchases($userId) {
        $sql = "SELECT p.*, e.title, e.scheduled_start, e.status as event_status
                FROM purchases p
                INNER JOIN events e ON p.event_id = e.id
                WHERE p.user_id = ?
                ORDER BY p.purchased_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    // Verificar si un usuario puede acceder a un evento
    public function canAccessEvent($userId, $eventId) {
        $sql = "SELECT * FROM purchases 
                WHERE user_id = ? AND event_id = ? 
                AND status = 'completed'
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $eventId]);
        
        return $stmt->fetch() !== false;
    }
}
