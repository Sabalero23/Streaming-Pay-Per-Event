<?php
// public/forgot-password.php
// Página de recuperación de contraseña

session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: /public/profile.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

$error = '';
$success = '';
$debug_info = []; // Para debug en desarrollo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        try {
            $debug_info[] = "1. Validación de email OK: $email";
            
            // Verificar conexión a BD
            $db = Database::getInstance()->getConnection();
            $debug_info[] = "2. Conexión a BD OK";
            
            // Verificar si el usuario existe (SIN username, usar full_name)
            $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            $debug_info[] = "3. Query usuarios ejecutada";
            
            if ($user) {
                $debug_info[] = "4. Usuario encontrado: ID {$user['id']}, Nombre: {$user['full_name']}";
                
                // Generar token de recuperación
                $resetToken = bin2hex(random_bytes(32));
                $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $debug_info[] = "5. Token generado: " . substr($resetToken, 0, 10) . "...";
                
                // Guardar token en la BD
                try {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET reset_token = ?, reset_token_expires = ? 
                        WHERE email = ?
                    ");
                    $stmt->execute([$resetToken, $resetExpires, $email]);
                    $debug_info[] = "6. Token guardado en BD";
                } catch (PDOException $e) {
                    $debug_info[] = "6. ERROR al guardar token: " . $e->getMessage();
                    throw new Exception("Error al guardar token en BD: " . $e->getMessage());
                }
                
                // Verificar que EmailService existe y se puede instanciar
                try {
                    $emailService = new EmailService();
                    $debug_info[] = "7. EmailService instanciado OK";
                    
                    // Obtener estado SMTP para debug
                    $smtpStatus = $emailService->getSmtpStatus();
                    $debug_info[] = "8. Estado SMTP: " . ($smtpStatus['enabled'] ? 'HABILITADO' : 'DESHABILITADO');
                    $debug_info[] = "   - Host: {$smtpStatus['host']}:{$smtpStatus['port']}";
                    $debug_info[] = "   - From: {$smtpStatus['from_name']} <{$smtpStatus['from_address']}>";
                    
                } catch (Exception $e) {
                    $debug_info[] = "7. ERROR al instanciar EmailService: " . $e->getMessage();
                    throw new Exception("Error al inicializar el servicio de email: " . $e->getMessage());
                }
                
                // Enviar email de recuperación (usar full_name en lugar de username)
                try {
                    $emailSent = $emailService->sendPasswordReset($email, $resetToken, $user['full_name']);
                    $debug_info[] = "9. Intento de envío: " . ($emailSent ? 'EXITOSO' : 'FALLIDO');
                } catch (Exception $e) {
                    $debug_info[] = "9. ERROR al enviar email: " . $e->getMessage();
                    throw new Exception("Error al enviar email: " . $e->getMessage());
                }
                
                if ($emailSent) {
                    $success = 'Se ha enviado un enlace de recuperación a tu email. El enlace es válido por 1 hora.';
                    
                    // En desarrollo, mostrar el link y debug info
                    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/public/reset-password.php?token=" . $resetToken;
                        $success .= '<br><br><strong>🔧 Modo Desarrollo:</strong>';
                        $success .= '<br><a href="' . $resetLink . '" target="_blank">' . $resetLink . '</a>';
                        
                        // Mostrar debug info
                        $success .= '<br><br><strong>Debug Info:</strong><br>';
                        $success .= '<div style="text-align: left; font-size: 11px; background: #f0f0f0; padding: 10px; border-radius: 5px; margin-top: 10px;">';
                        foreach ($debug_info as $info) {
                            $success .= '✓ ' . htmlspecialchars($info) . '<br>';
                        }
                        $success .= '</div>';
                    }
                } else {
                    $error = 'No se pudo enviar el email de recuperación. ';
                    
                    // En desarrollo, mostrar más detalles
                    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                        $error .= '<br><br><strong>Debug Info:</strong><br>';
                        $error .= '<div style="text-align: left; font-size: 11px;">';
                        foreach ($debug_info as $info) {
                            $error .= htmlspecialchars($info) . '<br>';
                        }
                        $error .= '</div>';
                        $error .= '<br>Revisa los logs de PHP para más detalles.';
                    } else {
                        $error .= 'Por favor contacta al soporte.';
                    }
                    
                    error_log("Failed to send password reset email to: {$email}");
                }
                
            } else {
                $debug_info[] = "4. Usuario NO encontrado con email: $email";
                
                // Por seguridad, mostrar el mismo mensaje aunque no exista el usuario
                // Esto previene que alguien pueda saber qué emails están registrados
                $success = 'Si existe una cuenta con ese email, recibirás un enlace de recuperación.';
                
                // En desarrollo, mostrar que no se encontró
                if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                    $success .= '<br><br><strong>⚠️ Debug:</strong> No existe usuario activo con ese email.';
                }
            }
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Error en forgot-password: " . $errorMsg);
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // En desarrollo, mostrar el error real
            if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                $error = '<strong>Error de desarrollo:</strong><br>' . htmlspecialchars($errorMsg);
                $error .= '<br><br><strong>Debug Info:</strong><br>';
                $error .= '<div style="text-align: left; font-size: 11px;">';
                foreach ($debug_info as $info) {
                    $error .= htmlspecialchars($info) . '<br>';
                }
                $error .= '</div>';
            } else {
                $error = 'Ha ocurrido un error. Por favor intenta de nuevo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Streaming Platform</title>
    
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
        
        .forgot-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .forgot-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .forgot-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .forgot-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .forgot-body {
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
            margin-bottom: 25px;
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
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
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
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="icon">🔒</div>
            <h1>¿Olvidaste tu contraseña?</h1>
            <p>No te preocupes, te ayudaremos a recuperarla</p>
        </div>
        
        <div class="forgot-body">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            <?php else: ?>
            
            <div class="info-box">
                <strong>Instrucciones:</strong>
                <ul>
                    <li>Ingresa tu email registrado</li>
                    <li>Te enviaremos un enlace de recuperación</li>
                    <li>El enlace es válido por 1 hora</li>
                </ul>
            </div>
            
            <form method="POST" action="/public/forgot-password.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                    <small>Ingresa el email asociado a tu cuenta</small>
                </div>
                
                <button type="submit" class="btn">Enviar Enlace de Recuperación</button>
            </form>
            
            <?php endif; ?>
            
            <div class="divider">o</div>
            
            <div class="links">
                <a href="/public/login.php">← Volver al inicio de sesión</a>
                <a href="/public/register.php">Registrarse</a>
            </div>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>