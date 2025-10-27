<?php
// admin/events.php
// Gestión de eventos (CRUD completo + Control de transmisión)

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

// NUEVO: Cambiar estado de transmisión
if ($action === 'toggle_live' && isset($_GET['id'])) {
    try {
        $eventId = $_GET['id'];
        $event = $eventModel->findById($eventId);
        
        if (!$event) {
            throw new Exception("Evento no encontrado");
        }
        
        // Verificar permisos
        if ($isStreamer && $event['created_by'] != $userId) {
            throw new Exception("No tienes permiso para controlar este evento");
        }
        
        // Cambiar estado
        if ($event['status'] === 'scheduled') {
            // Activar transmisión
            $stmt = $db->prepare("UPDATE events SET status = 'live', actual_start = NOW() WHERE id = ?");
            $stmt->execute([$eventId]);
            $success = "✅ Transmisión ACTIVADA - El evento está EN VIVO";
        } elseif ($event['status'] === 'live') {
            // Desactivar transmisión
            $stmt = $db->prepare("UPDATE events SET status = 'ended', actual_end = NOW() WHERE id = ?");
            $stmt->execute([$eventId]);
            $success = "ℹ️ Transmisión FINALIZADA - El evento ha terminado";
        } else {
            throw new Exception("No se puede cambiar el estado desde: " . $event['status']);
        }
        
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
        $action = 'list';
    }
}

// NUEVO: Reactivar evento finalizado
if ($action === 'reactivate' && isset($_GET['id'])) {
    try {
        $eventId = $_GET['id'];
        $event = $eventModel->findById($eventId);
        
        if (!$event) {
            throw new Exception("Evento no encontrado");
        }
        
        // Verificar permisos
        if ($isStreamer && $event['created_by'] != $userId) {
            throw new Exception("No tienes permiso para reactivar este evento");
        }
        
        if ($event['status'] === 'ended') {
            $stmt = $db->prepare("UPDATE events SET status = 'scheduled', actual_start = NULL, actual_end = NULL WHERE id = ?");
            $stmt->execute([$eventId]);
            $success = "🔄 Evento reactivado - Ahora está PROGRAMADO";
        } else {
            throw new Exception("Solo se pueden reactivar eventos finalizados");
        }
        
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
        $action = 'list';
    }
}

// Procesar acciones de creación/edición
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

<style>
.live-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 10px;
}

