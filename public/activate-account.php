<?php
// public/activate-account.php
// Página para activar cuenta de usuario con token de verificación

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

$error = '';
$success = '';
$debug_info = [];

// Obtener el token de la URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de activación no proporcionado.';
} else {
    try {
        $debug_info[] = "1. Token recibido: " . substr($token, 0, 10) . "...";
        
        $db = Database::getInstance()->getConnection();
        $debug_info[] = "2. Conexión a BD OK";
        
        // Buscar usuario con ese token que no esté verificado
        $stmt = $db->prepare("
            SELECT id, email, full_name, email_verified 
            FROM users 
            WHERE verification_token = ? 
            AND status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $debug_info[] = "3. Token no válido o no encontrado";
            $error = 'El enlace de activación no es válido o ha expirado. Por favor solicita un nuevo enlace de activación.';
            
        } elseif ($user['email_verified'] == 1) {
            $debug_info[] = "3. Usuario ya estaba verificado";
            $success = 'Tu cuenta ya está activada. Puedes <a href="/public/login.php">iniciar sesión</a> normalmente.';
            
        } else {
            $debug_info[] = "3. Usuario encontrado: ID {$user['id']}, Email: {$user['email']}";
            
            // Activar la cuenta
            $stmt = $db->prepare("
                UPDATE users 
                SET email_verified = 1, 
                    verification_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            $debug_info[] = "4. Cuenta activada en BD (email_verified=1)";
            
            // Enviar email de bienvenida
            try {
                $emailService = new EmailService();
                $debug_info[] = "5. EmailService instanciado";
                
                $emailSent = $emailService->sendWelcomeEmail($user['email'], $user['full_name']);
                $debug_info[] = "6. Email de bienvenida: " . ($emailSent ? 'ENVIADO' : 'FALLIDO');
                
                if ($emailSent) {
                    $success = '¡Tu cuenta ha sido activada exitosamente! 🎉<br><br>';
                    $success .= 'Te hemos enviado un email de bienvenida con información útil.<br><br>';
                    $success .= 'Ya puedes <a href="/public/login.php"><strong>iniciar sesión</strong></a> y comenzar a disfrutar de nuestros servicios.';
                } else {
                    $success = '¡Tu cuenta ha sido activada exitosamente! 🎉<br><br>';
                    $success .= 'Ya puedes <a href="/public/login.php"><strong>iniciar sesión</strong></a> y comenzar a disfrutar de nuestros servicios.';
                    
                    error_log("Cuenta activada pero no se pudo enviar email de bienvenida a: {$user['email']}");
                }
                
            } catch (Exception $e) {
                $debug_info[] = "5. ERROR al enviar email de bienvenida: " . $e->getMessage();
                error_log("Error enviando email de bienvenida: " . $e->getMessage());
                
                // Aunque falle el email, la cuenta ya está activada
                $success = '¡Tu cuenta ha sido activada exitosamente! 🎉<br><br>';
                $success .= 'Ya puedes <a href="/public/login.php"><strong>iniciar sesión</strong></a> y comenzar a disfrutar de nuestros servicios.';
            }
            
            // En desarrollo, mostrar debug info
            if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                $success .= '<br><br><strong>Debug Info:</strong><br>';
                $success .= '<div style="text-align: left; font-size: 11px; background: #f0f0f0; padding: 10px; border-radius: 5px;">';
                foreach ($debug_info as $info) {
                    $success .= '✓ ' . htmlspecialchars($info) . '<br>';
                }
                $success .= '</div>';
            }
        }
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("Error en activate-account: " . $errorMsg);
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
            $error = 'Ha ocurrido un error al activar tu cuenta. Por favor contacta al soporte.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar Cuenta - Streaming Platform</title>
    
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
        
        .activation-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            overflow: hidden;
        }
        
        .activation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .activation-header .icon {
            font-size: 70px;
            margin-bottom: 15px;
        }
        
        .activation-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .activation-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .activation-body {
            padding: 40px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.8;
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
        
        .alert a:hover {
            opacity: 0.8;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
            line-height: 1.6;
        }
        
        .info-box h3 {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin: 10px 0 0 20px;
        }
        
        .info-box li {
            margin-bottom: 8px;
        }
        
        .btn {
            display: inline-block;
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
            text-decoration: none;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
            flex-wrap: wrap;
            gap: 10px;
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
        
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="activation-container">
        <div class="activation-header">
            <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1>¡Cuenta Activada!</h1>
            <p>Tu registro se ha completado exitosamente</p>
            <?php elseif ($error): ?>
            <div class="icon">❌</div>
            <h1>Error de Activación</h1>
            <p>No se pudo activar tu cuenta</p>
            <?php else: ?>
            <div class="icon">⏳</div>
            <h1>Activando Cuenta...</h1>
            <p>Por favor espera un momento</p>
            <?php endif; ?>
        </div>
        
        <div class="activation-body">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
            
            <div class="info-box">
                <h3>¿Necesitas ayuda?</h3>
                <ul>
                    <li>Verifica que hayas copiado el enlace completo del email</li>
                    <li>El enlace es válido por 24 horas desde el registro</li>
                    <li>Si necesitas un nuevo enlace, intenta registrarte nuevamente</li>
                    <li>Contacta al soporte si el problema persiste</li>
                </ul>
            </div>
            
            <div class="links">
                <a href="/public/register.php">Registrarme de nuevo</a>
                <a href="/public/login.php">Ir al inicio de sesión</a>
            </div>
            
            <?php elseif ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            
            <a href="/public/login.php" class="btn">Iniciar Sesión Ahora</a>
            
            <div class="info-box">
                <h3>🎉 ¡Bienvenido a nuestra plataforma!</h3>
                <p>Ahora puedes:</p>
                <ul>
                    <li>Acceder a todos los eventos en streaming</li>
                    <li>Participar en chats en vivo</li>
                    <li>Comprar tickets para eventos premium</li>
                    <li>Disfrutar de contenido exclusivo</li>
                </ul>
            </div>
            
            <?php else: ?>
            <div class="loader"></div>
            <p style="text-align: center; color: #666; margin-top: 20px;">
                Procesando tu activación...
            </p>
            <?php endif; ?>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>