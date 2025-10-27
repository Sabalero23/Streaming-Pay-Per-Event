<?php
// admin/purchases.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, u.full_name, u.email, e.title as event_title
        FROM purchases p
        JOIN users u ON p.user_id = u.id
        JOIN events e ON p.event_id = e.id
        WHERE 1=1";
$params = [];

if ($filter) {
    $sql .= " AND p.status = ?";
    $params[] = $filter;
}

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR e.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.purchased_at DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas con COALESCE para evitar NULL
$stats = $db->query("SELECT 
    COUNT(*) as total,
    COALESCE(SUM(CASE WHEN status='completed' THEN amount ELSE 0 END), 0) as revenue,
    COALESCE(AVG(CASE WHEN status='completed' THEN amount ELSE NULL END), 0) as avg_ticket
    FROM purchases")->fetch(PDO::FETCH_ASSOC);

// Asegurar que los valores no sean null
$stats['total'] = $stats['total'] ?? 0;
$stats['revenue'] = $stats['revenue'] ?? 0;
$stats['avg_ticket'] = $stats['avg_ticket'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .admin-header { 
            background: #2c3e50; 
            color: white; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
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
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
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
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section { 
            background: white; 
            padding: 30px; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        .badge { 
            padding: 4px 12px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: bold;
            display: inline-block;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .filter-bar { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: flex; 
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar a { 
            padding: 8px 16px; 
            border-radius: 5px; 
            text-decoration: none; 
            border: 2px solid #e0e0e0; 
            color: #333;
            transition: all 0.3s;
        }
        .filter-bar a.active,
        .filter-bar a:hover { 
            background: #667eea; 
            color: white;
            border-color: #667eea;
        }
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn:hover {
            background: #5568d3;
        }
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
        <h1>💰 Gestión de Compras</h1>
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
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Total Transacciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format((float)$stats['revenue'], 2) ?></div>
                <div class="stat-label">Revenue Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format((float)$stats['avg_ticket'], 2) ?></div>
                <div class="stat-label">Ticket Promedio</div>
            </div>
        </div>
        
        <form class="filter-bar" method="GET">
            <strong>Filtrar:</strong>
            <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">Todas</a>
            <a href="?status=completed" class="<?= $filter === 'completed' ? 'active' : '' ?>">Completadas</a>
            <a href="?status=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pendientes</a>
            <a href="?status=failed" class="<?= $filter === 'failed' ? 'active' : '' ?>">Fallidas</a>
            <a href="?status=refunded" class="<?= $filter === 'refunded' ? 'active' : '' ?>">Reembolsadas</a>
            
            <div class="search-box">
                <input type="text" name="search" placeholder="Buscar por usuario, email o evento..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn">🔍 Buscar</button>
        </form>
        
        <div class="section">
            <?php if (!empty($purchases)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><strong>#<?= $p['id'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($p['full_name']) ?><br>
                            <small style="color:#999;"><?= htmlspecialchars($p['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($p['event_title']) ?></td>
                        <td><?= strtoupper($p['payment_method']) ?></td>
                        <td><strong><?= $p['currency'] ?> <?= number_format((float)$p['amount'], 2) ?></strong></td>
                        <td>
                            <?php
                            $statusBadges = [
                                'completed' => 'badge-success',
                                'pending' => 'badge-warning',
                                'failed' => 'badge-danger',
                                'refunded' => 'badge-info'
                            ];
                            $badgeClass = $statusBadges[$p['status']] ?? 'badge-info';
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($p['purchased_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">💰</div>
                <h3>No hay compras registradas</h3>
                <p>Las transacciones aparecerán aquí cuando los usuarios compren eventos</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
