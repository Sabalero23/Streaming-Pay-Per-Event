<?php
// admin/sessions_monitor.php
// Panel para monitorear sesiones activas y conflictos

session_start();

// CAMBIO: Permitir admin Y streamer
$allowedRoles = ['admin', 'streamer', 'moderator'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$page_title = "Monitor de Sesiones";
$page_icon = "📡";

// Obtener sesiones activas
$stmt = $db->query("
    SELECT 
        s.id,
        s.user_id,
        u.full_name,
        u.email,
        s.event_id,
        e.title as event_title,
        s.session_token,
        s.ip_address,
        s.user_agent,
        s.last_heartbeat,
        TIMESTAMPDIFF(SECOND, s.last_heartbeat, NOW()) as seconds_ago
    FROM active_sessions s
    JOIN users u ON s.user_id = u.id
    JOIN events e ON s.event_id = e.id
    WHERE s.last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ORDER BY s.last_heartbeat DESC
");
$active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener conflictos recientes (últimas 24 horas)
$stmt = $db->query("
    SELECT 
        c.id,
        c.user_id,
        u.full_name,
        u.email,
        c.event_id,
        e.title as event_title,
        c.old_ip_address,
        c.new_ip_address,
        c.conflict_time,
        TIMESTAMPDIFF(MINUTE, c.conflict_time, NOW()) as minutes_ago
    FROM session_conflicts c
    JOIN users u ON c.user_id = u.id
    JOIN events e ON c.event_id = e.id
    WHERE c.conflict_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY c.conflict_time DESC
    LIMIT 50
");
$recent_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT user_id) as unique_viewers,
        COUNT(*) as total_sessions,
        COUNT(DISTINCT event_id) as active_events
    FROM active_sessions
    WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Conflictos por usuario (últimas 24h)
$stmt = $db->query("
    SELECT 
        u.full_name,
        u.email,
        COUNT(*) as conflict_count
    FROM session_conflicts c
    JOIN users u ON c.user_id = u.id
    WHERE c.conflict_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY c.user_id
    ORDER BY conflict_count DESC
    LIMIT 10
");
$top_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'styles.php';
?>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.refresh-btn {
    background: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s;
}

.refresh-btn:hover {
    background: #45a049;
}

.status-active {
    color: #4CAF50;
    font-weight: bold;
}

.status-stale {
    color: #ff9800;
}

.status-inactive {
    color: #999;
}

.ip-badge {
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-family: 'Courier New', monospace;
    color: #333;
    display: inline-block;
}

.ip-badge-conflict {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.time-badge {
    background: #667eea;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    display: inline-block;
}

.user-agent {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: #666;
}

.action-btn {
    padding: 6px 14px;
    background: #f44336;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #da190b;
}

.conflict-arrow {
    color: #999;
    margin: 0 8px;
}

@media (max-width: 768px) {
    .user-agent {
        max-width: 150px;
    }
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
    <div>
        <button onclick="location.reload()" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
</div>

<!-- Estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $stats['unique_viewers'] ?></div>
        <div class="stat-label">Usuarios Únicos Activos</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-tv"></i></div>
        <div class="stat-value"><?= $stats['total_sessions'] ?></div>
        <div class="stat-label">Sesiones Totales</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-film"></i></div>
        <div class="stat-value"><?= $stats['active_events'] ?></div>
        <div class="stat-label">Eventos en Vivo</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value"><?= count($recent_conflicts) ?></div>
        <div class="stat-label">Conflictos (24h)</div>
    </div>
</div>

<!-- Sesiones Activas -->
<div class="section">
    <h2><i class="fas fa-broadcast-tower"></i> Sesiones Activas Ahora</h2>
    <?php if (empty($active_sessions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-bed"></i></div>
            <h3>No hay sesiones activas</h3>
            <p>No hay usuarios conectados en este momento.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>IP</th>
                        <th>Dispositivo</th>
                        <th>Último Heartbeat</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_sessions as $session): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($session['full_name']) ?></strong><br>
                            <small style="color: #999;"><?= htmlspecialchars($session['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($session['event_title']) ?></td>
                        <td><span class="ip-badge"><?= htmlspecialchars($session['ip_address']) ?></span></td>
                        <td>
                            <div class="user-agent" title="<?= htmlspecialchars($session['user_agent']) ?>">
                                <?= htmlspecialchars(substr($session['user_agent'], 0, 50)) ?>...
                            </div>
                        </td>
                        <td>
                            <?= date('H:i:s', strtotime($session['last_heartbeat'])) ?><br>
                            <span class="time-badge">Hace <?= $session['seconds_ago'] ?>s</span>
                        </td>
                        <td>
                            <?php if ($session['seconds_ago'] < 30): ?>
                                <span class="status-active">● Activo</span>
                            <?php elseif ($session['seconds_ago'] < 120): ?>
                                <span class="status-stale">● Inactivo</span>
                            <?php else: ?>
                                <span class="status-inactive">● Desconectado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="action-btn" onclick="killSession(<?= $session['id'] ?>)">
                                <i class="fas fa-user-times"></i> Expulsar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Conflictos Recientes -->
<div class="section">
    <h2><i class="fas fa-exclamation-circle"></i> Conflictos Recientes (últimas 24h)</h2>
    <?php if (empty($recent_conflicts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Sin conflictos</h3>
            <p>No hay conflictos registrados en las últimas 24 horas.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Cambio de IP</th>
                        <th>Hace</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_conflicts as $conflict): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($conflict['full_name']) ?></strong><br>
                            <small style="color: #999;"><?= htmlspecialchars($conflict['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($conflict['event_title']) ?></td>
                        <td>
                            <span class="ip-badge"><?= htmlspecialchars($conflict['old_ip_address']) ?></span>
                            <span class="conflict-arrow">→</span>
                            <span class="ip-badge ip-badge-conflict"><?= htmlspecialchars($conflict['new_ip_address']) ?></span>
                        </td>
                        <td><?= $conflict['minutes_ago'] ?> minutos</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Top Usuarios con Conflictos -->
<?php if (!empty($top_conflicts)): ?>
<div class="section">
    <h2><i class="fas fa-user-shield"></i> Usuarios con Más Conflictos (24h)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Conflictos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_conflicts as $user): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span class="badge badge-danger"><?= $user['conflict_count'] ?> conflictos</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 30px;">
    <a href="/admin/dashboard.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>
</div>

<script>
function killSession(sessionId) {
    if (!confirm('¿Estás seguro de que quieres expulsar esta sesión?')) {
        return;
    }
    
    fetch('/api/admin/kill_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ session_id: sessionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sesión expulsada exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al expulsar sesión');
    });
}

// Auto-refresh cada 30 segundos
setTimeout(() => {
    location.reload();
}, 30000);
</script>

<?php require_once 'footer.php'; ?>