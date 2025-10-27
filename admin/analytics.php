<?php
// admin/analytics.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Métricas generales con COALESCE para evitar NULL
$metrics = $db->query("SELECT 
    (SELECT COUNT(*) FROM events) as total_events,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM purchases WHERE status='completed') as total_sales,
    (SELECT COALESCE(SUM(amount), 0) FROM purchases WHERE status='completed') as total_revenue,
    (SELECT COUNT(DISTINCT user_id) FROM purchases WHERE status='completed') as paying_users
")->fetch(PDO::FETCH_ASSOC);

// Asegurar que no hay NULL
$metrics['total_events'] = $metrics['total_events'] ?? 0;
$metrics['total_users'] = $metrics['total_users'] ?? 0;
$metrics['total_sales'] = $metrics['total_sales'] ?? 0;
$metrics['total_revenue'] = $metrics['total_revenue'] ?? 0;
$metrics['paying_users'] = $metrics['paying_users'] ?? 0;

// Eventos más vendidos
$top_events = $db->query("SELECT e.title, 
    COUNT(p.id) as sales, 
    COALESCE(SUM(p.amount), 0) as revenue
    FROM events e
    LEFT JOIN purchases p ON e.id = p.event_id AND p.status='completed'
    GROUP BY e.id
    ORDER BY sales DESC
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Ventas por día (últimos 30 días)
$daily_sales = $db->query("SELECT 
    DATE(purchased_at) as date,
    COUNT(*) as sales,
    COALESCE(SUM(amount), 0) as revenue
    FROM purchases
    WHERE status='completed' AND purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(purchased_at)
    ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Calcular tasa de conversión
$conversion_rate = 0;
if ($metrics['total_users'] > 0) {
    $conversion_rate = round(($metrics['paying_users'] / $metrics['total_users']) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analíticas - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        
        .admin-header { 
            background: #2c3e50; 
            color: white; 
            padding: 20px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-header h1 { font-size: 24px; }
        .admin-nav { display: flex; gap: 5px; }
        .admin-nav a { 
            color: white; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
        
        .container { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
        
        .metrics-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        .metric-card { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .metric-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .metric-value { 
            font-size: 36px; 
            font-weight: bold; 
            color: #667eea; 
            margin-bottom: 10px;
        }
        
        .metric-label { 
            color: #666; 
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .chart-container { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-container h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        table { width: 100%; border-collapse: collapse; }
        table th { 
            text-align: left; 
            padding: 12px; 
            background: #f8f9fa; 
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 14px;
        }
        table td { 
            padding: 12px; 
            border-bottom: 1px solid #dee2e6; 
            font-size: 14px;
        }
        table tr:hover { background: #f8f9fa; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>📊 Analíticas</h1>
        <nav class="admin-nav">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/events.php">Eventos</a>
            <a href="/admin/users.php">Usuarios</a>
            <a href="/admin/purchases.php">Compras</a>
            <a href="/admin/analytics.php">Analíticas</a>
            <a href="/public/logout.php">Salir</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">🎬</div>
                <div class="metric-value"><?= number_format($metrics['total_events']) ?></div>
                <div class="metric-label">Eventos Creados</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">👥</div>
                <div class="metric-value"><?= number_format($metrics['total_users']) ?></div>
                <div class="metric-label">Usuarios Totales</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">💳</div>
                <div class="metric-value"><?= number_format($metrics['paying_users']) ?></div>
                <div class="metric-label">Usuarios Pagadores</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">🛒</div>
                <div class="metric-value"><?= number_format($metrics['total_sales']) ?></div>
                <div class="metric-label">Ventas Totales</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">💰</div>
                <div class="metric-value">$<?= number_format((float)$metrics['total_revenue'], 2) ?></div>
                <div class="metric-label">Revenue Total</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">📈</div>
                <div class="metric-value"><?= $conversion_rate ?>%</div>
                <div class="metric-label">Tasa de Conversión</div>
            </div>
        </div>
        
        <?php if (!empty($daily_sales)): ?>
        <div class="chart-container">
            <h2>Ventas Últimos 30 Días</h2>
            <canvas id="salesChart" height="80"></canvas>
        </div>
        <?php else: ?>
        <div class="chart-container">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <h3>No hay datos de ventas</h3>
                <p>Los gráficos aparecerán cuando haya transacciones completadas</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="chart-container">
            <h2>Eventos Más Vendidos</h2>
            <?php if (!empty($top_events) && $top_events[0]['sales'] > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Ventas</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_events as $event): ?>
                        <?php if ($event['sales'] > 0): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['title']) ?></td>
                            <td><strong><?= number_format($event['sales']) ?></strong></td>
                            <td><strong>$<?= number_format((float)$event['revenue'], 2) ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎬</div>
                <h3>No hay ventas de eventos</h3>
                <p>Esta tabla mostrará los eventos más vendidos cuando haya compras</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($daily_sales)): ?>
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($daily_sales, 'date')) ?>,
                datasets: [{
                    label: 'Ventas',
                    data: <?= json_encode(array_column($daily_sales, 'sales')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Revenue ($)',
                    data: <?= json_encode(array_column($daily_sales, 'revenue')) ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { 
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
