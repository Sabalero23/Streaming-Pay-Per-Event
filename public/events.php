<?php
// public/events.php
// Página de listado y búsqueda de eventos

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';

$eventModel = new Event();

// Filtros
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// Obtener eventos según filtros
if ($search || $category || $status) {
    $events = $eventModel->search($search, $category, $status, 50);
} else {
    $events = $eventModel->getUpcomingEvents(50);
}

// Obtener categorías únicas para el filtro
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - Streaming Platform</title>
    
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
        
        .page-header {
            padding: 60px 20px 40px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }
        
        .page-header p {
            font-size: 18px;
            color: #999;
        }
        
        .filters {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #999;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-filter {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: opacity 0.3s;
            align-self: flex-end;
        }
        
        .btn-filter:hover {
            opacity: 0.9;
        }
        
        .results-count {
            margin-bottom: 20px;
            color: #999;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
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
        
        .event-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .event-status {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-live {
            background: #ff0000;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .status-scheduled {
            background: #4CAF50;
            color: white;
        }
        
        .status-ended {
            background: #666;
            color: white;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
        
        .event-description {
            color: #ccc;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #2a2a2a;
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
            font-size: 14px;
            font-weight: bold;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-watch {
            background: #ff0000;
        }
        
        .btn-watch:hover {
            background: #cc0000;
        }
        
        .no-events {
            text-align: center;
            padding: 100px 20px;
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
    
    <div class="page-header">
        <div class="container">
            <h1>Todos los Eventos</h1>
            <p>Encuentra y disfruta de transmisiones en vivo</p>
        </div>
    </div>
    
    <div class="container">
        <form class="filters" method="GET" action="/events.php">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" name="search" placeholder="Nombre del evento..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label>Categoría</label>
                <select name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Estado</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="live" <?= $status === 'live' ? 'selected' : '' ?>>En Vivo</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Próximos</option>
                    <option value="ended" <?= $status === 'ended' ? 'selected' : '' ?>>Finalizados</option>
                </select>
            </div>
            
            <button type="submit" class="btn-filter">Buscar</button>
        </form>
        
        <div class="results-count">
            <?= count($events) ?> evento(s) encontrado(s)
        </div>
        
        <?php if (!empty($events)): ?>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
            <div class="event-card" onclick="location.href='/event.php?id=<?= $event['id'] ?>'">
                <div class="event-thumbnail">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        ⚽
                    <?php endif; ?>
                    
                    <div class="event-status status-<?= $event['status'] ?>">
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
                
                <div class="event-info">
                    <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Sin categoría') ?></div>
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    
                    <?php if ($event['description']): ?>
                    <p class="event-description"><?= htmlspecialchars($event['description']) ?></p>
                    <?php endif; ?>
                    
                    <p class="event-date">
                        <?php if ($event['status'] === 'live'): ?>
                            🔴 En vivo ahora
                        <?php else: ?>
                            📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                        <?php endif; ?>
                    </p>
                    
                    <div class="event-footer">
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        
                        <?php if ($event['status'] === 'live'): ?>
                            <a href="/event.php?id=<?= $event['id'] ?>" class="btn btn-watch" onclick="event.stopPropagation()">Ver Ahora</a>
                        <?php else: ?>
                            <a href="/event.php?id=<?= $event['id'] ?>" class="btn" onclick="event.stopPropagation()">
                                <?= $event['status'] === 'ended' ? 'Ver Detalles' : 'Comprar' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-events">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3>No se encontraron eventos</h3>
            <p>Intenta ajustar tus filtros de búsqueda</p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2025 Streaming Platform. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
