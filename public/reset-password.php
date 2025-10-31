<?php
// public/reset-password.php
// Página para restablecer contraseña con token

session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: /public/profile.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';

$error = '';
$success = '';
$tokenValid = false;
$email = '';

// Verificar token en URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de recuperación no válido o ausente.';
} else {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verificar si el token existe y no ha expirado
        $stmt = $db->prepare("
            SELECT id, email, reset_token_expires 
            FROM users 
            WHERE reset_token = ? 
            AND status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Token de recuperación inválido o ya utilizado.';
        } elseif (strtotime($user['reset_token_expires']) < time()) {
            $error = 'El token de recuperación ha expirado. Por favor solicita uno nuevo.';
        } else {
            $tokenValid = true;
            $email = $user['email'];
        }
        
    } catch (Exception $e) {
        $error = 'Ha ocurrido un error al verificar el token.';
    }
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $tokenPost = $_POST['token'] ?? '';
    
    // Validaciones
    if (empty($newPassword)) {
        $error = 'Por favor ingresa una contraseña';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif ($tokenPost !== $token) {
        $error = 'Token inválido';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Verificar token nuevamente
            $stmt = $db->prepare("
                SELECT id, email 
                FROM users 
                WHERE reset_token = ? 
                AND reset_token_expires > NOW()
                AND status = 'active'
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Token inválido o expirado';
            } else {
                // Hash de la nueva contraseña
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Actualizar contraseña y limpiar token
                $stmt = $db->prepare("
                    UPDATE users 
                    SET password_hash = ?, 
                        reset_token = NULL, 
                        reset_token_expires = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$passwordHash, $user['id']])) {
                    $success = 'Tu contraseña ha sido actualizada exitosamente. Ya puedes iniciar sesión.';
                    $tokenValid = false; // Deshabilitar formulario
                } else {
                    $error = 'No se pudo actualizar la contraseña. Intenta de nuevo.';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Ha ocurrido un error al actualizar la contraseña.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Streaming Platform</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .reset-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .reset-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .reset-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .reset-body {
            padding: 40px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
            line-height: 1.6;
        }
        
        .info-box ul {
            margin: 10px 0 0 20px;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            display: block;
            margin-top: 8px;
            color: #999;
            font-size: 13px;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: #f44336;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #ff9800;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: #4caf50;
        }
        
        .password-strength-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-home a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon">🔐</div>
            <h1>Restablecer Contraseña</h1>
            <p><?= $tokenValid ? 'Ingresa tu nueva contraseña' : 'Verifica tu enlace de recuperación' ?></p>
        </div>
        
        <div class="reset-body">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 15px;">
                    <a href="/public/login.php" class="btn">Ir a Iniciar Sesión</a>
                </div>
            </div>
            <?php elseif ($tokenValid): ?>
            
            <div class="info-box">
                <strong>Requisitos de contraseña:</strong>
                <ul>
                    <li>Mínimo 8 caracteres</li>
                    <li>Se recomienda usar letras, números y símbolos</li>
                    <li>Evita usar información personal</li>
                </ul>
            </div>
            
            <form method="POST" action="/public/reset-password.php?token=<?= htmlspecialchars($token) ?>" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email) ?>" disabled>
                    <small>Cambiarás la contraseña para esta cuenta</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Ingresa tu nueva contraseña" 
                           minlength="8"
                           required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="password-strength-text" id="strengthText">Ingresa una contraseña</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirma tu nueva contraseña" 
                           minlength="8"
                           required>
                    <small id="matchText"></small>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">Restablecer Contraseña</button>
            </form>
            
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <div class="divider">o</div>
            
            <div class="links">
                <a href="/public/login.php">← Volver al inicio de sesión</a>
                <a href="/public/forgot-password.php">Solicitar nuevo enlace</a>
            </div>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Validación de fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');
        const submitBtn = document.getElementById('submitBtn');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                strengthBar.className = 'password-strength-bar';
                
                if (password.length === 0) {
                    strengthBar.className = 'password-strength-bar';
                    strengthText.textContent = 'Ingresa una contraseña';
                    strengthText.style.color = '#666';
                } else if (strength <= 2) {
                    strengthBar.classList.add('weak');
                    strengthText.textContent = 'Contraseña débil';
                    strengthText.style.color = '#f44336';
                } else if (strength <= 4) {
                    strengthBar.classList.add('medium');
                    strengthText.textContent = 'Contraseña media';
                    strengthText.style.color = '#ff9800';
                } else {
                    strengthBar.classList.add('strong');
                    strengthText.textContent = 'Contraseña fuerte';
                    strengthText.style.color = '#4caf50';
                }
                
                checkPasswordMatch();
            });
        }
        
        if (confirmInput) {
            confirmInput.addEventListener('input', checkPasswordMatch);
        }
        
        function checkPasswordMatch() {
            if (!confirmInput || !passwordInput) return;
            
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                matchText.textContent = '';
                matchText.style.color = '#666';
                submitBtn.disabled = false;
            } else if (password === confirm) {
                matchText.textContent = '✓ Las contraseñas coinciden';
                matchText.style.color = '#4caf50';
                submitBtn.disabled = false;
            } else {
                matchText.textContent = '✗ Las contraseñas no coinciden';
                matchText.style.color = '#f44336';
                submitBtn.disabled = true;
            }
        }
        
        // Validación del formulario
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 8 caracteres');
                    return false;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    return false;
                }
                
                return true;
            });
        }
    </script>
</body>
</html>