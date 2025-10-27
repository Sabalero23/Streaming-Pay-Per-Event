<?php
// admin/dashboard.php
// Panel principal de administración

session_start();

$page_title = "Dashboard";
$page_icon = "📊";

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Determinar si es admin o streamer
$isAdmin = $_SESSION['user_role'] === 'admin';
$isStreamer = $_SESSION['user_role'] === 'streamer';
$userId = $_SESSION['user_id'];

// Estadísticas generales
$stats = [];

if ($isAdmin) {
    // ESTADÍSTICAS PARA ADMIN (todas las métricas)
    
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
    
    // Eventos recientes (todos)
    $stmt = $db->query("SELECT e.*, u.full_name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.created_at DESC LIMIT 10");
    $recent_events = $stmt->fetchAll();
    
    // Últimas compras (todas)
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
    
} else if ($isStreamer) {
    // ESTADÍSTICAS PARA STREAMER (solo sus datos)
    
    // Total de eventos del streamer
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM events WHERE created_by = ?");
    $stmt->execute([$userId]);
    $stats['total_events'] = $stmt->fetch()['total'];
    
    // Eventos en vivo del streamer
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM events WHERE created_by = ? AND status = 'live'");
    $stmt->execute([$userId]);
    $stats['live_events'] = $stmt->fetch()['total'];
    
    // Eventos próximos del streamer
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM events WHERE created_by = ? AND status = 'scheduled'");
    $stmt->execute([$userId]);
    $stats['scheduled_events'] = $stmt->fetch()['total'];
    
    // Revenue del streamer por moneda
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(p.amount), 0) as total, p.currency 
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ? AND p.status = 'completed'
        GROUP BY p.currency
    ");
    $stmt->execute([$userId]);
    $revenue = $stmt->fetchAll();
    
    // Ventas totales del streamer
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ? AND p.status = 'completed'
    ");
    $stmt->execute([$userId]);
    $stats['total_sales'] = $stmt->fetch()['total'];
    
    // Espectadores activos en eventos del streamer
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM active_sessions a
        JOIN events e ON a.event_id = e.id
        WHERE e.created_by = ? AND a.last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmt->execute([$userId]);
    $stats['active_viewers'] = $stmt->fetch()['total'];
    
    // Total de espectadores únicos
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.user_id) as total
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ? AND p.status = 'completed'
    ");
    $stmt->execute([$userId]);
    $stats['total_viewers'] = $stmt->fetch()['total'];
    
    // Eventos del streamer
    $stmt = $db->prepare("SELECT * FROM events WHERE created_by = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $recent_events = $stmt->fetchAll();
    
    // Compras de los eventos del streamer
    $stmt = $db->prepare("
        SELECT p.*, u.full_name, u.email, e.title 
        FROM purchases p
        JOIN users u ON p.user_id = u.id
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ?
        ORDER BY p.purchased_at DESC
        LIMIT 15
    ");
    $stmt->execute([$userId]);
    $recent_purchases = $stmt->fetchAll();
    
    // No mostrar usuarios recientes para streamers
    $recent_users = [];
}

require_once 'header.php';
require_once 'styles.php';
?>

<?php if ($isStreamer): ?>
<!-- Banner de bienvenida para Streamer -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
    <h2 style="margin: 0 0 10px 0; font-size: 28px;">👋 ¡Bienvenido, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
    <p style="margin: 0 0 20px 0; font-size: 16px; opacity: 0.9;">Gestiona tus transmisiones y eventos en vivo</p>
    <a href="/admin/events.php?action=create" class="btn" style="background: white; color: #667eea; font-weight: bold; padding: 12px 30px;">
        🎬 Crear Nuevo Evento
    </a>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= $stats['total_events'] ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Eventos' : 'Total de Eventos' ?></div>
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
    
    <?php if ($isAdmin): ?>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $stats['total_users'] ?></div>
        <div class="stat-label">Usuarios Registrados</div>
    </div>
    <?php endif; ?>
    
    <?php if ($isStreamer): ?>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $stats['total_viewers'] ?></div>
        <div class="stat-label">Espectadores Únicos</div>
    </div>
    <?php endif; ?>
    
    <div class="stat-card">
        <div class="stat-icon">👁️</div>
        <div class="stat-value"><?= $stats['active_viewers'] ?></div>
        <div class="stat-label">Espectadores Activos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?= $stats['total_sales'] ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ventas' : 'Ventas Totales' ?></div>
    </div>
    
    <?php foreach ($revenue as $r): ?>
    <div class="stat-card">
        <div class="stat-icon">💵</div>
        <div class="stat-value"><?= $r['currency'] ?> <?= number_format((float)$r['total'], 2) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ganancias' : 'Revenue' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2><?= $isStreamer ? '🎬 Mis Eventos' : '📺 Eventos Recientes' ?></h2>
        <a href="/admin/events.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <?php if (!empty($recent_events)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <?php if ($isAdmin): ?>
                    <th>Creador</th>
                    <?php endif; ?>
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
                    <?php if ($isAdmin): ?>
                    <td><?= htmlspecialchars($event['creator_name'] ?? 'N/A') ?></td>
                    <?php endif; ?>
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
                        <?php if ($event['status'] === 'scheduled'): ?>
                        <a href="/admin/stream.php?event_id=<?= $event['id'] ?>" class="btn btn-success" style="background: #4CAF50;">Iniciar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <h3><?= $isStreamer ? 'No tienes eventos creados' : 'No hay eventos registrados' ?></h3>
        <p><?= $isStreamer ? 'Crea tu primer evento para comenzar a transmitir' : 'Crea tu primer evento para comenzar' ?></p>
        <?php if ($isStreamer): ?>
        <a href="/admin/events.php?action=create" class="btn btn-primary" style="margin-top: 15px;">Crear Mi Primer Evento</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2><?= $isStreamer ? '💰 Mis Ventas' : '💳 Últimas Compras' ?></h2>
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
                    <td><strong style="color: #4CAF50;"><?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?></strong></td>
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
        <h3><?= $isStreamer ? 'Aún no tienes ventas' : 'No hay compras registradas' ?></h3>
        <?php if ($isStreamer): ?>
        <p>Crea eventos y comienza a generar ingresos</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin && !empty($recent_users)): ?>
