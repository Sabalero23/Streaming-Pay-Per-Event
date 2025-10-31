<?php
// public/resend-activation.php
// Página para reenviar el email de activación si el usuario no lo recibió

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

$error = '';
$success = '';
$debug_info = [];

// Obtener email de la URL o del POST
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        try {
            $debug_info[] = "1. Email recibido: $email";
            
            $db = Database::getInstance()->getConnection();
            $debug_info[] = "2. Conexión a BD OK";
            
            // Buscar usuario no verificado con ese email
            $stmt = $db->prepare("
                SELECT id, email, full_name, email_verified 
                FROM users 
                WHERE email = ? 
                AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $debug_info[] = "3. Usuario no encontrado";
                // Por seguridad, mostrar mensaje genérico
                $success = 'Si existe una cuenta con ese email que no esté verificada, recibirás un nuevo email de activación.';
                
            } elseif ($user['email_verified'] == 1) {
                $debug_info[] = "3. Usuario ya está verificado";
                $success = 'Tu cuenta ya está activada. Puedes <a href="/public/login.php">iniciar sesión</a> normalmente.';
                
            } else {
                $debug_info[] = "3. Usuario encontrado: ID {$user['id']}, no verificado";
                
                // Generar nuevo token de verificación
                $verificationToken = bin2hex(random_bytes(32));
                $debug_info[] = "4. Nuevo token generado: " . substr($verificationToken, 0, 10) . "...";
                
                // Actualizar token en BD
                $stmt = $db->prepare("
                    UPDATE users 
                    SET verification_token = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$verificationToken, $user['id']]);
                $debug_info[] = "5. Token actualizado en BD";
                
                // Enviar email de activación
                try {
                    $emailService = new EmailService();
                    $debug_info[] = "6. EmailService instanciado";
                    
                    $emailSent = $emailService->sendAccountActivation($email, $verificationToken, $user['full_name']);
                    $debug_info[] = "7. Email enviado: " . ($emailSent ? 'EXITOSO' : 'FALLIDO');
                    
                    if ($emailSent) {
                        $success = 'Hemos enviado un nuevo email de activación a <strong>' . htmlspecialchars($email) . '</strong>. 
                                    Por favor revisa tu bandeja de entrada (y la carpeta de spam).';
                        
                        // En desarrollo, mostrar el link
                        if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                            $activationLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/public/activate-account.php?token=" . $verificationToken;
                            $success .= '<br><br><strong>🔧 Modo Desarrollo:</strong>';
                            $success .= '<br><a href="' . $activationLink . '" target="_blank">Haz clic aquí para activar la cuenta</a>';
                            
                            // Mostrar debug info
                            $success .= '<br><br><strong>Debug Info:</strong><br>';
                            $success .= '<div style="text-align: left; font-size: 11px; background: #f0f0f0; padding: 10px; border-radius: 5px;">';
                            foreach ($debug_info as $info) {
                                $success .= '✓ ' . htmlspecialchars($info) . '<br>';
                            }
                            $success .= '</div>';
                        }
                        
                    } else {
                        $error = 'No se pudo enviar el email. Por favor intenta de nuevo más tarde o contacta al soporte.';
                        
                        // En desarrollo, mostrar debug
                        if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                            $error .= '<br><br><strong>Debug Info:</strong><br>';
                            $error .= '<div style="text-align: left; font-size: 11px;">';
                            foreach ($debug_info as $info) {
                                $error .= htmlspecialchars($info) . '<br>';
                            }
                            $error .= '</div>';
                        }
                    }
                    
                } catch (Exception $e) {
                    $debug_info[] = "6. ERROR al enviar email: " . $e->getMessage();
                    error_log("Error enviando email de activación: " . $e->getMessage());
                    
                    $error = 'Hubo un error al enviar el email. Por favor intenta de nuevo más tarde.';
                    
                    // En desarrollo, mostrar error
                    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                        $error .= '<br><br><strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                        $error .= '<br><br><strong>Debug Info:</strong><br>';
                        $error .= '<div style="text-align: left; font-size: 11px;">';
                        foreach ($debug_info as $info) {
                            $error .= htmlspecialchars($info) . '<br>';
                        }
                        $error .= '</div>';
                    }
                }
            }
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Error en resend-activation: " . $errorMsg);
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // En desarrollo, mostrar error
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
    <title>Reenviar Activación - Streaming Platform</title>
    
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
        
        .resend-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .resend-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .resend-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .resend-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .resend-header p {
            opacity: 0.9;
        }
        
        .resend-body {
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
        
        .info-box strong {
            display: block;
            margin-bottom: 8px;
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
    <div class="resend-container">
        <div class="resend-header">
            <div class="icon">📧</div>
            <h1>Reenviar Activación</h1>
            <p>¿No recibiste el email de activación?</p>
        </div>
        
        <div class="resend-body">
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
                <strong>Antes de reenviar, verifica:</strong>
                <ul>
                    <li>Revisa tu carpeta de spam o correo no deseado</li>
                    <li>Verifica que el email sea correcto</li>
                    <li>Espera unos minutos, puede tardar un poco en llegar</li>
                    <li>Contacta al soporte si el problema persiste</li>
                </ul>
            </div>
            
            <form method="POST" action="/public/resend-activation.php">
                <div class="form-group">
                    <label for="email">Email de tu cuenta</label>
                    <input type="email" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?= htmlspecialchars($email) ?>"
                           required>
                    <small>Ingresa el email con el que te registraste</small>
                </div>
                
                <button type="submit" class="btn">Reenviar Email de Activación</button>
            </form>
            
            <?php endif; ?>
            
            <div class="divider">o</div>
            
            <div class="links">
                <a href="/public/login.php">← Volver al login</a>
                <a href="/public/register.php">Registrarme</a>
            </div>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>