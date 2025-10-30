<?php
// admin/users.php
// Gestión completa de usuarios (CRUD + roles + contraseñas)

session_start();

$page_title = "Usuarios";
$page_icon = "fas fa-users";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if (empty($email) || empty($password) || empty($full_name)) {
        $error = "Email, contraseña y nombre completo son obligatorios";
    } else {
        // Verificar si el email ya existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "El email ya está registrado";
        } else {
            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, phone, role, status, email_verified, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', 1, NOW(), NOW())");
            
            if ($stmt->execute([$email, $password_hash, $full_name, $phone, $role])) {
                $success = "Usuario creado exitosamente";
                $action = 'list';
            } else {
                $error = "Error al crear el usuario";
            }
        }
    }
}

// Editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $user_id = $_POST['user_id'] ?? 0;
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($email) || empty($full_name)) {
        $error = "Email y nombre completo son obligatorios";
    } else {
        // Verificar si el email ya existe en otro usuario
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = "El email ya está registrado por otro usuario";
        } else {
            // Actualizar usuario
            if (!empty($new_password)) {
                // Actualizar con nueva contraseña
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET email=?, password_hash=?, full_name=?, phone=?, role=?, status=?, updated_at=NOW() WHERE id=?");
                $result = $stmt->execute([$email, $password_hash, $full_name, $phone, $role, $status, $user_id]);
            } else {
                // Actualizar sin cambiar contraseña
                $stmt = $db->prepare("UPDATE users SET email=?, full_name=?, phone=?, role=?, status=?, updated_at=NOW() WHERE id=?");
                $result = $stmt->execute([$email, $full_name, $phone, $role, $status, $user_id]);
            }
            
            if ($result) {
                $success = "Usuario actualizado exitosamente";
                $action = 'list';
            } else {
                $error = "Error al actualizar el usuario";
            }
        }
    }
}

// Eliminar usuario
if ($action === 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // No permitir eliminar al usuario actual
    if ($user_id == $_SESSION['user_id']) {
        $error = "No puedes eliminar tu propia cuenta";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success = "Usuario eliminado exitosamente";
        } else {
            $error = "Error al eliminar el usuario";
        }
    }
    $action = 'list';
}

// Cambio rápido de estado
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

// Cambio rápido de rol
if ($action === 'change_role' && isset($_GET['id']) && isset($_GET['role'])) {
    $userId = $_GET['id'];
    $newRole = $_GET['role'];
    
    if (in_array($newRole, ['user', 'admin', 'streamer'])) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $success = "Rol del usuario actualizado";
    }
    $action = 'list';
}

// Obtener usuario para editar
$user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "Usuario no encontrado";
        $action = 'list';
    }
}

// Filtrar usuarios
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT u.*, 
        COUNT(DISTINCT p.id) as total_purchases,
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_spent
        FROM users u
        LEFT JOIN purchases p ON u.id = p.user_id
        WHERE 1=1";

$params = [];

