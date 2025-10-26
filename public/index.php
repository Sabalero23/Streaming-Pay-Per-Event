<?php
// public/index.php
// Página principal de la plataforma

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';

$eventModel = new Event();
$upcomingEvents = $eventModel->getUpcomingEvents(12);
$liveEvents = $eventModel->getLiveEvents();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Platform - Eventos Deportivos en Vivo</title>
    
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
            max-width: 1400px;
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
            transition: opacity 0.3s;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 20px;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            opacity: 0.9;
        }
        
        .section {
            padding: 60px 20px;
        }
        
        .section-title {
            font-size: 32px;
            margin-bottom: 40px;
        }
        
        .live-badge {
            background: #ff0000;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            animation: pulse 2s infinite;
            margin-bottom: 30px;
        }
        
        .live-badge::before {
            content: "●";
            margin-right: 8px;
            font-size: 18px;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .event-card {
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .event-thumbnail {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
        }
        
        .event-info {
            padding: 20px;
        }
        
        .event-category {
            color: #667eea;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .event-title {
            font-size: 20px;
            margin: 10px 0;
        }
        
        .event-date {
            color: #999;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .event-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .no-events {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-events svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .footer {
            background: #1a1a1a;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer p {
            color: #666;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .stat-card {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: #999;
            margin-top: 10px;
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
                        <a href="/logout.php">Salir</a>
                    <?php else: ?>
                        <a href="/login.php">Iniciar Sesión</a>
                        <a href="/register.php">Registrarse</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="hero">
        <div class="container">
            <h1>Vive la Emoción del Deporte en Vivo</h1>
            <p>Transmisiones en alta calidad de tus partidos favoritos</p>
        </div>
    </div>
    
    <?php if (!empty($liveEvents)): ?>
    <div class="section">
        <div class="container">
            <div class="live-badge">EN VIVO AHORA</div>
            <div class="events-grid">
                <?php foreach ($liveEvents as $event): ?>
                <div class="event-card" onclick="location.href='/event.php?id=<?= $event['id'] ?>'">
                    <div class="event-thumbnail">
                        <?php if ($event['thumbnail_url']): ?>
                            <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                        <?php else: ?>
                            ⚽
                        <?php endif; ?>
                    </div>
                    <div class="event-info">
                        <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <p class="event-date">
                            🔴 En vivo · <?= $event['current_viewers'] ?? 0 ?> espectadores
                        </p>
                        <div class="event-price">
                            <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                            <a href="/event.php?id=<?= $event['id'] ?>" class="btn">Ver Ahora</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <div class="container">
            <h2 class="section-title">Próximos Eventos</h2>
            
            <?php if (!empty($upcomingEvents)): ?>
            <div class="events-grid">
                <?php foreach ($upcomingEvents as $event): ?>
                <div class="event-card" onclick="location.href='/event.php?id=<?= $event['id'] ?>'">
                    <div class="event-thumbnail">
                        <?php if ($event['thumbnail_url']): ?>
                            <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                        <?php else: ?>
                            ⚽
                        <?php endif; ?>
                    </div>
                    <div class="event-info">
                        <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <p class="event-date">
                            📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                        </p>
                        <div class="event-price">
                            <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                            <a href="/event.php?id=<?= $event['id'] ?>" class="btn">Comprar</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-events">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <h3>No hay eventos programados</h3>
                <p>Pronto estaremos transmitiendo nuevos eventos</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="section" style="background: #1a1a1a;">
        <div class="container">
            <h2 class="section-title" style="text-align: center;">¿Por qué elegirnos?</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number">HD</div>
                    <div class="stat-label">Calidad Alta Definición</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Soporte Disponible</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Pago Seguro</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Lag o Buffering</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2025 Streaming Platform. Todos los derechos reservados.</p>
            <p style="margin-top: 10px;">
                <a href="/terms.php" style="color: #667eea; text-decoration: none;">Términos</a> · 
                <a href="/privacy.php" style="color: #667eea; text-decoration: none;">Privacidad</a> · 
                <a href="/contact.php" style="color: #667eea; text-decoration: none;">Contacto</a>
            </p>
        </div>
    </div>
</body>
</html>
