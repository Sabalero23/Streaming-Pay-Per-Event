<?php
// src/Services/PaymentService.php
// Compatible con MercadoPago SDK v3.x

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Event.php';

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;

class PaymentService {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../../config/payment.php';
        $this->db = Database::getInstance()->getConnection();
        
        // Configurar MercadoPago SDK v3.x
        if ($this->config['default_provider'] === 'mercadopago') {
            MercadoPagoConfig::setAccessToken($this->config['mercadopago']['access_token']);
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
        
        // Crear registro de compra pendiente
        $transactionId = $this->generateTransactionId($userId, $eventId);
        $purchaseId = $this->createPendingPurchase(
            $userId,
            $eventId,
            $transactionId,
            $event['price'],
            $event['currency']
        );
        
        // Calcular ganancias
        $earnings = $this->calculateEarnings($event['price'], $event['created_by']);
        
        // Crear cliente de preferencias
        $client = new PreferenceClient();
        
        // Preparar datos de la preferencia
        $preferenceData = [
            'items' => [
                [
                    'id' => (string)$eventId,
                    'title' => $event['title'],
                    'description' => substr($event['description'] ?? '', 0, 255),
                    'quantity' => 1,
                    'currency_id' => $event['currency'],
                    'unit_price' => (float)$event['price']
                ]
            ],
            'payer' => [
                'name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => [
                    'number' => $user['phone'] ?? ''
                ]
            ],
            'back_urls' => [
                'success' => $this->config['mercadopago']['success_url'] . '?purchase_id=' . $purchaseId,
                'failure' => $this->config['mercadopago']['failure_url'] . '?purchase_id=' . $purchaseId,
                'pending' => $this->config['mercadopago']['pending_url'] . '?purchase_id=' . $purchaseId
            ],
            'auto_return' => 'approved',
            'external_reference' => $transactionId,
            'notification_url' => $this->config['mercadopago']['notification_url'],
            'statement_descriptor' => 'STREAMING_EVENT',
            'metadata' => [
                'purchase_id' => $purchaseId,
                'user_id' => $userId,
                'event_id' => $eventId,
                'email' => $user['email'],
                'streamer_id' => $event['created_by'],
                'streamer_earnings' => $earnings['streamer_earnings'],
                'platform_earnings' => $earnings['platform_earnings']
            ]
        ];
        
