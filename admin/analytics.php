<?php
// admin/analytics.php
session_start();

// CAMBIO: Permitir admin Y streamer
$allowedRoles = ['admin', 'streamer', 'moderator'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /public/login.php');
    exit;
}

$isAdmin = $_SESSION['user_role'] === 'admin';
$isStreamer = $_SESSION['user_role'] === 'streamer';
$userId = $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

if ($isAdmin) {
    // Admin: métricas globales
    $metrics = $db->query("SELECT 
        (SELECT COUNT(*) FROM events) as total_events,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM purchases WHERE status='completed') as total_sales,
        (SELECT COALESCE(SUM(amount), 0) FROM purchases WHERE status='completed') as total_revenue,
        (SELECT COUNT(DISTINCT user_id) FROM purchases WHERE status='completed') as paying_users
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Eventos más vendidos
    $top_events = $db->query("SELECT e.title, 
        COUNT(p.id) as sales, 
        COALESCE(SUM(p.amount), 0) as revenue
        FROM events e
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status='completed'
        GROUP BY e.id
        ORDER BY sales DESC
        LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // Ventas por día
    $daily_sales = $db->query("SELECT 
        DATE(purchased_at) as date,
        COUNT(*) as sales,
        COALESCE(SUM(amount), 0) as revenue
        FROM purchases
        WHERE status='completed' AND purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(purchased_at)
        ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);
        
} else {
    // Streamer: solo sus métricas
    $stmt = $db->prepare("SELECT 
        (SELECT COUNT(*) FROM events WHERE created_by = ?) as total_events,
        (SELECT COUNT(DISTINCT p.user_id) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed') as paying_users,
        (SELECT COUNT(*) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed') as total_sales,
        (SELECT COALESCE(SUM(p.amount), 0) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed') as total_revenue
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    $metrics['total_users'] = 0; // Streamers no ven total de usuarios
    
    // Sus eventos más vendidos
    $stmt = $db->prepare("SELECT e.title, 
        COUNT(p.id) as sales, 
        COALESCE(SUM(p.amount), 0) as revenue
        FROM events e
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status='completed'
        WHERE e.created_by = ?
        GROUP BY e.id
        ORDER BY sales DESC
        LIMIT 10");
    $stmt->execute([$userId]);
    $top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sus ventas por día
    $stmt = $db->prepare("SELECT 
        DATE(p.purchased_at) as date,
        COUNT(*) as sales,
        COALESCE(SUM(p.amount), 0) as revenue
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ? AND p.status='completed' AND p.purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(p.purchased_at)
        ORDER BY date ASC");
    $stmt->execute([$userId]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Asegurar que no hay NULL
$metrics['total_events'] = $metrics['total_events'] ?? 0;
$metrics['total_users'] = $metrics['total_users'] ?? 0;
$metrics['total_sales'] = $metrics['total_sales'] ?? 0;
$metrics['total_revenue'] = $metrics['total_revenue'] ?? 0;
$metrics['paying_users'] = $metrics['paying_users'] ?? 0;

// Calcular tasa de conversión (solo para admin)
$conversion_rate = 0;
if ($isAdmin && $metrics['total_users'] > 0) {
    $conversion_rate = round(($metrics['paying_users'] / $metrics['total_users']) * 100, 1);
}

$page_title = "Analíticas";
$page_icon = "📊";

require_once 'header.php';
require_once 'styles.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🎬</div>
        <div class="stat-value"><?= number_format($metrics['total_events']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Eventos' : 'Eventos Creados' ?></div>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($metrics['total_users']) ?></div>
        <div class="stat-label">Usuarios Totales</div>
    </div>
    <?php endif; ?>
    
    <div class="stat-card">
        <div class="stat-icon">💳</div>
        <div class="stat-value"><?= number_format($metrics['paying_users']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Compradores' : 'Usuarios Pagadores' ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-value"><?= number_format($metrics['total_sales']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ventas' : 'Ventas Totales' ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value">$<?= number_format((float)$metrics['total_revenue'], 2) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ganancias' : 'Revenue Total' ?></div>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-value"><?= $conversion_rate ?>%</div>
        <div class="stat-label">Tasa de Conversión</div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($daily_sales)): ?>
<div class="section">
    <h2>💹 <?= $isStreamer ? 'Mis Ventas' : 'Ventas' ?> Últimos 30 Días</h2>
    <canvas id="salesChart" height="80"></canvas>
</div>
<?php else: ?>
<div class="section">
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <h3>No hay datos de ventas</h3>
        <p>Los gráficos aparecerán cuando haya transacciones completadas</p>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <h2>🏆 <?= $isStreamer ? 'Mis Eventos' : 'Eventos' ?> Más Vendidos</h2>
    <?php if (!empty($top_events) && $top_events[0]['sales'] > 0): ?>
    <div class="table-responsive">
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
                        <td><strong style="color: #4CAF50;">$<?= number_format((float)$event['revenue'], 2) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <h3>No hay ventas de eventos</h3>
        <p>Esta tabla mostrará los eventos más vendidos cuando haya compras</p>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($daily_sales)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    labels: { font: { size: 14 } }
                },
                tooltip: { 
                    backgroundColor: 'rgba(0,0,0,0.8)', 
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { font: { size: 12 } }
                },
                x: {
                    ticks: { font: { size: 12 } }
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>