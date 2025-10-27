<?php
// admin/users.php
// Gestión de usuarios

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Cambiar estado de usuario
if ($action === 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $userId = $_GET['id'];
    $newStatus = $_GET['status'];
    
    if (in_array($newStatus, ['active', 'suspended', 'banned'])) {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        $success = "Estado del usuario actualizado";
    }
    $action = 'list';
}

// Cambiar rol de usuario
if ($action === 'change_role' && isset($_GET['id']) && isset($_GET['role'])) {
    $userId = $_GET['id'];
    $newRole = $_GET['role'];
    
    if (in_array($newRole, ['user', 'admin', 'moderator'])) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $success = "Rol del usuario actualizado";
    }
    $action = 'list';
}

// Filtrar usuarios
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT u.*, 
        COUNT(DISTINCT p.id) as total_purchases,
        SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as total_spent
        FROM users u
        LEFT JOIN purchases p ON u.id = p.user_id
        WHERE 1=1";

$params = [];

if ($filter === 'admin') {
    $sql .= " AND u.role = 'admin'";
} elseif ($filter === 'suspended') {
    $sql .= " AND u.status = 'suspended'";
} elseif ($filter === 'banned') {
    $sql .= " AND u.status = 'banned'";
}

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Estadísticas
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['admins'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['new_month'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        .admin-header { background: #2c3e50; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 24px; }
        .admin-nav { display: flex; gap: 5px; }
        .admin-nav a { color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; transition: background 0.3s; }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        .section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-bar { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-bar a { padding: 8px 16px; border-radius: 5px; text-decoration: none; border: 2px solid #e0e0e0; color: #333; }
        .filter-bar a.active, .filter-bar a:hover { background: #667eea; color: white; border-color: #667eea; }
        .search-box { flex: 1; min-width: 250px; }
        .search-box input { width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 12px; background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 14px; }
        table td { padding: 12px; border-bottom: 1px solid #dee2e6; font-size: 14px; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-block; cursor: pointer; border: none; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-success { background: #4CAF50; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>👥 Gestión de Usuarios</h1>
        <nav class="admin-nav">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/events.php">Eventos</a>
            <a href="/admin/users.php">Usuarios</a>
            <a href="/admin/purchases.php">Compras</a>
            <a href="/public/logout.php">Salir</a>
        </nav>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['active'] ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['admins'] ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['new_month'] ?></div>
                <div class="stat-label">Nuevos (30 días)</div>
            </div>
        </div>
        
        <form method="GET" class="filter-bar">
            <strong>Filtrar:</strong>
            <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">Todos</a>
            <a href="?filter=admin" class="<?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
            <a href="?filter=suspended" class="<?= $filter === 'suspended' ? 'active' : '' ?>">Suspendidos</a>
            <a href="?filter=banned" class="<?= $filter === 'banned' ? 'active' : '' ?>">Baneados</a>
            
            <div class="search-box">
                <input type="text" name="search" placeholder="Buscar por nombre o email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
        </form>
        
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Estado</th>
                        <th>Compras</th>
                        <th>Total Gastado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                                <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                                <?= strtoupper($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= strtoupper($user['status']) ?>
                            </span>
                        </td>
                        <td><?= $user['total_purchases'] ?></td>
                        <td>$<?= number_format($user['total_spent'], 2) ?></td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <select onchange="if(confirm('¿Cambiar estado?')) location.href='?action=change_status&id=<?= $user['id'] ?>&status='+this.value" class="btn">
                                <option value="">Estado...</option>
                                <option value="active">Activar</option>
                                <option value="suspended">Suspender</option>
                                <option value="banned">Banear</option>
                            </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
