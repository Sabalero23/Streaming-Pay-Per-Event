<?php
// admin/dashboard.php
// Panel de administración principal

session_start();

// Verificar que sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Estadísticas generales
$stats = [];

// Total de eventos
$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = $stmt->fetch()['total'];

// Eventos en vivo
$stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE status = 'live'");
$stats['live_events'] = $stmt->fetch()['total'];

// Total de usuarios
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

// Revenue total
$stmt = $db->query("SELECT SUM(amount) as total, currency FROM purchases WHERE status = 'completed' GROUP BY currency");
$revenue = $stmt->fetchAll();

// Ventas totales
$stmt = $db->query("SELECT COUNT(*) as total FROM purchases WHERE status = 'completed'");
$stats['total_sales'] = $stmt->fetch()['total'];

// Espectadores activos
$stmt = $db->query("SELECT COUNT(*) as total FROM active_sessions WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stats['active_viewers'] = $stmt->fetch()['total'];

// Eventos recientes
$stmt = $db->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 5");
$recent_events = $stmt->fetchAll();

// Últimas compras
$stmt = $db->query("
    SELECT p.*, u.full_name, u.email, e.title 
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN events e ON p.event_id = e.id
    ORDER BY p.purchased_at DESC
    LIMIT 10
");
$recent_purchases = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 24px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            margin-left: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🎥 Panel de Administración</h1>
        <nav class="admin-nav">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/events.php">Eventos</a>
            <a href="/admin/users.php">Usuarios</a>
            <a href="/">Ver Sitio</a>
            <a href="/logout.php">Salir</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_events'] ?></div>
                <div class="stat-label">Total de Eventos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $stats['live_events'] ?></div>
                <div class="stat-label">🔴 En Vivo Ahora</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Usuarios Registrados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $stats['active_viewers'] ?></div>
                <div class="stat-label">Espectadores Activos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_sales'] ?></div>
                <div class="stat-label">Ventas Totales</div>
            </div>
            
            <div class="stat-card">
                <?php foreach ($revenue as $r): ?>
                <div class="stat-value"><?= $r['currency'] ?> <?= number_format($r['total'], 2) ?></div>
                <?php endforeach; ?>
                <div class="stat-label">Revenue Total</div>
            </div>
        </div>
        
        <div class="section">
            <h2>Eventos Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_events as $event): ?>
                    <tr>
                        <td>#<?= $event['id'] ?></td>
                        <td><?= htmlspecialchars($event['title']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?></td>
                        <td><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></td>
                        <td>
                            <?php
                            $badges = [
                                'live' => 'badge-danger',
                                'scheduled' => 'badge-success',
                                'ended' => 'badge-warning',
                                'cancelled' => 'badge-info'
                            ];
                            ?>
                            <span class="badge <?= $badges[$event['status']] ?>">
                                <?= strtoupper($event['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/event.php?id=<?= $event['id'] ?>" class="btn btn-primary">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Últimas Compras</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_purchases as $purchase): ?>
                    <tr>
                        <td>#<?= $purchase['id'] ?></td>
                        <td><?= htmlspecialchars($purchase['full_name']) ?></td>
                        <td><?= htmlspecialchars($purchase['title']) ?></td>
                        <td><?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?></td>
                        <td>
                            <span class="badge badge-success">
                                <?= strtoupper($purchase['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($purchase['purchased_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