.btn-live {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.btn-live:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.btn-live.active {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    animation: pulse 2s infinite;
}

.btn-end {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    color: white;
}

.btn-reactivate {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: blink 1.5s infinite;
}

.status-dot.live {
    background: #e74c3c;
}

.status-dot.scheduled {
    background: #27ae60;
    animation: none;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

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
                    <th>Control</th>
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
                        <span class="badge <?= $badgeClass ?> status-indicator">
                            <?php if ($evt['status'] === 'live'): ?>
                                <span class="status-dot live"></span>
                            <?php endif; ?>
                            <?= strtoupper($evt['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($evt['status'] === 'scheduled'): ?>
                            <a href="?action=toggle_live&id=<?= $evt['id'] ?>" 
                               class="btn-live"
                               onclick="return confirm('¿Activar transmisión EN VIVO?')">
                                ▶️ INICIAR
                            </a>
                        <?php elseif ($evt['status'] === 'live'): ?>
                            <a href="?action=toggle_live&id=<?= $evt['id'] ?>" 
                               class="btn-live btn-end"
                               onclick="return confirm('¿Finalizar transmisión?')">
                                ⏹️ FINALIZAR
                            </a>
                        <?php elseif ($evt['status'] === 'ended'): ?>
                            <a href="?action=reactivate&id=<?= $evt['id'] ?>" 
                               class="btn-live btn-reactivate"
                               onclick="return confirm('¿Reactivar este evento?')">
                                🔄 REACTIVAR
                            </a>
                        <?php else: ?>
                            <span style="color: #95a5a6;">—</span>
                        <?php endif; ?>
                    </td>
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
        
        <!-- NUEVO: Control de transmisión en el formulario de edición -->
        <?php if ($event['status'] === 'scheduled' || $event['status'] === 'live'): ?>
        <div class="live-controls" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <strong>Control de Transmisión:</strong>
            <?php if ($event['status'] === 'scheduled'): ?>
                <a href="?action=toggle_live&id=<?= $event['id'] ?>" 
                   class="btn-live"
                   onclick="return confirm('¿Activar transmisión EN VIVO?')">
                    ▶️ INICIAR TRANSMISIÓN
                </a>
            <?php elseif ($event['status'] === 'live'): ?>
                <span class="badge badge-live status-indicator" style="padding: 10px 15px; font-size: 14px;">
                    <span class="status-dot live"></span>
                    TRANSMITIENDO EN VIVO
                </span>
                <a href="?action=toggle_live&id=<?= $event['id'] ?>" 
                   class="btn-live btn-end"
                   onclick="return confirm('¿Finalizar transmisión?')">
                    ⏹️ FINALIZAR TRANSMISIÓN
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
            
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <p style="margin-top:10px; color:#666; font-size:13px;">
                <strong>📡 Configuración para OBS Studio:</strong><br>
            </p>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                <p style="margin: 5px 0; font-size: 13px;">
                    <strong>Servidor RTMP:</strong><br>
                    <code style="background: #2c3e50; color: #3498db; padding: 5px 10px; display: inline-block; border-radius: 4px;">
                        rtmp://streaming.cellcomweb.com.ar/live
                    </code>
                </p>
                
                <p style="margin: 15px 0 5px 0; font-size: 13px;">
                    <strong>Clave de transmisión:</strong><br>
                    <code style="background: #2c3e50; color: #e74c3c; padding: 5px 10px; display: inline-block; border-radius: 4px;">
                        <?= $event['stream_key'] ?>
                    </code>
                </p>
            </div>
            
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <p style="margin-top:10px; color:#666; font-size:13px;">
                <strong>🎬 URL completa (copiar en OBS):</strong><br>
                <code style="background: #2c3e50; color: #4CAF50; padding: 8px; display: block; margin-top: 5px; border-radius: 4px; word-break: break-all; font-size: 12px;">
                    rtmp://streaming.cellcomweb.com.ar/live/<?= $event['stream_key'] ?>
                </code>
            </p>
            
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <p style="margin-top:10px; color:#999; font-size:12px; font-style: italic;">
                <strong>💡 Para ver el stream en navegador:</strong><br>
                <code style="background: #f8f9fa; color: #7f8c8d; padding: 5px; display: block; margin-top: 5px; border-radius: 4px; word-break: break-all; font-size: 11px;">
                    http://streaming.cellcomweb.com.ar:8889/live/<?= $event['stream_key'] ?>/index.m3u8
                </code>
            </p>
            
            <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong style="color: #856404;">⚠️ Instrucciones OBS:</strong>
                <ol style="margin: 10px 0 0 20px; padding: 0; color: #856404; font-size: 13px;">
                    <li>Abrir OBS Studio</li>
                    <li>Ir a <strong>Configuración → Emisión</strong></li>
                    <li>Seleccionar <strong>Servicio: Personalizado</strong></li>
                    <li>Pegar el servidor RTMP arriba</li>
                    <li>Pegar la clave de transmisión</li>
                    <li>Clic en <strong>Aplicar</strong> y <strong>Aceptar</strong></li>
                    <li>Clic en <strong>Iniciar transmisión</strong></li>
                    <li>Luego hacer clic en <strong>▶️ INICIAR</strong> aquí para activar el evento</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'create' ? '✨ Crear Evento' : '💾 Guardar Cambios' ?>
            </button>
            <a href="?" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php require_once 'footer.php'; ?>