<?php
// public/payment/failure.php
// Página cuando el pago falla o es rechazado
session_start();

require_once __DIR__ . '/../../config/database.php';

$db = Database::getInstance()->getConnection();

$purchase_id = $_GET['purchase_id'] ?? 0;
$payment_id = $_GET['payment_id'] ?? null;

// Obtener información de la compra
$stmt = $db->prepare("
    SELECT p.*, e.title as event_title, e.id as event_id
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

// Actualizar estado a failed
if ($purchase['status'] === 'pending') {
    $stmt = $db->prepare("UPDATE purchases SET status = 'failed' WHERE id = ?");
    $stmt->execute([$purchase_id]);
}

$page_title = "Pago Rechazado";

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../styles.php';
?>

<style>
.failure-container {
    max-width: 600px;
    margin: 50px auto;
    text-align: center;
}

.failure-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    font-size: 50px;
    animation: shake 0.5s ease-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.failure-card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.reasons-list {
    text-align: left;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.reasons-list li {
    margin: 10px 0;
    color: #999;
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
    .failure-container {
        margin: 20px;
    }
    
    .failure-card {
        padding: 25px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="section">
    <div class="failure-container">
        <div class="failure-card">
            <div class="failure-icon">
                ❌
            </div>
            
            <h1 style="margin-bottom: 15px; font-size: 32px; color: #f44336;">
                Pago Rechazado
            </h1>
            
            <p style="color: #999; font-size: 16px; margin-bottom: 30px;">
                Tu pago no pudo ser procesado
            </p>

            <div style="background: rgba(244, 67, 54, 0.1); border: 1px solid #f44336; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #f44336; margin-bottom: 15px;">Evento:</h3>
                <p style="font-size: 18px; margin: 0;"><?= htmlspecialchars($purchase['event_title']) ?></p>
            </div>

            <div class="reasons-list">
                <h4 style="margin-bottom: 15px; color: #fff;">Posibles razones:</h4>
                <ul>
                    <li>💳 Fondos insuficientes en la tarjeta</li>
                    <li>🚫 Tarjeta rechazada por el banco</li>
                    <li>⏰ Sesión de pago expirada</li>
                    <li>🔒 Datos de pago incorrectos</li>
                    <li>🏦 Límites de compra alcanzados</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="/public/event.php?id=<?= $purchase['event_id'] ?>" class="btn btn-primary">
                    🔄 Intentar Nuevamente
                </a>
                <a href="/public/events.php" class="btn" style="background: #333;">
                    📋 Ver Otros Eventos
                </a>
            </div>

            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #333;">
                <p style="color: #666; font-size: 13px; margin: 0;">
                    💡 <strong>Sugerencia:</strong> Verifica con tu banco o prueba con otro medio de pago.<br>
                    Si el problema persiste, contacta a nuestro soporte.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>