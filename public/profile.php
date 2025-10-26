<?php
// public/profile.php
// Perfil del usuario con sus eventos comprados

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);

if (!$user) {
    header('Location: /logout.php');
    exit;
}

// Obtener eventos comprados
$purchasedEvents = $userModel->getPurchasedEvents($_SESSION['user_id']);

// Obtener estadísticas
$stats = $userModel->getStats($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Streaming Platform</title>
    
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
        
        .profile-header {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 12px;
            margin: 40px 0;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
        }
        
        .profile-info h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .profile-info p {
            color: #999;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #999;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 30px;
        }
        
        .events-list {
            display: grid;
            gap: 20px;
        }
        
        .event-item {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .event-details h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .event-meta {
            color: #999;
            font-size: 14px;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: opacity 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background: #666;
            color: white;
        }
        
        .no-events {
            text-align: center;
            padding: 60px 20px;
            color: #666;
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
                    <a href="/profile.php">Mi Cuenta</a>
                    <a href="/logout.php">Salir</a>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['full_name']) ?></h1>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <p>Miembro desde <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['events_purchased'] ?? 0 ?></div>
                <div class="stat-label">Eventos Comprados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $event['currency'] ?? 'ARS' ?> <?= number_format($stats['total_spent'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Gastado</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_views'] ?? 0 ?></div>
                <div class="stat-label">Eventos Vistos</div>
            </div>
        </div>
        
        <h2 class="section-title">Mis Eventos</h2>
        
        <?php if (!empty($purchasedEvents)): ?>
        <div class="events-list">
            <?php foreach ($purchasedEvents as $event): ?>
            <div class="event-item">
                <div class="event-details">
                    <h3><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="event-meta">
                        📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?> · 
                        💰 <?= $event['currency'] ?> <?= number_format($event['price'], 2) ?> · 
                        Comprado el <?= date('d/m/Y', strtotime($event['purchased_at'])) ?>
                    </div>
                </div>
                <div class="event-actions">
                    <?php if ($event['status'] === 'live'): ?>
                        <a href="/watch/<?= $event['id'] ?>?token=<?= $event['access_token'] ?>" class="btn btn-success">
                            🔴 Ver Ahora
                        </a>
                    <?php elseif ($event['status'] === 'scheduled'): ?>
                        <button class="btn btn-secondary" disabled>Próximamente</button>
                    <?php else: ?>
                        <a href="/event.php?id=<?= $event['id'] ?>" class="btn btn-primary">Ver Detalles</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-events">
            <h3>No has comprado eventos aún</h3>
            <p>Explora nuestros eventos disponibles</p>
            <a href="/events.php" class="btn btn-primary" style="display: inline-block; margin-top: 20px;">
                Ver Eventos
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
