<?php
// public/register.php
// Página de registro de nuevos usuarios con activación por email

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
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validaciones
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!$terms) {
        $error = 'Debes aceptar los términos y condiciones';
    } else {
        try {
            $debug_info[] = "1. Validaciones OK";
            
            $db = Database::getInstance()->getConnection();
            $debug_info[] = "2. Conexión a BD OK";
            
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este email ya está registrado. <a href="/public/login.php">Inicia sesión</a> o <a href="/public/forgot-password.php">recupera tu contraseña</a>.';
            } else {
                $debug_info[] = "3. Email disponible";
                
                // Generar token de verificación (válido por 24 horas)
                $verificationToken = bin2hex(random_bytes(32));
                $debug_info[] = "4. Token generado: " . substr($verificationToken, 0, 10) . "...";
                
                // Hash de la contraseña
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $debug_info[] = "5. Password hasheada";
                
                // Insertar usuario con email_verified = 0
                $stmt = $db->prepare("
                    INSERT INTO users (
                        email, password_hash, full_name, phone, 
                        role, status, email_verified, verification_token, 
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'user', 'active', 0, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $email,
                    $passwordHash,
                    $fullName,
                    $phone,
                    $verificationToken
                ]);
                
                $userId = $db->lastInsertId();
                $debug_info[] = "6. Usuario creado con ID: $userId (email_verified=0)";
                
                // Enviar email de activación
                try {
                    $emailService = new EmailService();
                    $debug_info[] = "7. EmailService instanciado";
                    
                    $emailSent = $emailService->sendAccountActivation($email, $verificationToken, $fullName);
                    $debug_info[] = "8. Intento de envío de activación: " . ($emailSent ? 'EXITOSO' : 'FALLIDO');
                    
                    if ($emailSent) {
                        $success = '¡Registro exitoso! Hemos enviado un email de activación a <strong>' . htmlspecialchars($email) . '</strong>. 
                                    Por favor revisa tu bandeja de entrada (y spam) y haz clic en el enlace para activar tu cuenta.';
                        
                        // En desarrollo, mostrar el link de activación
                        if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                            $activationLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/public/activate-account.php?token=" . $verificationToken;
                            $success .= '<br><br><strong>🔧 Modo Desarrollo:</strong>';
                            $success .= '<br><a href="' . $activationLink . '" target="_blank">Haz clic aquí para activar la cuenta</a>';
                            $success .= '<br><small style="font-size: 11px;">Link de activación: ' . $activationLink . '</small>';
                            
                            // Mostrar debug info
                            $success .= '<br><br><strong>Debug Info:</strong><br>';
                            $success .= '<div style="text-align: left; font-size: 11px; background: #f0f0f0; padding: 10px; border-radius: 5px;">';
                            foreach ($debug_info as $info) {
                                $success .= '✓ ' . htmlspecialchars($info) . '<br>';
                            }
                            $success .= '</div>';
                        }
                        
                        // Limpiar el formulario
                        $_POST = [];
                        
                    } else {
                        $error = 'El usuario fue creado pero no se pudo enviar el email de activación. ';
                        $error .= 'Por favor contacta al soporte para activar tu cuenta manualmente.';
                        
                        // En desarrollo, mostrar más detalles
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
                    $debug_info[] = "8. ERROR al enviar email: " . $e->getMessage();
                    error_log("Error enviando email de activación: " . $e->getMessage());
                    
                    $error = 'El usuario fue creado pero hubo un error al enviar el email de activación. ';
                    
                    // En desarrollo, mostrar el error
                    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                        $error .= '<br><br><strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                        $error .= '<br><br><strong>Debug Info:</strong><br>';
                        $error .= '<div style="text-align: left; font-size: 11px;">';
                        foreach ($debug_info as $info) {
                            $error .= htmlspecialchars($info) . '<br>';
                        }
                        $error .= '</div>';
                    } else {
                        $error .= 'Por favor contacta al soporte.';
                    }
                }
            }
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Error en register: " . $errorMsg);
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
    <title>Registrarse - Streaming Platform</title>
    
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
        
        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .register-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            opacity: 0.9;
        }
        
        .register-body {
            padding: 40px;
            max-height: 70vh;
            overflow-y: auto;
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
        
        .form-group label .required {
            color: #ff0000;
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
            margin-top: 5px;
            color: #999;
            font-size: 12px;
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-weak { width: 33%; background: #ff5252; }
        .strength-medium { width: 66%; background: #ffc107; }
        .strength-strong { width: 100%; background: #4CAF50; }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .checkbox-group label {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        .checkbox-group label a {
            color: #667eea;
            text-decoration: none;
        }
        
        .checkbox-group label a:hover {
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
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
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
    <div class="register-container">
        <div class="register-header">
            <h1>🎥 Únete a Nosotros</h1>
            <p>Crea tu cuenta para empezar</p>
        </div>
        
        <div class="register-body">
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
            
            <form method="POST" action="/public/register.php" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Nombre Completo <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" 
                           placeholder="Juan Pérez" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           placeholder="tu@email.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                    <small>Te enviaremos un email para activar tu cuenta</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="+54 9 11 1234-5678" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    <small>Opcional - Para notificaciones importantes</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña <span class="required">*</span></label>
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           required
                           minlength="8">
                    <small>Mínimo 8 caracteres</small>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="••••••••" 
                           required
                           minlength="8">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        Acepto los <a href="/public/terms.php" target="_blank">términos y condiciones</a> 
                        y la <a href="/public/privacy.php" target="_blank">política de privacidad</a>
                    </label>
                </div>
                
                <button type="submit" class="btn">Crear Cuenta</button>
            </form>
            
            <?php endif; ?>
            
            <div class="divider">o</div>
            
            <div class="login-link">
                ¿Ya tienes cuenta? <a href="/public/login.php">Inicia sesión aquí</a>
            </div>
            
            <div class="back-home">
                <a href="/public/">← Volver al inicio</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength === 1 || strength === 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else if (strength >= 4) {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Validar que las contraseñas coincidan
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>