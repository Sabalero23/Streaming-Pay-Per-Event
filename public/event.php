<?php
// public/event.php
// Página de detalle de evento con integración de pagos MercadoPago SDK v3.x
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';

// Cargar autoload de Composer AL INICIO
require_once __DIR__ . '/../vendor/autoload.php';

// Importar clases de MercadoPago AL INICIO
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

$db = Database::getInstance()->getConnection();
$paymentConfig = PaymentConfig::getInstance();

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

// Obtener configuración de pago
$mpConfig = $paymentConfig->getMercadoPago();
$isPaymentConfigured = $paymentConfig->isMercadoPagoConfigured();

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
            $hasPurchased = true;
            
        } else {
            // Evento de pago
            if (!$isPaymentConfigured) {
                throw new Exception("El sistema de pagos no está configurado. Contacta al administrador.");
            }
            
            // Registrar compra pendiente
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
            
            $purchase_id = $db->lastInsertId();
            
            // Calcular distribución de ganancias
            $earnings = $paymentConfig->calculateEarnings($event['price'], $event['created_by']);
            
            // Configurar MercadoPago con el access token
            MercadoPagoConfig::setAccessToken($mpConfig['access_token']);
            
            // Crear cliente de preferencias
            $client = new PreferenceClient();
            
            // Preparar datos de la preferencia
            $preferenceData = [
                'items' => [
                    [
                        'id' => (string)$event_id,
                        'title' => $event['title'],
                        'description' => substr($event['description'], 0, 200),
                        'quantity' => 1,
                        'currency_id' => $event['currency'],
                        'unit_price' => (float)$event['price']
                    ]
                ],
                'payer' => [
                    'email' => $_SESSION['user_email'] ?? 'guest@example.com',
                    'name' => $_SESSION['user_name'] ?? 'Usuario'
                ],
                'back_urls' => [
                    'success' => $mpConfig['success_url'] . '?purchase_id=' . $purchase_id,
                    'failure' => $mpConfig['failure_url'] . '?purchase_id=' . $purchase_id,
                    'pending' => $mpConfig['pending_url'] . '?purchase_id=' . $purchase_id
                ],
                'auto_return' => 'approved',
                'external_reference' => $transaction_id,
                'notification_url' => $mpConfig['notification_url'],
                'statement_descriptor' => 'STREAMING_EVENT',
                'metadata' => [
                    'purchase_id' => $purchase_id,
                    'event_id' => $event_id,
                    'user_id' => $_SESSION['user_id'],
                    'streamer_id' => $event['created_by'],
                    'streamer_earnings' => $earnings['streamer_earnings'],
                    'platform_earnings' => $earnings['platform_earnings']
                ]
            ];
            
            // Crear preferencia
            $preference = $client->create($preferenceData);
            
            // Actualizar purchase con preference_id
            $stmt = $db->prepare("UPDATE purchases SET transaction_id = ? WHERE id = ?");
            $stmt->execute([$preference->id, $purchase_id]);
            
            // Redireccionar a MercadoPago
            if ($mpConfig['sandbox']) {
                $init_point = $preference->sandbox_init_point;
            } else {
                $init_point = $preference->init_point;
            }
            
            header('Location: ' . $init_point);
            exit;
        }
        
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
                    'payment_method' => $isFree ? 'free' : 'mercadopago',
                    'transaction_id' => $transaction_id
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Analytics error: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("=== PURCHASE ERROR DEBUG ===");
        error_log("Error Type: " . get_class($e));
        error_log("Error Message: " . $e->getMessage());
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        // Debug específico para MercadoPago
        if ($e instanceof \MercadoPago\Exceptions\MPApiException) {
            error_log("=== MERCADOPAGO API ERROR ===");
            error_log("Status Code: " . $e->getStatusCode());
            error_log("API Response: " . json_encode($e->getApiResponse()));
            
            // Mostrar error más específico al usuario
            $statusCode = $e->getStatusCode();
            if ($statusCode == 401) {
                $error = "Error de autenticación con MercadoPago. Las credenciales no son válidas.";
            } elseif ($statusCode == 400) {
                $apiResponse = $e->getApiResponse();
                $errorMessage = $apiResponse['message'] ?? 'Datos incorrectos';
                $error = "Error en los datos: " . $errorMessage;
            } elseif ($statusCode == 404) {
                $error = "Recurso no encontrado en MercadoPago.";
            } else {
                $error = "Error de MercadoPago (Código: " . $statusCode . ")";
            }
        } else {
            $error = "Error al procesar el pago: " . $e->getMessage();
        }
        
        // Debug de configuración
        error_log("=== PAYMENT CONFIG DEBUG ===");
        error_log("Is MP Configured: " . ($isPaymentConfigured ? 'YES' : 'NO'));
        error_log("Access Token Length: " . strlen($mpConfig['access_token']));
        error_log("Access Token Preview: " . substr($mpConfig['access_token'], 0, 20) . '...');
        error_log("Sandbox Mode: " . ($mpConfig['sandbox'] ? 'YES' : 'NO'));
        error_log("Currency: " . $event['currency']);
        error_log("Price: " . $event['price']);
        error_log("Event ID: " . $event_id);
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

.config-warning {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #ffeaa7;
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
        
        <?php if (!$isFree && !$isPaymentConfigured && !$hasPurchased): ?>
        <div class="config-warning">
            ⚠️ <strong>Sistema de pagos en configuración.</strong> 
            Por favor, contacta al administrador para realizar tu compra.
        </div>
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
                        <a href="/public/watch.php?id=<?= $event_id ?>" class="btn btn-primary" style="width: 100%; text-align: center; font-size: 18px; padding: 15px;">
                            🎥 Ver Grabación
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
                        <p style="color: #999;">Pago único - Seguro con MercadoPago</p>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($isFree || $isPaymentConfigured): ?>
                        <form method="POST">
                            <button type="submit" name="purchase" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 15px;">
                                <?php if ($isFree): ?>
                                🎁 Obtener Acceso Gratis
                                <?php else: ?>
                                🎫 Comprar con MercadoPago
                                <?php endif; ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <button disabled class="btn" style="width: 100%; font-size: 18px; padding: 15px; opacity: 0.5; cursor: not-allowed;">
                            ⚠️ Pagos en Configuración
                        </button>
                        <?php endif; ?>
                        
                        <p style="text-align: center; color: #999; font-size: 13px; margin-top: 15px;">
                            <?php if ($isFree): ?>
                            Acceso inmediato sin costo
                            <?php elseif ($isPaymentConfigured): ?>
                            Pago seguro y acceso inmediato
                            <?php else: ?>
                            Contacta al administrador
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <a href="/public/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary" style="width: 100%; text-align: center; font-size: 18px; padding: 15px;">
                            <?php if ($isFree): ?>
                            🔐 Inicia Sesión para Acceder
                            <?php else: ?>
                            🔐 Inicia Sesión para Comprar
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
                    
                    <?php if (!$isFree && $isPaymentConfigured): ?>
                    <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <img src="https://http2.mlstatic.com/frontend-assets/mercadopago-menu/MP_Branding_OffWhite.svg" 
                                 alt="MercadoPago" style="height: 20px;">
                            <span style="color: #999; font-size: 12px;">Pago seguro</span>
                        </div>
                        <p style="font-size: 11px; color: #666; margin: 0;">
                            Aceptamos todas las tarjetas y medios de pago
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>