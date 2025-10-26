<?php
// public/event.php
// Página de detalle de un evento individual con opción de compra

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    header('Location: /events.php');
    exit;
}

$eventModel = new Event();
$event = $eventModel->findById($eventId);

if (!$event) {
    header('Location: /events.php');
    exit;
}

// Verificar si el usuario ya compró este evento
$hasAccess = false;
$purchase = null;

if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $hasAccess = $userModel->hasAccessToEvent($_SESSION['user_id'], $eventId);
    
    if ($hasAccess) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM purchases WHERE user_id = ? AND event_id = ? AND status = 'completed' LIMIT 1");
        $stmt->execute([$_SESSION['user_id'], $eventId]);
        $purchase = $stmt->fetch();
    }
}

// Obtener estadísticas del evento
$stats = $eventModel->getStats($eventId);

// Error al iniciar compra
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - Streaming Platform</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #fff;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 30px;
        }
        
        .event-detail {
            padding: 60px 20px;
        }
        
        .event-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .event-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .event-main {
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .event-banner {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 120px;
            position: relative;
        }
        
        .event-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .event-status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .status-live {
            background: #ff0000;
            animation: pulse 2s infinite;
        }
        
        .status-scheduled {
            background: #4CAF50;
        }
        
        .status-ended {
            background: #666;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .event-content {
            padding: 40px;
        }
        
        .event-category {
            color: #667eea;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .event-title {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .event-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            color: #999;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .event-description {
            line-height: 1.8;
            color: #ccc;
            margin-bottom: 30px;
        }
        
        .event-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .feature-item {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .feature-label {
            font-size: 12px;
            color: #999;
        }
        
        .event-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .purchase-card {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        
        .price-display {
            font-size: 48px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .price-currency {
            font-size: 24px;
            color: #999;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-success {
            background: #4CAF50;
        }
        
        .btn-danger {
            background: #ff0000;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .purchase-info {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .purchase-info p {
            margin-bottom: 10px;
            color: #ccc;
            font-size: 14px;
        }
        
        .purchase-info p:last-child {
            margin-bottom: 0;
        }
        
        .stats-card {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 25px;
        }
        
        .stats-card h3 {
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        
        .access-notice {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .access-notice h4 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">🎥 Streaming Platform</a>
                <div class="nav-links">
                    <a href="/">Inicio</a>
                    <a href="/events.php">Eventos</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/profile.php">Mi Cuenta</a>
                    <?php else: ?>
                        <a href="/login.php">Iniciar Sesión</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="event-detail">
        <div class="container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <div class="event-grid">
                <div class="event-main">
                    <div class="event-banner">
                        <?php if ($event['thumbnail_url']): ?>
                            <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                        <?php else: ?>
                            ⚽
                        <?php endif; ?>
                        
                        <div class="event-status-badge status-<?= $event['status'] ?>">
                            <?php 
                            switch($event['status']) {
                                case 'live':
                                    echo '🔴 EN VIVO';
                                    break;
                                case 'scheduled':
                                    echo '📅 PRÓXIMO';
                                    break;
                                case 'ended':
                                    echo '✓ FINALIZADO';
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="event-content">
                        <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Sin categoría') ?></div>
                        <h1 class="event-title"><?= htmlspecialchars($event['title']) ?></h1>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <?= date('d/m/Y', strtotime($event['scheduled_start'])) ?>
                            </div>
                            <div class="meta-item">
                                🕐 <?= date('H:i', strtotime($event['scheduled_start'])) ?> hs
                            </div>
                            <?php if ($event['status'] === 'live'): ?>
                            <div class="meta-item">
                                👥 <?= $stats['unique_viewers'] ?? 0 ?> espectadores
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($event['description']): ?>
                        <div class="event-description">
                            <?= nl2br(htmlspecialchars($event['description'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="event-features">
                            <?php if ($event['enable_recording']): ?>
                            <div class="feature-item">
                                <div class="feature-icon">💾</div>
                                <div class="feature-label">Grabación disponible</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['enable_chat']): ?>
                            <div class="feature-item">
                                <div class="feature-icon">💬</div>
                                <div class="feature-label">Chat en vivo</div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="feature-item">
                                <div class="feature-icon">📺</div>
                                <div class="feature-label">HD 1080p</div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">🔒</div>
                                <div class="feature-label">Pago seguro</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="event-sidebar">
                    <div class="purchase-card">
                        <?php if ($hasAccess): ?>
                            <div class="access-notice">
                                <h4>✓ Ya tienes acceso a este evento</h4>
                                <p>Compraste este evento el <?= date('d/m/Y', strtotime($purchase['purchased_at'])) ?></p>
                            </div>
                            
                            <?php if ($event['status'] === 'live'): ?>
                                <a href="/watch/<?= $event['id'] ?>?token=<?= $purchase['access_token'] ?>" class="btn btn-danger">
                                    🔴 Ver Ahora
                                </a>
                            <?php elseif ($event['status'] === 'scheduled'): ?>
                                <button class="btn" disabled>
                                    Evento no iniciado
                                </button>
                                <p style="text-align: center; color: #999; margin-top: 10px; font-size: 14px;">
                                    Recibirás un email cuando comience
                                </p>
                            <?php else: ?>
                                <button class="btn" disabled>
                                    Evento finalizado
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="price-display">
                                <span class="price-currency"><?= $event['currency'] ?></span> <?= number_format($event['price'], 2) ?>
                            </div>
                            
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="/login.php?redirect=/event.php?id=<?= $event['id'] ?>" class="btn">
                                    Inicia sesión para comprar
                                </a>
                            <?php elseif ($event['status'] === 'ended'): ?>
                                <button class="btn" disabled>
                                    Evento finalizado
                                </button>
                            <?php else: ?>
                                <form action="/api/purchase.php" method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="btn">
                                        Comprar Acceso
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <div class="purchase-info">
                                <p>✓ Acceso único y personal</p>
                                <p>✓ Pago 100% seguro</p>
                                <p>✓ Soporte 24/7</p>
                                <?php if ($event['enable_recording']): ?>
                                <p>✓ Grabación incluida</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($event['status'] !== 'scheduled'): ?>
                    <div class="stats-card">
                        <h3>Estadísticas</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_purchases'] ?? 0 ?></div>
                                <div class="stat-label">Entradas vendidas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['unique_viewers'] ?? 0 ?></div>
                                <div class="stat-label">Espectadores</div>
                            </div>
                            <?php if ($event['status'] === 'ended'): ?>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['peak_viewers'] ?? 0 ?></div>
                                <div class="stat-label">Pico máximo</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
