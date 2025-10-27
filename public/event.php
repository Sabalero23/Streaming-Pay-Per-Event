<?php
// public/event.php
// Página de detalle de evento
session_start();

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$event_id = $_GET['id'] ?? 0;

// Obtener información del evento
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: /public/events.php');
    exit;
}

$page_title = $event['title'];

// Verificar si el evento es gratuito
$isFree = (float)$event['price'] === 0.0;

// Verificar si el usuario ya compró/accedió al evento
$hasPurchased = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT id FROM purchases WHERE user_id = ? AND event_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id'], $event_id]);
    $hasPurchased = $stmt->fetch() !== false;
}

// Procesar compra o acceso gratuito
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /public/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    try {
        // Generar ID de transacción único
        $transaction_id = 'TXN-' . strtoupper(uniqid()) . '-' . time();
        
        // Generar token de acceso único
        $access_token = bin2hex(random_bytes(32));
        
        if ($isFree) {
            // Evento gratuito - acceso inmediato sin pago
            $stmt = $db->prepare("
                INSERT INTO purchases 
                (user_id, event_id, transaction_id, payment_method, amount, currency, status, access_token, purchased_at) 
                VALUES (?, ?, ?, 'free', 0.00, ?, 'completed', ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $event_id, 
                $transaction_id,
                $event['currency'],
                $access_token
            ]);
            
            $success = "¡Acceso obtenido con éxito! Ya puedes ver el evento.";
        } else {
            // Evento de pago - registrar compra pendiente
            $stmt = $db->prepare("
                INSERT INTO purchases 
                (user_id, event_id, transaction_id, payment_method, amount, currency, status, access_token, purchased_at) 
                VALUES (?, ?, ?, 'pending', ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $event_id, 
                $transaction_id,
                $event['price'],
                $event['currency'],
                $access_token
            ]);
            
            // Redireccionar a pasarela de pago (implementar según tu método)
            // header('Location: /public/checkout.php?purchase_id=' . $db->lastInsertId());
            // exit;
            
            // Por ahora, marcar como completado (temporal)
            $purchase_id = $db->lastInsertId();
            $stmt = $db->prepare("UPDATE purchases SET status = 'completed' WHERE id = ?");
            $stmt->execute([$purchase_id]);
            
            $success = "¡Compra realizada con éxito! Ya puedes ver el evento.";
        }
        
        $hasPurchased = true;
        
        // Registrar en analytics
        try {
            $stmt = $db->prepare("
                INSERT INTO analytics 
                (event_id, user_id, action, details, ip_address, user_agent) 
                VALUES (?, ?, 'purchase', ?, ?, ?)
            ");
            $stmt->execute([
                $event_id,
                $_SESSION['user_id'],
                json_encode([
                    'amount' => $event['price'],
                    'currency' => $event['currency'],
                    'payment_method' => $isFree ? 'free' : 'pending',
                    'transaction_id' => $transaction_id
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Analytics error: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("Purchase error: " . $e->getMessage());
        $error = "Error al procesar la compra. Intenta nuevamente.";
    }
}

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.event-detail {
    max-width: 1200px;
    margin: 0 auto;
}

.event-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 40px;
    margin-bottom: 30px;
}

.event-main {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.event-content {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 30px;
}

.event-sidebar {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 30px;
    height: fit-content;
}

.event-thumbnail-large {
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 120px;
    margin-bottom: 30px;
}

.event-thumbnail-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 0;
    border-bottom: 1px solid #333;
}

.info-item:last-child {
    border-bottom: none;
}

.free-badge {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    display: inline-block;
    font-size: 14px;
}

@media (max-width: 768px) {
    .event-main {
        grid-template-columns: 1fr;
    }
    
    .event-hero {
        padding: 20px;
    }
    
    .event-thumbnail-large {
        height: 250px;
        font-size: 80px;
    }
}
</style>

<div class="section">
    <div class="event-detail">
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="event-hero">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                <span class="event-category"><?= htmlspecialchars($event['category']) ?></span>
                
                <?php if ($isFree): ?>
                <span class="free-badge">🎁 GRATIS</span>
                <?php endif; ?>
                
                <?php if ($event['status'] === 'live'): ?>
                <span class="badge-live">🔴 EN VIVO</span>
                <?php elseif ($event['status'] === 'scheduled'): ?>
                <span class="badge badge-success">PRÓXIMAMENTE</span>
                <?php elseif ($event['status'] === 'ended'): ?>
                <span class="badge badge-warning">FINALIZADO</span>
                <?php endif; ?>
            </div>
            
            <h1 style="font-size: 36px; margin-bottom: 15px;"><?= htmlspecialchars($event['title']) ?></h1>
            
            <p style="font-size: 18px; opacity: 0.9; line-height: 1.6;">
                <?= htmlspecialchars($event['description']) ?>
            </p>
        </div>

        <div class="event-main">
            <div class="event-content">
                <div class="event-thumbnail-large">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        ⚽
                    <?php endif; ?>
                </div>

                <h2 style="margin-bottom: 20px;">Detalles del Evento</h2>
                
                <div class="info-item">
                    <span style="font-size: 24px;">📅</span>
                    <div>
                        <strong>Fecha y Hora</strong><br>
                        <span style="color: #999;">
                            <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <span style="font-size: 24px;">🏆</span>
                    <div>
                        <strong>Categoría</strong><br>
                        <span style="color: #999;"><?= htmlspecialchars($event['category']) ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <span style="font-size: 24px;">💰</span>
                    <div>
                        <strong>Precio</strong><br>
                        <?php if ($isFree): ?>
                        <span style="color: #4CAF50; font-size: 24px; font-weight: bold;">
                            GRATIS
                        </span>
                        <?php else: ?>
                        <span style="color: #4CAF50; font-size: 24px; font-weight: bold;">
                            <?= $event['currency'] ?> <?= number_format($event['price'], 2) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($event['enable_chat']): ?>
                <div class="info-item">
                    <span style="font-size: 24px;">💬</span>
                    <div>
                        <strong>Chat en Vivo</strong><br>
                        <span style="color: #999;">Disponible durante la transmisión</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($event['enable_recording']): ?>
                <div class="info-item">
                    <span style="font-size: 24px;">🎥</span>
                    <div>
                        <strong>Grabación</strong><br>
                        <span style="color: #999;">Disponible después del evento</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="event-sidebar">
                <h3 style="margin-bottom: 20px;">Acceso al Evento</h3>

                <?php if ($hasPurchased): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        ✅ Ya tienes acceso a este evento
                    </div>

                    <?php if ($event['status'] === 'live'): ?>
                        <a href="/public/watch.php?id=<?= $event_id ?>" class="btn btn-primary" style="width: 100%; text-align: center; font-size: 18px; padding: 15px;">
                            ▶️ Ver Ahora
                        </a>
                    <?php elseif ($event['status'] === 'ended'): ?>
                        <?php if ($event['enable_recording']): ?>
                        <a style="width: 100%; text-align: center;">
                            🔴 Evento Finalizado
                        </a>
                        <?php else: ?>
                        <p style="text-align: center; color: #999;">
                            El evento ha finalizado
                        </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #999;">
                            El evento comenzará el<br>
                            <strong><?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?></strong>
                        </p>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <?php if ($isFree): ?>
                        <div style="font-size: 48px; font-weight: bold; color: #4CAF50; margin-bottom: 10px;">
                            GRATIS
                        </div>
                        <p style="color: #999;">Acceso sin costo</p>
                        <?php else: ?>
                        <div style="font-size: 48px; font-weight: bold; color: #4CAF50; margin-bottom: 10px;">
                            <?= $event['currency'] ?> <?= number_format($event['price'], 2) ?>
                        </div>
                        <p style="color: #999;">Pago único</p>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST">
                            <button type="submit" name="purchase" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 15px;">
                                <?php if ($isFree): ?>
                                🎁 Obtener Acceso Gratis
                                <?php else: ?>
                                🎫 Comprar Ahora
                                <?php endif; ?>
                            </button>
                        </form>
                        <p style="text-align: center; color: #999; font-size: 13px; margin-top: 15px;">
                            <?php if ($isFree): ?>
                            Acceso inmediato sin costo
                            <?php else: ?>
                            Pago seguro y acceso inmediato
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <a href="/public/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary" style="width: 100%; text-align: center; font-size: 18px; padding: 15px;">
                            <?php if ($isFree): ?>
                            🔓 Inicia Sesión para Acceder
                            <?php else: ?>
                            🔓 Inicia Sesión para Comprar
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #333;">
                    <h4 style="margin-bottom: 15px;">¿Qué incluye?</h4>
                    <ul style="color: #999; line-height: 2;">
                        <li>✅ Acceso completo al evento</li>
                        <li>✅ Calidad HD</li>
                        <?php if ($event['enable_chat']): ?>
                        <li>✅ Chat en vivo</li>
                        <?php endif; ?>
                        <?php if ($event['enable_recording']): ?>
                        <li>✅ Grabación disponible</li>
                        <?php endif; ?>
                        <li>✅ Soporte técnico</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>