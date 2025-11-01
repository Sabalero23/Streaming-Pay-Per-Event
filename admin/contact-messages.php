<?php
// admin/contact-messages.php
// Panel para ver y gestionar mensajes de contacto

session_start();
require_once __DIR__ . '/../config/database.php';

$page_title = 'Mensajes de Contacto';
$page_icon = '📬';
require_once 'header.php';

// Solo admin puede ver esto
if (!$isAdmin) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Manejar acciones (marcar como leído, respondido, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $messageId = (int)($_POST['message_id'] ?? 0);
    
    if ($messageId > 0) {
        switch ($_POST['action']) {
            case 'mark_read':
                $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
                $stmt->execute([$messageId]);
                break;
                
            case 'mark_replied':
                $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
                $stmt->execute([$messageId]);
                break;
                
            case 'mark_new':
                $stmt = $db->prepare("UPDATE contact_messages SET status = 'new' WHERE id = ?");
                $stmt->execute([$messageId]);
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$messageId]);
                break;
        }
        
        header('Location: /admin/contact-messages.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
        exit;
    }
}

// Verificar si la tabla existe
$tableExists = false;
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'contact_messages'");
    $tableExists = $checkTable->rowCount() > 0;
} catch (Exception $e) {
    $tableExists = false;
}

// Obtener estadísticas
$stats = ['new' => 0, 'read' => 0, 'replied' => 0, 'total' => 0];

if ($tableExists) {
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM contact_messages
        GROUP BY status
    ");
    
    while ($row = $stmt->fetch()) {
        $stats[$row['status']] = $row['count'];
        $stats['total'] += $row['count'];
    }
}

// Filtros
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construir query
$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Obtener mensajes
$messages = [];
$totalMessages = 0;