<div class="section">
    <div class="section-header">
        <h2>👥 Usuarios Recientes</h2>
        <a href="/admin/users.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
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
                        <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : ($user['role'] === 'streamer' ? 'badge-info' : 'badge-secondary') ?>">
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
</div>
<?php endif; ?>

<?php if ($isStreamer): ?>
<!-- Sección de ayuda para streamers -->
<div class="section" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border: 2px solid rgba(102, 126, 234, 0.3); border-radius: 12px; padding: 30px;">
    <h2 style="margin-bottom: 20px;">📚 Guía Rápida para Streamers</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">1️⃣ Crea un Evento</h3>
            <p style="color: #ccc; margin-bottom: 10px;">Define título, fecha, precio y detalles de tu transmisión</p>
            <a href="/admin/events.php?action=create" class="btn btn-primary">Crear Evento</a>
        </div>
        <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">2️⃣ Configura OBS</h3>
            <p style="color: #ccc; margin-bottom: 10px;">Obtén tu Stream Key y configura tu software de streaming</p>
            <a href="/admin/stream-settings.php" class="btn btn-primary">Ver Configuración</a>
        </div>
        <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">3️⃣ Inicia Stream</h3>
            <p style="color: #ccc; margin-bottom: 10px;">Comienza tu transmisión cuando el evento esté programado</p>
            <a href="/admin/events.php" class="btn btn-primary">Mis Eventos</a>
        </div>
        <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">4️⃣ Revisa Ganancias</h3>
            <p style="color: #ccc; margin-bottom: 10px;">Monitorea tus ventas y estadísticas en tiempo real</p>
            <a href="/admin/purchases.php" class="btn btn-primary">Ver Ventas</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>