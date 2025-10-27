<?php
// admin/events.php
// Gestión de eventos (CRUD completo)

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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'currency' => $_POST['currency'] ?? 'USD',
            'scheduled_start' => $_POST['scheduled_start'] ?? '',
            'enable_recording' => isset($_POST['enable_recording']) ? 1 : 0,
            'enable_chat' => isset($_POST['enable_chat']) ? 1 : 0,
        ];
        
        try {
            if ($action === 'create') {
                // Generar stream key único
                $streamKey = 'stream_' . bin2hex(random_bytes(16));
                
                $stmt = $db->prepare("INSERT INTO events (title, description, category, price, currency, stream_key, scheduled_start, enable_recording, enable_chat, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, NOW())");
                $stmt->execute([
                    $data['title'],
                    $data['description'],
                    $data['category'],
                    $data['price'],
                    $data['currency'],
                    $streamKey,
                    $data['scheduled_start'],
                    $data['enable_recording'],
                    $data['enable_chat'],
                    $_SESSION['user_id']
                ]);
                
                $eventId = $db->lastInsertId();
                $success = "Evento creado exitosamente. Stream Key: {$streamKey}";
                $action = 'edit';
                $_GET['id'] = $eventId;
            } else {
                $eventId = $_POST['event_id'];
                $stmt = $db->prepare("UPDATE events SET title=?, description=?, category=?, price=?, currency=?, scheduled_start=?, enable_recording=?, enable_chat=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([
                    $data['title'],
                    $data['description'],
                    $data['category'],
                    $data['price'],
                    $data['currency'],
                    $data['scheduled_start'],
                    $data['enable_recording'],
                    $data['enable_chat'],
                    $eventId
                ]);
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
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $success = "Evento eliminado exitosamente";
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener evento para editar
$event = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        $error = "Evento no encontrado";
        $action = 'list';
    }
}

// Listar eventos
$filter = $_GET['status'] ?? '';
if ($filter) {
    $stmt = $db->prepare("SELECT * FROM events WHERE status = ? ORDER BY scheduled_start DESC");
    $stmt->execute([$filter]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $events = $db->query("SELECT * FROM events ORDER BY scheduled_start DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-nav {
            display: flex;
            gap: 5px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section h2 {
            color: #2c3e50;
            font-size: 22px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 14px;
            color: #495057;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-live {
            background: #ff0000;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
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
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .stream-key-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .stream-key-box code {
            background: #2c3e50;
            color: #4CAF50;
            padding: 5px 10px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🎬 Gestión de Eventos</h1>
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
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
        
        <div class="section-header">
            <h2>Gestión de Eventos</h2>
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
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
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
            <?php else: ?>
            <div class="empty-state">
                <p>No hay eventos registrados</p>
                <a href="?action=create" class="btn btn-primary">Crear Primer Evento</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        
        <div class="section-header">
            <h2><?= $action === 'create' ? 'Crear Nuevo Evento' : 'Editar Evento' ?></h2>
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
                            <option value="USD" <?= ($event['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD - Dólar</option>
                            <option value="ARS" <?= ($event['currency'] ?? '') === 'ARS' ? 'selected' : '' ?>>ARS - Peso Argentino</option>
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
                    <strong>Stream Key (para OBS):</strong><br>
                    <code><?= $event['stream_key'] ?></code>
                    <p style="margin-top:10px; color:#666; font-size:13px;">
                        Usar en OBS: <code>rtmp://tu-servidor/live/<?= $event['stream_key'] ?></code>
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
    </div>
</body>
</html>