        try {
            // Crear preferencia
            $preference = $client->create($preferenceData);
            
            // Actualizar purchase con preference_id
            $sql = "UPDATE purchases SET transaction_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$preference->id, $purchaseId]);
            
            return [
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point
            ];
            
        } catch (MPApiException $e) {
            error_log("MercadoPago API Error: " . $e->getMessage());
            throw new Exception("Error al crear preferencia de pago: " . $e->getMessage());
        }
    }
    
    // Generar ID de transacción único
    private function generateTransactionId($userId, $eventId) {
        return 'TXN_' . $userId . '_' . $eventId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }
    
    // Crear compra pendiente
    private function createPendingPurchase($userId, $eventId, $transactionId, $amount, $currency) {
        $accessToken = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO purchases 
                (user_id, event_id, transaction_id, payment_method, amount, currency, status, access_token, purchased_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $eventId,
            $transactionId,
            'mercadopago',
            $amount,
            $currency,
            $accessToken
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
            // Crear cliente de pagos
            $client = new PaymentClient();
            $payment = $client->get($paymentId);
            
            if (!$payment) {
                throw new Exception("Pago no encontrado");
            }
            
            // Buscar la compra en nuestra base de datos
            $externalReference = $payment->external_reference;
            $sql = "SELECT * FROM purchases WHERE transaction_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$externalReference]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$purchase) {
                throw new Exception("Compra no encontrada");
            }
            
            // Actualizar estado según el status del pago
            switch ($payment->status) {
                case 'approved':
                    $this->approvePurchase($purchase['id'], $paymentId, $payment);
                    break;
                    
                case 'rejected':
                case 'cancelled':
                    $this->rejectPurchase($purchase['id'], $payment->status_detail);
                    break;
                    
                case 'refunded':
                    $this->refundPurchase($purchase['id']);
                    break;
                    
                case 'pending':
                case 'in_process':
                case 'in_mediation':
                    $this->updatePurchaseStatus($purchase['id'], $payment->status);
                    break;
            }
            
            return ['success' => true, 'status' => $payment->status];
            
        } catch (MPApiException $e) {
            error_log("MercadoPago API Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error processing webhook: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Aprobar compra
    private function approvePurchase($purchaseId, $paymentId, $payment) {
        try {
            $this->db->beginTransaction();
            
            // Actualizar estado de la compra
            $sql = "UPDATE purchases 
                    SET status = 'completed', 
                        payment_id = ?,
                        payment_status = 'approved',
                        completed_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paymentId, $purchaseId]);
            
            // Obtener información de la compra
            $sql = "SELECT * FROM purchases WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener información del evento
            $sql = "SELECT * FROM events WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchase['event_id']]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Registrar ganancias
            $earnings = $this->calculateEarnings($purchase['amount'], $event['created_by']);
            $this->recordEarnings(
                $purchase['event_id'],
                $event['created_by'],
                $purchase['amount'],
                $earnings['streamer_earnings'],
                $earnings['platform_earnings'],
                $purchaseId
            );
            
            // Incrementar contador de ventas del evento
            $sql = "UPDATE events SET purchases_count = purchases_count + 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchase['event_id']]);
            
            // Registrar en analytics
            $sql = "INSERT INTO analytics 
                    (event_id, user_id, action, details, ip_address, user_agent, created_at) 
                    VALUES (?, ?, 'purchase_completed', ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $purchase['event_id'],
                $purchase['user_id'],
                json_encode([
                    'payment_id' => $paymentId,
                    'amount' => $purchase['amount'],
                    'currency' => $purchase['currency'],
                    'payment_method' => $payment->payment_method_id ?? 'mercadopago'
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'webhook',
                $_SERVER['HTTP_USER_AGENT'] ?? 'MercadoPago Webhook'
            ]);
            
            $this->db->commit();
            
            // Enviar notificación al usuario (opcional)
            $this->sendPurchaseConfirmation($purchase['user_id'], $purchase['event_id']);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error approving purchase: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Rechazar compra
    private function rejectPurchase($purchaseId, $statusDetail = null) {
        $sql = "UPDATE purchases 
                SET status = 'failed',
                    payment_status = 'rejected',
                    status_detail = ?,
                    updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$statusDetail, $purchaseId]);
    }
    
    // Reembolsar compra
    private function refundPurchase($purchaseId) {
        try {
            $this->db->beginTransaction();
            
            // Obtener información de la compra
            $sql = "SELECT * FROM purchases WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$purchase) {
                throw new Exception("Compra no encontrada");
            }
            
            // Actualizar estado de la compra
            $sql = "UPDATE purchases 
                    SET status = 'refunded',
                        payment_status = 'refunded',
                        refunded_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchaseId]);
            
            // Revertir ganancias si existen
            $sql = "UPDATE earnings 
                    SET status = 'refunded', updated_at = NOW() 
                    WHERE purchase_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchaseId]);
            
            // Decrementar contador de ventas del evento
            $sql = "UPDATE events SET purchases_count = GREATEST(0, purchases_count - 1) WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$purchase['event_id']]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error refunding purchase: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Actualizar estado de compra
    private function updatePurchaseStatus($purchaseId, $status) {
        $sql = "UPDATE purchases 
                SET payment_status = ?,
                    updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $purchaseId]);
    }
    
    // Calcular distribución de ganancias
    private function calculateEarnings($amount, $streamerId) {
        // Obtener porcentaje de comisión de la plataforma
        $platformFee = $this->config['platform_fee_percentage'];
        
        $platformEarnings = $amount * ($platformFee / 100);
        $streamerEarnings = $amount - $platformEarnings;
        
        return [
            'streamer_earnings' => round($streamerEarnings, 2),
            'platform_earnings' => round($platformEarnings, 2),
            'platform_fee_percentage' => $platformFee
        ];
    }
    
    // Registrar ganancias
    private function recordEarnings($eventId, $streamerId, $totalAmount, $streamerEarnings, $platformEarnings, $purchaseId) {
        $sql = "INSERT INTO earnings 
                (event_id, user_id, purchase_id, total_amount, streamer_amount, platform_amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $eventId,
            $streamerId,
            $purchaseId,
            $totalAmount,
            $streamerEarnings,
            $platformEarnings
        ]);
    }
    
    // Enviar confirmación de compra (método placeholder)
    private function sendPurchaseConfirmation($userId, $eventId) {
        // Implementar envío de email/notificación
        // Puede usar PHPMailer u otro servicio de email
        try {
            // TODO: Implementar envío de email
            error_log("Purchase confirmation should be sent to user $userId for event $eventId");
        } catch (Exception $e) {
            error_log("Error sending purchase confirmation: " . $e->getMessage());
        }
    }
    
    // Verificar estado de pago por ID
    public function checkPaymentStatus($paymentId) {
        try {
            $client = new PaymentClient();
            $payment = $client->get($paymentId);
            
            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'amount' => $payment->transaction_amount,
                'currency' => $payment->currency_id,
                'payment_method' => $payment->payment_method_id,
                'date_approved' => $payment->date_approved
            ];
            
        } catch (MPApiException $e) {
            error_log("MercadoPago API Error: " . $e->getMessage());
            throw new Exception("Error al verificar estado del pago");
        }
    }
    
    // Obtener compra por ID
    public function getPurchaseById($purchaseId) {
        $sql = "SELECT p.*, e.title as event_title, u.email as user_email 
                FROM purchases p
                LEFT JOIN events e ON p.event_id = e.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener historial de compras de un usuario
    public function getUserPurchases($userId, $limit = 50) {
        $sql = "SELECT p.*, e.title, e.thumbnail_url, e.scheduled_start 
                FROM purchases p
                JOIN events e ON p.event_id = e.id
                WHERE p.user_id = ? 
                ORDER BY p.purchased_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener ventas de eventos de un streamer
    public function getStreamerSales($streamerId, $limit = 50) {
        $sql = "SELECT p.*, e.title, u.email as buyer_email 
                FROM purchases p
                JOIN events e ON p.event_id = e.id
                JOIN users u ON p.user_id = u.id
                WHERE e.created_by = ? AND p.status = 'completed'
                ORDER BY p.completed_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}