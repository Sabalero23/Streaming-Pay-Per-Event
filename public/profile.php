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
.profile-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #333;
}

.tab {
    padding: 15px 30px;
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 16px;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}

.tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.purchase-item {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 768px) {
    .purchase-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .profile-tabs {
        overflow-x: auto;
    }
    
    .tab {
        white-space: nowrap;
    }
}
</style>

<div class="section">
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <h1><?= htmlspecialchars($user['full_name']) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <p style="margin-top: 10px;">
                <span class="badge badge-info"><?= strtoupper($user['role']) ?></span>
            </p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-tabs">
            <button class="tab active" onclick="showTab('info')">📋 Mi Información</button>
            <button class="tab" onclick="showTab('purchases')">🎫 Mis Compras</button>
            <button class="tab" onclick="showTab('security')">🔒 Seguridad</button>
        </div>

        <!-- Tab: Mi Información -->
        <div id="info" class="tab-content active">
            <div class="card">
                <h2 style="margin-bottom: 20px;">Información Personal</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                        <small style="color: #999;">El email no se puede cambiar</small>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+54 123 456 7890">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">💾 Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Tab: Mis Compras -->
        <div id="purchases" class="tab-content">
            <div class="card">
                <h2 style="margin-bottom: 20px;">Historial de Compras</h2>
                <?php if (!empty($purchases)): ?>
                    <?php foreach ($purchases as $purchase): ?>
                    <div class="purchase-item">
                        <div>
                            <h3><?= htmlspecialchars($purchase['title']) ?></h3>
                            <p style="color: #999; font-size: 14px;">
                                <?= date('d/m/Y H:i', strtotime($purchase['purchased_at'])) ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 20px; font-weight: bold; color: #4CAF50;">
                                <?= $purchase['currency'] ?> <?= number_format($purchase['amount'], 2) ?>
                            </p>
                            <?php if ($purchase['event_status'] === 'live'): ?>
                            <a href="/public/watch.php?id=<?= $purchase['event_id'] ?>" class="btn btn-primary">▶ Ver Ahora</a>
                            <?php elseif ($purchase['event_status'] === 'ended'): ?>
                            <span class="badge badge-warning">Finalizado</span>
                            <?php else: ?>
                            <span class="badge badge-info">Próximamente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    No has realizado ninguna compra aún
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Seguridad -->
        <div id="security" class="tab-content">
            <div class="card">
                <h2 style="margin-bottom: 20px;">Cambiar Contraseña</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="new_password" minlength="6" required>
                        <small style="color: #999;">Mínimo 6 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">🔒 Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>
