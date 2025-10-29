<?php
// public/profile.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

$page_title = "Mi Cuenta";

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// Obtener datos del usuario
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (!empty($full_name)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$full_name, $phone, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $full_name;
            $message = "Perfil actualizado exitosamente";
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
        } else {
            $error = "Error al actualizar el perfil";
        }
    }
}

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
                $message = "Contraseña cambiada exitosamente";
            } else {
                $error = "Error al cambiar la contraseña";
            }
        } else {
            $error = "Las contraseñas no coinciden o son muy cortas (mínimo 6 caracteres)";
        }
    } else {
        $error = "Contraseña actual incorrecta";
    }
}

// Obtener compras del usuario
$stmt = $db->prepare("
    SELECT p.*, e.title, e.scheduled_start, e.status as event_status
    FROM purchases p
    JOIN events e ON p.event_id = e.id
    WHERE p.user_id = ?
    ORDER BY p.purchased_at DESC
    LIMIT 20
");
$stmt->execute([$_SESSION['user_id']]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
require_once 'styles.php';
?>

<style>
/* Contenedor principal más compacto */
.profile-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Header del perfil - más compacto */
.profile-header {
    text-align: center;
    padding: 30px 20px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: white;
    margin: 0 auto 15px;
    border: 3px solid rgba(255,255,255,0.3);
}

.profile-header h1 {
    font-size: 24px;
    margin-bottom: 5px;
    color: white;
}

.profile-header p {
    color: rgba(255,255,255,0.9);
    font-size: 14px;
    margin: 3px 0;
}

/* Tabs más compactos */
.profile-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #333;
    overflow-x: auto;
    padding-bottom: 0;
}

.tab {
    padding: 12px 20px;
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    white-space: nowrap;
    flex-shrink: 0;
}

.tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Cards más compactas */
.card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
}

.card h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Form groups más compactos */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #ccc;
    font-size: 14px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    background: #0f0f0f;
    border: 1px solid #333;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
}

.form-group input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

/* Purchase items más compactos */
.purchase-item {
    background: #0f0f0f;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    border: 1px solid #333;
    transition: all 0.3s;
}

.purchase-item:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.purchase-item h3 {
    font-size: 16px;
    margin-bottom: 5px;
    color: white;
}

.purchase-item .purchase-date {
    color: #999;
    font-size: 13px;
}

.purchase-item .purchase-price {
    font-size: 18px;
    font-weight: bold;
    color: #4CAF50;
    margin-bottom: 8px;
}

.purchase-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-info {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.badge-warning {
    background: rgba(243, 156, 18, 0.2);
    color: #f39c12;
}

.badge-success {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Alerts más compactas */
.alert {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.alert-error {
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 14px;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header {
        padding: 25px 15px 15px;
    }
    
    .profile-avatar {
        width: 70px;
        height: 70px;
        font-size: 28px;
    }
    
    .profile-header h1 {
        font-size: 20px;
    }
    
    .card {
        padding: 20px 15px;
    }
    
    .purchase-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
    }
    
    .purchase-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .profile-tabs {
        gap: 0;
    }
    
    .tab {
        padding: 12px 15px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .profile-header h1 {
        font-size: 18px;
    }
    
    .tab {
        padding: 10px 12px;
        font-size: 12px;
    }
}
</style>

<div class="section">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <h1><?= htmlspecialchars($user['full_name']) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <p style="margin-top: 8px;">
                <span class="badge badge-info"><?= strtoupper($user['role']) ?></span>
            </p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="profile-tabs">
            <button class="tab active" onclick="showTab('info')">📋 Información</button>
            <button class="tab" onclick="showTab('purchases')">🎫 Compras</button>
            <button class="tab" onclick="showTab('security')">🔒 Seguridad</button>
        </div>

        <!-- Tab: Mi Información -->
        <div id="info" class="tab-content active">
            <div class="card">
                <h2>📋 Información Personal</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small>El email no se puede cambiar</small>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+54 123 456 7890">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        💾 Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- Tab: Mis Compras -->
        <div id="purchases" class="tab-content">
            <div class="card">
                <h2>🎫 Historial de Compras</h2>
                <?php if (!empty($purchases)): ?>
                    <?php foreach ($purchases as $purchase): ?>
                    <div class="purchase-item">
                        <div style="flex: 1;">
                            <h3><?= htmlspecialchars($purchase['title']) ?></h3>
                            <p class="purchase-date">
                                📅 <?= date('d/m/Y H:i', strtotime($purchase['purchased_at'])) ?>
                            </p>
                        </div>
                        <div class="purchase-actions">
                            <div style="text-align: right;">
                                <div class="purchase-price">
                                    <?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?>
                                </div>
                                <?php if ($purchase['event_status'] === 'live'): ?>
                                <a href="/public/watch.php?id=<?= $purchase['event_id'] ?>" class="btn btn-primary btn-sm">
                                    ▶ Ver Ahora
                                </a>
                                <?php elseif ($purchase['event_status'] === 'ended'): ?>
                                <span class="badge badge-warning">Finalizado</span>
                                <?php else: ?>
                                <span class="badge badge-info">Próximamente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎫</div>
                    <p>No has realizado ninguna compra aún</p>
                    <a href="/public/events.php" class="btn btn-primary">Ver Eventos Disponibles</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Seguridad -->
        <div id="security" class="tab-content">
            <div class="card">
                <h2>🔒 Cambiar Contraseña</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="new_password" minlength="6" required>
                        <small>Mínimo 6 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        🔑 Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Remover clase active de todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Agregar clase active al tab seleccionado
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>