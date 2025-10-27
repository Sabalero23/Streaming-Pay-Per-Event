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

$page_title = "Monitor de Sesiones Activas";

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

<style>
.monitor-container {
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px;
    border-radius: 12px;
    color: white;
}

.stat-card h3 {
    font-size: 36px;
    margin: 0 0 10px 0;
}

.stat-card p {
    margin: 0;
    opacity: 0.9;
}

.sessions-table {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    overflow-x: auto;
}

.sessions-table h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    text-align: left;
    padding: 12px;
    background: #2a2a2a;
    color: #667eea;
    font-weight: 600;
}

table td {
    padding: 12px;
    border-top: 1px solid #333;
}

.status-active {
    color: #27ae60;
    font-weight: bold;
}

.status-stale {
    color: #f39c12;
}

.status-conflict {
    color: #e74c3c;
    font-weight: bold;
}

.ip-badge {
    background: #2a2a2a;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-family: monospace;
}

.time-badge {
    background: #667eea;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}

.user-agent {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: #999;
}

.action-btn {
    padding: 6px 12px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.action-btn:hover {
    background: #c0392b;
}

.refresh-btn {
    background: #27ae60;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin-bottom: 20px;
}

.refresh-btn:hover {
    background: #229954;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}
</style>

<div class="section">
    <div class="monitor-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>🔍 Monitor de Sesiones Activas</h1>
            <button onclick="location.reload()" class="refresh-btn">🔄 Actualizar</button>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $stats['unique_viewers'] ?></h3>
                <p>👤 Usuarios Únicos Activos</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?= $stats['total_sessions'] ?></h3>
                <p>📺 Sesiones Totales</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?= $stats['active_events'] ?></h3>
                <p>🎬 Eventos en Vivo</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <h3><?= count($recent_conflicts) ?></h3>
                <p>⚠️ Conflictos (24h)</p>
            </div>
        </div>

        <!-- Sesiones Activas -->
        <div class="sessions-table">
            <h2>📡 Sesiones Activas Ahora</h2>
            <?php if (empty($active_sessions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💤</div>
                    <p>No hay sesiones activas en este momento.</p>
                </div>
            <?php else: ?>
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
                                    <span style="color: #999;">● Desconectado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-btn" onclick="killSession(<?= $session['id'] ?>)">
                                    🚫 Expulsar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Conflictos Recientes -->
        <div class="sessions-table">
            <h2>⚠️ Conflictos Recientes (últimas 24h)</h2>
            <?php if (empty($recent_conflicts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <p>No hay conflictos registrados en las últimas 24 horas.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Evento</th>
                            <th>IP Anterior → Nueva</th>
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
                                →
                                <span class="ip-badge" style="background: #e74c3c;"><?= htmlspecialchars($conflict['new_ip_address']) ?></span>
                            </td>
                            <td><?= $conflict['minutes_ago'] ?> minutos</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Top Usuarios con Conflictos -->
        <?php if (!empty($top_conflicts)): ?>
        <div class="sessions-table">
            <h2>👥 Usuarios con Más Conflictos (24h)</h2>
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
                        <td><span class="status-conflict"><?= $user['conflict_count'] ?> conflictos</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/admin/dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
        </div>
    </div>
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