if ($tableExists) {
    // Contar total
    $stmt = $db->prepare("SELECT COUNT(*) FROM contact_messages $whereClause");
    $stmt->execute($params);
    $totalMessages = $stmt->fetchColumn();
    
    // Obtener mensajes
    $stmt = $db->prepare("
        SELECT * FROM contact_messages 
        $whereClause
        ORDER BY created_at DESC 
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
}

$totalPages = ceil($totalMessages / $perPage);
?>

<style>
.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.page-header h1 {
    margin-bottom: 10px;
    color: #2c3e50;
}

.page-header p {
    color: #7f8c8d;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.stat-card.active {
    border: 2px solid #667eea;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-card.new .stat-number { color: #e74c3c; }
.stat-card.read .stat-number { color: #3498db; }
.stat-card.replied .stat-number { color: #2ecc71; }
.stat-card.total .stat-number { color: #95a5a6; }

.stat-label {
    color: #7f8c8d;
    font-size: 14px;
    text-transform: uppercase;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.filters-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 5px;
    font-size: 14px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-small {
    padding: 5px 10px;
    font-size: 12px;
}

.messages-table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
}

.messages-table {
    width: 100%;
    border-collapse: collapse;
}

.messages-table thead {
    background: #f8f9fa;
}

.messages-table th {
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    color: #555;
    font-size: 14px;
    border-bottom: 2px solid #e0e0e0;
}

.messages-table td {
    padding: 15px 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.messages-table tbody tr {
    transition: background 0.3s;
}

.messages-table tbody tr:hover {
    background: #f8f9fa;
}

.messages-table tbody tr.unread {
    background: #fff9e6;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-new {
    background: #ffebee;
    color: #c62828;
}

.status-read {
    background: #e3f2fd;
    color: #1565c0;
}

.status-replied {
    background: #e8f5e9;
    color: #2e7d32;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.action-btn.view {
    background: #667eea;
    color: white;
}

.action-btn.view:hover {
    background: #5568d3;
}

.action-btn.reply {
    background: #2ecc71;
    color: white;
}

.action-btn.reply:hover {
    background: #27ae60;
}

.action-btn.delete {
    background: #e74c3c;
    color: white;
}

.action-btn.delete:hover {
    background: #c0392b;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
    padding: 20px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    text-decoration: none;
    color: #555;
    transition: all 0.3s;
}

.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination .active {
    background: #667eea;
    color: white;
    border-color: #667eea;
    font-weight: bold;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #95a5a6;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: #7f8c8d;
}

.no-table-warning {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.no-table-warning h3 {
    color: #856404;
    margin-bottom: 10px;
}

.no-table-warning p {
    color: #856404;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .messages-table {
        font-size: 12px;
    }
    
    .messages-table th,
    .messages-table td {
        padding: 10px 5px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
}

/* Modal para ver mensaje completo */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 10px;
    max-width: 700px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #95a5a6;
}

.modal-close:hover {
    color: #e74c3c;
}

.modal-body {
    padding: 20px;
}

.message-detail {
    margin-bottom: 20px;
}

.message-detail label {
    font-weight: 600;
    color: #555;
    display: block;
    margin-bottom: 5px;
}

.message-detail-value {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    color: #333;
}

.message-text {
    white-space: pre-wrap;
    line-height: 1.6;
}

.modal-footer {
    padding: 20px;
    border-top: 2px solid #f0f0f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.modal-footer {
    padding: 20px;
    border-top: 2px solid #f0f0f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

textarea.form-control {
    resize: vertical;
    min-height: 200px;
}

.reply-status-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #66bb6a;
}

.reply-status-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef5350;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<?php if (!$tableExists): ?>
<div class="no-table-warning">
    <h3>⚠️ Tabla no encontrada</h3>
    <p>La tabla <code>contact_messages</code> aún no existe en la base de datos.</p>
    <p>Se creará automáticamente cuando alguien envíe el primer mensaje de contacto.</p>
</div>
<?php else: ?>

<div class="page-header">
    <h1>📬 Mensajes de Contacto</h1>
    <p>Gestiona los mensajes recibidos desde el formulario de contacto</p>
</div>

<!-- Estadísticas -->
<div class="stats-grid">
    <a href="?status=new" class="stat-card new <?= $status === 'new' ? 'active' : '' ?>" style="text-decoration: none;">
        <div class="stat-number"><?= $stats['new'] ?></div>
        <div class="stat-label">Nuevos</div>
    </a>
    
    <a href="?status=read" class="stat-card read <?= $status === 'read' ? 'active' : '' ?>" style="text-decoration: none;">
        <div class="stat-number"><?= $stats['read'] ?></div>
        <div class="stat-label">Leídos</div>
    </a>
    
    <a href="?status=replied" class="stat-card replied <?= $status === 'replied' ? 'active' : '' ?>" style="text-decoration: none;">
        <div class="stat-number"><?= $stats['replied'] ?></div>
        <div class="stat-label">Respondidos</div>
    </a>
    
    <a href="?status=all" class="stat-card total <?= $status === 'all' ? 'active' : '' ?>" style="text-decoration: none;">
        <div class="stat-number"><?= $stats['total'] ?></div>
        <div class="stat-label">Total</div>
    </a>
</div>

<!-- Filtros -->
<div class="filters-section">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <label>Estado</label>
            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Nuevos</option>
                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Leídos</option>
                <option value="replied" <?= $status === 'replied' ? 'selected' : '' ?>>Respondidos</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Buscar</label>
            <input type="text" name="search" placeholder="Nombre, email, asunto..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">🔍 Buscar</button>
        <a href="?" class="btn btn-secondary">🔄 Limpiar</a>
    </form>
</div>

<!-- Tabla de mensajes -->
<?php if (empty($messages)): ?>
<div class="messages-table-container">
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>No hay mensajes</h3>
        <p><?= $search ? 'No se encontraron mensajes con esos criterios' : 'Aún no has recibido mensajes de contacto' ?></p>
    </div>
</div>
<?php else: ?>
<div class="messages-table-container">
    <table class="messages-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Asunto</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr class="<?= $msg['status'] === 'new' ? 'unread' : '' ?>">
                <td><strong>#<?= $msg['id'] ?></strong></td>
                <td><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                <td><?= htmlspecialchars($msg['name']) ?></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" 
                       style="color: #667eea; text-decoration: none;">
                        <?= htmlspecialchars($msg['email']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($msg['subject']) ?></td>
                <td>
                    <span class="status-badge status-<?= $msg['status'] ?>">
                        <?= $msg['status'] === 'new' ? 'Nuevo' : ($msg['status'] === 'read' ? 'Leído' : 'Respondido') ?>
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="viewMessage(<?= $msg['id'] ?>)" class="action-btn view" title="Ver mensaje completo">
                            👁️ Ver
                        </button>
                        <button onclick="openReplyModal(<?= $msg['id'] ?>, '<?= htmlspecialchars($msg['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($msg['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($msg['subject'], ENT_QUOTES) ?>')" 
                                class="action-btn reply" 
                                title="Responder por email">
                            ✉️ Responder
                        </button>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('¿Eliminar este mensaje?')">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="action-btn delete" title="Eliminar mensaje">
                                🗑️
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">← Anterior</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Siguiente →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>

<!-- Modal para responder mensaje -->
<div id="replyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>✉️ Responder Mensaje</h2>
            <button class="modal-close" onclick="closeReplyModal()">×</button>
        </div>
        <form id="replyForm" onsubmit="sendReply(event)">
            <div class="modal-body">
                <div class="message-detail">
                    <label>Para:</label>
                    <div class="message-detail-value" id="replyTo"></div>
                </div>
                
                <div class="message-detail">
                    <label>Asunto:</label>
                    <input type="text" id="replySubject" class="form-control" required readonly>
                </div>
                
                <div class="message-detail">
                    <label>Tu Respuesta: *</label>
                    <textarea id="replyMessage" 
                              class="form-control" 
                              rows="10" 
                              placeholder="Escribe tu respuesta aquí..."
                              required></textarea>
                    <small style="color: #999; display: block; margin-top: 5px;">
                        Escribe una respuesta clara y profesional
                    </small>
                </div>
                
                <input type="hidden" id="replyMessageId" value="">
                <input type="hidden" id="replyEmail" value="">
                <input type="hidden" id="replyUserName" value="">
                
                <div id="replyStatus" style="display: none; padding: 10px; border-radius: 5px; margin-top: 15px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeReplyModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="sendReplyBtn">
                    📤 Enviar Respuesta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para ver mensaje completo -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📧 Detalle del Mensaje</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="modalBody">
            <p>Cargando...</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="btn btn-secondary">Cerrar</button>
        </div>
    </div>
</div>

<script>
function viewMessage(id) {
    const modal = document.getElementById('messageModal');
    const modalBody = document.getElementById('modalBody');
    
    // Buscar el mensaje en los datos
    fetch(`/admin/ajax/get-message.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const msg = data.message;
                modalBody.innerHTML = `
                    <div class="message-detail">
                        <label>ID del Mensaje</label>
                        <div class="message-detail-value">#${msg.id}</div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Fecha</label>
                        <div class="message-detail-value">${msg.created_at}</div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Nombre</label>
                        <div class="message-detail-value">${msg.name}</div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Email</label>
                        <div class="message-detail-value">
                            <a href="mailto:${msg.email}" style="color: #667eea;">${msg.email}</a>
                        </div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Asunto</label>
                        <div class="message-detail-value">${msg.subject}</div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Mensaje</label>
                        <div class="message-detail-value message-text">${msg.message}</div>
                    </div>
                    
                    <div class="message-detail">
                        <label>Estado</label>
                        <div class="message-detail-value">
                            <select id="statusSelect" onchange="updateStatus(${msg.id}, this.value)">
                                <option value="new" ${msg.status === 'new' ? 'selected' : ''}>Nuevo</option>
                                <option value="read" ${msg.status === 'read' ? 'selected' : ''}>Leído</option>
                                <option value="replied" ${msg.status === 'replied' ? 'selected' : ''}>Respondido</option>
                            </select>
                        </div>
                    </div>
                    
                    ${msg.ip_address ? `
                    <div class="message-detail">
                        <label>IP</label>
                        <div class="message-detail-value">${msg.ip_address}</div>
                    </div>
                    ` : ''}
                `;
                
                // Marcar como leído si es nuevo
                if (msg.status === 'new') {
                    updateStatus(msg.id, 'read', false);
                }
            } else {
                modalBody.innerHTML = '<p>Error al cargar el mensaje</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<p>Error al cargar el mensaje</p>';
        });
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('messageModal').classList.remove('active');
}

function updateStatus(id, status, reload = true) {
    const formData = new FormData();
    formData.append('message_id', id);
    formData.append('action', 'mark_' + status);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(() => {
        if (reload) {
            location.reload();
        }
    });
}

// ✅ NUEVAS FUNCIONES PARA EL MODAL DE RESPUESTA
function openReplyModal(messageId, email, userName, subject) {
    const modal = document.getElementById('replyModal');
    
    // Llenar datos del formulario
    document.getElementById('replyMessageId').value = messageId;
    document.getElementById('replyEmail').value = email;
    document.getElementById('replyUserName').value = userName;
    document.getElementById('replyTo').textContent = `${userName} <${email}>`;
    document.getElementById('replySubject').value = `Re: ${subject}`;
    document.getElementById('replyMessage').value = '';
    
    // Ocultar mensaje de estado
    document.getElementById('replyStatus').style.display = 'none';
    
    // Habilitar botón de envío
    document.getElementById('sendReplyBtn').disabled = false;
    
    modal.classList.add('active');
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.remove('active');
}

function sendReply(event) {
    event.preventDefault();
    
    const messageId = document.getElementById('replyMessageId').value;
    const email = document.getElementById('replyEmail').value;
    const userName = document.getElementById('replyUserName').value;
    const subject = document.getElementById('replySubject').value;
    const message = document.getElementById('replyMessage').value;
    const statusDiv = document.getElementById('replyStatus');
    const sendBtn = document.getElementById('sendReplyBtn');
    
    // Deshabilitar botón
    sendBtn.disabled = true;
    sendBtn.textContent = '⏳ Enviando...';
    
    // Mostrar mensaje de carga
    statusDiv.style.display = 'block';
    statusDiv.className = '';
    statusDiv.style.background = '#e3f2fd';
    statusDiv.style.color = '#1565c0';
    statusDiv.innerHTML = '📤 Enviando respuesta...';
    
    // Enviar respuesta
    const formData = new FormData();
    formData.append('message_id', messageId);
    formData.append('email', email);
    formData.append('user_name', userName);
    formData.append('subject', subject);
    formData.append('message', message);
    
    fetch('/admin/ajax/send-reply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            statusDiv.className = 'reply-status-success';
            statusDiv.innerHTML = '✅ ' + data.message;
            
            // Cerrar modal después de 2 segundos y recargar
            setTimeout(() => {
                closeReplyModal();
                location.reload();
            }, 2000);
        } else {
            // Mostrar error
            statusDiv.className = 'reply-status-error';
            statusDiv.innerHTML = '❌ ' + (data.error || 'Error al enviar la respuesta');
            
            // Rehabilitar botón
            sendBtn.disabled = false;
            sendBtn.textContent = '📤 Enviar Respuesta';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusDiv.className = 'reply-status-error';
        statusDiv.innerHTML = '❌ Error de conexión. Por favor intenta de nuevo.';
        
        // Rehabilitar botón
        sendBtn.disabled = false;
        sendBtn.textContent = '📤 Enviar Respuesta';
    });
}

// Cerrar modales al hacer clic fuera
document.getElementById('messageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReplyModal();
    }
});

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeReplyModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>