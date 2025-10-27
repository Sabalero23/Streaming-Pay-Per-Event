<?php
// admin/dashboard.php
// Panel principal de administración

session_start();

$page_title = "Dashboard";
$page_icon = "📊";

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

// Eventos próximos
$stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE status = 'scheduled'");
$stats['scheduled_events'] = $stmt->fetch()['total'];

// Total de usuarios
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

// Revenue total por moneda
$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total, currency FROM purchases WHERE status = 'completed' GROUP BY currency");
$revenue = $stmt->fetchAll();

// Ventas totales
$stmt = $db->query("SELECT COUNT(*) as total FROM purchases WHERE status = 'completed'");
$stats['total_sales'] = $stmt->fetch()['total'];

// Espectadores activos (últimos 2 minutos)
$stmt = $db->query("SELECT COUNT(*) as total FROM active_sessions WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stats['active_viewers'] = $stmt->fetch()['total'];

// Eventos recientes
$stmt = $db->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 10");
$recent_events = $stmt->fetchAll();

// Últimas compras
$stmt = $db->query("
    SELECT p.*, u.full_name, u.email, e.title 
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN events e ON p.event_id = e.id
    ORDER BY p.purchased_at DESC
    LIMIT 15
");
$recent_purchases = $stmt->fetchAll();

// Usuarios recientes
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$recent_users = $stmt->fetchAll();

require_once 'header.php';
require_once 'styles.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= $stats['total_events'] ?></div>
        <div class="stat-label">Total de Eventos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🔴</div>
        <div class="stat-value"><?= $stats['live_events'] ?></div>
        <div class="stat-label">En Vivo Ahora</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= $stats['scheduled_events'] ?></div>
        <div class="stat-label">Eventos Próximos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $stats['total_users'] ?></div>
        <div class="stat-label">Usuarios Registrados</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">👁️</div>
        <div class="stat-value"><?= $stats['active_viewers'] ?></div>
        <div class="stat-label">Espectadores Activos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?= $stats['total_sales'] ?></div>
        <div class="stat-label">Ventas Totales</div>
    </div>
    
    <?php foreach ($revenue as $r): ?>
    <div class="stat-card">
        <div class="stat-icon">💵</div>
        <div class="stat-value"><?= $r['currency'] ?> <?= number_format((float)$r['total'], 2) ?></div>
        <div class="stat-label">Revenue</div>
    </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2>Eventos Recientes</h2>
        <a href="/admin/events.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <?php if (!empty($recent_events)): ?>
    <div class="table-responsive">
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
                            'live' => 'badge-live',
                            'scheduled' => 'badge-success',
                            'ended' => 'badge-warning',
                            'cancelled' => 'badge-danger'
                        ];
                        ?>
                        <span class="badge <?= $badges[$event['status']] ?>">
                            <?= strtoupper($event['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/admin/events.php?action=edit&id=<?= $event['id'] ?>" class="btn btn-primary">Editar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <h3>No hay eventos registrados</h3>
        <p>Crea tu primer evento para comenzar</p>
    </div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2>Últimas Compras</h2>
        <a href="/admin/purchases.php" class="btn btn-primary">Ver Todas</a>
    </div>
    
    <?php if (!empty($recent_purchases)): ?>
    <div class="table-responsive">
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
                    <td><?= htmlspecialchars($purchase['full_name']) ?><br>
                        <small style="color:#999;"><?= htmlspecialchars($purchase['email']) ?></small>
                    </td>
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
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">💰</div>
        <h3>No hay compras registradas</h3>
    </div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2>Usuarios Recientes</h2>
        <a href="/admin/users.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <?php if (!empty($recent_users)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Estado</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td>#<?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                            <?= strtoupper($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $user['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                            <?= strtoupper($user['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">👥</div>
        <h3>No hay usuarios registrados</h3>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