if ($filter === 'admin') {
    $sql .= " AND u.role = 'admin'";
} elseif ($filter === 'streamer') {
    $sql .= " AND u.role = 'streamer'";
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
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['admins'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'streamer'");
$stats['streamers'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['new_month'] = $stmt->fetch()['total'];

require_once 'header.php';
require_once 'styles.php';
?>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.role-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin { background: #f8d7da; color: #721c24; }
.role-streamer { background: #cfe2ff; color: #084298; }
.role-user { background: #d1ecf1; color: #0c5460; }

.quick-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.quick-actions select {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.password-hint {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Usuarios</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $stats['active'] ?></div>
        <div class="stat-label">Usuarios Activos</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
        <div class="stat-value"><?= $stats['admins'] ?></div>
        <div class="stat-label">Administradores</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-video"></i></div>
        <div class="stat-value"><?= $stats['streamers'] ?></div>
        <div class="stat-label">Streamers</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-value"><?= $stats['new_month'] ?></div>
        <div class="stat-label">Nuevos (30 días)</div>
    </div>
</div>

<div class="section-header">
    <h2>Gestión de Usuarios</h2>
    <a href="?action=create" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Crear Usuario
    </a>
</div>

<form method="GET" class="filter-bar">
    <strong><i class="fas fa-filter"></i> Filtrar:</strong>
    <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">Todos</a>
    <a href="?filter=admin" class="<?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
    <a href="?filter=streamer" class="<?= $filter === 'streamer' ? 'active' : '' ?>">Streamers</a>
    <a href="?filter=suspended" class="<?= $filter === 'suspended' ? 'active' : '' ?>">Suspendidos</a>
    <a href="?filter=banned" class="<?= $filter === 'banned' ? 'active' : '' ?>">Baneados</a>
    
    <div class="search-box">
        <input type="text" name="search" placeholder="Buscar por nombre o email..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Buscar
    </button>
</form>

<div class="section">
    <div class="table-responsive">
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
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="user-avatar"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                            <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $u['role'] ?>">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= strtoupper($u['status']) ?>
                        </span>
                    </td>
                    <td><?= $u['total_purchases'] ?></td>
                    <td>$<?= number_format((float)$u['total_spent'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="quick-actions">
                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-primary" style="padding:4px 10px; font-size:12px;">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger" style="padding:4px 10px; font-size:12px;" onclick="return confirm('¿Eliminar este usuario?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>

<div class="section-header">
    <h2>
        <?= $action === 'create' ? '<i class="fas fa-user-plus"></i> Crear Nuevo Usuario' : '<i class="fas fa-user-edit"></i> Editar Usuario' ?>
    </h2>
    <a href="?" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="section">
    <form method="POST" action="?action=<?= $action ?><?= $user ? '&id=' . $user['id'] : '' ?>">
        <?php if ($user): ?>
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+54 123 456 7890">
            </div>
            
            <div class="form-group">
                <label>Rol *</label>
                <select name="role" required>
                    <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>
                        👤 Usuario Normal
                    </option>
                    <option value="streamer" <?= ($user['role'] ?? '') === 'streamer' ? 'selected' : '' ?>>
                        🎬 Streamer (Crea y transmite eventos)
                    </option>
                    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                        👑 Administrador
                    </option>
                </select>
                <div class="password-hint">
                    <strong>User:</strong> Solo puede comprar eventos<br>
                    <strong>Streamer:</strong> Puede crear eventos y transmitir<br>
                    <strong>Admin:</strong> Acceso completo al panel
                </div>
            </div>
        </div>
        
        <?php if ($user): ?>
        <div class="form-row">
            <div class="form-group">
                <label>Estado *</label>
                <select name="status" required>
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                        ✅ Activo
                    </option>
                    <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>
                        ⏸️ Suspendido
                    </option>
                    <option value="banned" <?= ($user['status'] ?? '') === 'banned' ? 'selected' : '' ?>>
                        🚫 Baneado
                    </option>
                </select>
            </div>
        </div>
        <?php endif; ?>
        
        <hr style="margin: 30px 0; border: none; border-top: 2px solid #f0f0f0;">
        
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-key"></i> <?= $action === 'create' ? 'Contraseña' : 'Cambiar Contraseña' ?>
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label><?= $action === 'create' ? 'Contraseña *' : 'Nueva Contraseña' ?></label>
                <input type="password" name="<?= $action === 'create' ? 'password' : 'new_password' ?>" 
                       placeholder="<?= $action === 'create' ? 'Mínimo 6 caracteres' : 'Dejar vacío para no cambiar' ?>"
                       minlength="6" <?= $action === 'create' ? 'required' : '' ?>>
                <div class="password-hint">
                    <?= $action === 'create' ? 'Mínimo 6 caracteres' : 'Solo completar si deseas cambiar la contraseña' ?>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">
                <?= $action === 'create' ? '<i class="fas fa-check"></i> Crear Usuario' : '<i class="fas fa-save"></i> Guardar Cambios' ?>
            </button>
            <a href="?" class="btn">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php require_once 'footer.php'; ?>