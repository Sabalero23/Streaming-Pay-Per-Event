<?php
// public/login.php - CON VALIDACIÓN DE EMAIL VERIFICADO
// Este es un ejemplo de cómo debería ser tu login.php actualizado

session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: /public/events.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

// Mensaje de registro exitoso
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = '¡Registro exitoso! Por favor revisa tu email para activar tu cuenta antes de iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validaciones
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu email y contraseña';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // ✅ IMPORTANTE: También obtener email_verified
            $stmt = $db->prepare("
                SELECT id, email, password_hash, full_name, role, status, email_verified
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Email o contraseña incorrectos';
            } elseif ($user['status'] !== 'active') {
                $error = 'Tu cuenta ha sido suspendida. Contacta al soporte.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Email o contraseña incorrectos';
            } 
            // ✅ NUEVA VALIDACIÓN: Verificar que el email esté verificado
            elseif ($user['email_verified'] == 0) {
                $error = 'Tu cuenta aún no está activada. Por favor revisa tu email y haz clic en el enlace de activación.';
                $error .= '<br><br><a href="/public/resend-activation.php?email=' . urlencode($email) . '" style="color: inherit; font-weight: bold;">Reenviar email de activación</a>';
            } 
            else {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Actualizar última conexión
                $stmt = $db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Remember me (opcional)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 días
                    
                    // Guardar token en BD (necesitarías crear esta tabla)
                    // $stmt = $db->prepare("INSERT INTO remember_tokens ...");
                }
                
                // Redirigir según el rol
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /public/events.php');
                }
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Ha ocurrido un error. Por favor intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Streaming Platform</title>
    
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
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
        }
        
        .login-body {
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
        
        .alert a {
            color: inherit;
            font-weight: bold;
            text-decoration: underline;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
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
        
        .divider {
            text-align: center;
            margin: 30px 0;
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
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
    <div class="login-container">
        <div class="login-header">
            <h1>🎥 Bienvenido</h1>
            <p>Inicia sesión para continuar</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/public/login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           required>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Recordarme</label>
                    </div>
                    <div class="forgot-password">
                        <a href="/public/forgot-password.php">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
            
            <div class="divider">o</div>
            
            <div class="register-link">
                ¿No tienes cuenta? <a href="/public/register.php">Regístrate aquí</a>
            </div>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>