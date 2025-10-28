<?php
// public/payment/pending.php
// Página de pago pendiente de confirmación
session_start();

require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance()->getConnection();

$purchase_id = $_GET['purchase_id'] ?? 0;
$payment_id = $_GET['payment_id'] ?? null;
$status = $_GET['status'] ?? null;

// Obtener información de la compra
$stmt = $db->prepare("
    SELECT 
        p.*,
        e.title as event_title,
        e.id as event_id,
        e.price,
        e.currency,
        e.scheduled_start,
        e.status as event_status
    FROM purchases p
    JOIN events e ON p.event_id = e.id
    WHERE p.id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    header('Location: /public/events.php');
    exit;
}

// Verificar que el usuario sea el dueño de la compra
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $purchase['user_id']) {
    header('Location: /public/login.php');
    exit;
}

// Mantener estado como pending si aún no está confirmado
if ($purchase['status'] === 'pending') {
    $stmt = $db->prepare("UPDATE purchases SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$purchase_id]);
}

// Registrar en analytics
try {
    $stmt = $db->prepare("
        INSERT INTO analytics 
        (event_id, user_id, action, details, ip_address, user_agent) 
        VALUES (?, ?, 'payment_pending', ?, ?, ?)
    ");
    $stmt->execute([
        $purchase['event_id'],
        $_SESSION['user_id'],
        json_encode([
            'purchase_id' => $purchase_id,
            'payment_id' => $payment_id,
            'status' => $status,
            'amount' => $purchase['amount']
        ]),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
}

$page_title = "Pago Pendiente";

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../styles.php';
?>

<style>
.pending-container {
    max-width: 600px;
    margin: 50px auto;
    text-align: center;
}

.pending-icon {
    font-size: 120px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.pending-card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 40px;
    margin-top: 30px;
}

.purchase-detail {
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #999;
}

.detail-value {
    color: white;
    font-weight: 600;
}

.info-box {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.timeline {
    text-align: left;
    margin: 20px 0;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    color: #999;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: 0;
    top: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #FFC107;
}

.timeline-item:after {
    content: '';
    position: absolute;
    left: 5px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background: rgba(255, 193, 7, 0.3);
}

.timeline-item:last-child:after {
    display: none;
}

.timeline-item.completed:before {
    background: #4CAF50;
}

.timeline-item.completed:after {
    background: rgba(76, 175, 80, 0.3);
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
    background: rgba(255, 193, 7, 0.2);
    color: #FFC107;
    border: 1px solid rgba(255, 193, 7, 0.4);
}
</style>

<div class="section">
    <div class="pending-container">
        <div class="pending-icon">⏳</div>
        
        <h1 style="font-size: 36px; margin: 20px 0; color: #FFC107;">Pago Pendiente de Confirmación</h1>
        
        <p style="font-size: 18px; color: #999;">
            Tu pago está siendo procesado
        </p>
        
        <div class="status-badge">
            ⏳ PROCESANDO
        </div>
        
        <div class="pending-card">
            <h2 style="margin-bottom: 20px;">Detalles de tu Compra</h2>
            
            <div class="purchase-detail">
                <div class="detail-row">
                    <span class="detail-label">Evento:</span>
                    <span class="detail-value"><?= htmlspecialchars($purchase['event_title']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Monto:</span>
                    <span class="detail-value"><?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">ID de Transacción:</span>
                    <span class="detail-value"><?= htmlspecialchars($purchase['transaction_id']) ?></span>
                </div>
                
                <?php if ($payment_id): ?>
                <div class="detail-row">
                    <span class="detail-label">ID de Pago:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment_id) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value"><?= date('d/m/Y H:i', strtotime($purchase['purchased_at'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value" style="color: #FFC107;">
                        ⏳ Pendiente
                    </span>
                </div>
            </div>
            
            <div class="info-box">
                <h3 style="margin-bottom: 15px;">📋 Estado del Proceso</h3>
                
                <div class="timeline">
                    <div class="timeline-item completed">
                        <strong style="color: white;">Pago Iniciado</strong><br>
                        <span style="font-size: 13px;">Tu pago ha sido recibido</span>
                    </div>
                    <div class="timeline-item">
                        <strong style="color: white;">En Verificación</strong><br>
                        <span style="font-size: 13px;">Estamos confirmando tu pago con el banco</span>
                    </div>
                    <div class="timeline-item">
                        <strong style="color: white;">Confirmación Final</strong><br>
                        <span style="font-size: 13px;">Recibirás el acceso al evento</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px;">⏰ ¿Cuánto Tiempo Tomará?</h3>
                <p style="color: #999; line-height: 1.8;">
                    <strong style="color: white;">Transferencia bancaria:</strong> 1-3 días hábiles<br>
                    <strong style="color: white;">Pago en efectivo:</strong> Hasta 48 horas<br>
                    <strong style="color: white;">Otros medios:</strong> Hasta 24 horas
                </p>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="/public/profile.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">
                    Ver Estado en Mi Perfil
                </a>
            </div>
            
            <div style="margin-top: 15px;">
                <a href="/public/event.php?id=<?= $purchase['event_id'] ?>" class="btn">
                    Volver al Evento
                </a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">📧 Te Mantendremos Informado</h3>
            <p style="color: #999; font-size: 14px; margin-bottom: 10px;">
                Recibirás un email cuando tu pago sea confirmado.
            </p>
            <p style="color: #666; font-size: 13px; margin: 0;">
                Mientras tanto, puedes revisar el estado de tu compra en tu perfil.
            </p>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border: 1px solid rgba(76, 175, 80, 0.3);">
            <p style="color: #4CAF50; font-size: 13px; margin: 0;">
                ✓ Tu lugar está reservado. Una vez confirmado el pago, tendrás acceso inmediato.
            </p>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">❓ ¿Tienes Dudas?</h3>
            <p style="color: #999; font-size: 14px; margin-bottom: 15px;">
                Si tienes preguntas sobre tu pago o necesitas ayuda:
            </p>
            <a href="mailto:soporte@tudominio.com" class="btn" style="font-size: 14px; padding: 10px 20px;">
                📧 Contactar Soporte
            </a>
        </div>
    </div>
</div>

<script>
// Auto-refresh cada 30 segundos para verificar actualización del estado
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>