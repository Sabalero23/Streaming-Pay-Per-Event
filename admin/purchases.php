<?php
// admin/purchases.php
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

$filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Construir query según rol
if ($isAdmin) {
    $sql = "SELECT p.*, u.full_name, u.email, e.title as event_title
            FROM purchases p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id
            WHERE 1=1";
    $params = [];
} else {
    $sql = "SELECT p.*, u.full_name, u.email, e.title as event_title
            FROM purchases p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id
            WHERE e.created_by = ?";
    $params = [$userId];
}

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

// Estadísticas según rol
if ($isAdmin) {
    $stats = $db->query("SELECT 
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN status='completed' THEN amount ELSE 0 END), 0) as revenue,
        COALESCE(AVG(CASE WHEN status='completed' THEN amount ELSE NULL END), 0) as avg_ticket
        FROM purchases")->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN p.status='completed' THEN p.amount ELSE 0 END), 0) as revenue,
        COALESCE(AVG(CASE WHEN p.status='completed' THEN p.amount ELSE NULL END), 0) as avg_ticket
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Asegurar que los valores no sean null
$stats['total'] = $stats['total'] ?? 0;
$stats['revenue'] = $stats['revenue'] ?? 0;
$stats['avg_ticket'] = $stats['avg_ticket'] ?? 0;

$page_title = $isStreamer ? "Mis Ventas" : "Gestión de Compras";
$page_icon = "💰";

require_once 'header.php';
require_once 'styles.php';
?>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="stat-value"><?= number_format($stats['total']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Transacciones' : 'Total Transacciones' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-value">$<?= number_format((float)$stats['revenue'], 2) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ganancias' : 'Revenue Total' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-value">$<?= number_format((float)$stats['avg_ticket'], 2) ?></div>
        <div class="stat-label">Ticket Promedio</div>
    </div>
</div>

<form class="filter-bar" method="GET">
    <strong><i class="fas fa-filter"></i> Filtrar:</strong>
    <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">Todas</a>
    <a href="?status=completed" class="<?= $filter === 'completed' ? 'active' : '' ?>">
        <i class="fas fa-check-circle"></i> Completadas
    </a>
    <a href="?status=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">
        <i class="fas fa-clock"></i> Pendientes
    </a>
    <a href="?status=failed" class="<?= $filter === 'failed' ? 'active' : '' ?>">
        <i class="fas fa-times-circle"></i> Fallidas
    </a>
    <a href="?status=refunded" class="<?= $filter === 'refunded' ? 'active' : '' ?>">
        <i class="fas fa-undo"></i> Reembolsadas
    </a>
    
    <div class="search-box">
        <input type="text" name="search" placeholder="Buscar por usuario, email o evento..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn">
        <i class="fas fa-search"></i> Buscar
    </button>
</form>

<div class="section">
    <h2><i class="fas fa-credit-card"></i> <?= $isStreamer ? 'Mis Ventas' : 'Listado de Compras' ?></h2>
    <?php if (!empty($purchases)): ?>
    <div class="table-responsive">
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
                        <i class="fas fa-user"></i> <?= htmlspecialchars($p['full_name']) ?><br>
                        <small style="color:#999;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($p['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($p['event_title']) ?></td>
                    <td><?= strtoupper($p['payment_method']) ?></td>
                    <td><strong style="color: #4CAF50;"><?= $p['currency'] ?> <?= number_format((float)$p['amount'], 2) ?></strong></td>
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
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-shopping-cart"></i></div>
        <h3><?= $isStreamer ? 'Aún no tienes ventas' : 'No hay compras registradas' ?></h3>
        <p><?= $isStreamer ? 'Las ventas de tus eventos aparecerán aquí' : 'Las transacciones aparecerán aquí cuando los usuarios compren eventos' ?></p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>