<?php
// public/payment/success.php
// Página de confirmación de pago exitoso con confirmación automática
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/payment.php';

$db = Database::getInstance()->getConnection();
$paymentConfig = PaymentConfig::getInstance();

$purchase_id = $_GET['purchase_id'] ?? 0;
$payment_id = $_GET['payment_id'] ?? null;
$status = $_GET['status'] ?? null;
$merchant_order_id = $_GET['merchant_order_id'] ?? null;

// Obtener información de la compra
$stmt = $db->prepare("
    SELECT p.*, e.title as event_title, e.price, e.currency, u.email as user_email, u.full_name as user_name
    FROM purchases p
    JOIN events e ON p.event_id = e.id
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    header('Location: /public/events.php');
    exit;
}

// CONFIRMACIÓN AUTOMÁTICA DEL PAGO
// Si el pago está pendiente y MercadoPago devolvió status approved, confirmarlo
if ($purchase['status'] === 'pending' && ($status === 'approved' || $payment_id)) {
    try {
        // Actualizar el estado de la compra a completed
        $stmt = $db->prepare("
            UPDATE purchases 
            SET status = 'completed',
                payment_method = 'mercadopago',
                transaction_id = COALESCE(?, transaction_id)
            WHERE id = ?
        ");
        $stmt->execute([$payment_id, $purchase_id]);
        
        // Obtener el streamer_id del evento
        $stmt = $db->prepare("SELECT created_by FROM events WHERE id = ?");
        $stmt->execute([$purchase['event_id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        $streamer_id = $event['created_by'];
        
        // Calcular distribución de ganancias
        $earnings = $paymentConfig->calculateEarnings($purchase['price'], $streamer_id);
        
        // Registrar en earnings
        $stmt = $db->prepare("
            INSERT INTO earnings 
            (purchase_id, streamer_id, platform_earnings, streamer_earnings, currency, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $purchase_id,
            $streamer_id,
            $earnings['platform_earnings'],
            $earnings['streamer_earnings'],
            $purchase['currency']
        ]);
        
        // Actualizar datos de la compra para mostrar
        $purchase['status'] = 'completed';
        $purchase['transaction_id'] = $payment_id ?? $purchase['transaction_id'];
        
        // Log de éxito
        error_log("Pago confirmado automáticamente - Purchase ID: $purchase_id, Payment ID: $payment_id");
        
    } catch (Exception $e) {
        error_log("Error al confirmar pago automáticamente: " . $e->getMessage());
    }
}

$page_title = "Pago Exitoso";

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../styles.php';
?>

<style>
.success-container {
    max-width: 600px;
    margin: 50px auto;
    text-align: center;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    font-size: 50px;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.success-card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.purchase-details {
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #333;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #999;
    font-size: 14px;
}

.detail-value {
    color: #fff;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}

.status-completed {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
}

.status-pending {
    background: linear-gradient(135deg, #FFA726 0%, #FB8C00 100%);
    color: white;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.action-buttons .btn {
    flex: 1;
    padding: 15px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .success-container {
        margin: 20px;
    }
    
    .success-card {
        padding: 25px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="section">
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                ✅
            </div>
            
            <h1 style="margin-bottom: 15px; font-size: 32px;">
                ¡Pago Exitoso!
            </h1>
            
            <p style="color: #999; font-size: 16px; margin-bottom: 30px;">
                Tu compra ha sido procesada correctamente
            </p>

            <div class="purchase-details">
                <h3 style="margin-bottom: 20px; font-size: 18px;">
                    Detalles de tu Compra
                </h3>
                
                <div class="detail-row">
                    <span class="detail-label">Evento:</span>
                    <span class="detail-value"><?= htmlspecialchars($purchase['event_title']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Monto Pagado:</span>
                    <span class="detail-value" style="color: #4CAF50;">
                        <?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">ID de Transacción:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 12px;">
                        <?= htmlspecialchars($purchase['transaction_id']) ?>
                    </span>
                </div>
                
                <?php if ($payment_id): ?>
                <div class="detail-row">
                    <span class="detail-label">ID de Pago:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 12px;">
                        <?= htmlspecialchars($payment_id) ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Fecha de Compra:</span>
                    <span class="detail-value">
                        <?= date('d/m/Y H:i', strtotime($purchase['purchased_at'])) ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">
                        <?php if ($purchase['status'] === 'completed'): ?>
                            <span class="status-badge status-completed">✅ Confirmado</span>
                        <?php else: ?>
                            <span class="status-badge status-pending">⏳ Pendiente de Confirmación</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <?php if ($purchase['status'] === 'completed'): ?>
                <div style="background: rgba(76, 175, 80, 0.1); border: 1px solid #4CAF50; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="color: #4CAF50; margin: 0; font-size: 14px;">
                        ✅ <strong>¡Ya puedes acceder al evento!</strong><br>
                        Tu compra ha sido confirmada exitosamente.
                    </p>
                </div>

                <div class="action-buttons">
                    <a href="/public/watch.php?id=<?= $purchase['event_id'] ?>" class="btn btn-primary">
                        ▶️ Ver Evento Ahora
                    </a>
                    <a href="/public/events.php" class="btn" style="background: #333;">
                        📋 Ver Todos los Eventos
                    </a>
                </div>
            <?php else: ?>
                <div style="background: rgba(255, 167, 38, 0.1); border: 1px solid #FFA726; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="color: #FFA726; margin: 0; font-size: 14px;">
                        ⏳ <strong>Tu pago está siendo procesado</strong><br>
                        Recibirás una confirmación en unos minutos.
                    </p>
                </div>

                <div class="action-buttons">
                    <a href="/public/events.php" class="btn btn-primary">
                        📋 Volver a Eventos
                    </a>
                    <a href="/public/payment/success.php?purchase_id=<?= $purchase_id ?>" class="btn" style="background: #333;">
                        🔄 Actualizar Estado
                    </a>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #333;">
                <p style="color: #666; font-size: 13px; margin: 0;">
                    Si tienes alguna duda sobre tu compra, contacta a nuestro soporte.<br>
                    Guarda este número de transacción para futuras referencias.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh si el estado es pendiente (cada 5 segundos durante 30 segundos máximo)
<?php if ($purchase['status'] === 'pending'): ?>
let refreshCount = 0;
const maxRefreshes = 6; // 6 refreshes * 5 segundos = 30 segundos

const refreshInterval = setInterval(() => {
    refreshCount++;
    if (refreshCount >= maxRefreshes) {
        clearInterval(refreshInterval);
        console.log('Se alcanzó el máximo de intentos de actualización');
    } else {
        console.log(`Actualizando estado... Intento ${refreshCount}/${maxRefreshes}`);
        window.location.reload();
    }
}, 5000);

// Limpiar interval si el usuario navega
window.addEventListener('beforeunload', () => {
    clearInterval(refreshInterval);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>