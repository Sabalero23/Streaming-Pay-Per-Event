<?php
// admin/events.php
// Gestión de eventos (CRUD completo)

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
require_once __DIR__ . '/../src/Models/Event.php';

$db = Database::getInstance()->getConnection();
$eventModel = new Event();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'currency' => $_POST['currency'] ?? 'ARS',
            'scheduled_start' => $_POST['scheduled_start'] ?? '',
            'enable_recording' => isset($_POST['enable_recording']) ? 1 : 0,
            'enable_chat' => isset($_POST['enable_chat']) ? 1 : 0,
        ];
        
        try {
            if ($action === 'create') {
                // Crear evento (siempre asignar al usuario actual)
                $data['created_by'] = $userId;
                $result = $eventModel->createEvent($data);
                
                $success = "Evento creado exitosamente. Stream Key: {$result['stream_key']}";
                $action = 'edit';
                $_GET['id'] = $result['id'];
            } else {
                $eventId = $_POST['event_id'];
                
                // Verificar permisos: admin puede editar todo, streamer solo sus eventos
                if ($isStreamer) {
                    $event = $eventModel->findById($eventId);
                    if ($event['created_by'] != $userId) {
                        throw new Exception("No tienes permiso para editar este evento");
                    }
                }
                
                $eventModel->update($eventId, $data);
                $success = "Evento actualizado exitosamente";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Eliminar evento
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $eventId = $_GET['id'];
        
        // Verificar permisos
        if ($isStreamer) {
            $event = $eventModel->findById($eventId);
            if ($event['created_by'] != $userId) {
                throw new Exception("No tienes permiso para eliminar este evento");
            }
        }
        
        $eventModel->delete($eventId);
        $success = "Evento eliminado exitosamente";
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener evento para editar
$event = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $event = $eventModel->findById($_GET['id']);
    
    if (!$event) {
        $error = "Evento no encontrado";
        $action = 'list';
    } elseif ($isStreamer && $event['created_by'] != $userId) {
        $error = "No tienes permiso para editar este evento";
        $action = 'list';
    }
}

// Listar eventos
$filter = $_GET['status'] ?? '';

if ($isAdmin) {
    // Admin ve todos los eventos
    if ($filter) {
        $stmt = $db->prepare("SELECT e.*, u.full_name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.status = ? ORDER BY e.scheduled_start DESC");
        $stmt->execute([$filter]);
    } else {
        $stmt = $db->query("SELECT e.*, u.full_name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.scheduled_start DESC LIMIT 100");
    }
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Streamer solo ve sus eventos
    if ($filter) {
        $stmt = $db->prepare("SELECT e.*, u.full_name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.created_by = ? AND e.status = ? ORDER BY e.scheduled_start DESC");
        $stmt->execute([$userId, $filter]);
    } else {
        $stmt = $db->prepare("SELECT e.*, u.full_name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.created_by = ? ORDER BY e.scheduled_start DESC LIMIT 100");
        $stmt->execute([$userId]);
    }
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = $isStreamer ? "Mis Eventos" : "Gestión de Eventos";
$page_icon = "🎬";

require_once 'header.php';
require_once 'styles.php';
?>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<div class="section-header">
    <h2><?= $isStreamer ? '🎬 Mis Eventos' : '🎬 Gestión de Eventos' ?></h2>
    <a href="?action=create" class="btn btn-primary">➕ Crear Evento</a>
</div>

<div class="filter-bar">
    <strong>Filtrar:</strong>
    <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">Todos</a>
    <a href="?status=scheduled" class="<?= $filter === 'scheduled' ? 'active' : '' ?>">Próximos</a>
    <a href="?status=live" class="<?= $filter === 'live' ? 'active' : '' ?>">En Vivo</a>
    <a href="?status=ended" class="<?= $filter === 'ended' ? 'active' : '' ?>">Finalizados</a>
    <a href="?status=cancelled" class="<?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelados</a>
</div>

<div class="section">
    <?php if (!empty($events)): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <?php if ($isAdmin): ?>
                    <th>Creador</th>
                    <?php endif; ?>
                    <th>Categoría</th>
                    <th>Fecha</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Stream Key</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $evt): ?>
                <tr>
                    <td><strong>#<?= $evt['id'] ?></strong></td>
                    <td><?= htmlspecialchars($evt['title']) ?></td>
                    <?php if ($isAdmin): ?>
                    <td><?= htmlspecialchars($evt['creator_name'] ?? 'N/A') ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($evt['category']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($evt['scheduled_start'])) ?></td>
                    <td><?= $evt['currency'] ?> <?= number_format((float)$evt['price'], 2) ?></td>
                    <td>
                        <?php
                        $badges = [
                            'live' => 'badge-live',
                            'scheduled' => 'badge-success',
                            'ended' => 'badge-warning',
                            'cancelled' => 'badge-danger'
                        ];
                        $badgeClass = $badges[$evt['status']] ?? 'badge-info';
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= strtoupper($evt['status']) ?>
                        </span>
                    </td>
                    <td><code style="font-size:11px;"><?= substr($evt['stream_key'], 0, 15) ?>...</code></td>
                    <td>
                        <a href="?action=edit&id=<?= $evt['id'] ?>" class="btn btn-primary">✏️</a>
                        <a href="?action=delete&id=<?= $evt['id'] ?>" class="btn btn-danger" 
                           onclick="return confirm('¿Eliminar este evento?')">🗑️</a>
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
        <p><?= $isStreamer ? 'Crea tu primer evento para comenzar a transmitir' : 'Crea el primer evento' ?></p>
        <a href="?action=create" class="btn btn-primary" style="margin-top: 15px;">Crear Primer Evento</a>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>

<div class="section-header">
    <h2><?= $action === 'create' ? '✨ Crear Nuevo Evento' : '✏️ Editar Evento' ?></h2>
    <a href="?" class="btn btn-primary">← Volver</a>
</div>

<div class="section">
    <form method="POST" action="?action=<?= $action ?><?= $event ? '&id=' . $event['id'] : '' ?>">
        <?php if ($event): ?>
        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Categoría *</label>
                <select name="category" required>
                    <option value="">Seleccionar...</option>
                    <option value="Fútbol" <?= ($event['category'] ?? '') === 'Fútbol' ? 'selected' : '' ?>>Fútbol</option>
                    <option value="Baloncesto" <?= ($event['category'] ?? '') === 'Baloncesto' ? 'selected' : '' ?>>Baloncesto</option>
                    <option value="Tenis" <?= ($event['category'] ?? '') === 'Tenis' ? 'selected' : '' ?>>Tenis</option>
                    <option value="MMA" <?= ($event['category'] ?? '') === 'MMA' ? 'selected' : '' ?>>MMA</option>
                    <option value="Boxeo" <?= ($event['category'] ?? '') === 'Boxeo' ? 'selected' : '' ?>>Boxeo</option>
                    <option value="Concierto" <?= ($event['category'] ?? '') === 'Concierto' ? 'selected' : '' ?>>Concierto</option>
                    <option value="Conferencia" <?= ($event['category'] ?? '') === 'Conferencia' ? 'selected' : '' ?>>Conferencia</option>
                    <option value="Otro" <?= ($event['category'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
        </div>
        
        <div class="form-group full-width">
            <label>Descripción</label>
            <textarea name="description"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Fecha y Hora de Inicio *</label>
                <input type="datetime-local" name="scheduled_start" 
                       value="<?= $event ? date('Y-m-d\TH:i', strtotime($event['scheduled_start'])) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Precio *</label>
                <input type="number" name="price" step="0.01" value="<?= $event['price'] ?? '0' ?>" required>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Moneda</label>
                <select name="currency">
                    <option value="ARS" <?= ($event['currency'] ?? 'ARS') === 'ARS' ? 'selected' : '' ?>>ARS - Peso Argentino</option>
                    <option value="USD" <?= ($event['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - Dólar</option>
                    <option value="EUR" <?= ($event['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                    <option value="MXN" <?= ($event['currency'] ?? '') === 'MXN' ? 'selected' : '' ?>>MXN - Peso Mexicano</option>
                </select>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="enable_recording" id="recording" 
                           <?= ($event['enable_recording'] ?? 1) ? 'checked' : '' ?>>
                    <label for="recording">Habilitar grabación (VOD)</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="enable_chat" id="chat" 
                           <?= ($event['enable_chat'] ?? 1) ? 'checked' : '' ?>>
                    <label for="chat">Habilitar chat en vivo</label>
                </div>
            </div>
        </div>
        
        <?php if ($event): ?>
        <div class="stream-key-box">
            <strong>🔑 Stream Key (para OBS):</strong><br>
            <code><?= $event['stream_key'] ?></code>
            <p style="margin-top:10px; color:#666; font-size:13px;">
                <strong>Configuración OBS:</strong><br>
                • Servidor: <code><?= getenv('RTMP_HOST') ?: 'rtmp://streaming.cellcomweb.com.ar/live' ?></code><br>
                • Clave de transmisión: <code><?= $event['stream_key'] ?></code>
            </p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
            <p style="margin-top:10px; color:#666; font-size:13px;">
                <strong>📺 URL completa para OBS:</strong><br>
                <code style="background: #2c3e50; color: #4CAF50; padding: 8px; display: block; margin-top: 5px; border-radius: 4px; word-break: break-all;">
                    <?= getenv('RTMP_HOST') ?: 'rtmp://streaming.cellcomweb.com.ar/live' ?>/<?= $event['stream_key'] ?>
                </code>
            </p>
        </div>
        <?php endif; ?>
        
        <div style="margin-top:30px;">
            <button type="submit" class="btn btn-success">
                <?= $action === 'create' ? '✅ Crear Evento' : '💾 Guardar Cambios' ?>
            </button>
            <a href="?" class="btn">Cancelar</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php require_once 'footer.php'; ?>