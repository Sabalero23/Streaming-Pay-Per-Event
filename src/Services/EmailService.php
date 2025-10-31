<?php
// src/Services/EmailService.php - VERSIÓN CON ACTIVACIÓN DE CUENTA

class EmailService {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadConfig();
    }
    
    /**
     * Carga la configuración de email desde la base de datos
     */
    private function loadConfig() {
        try {
            $stmt = $this->db->prepare("
                SELECT config_key, config_value 
                FROM system_config 
                WHERE config_key IN (
                    'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 
                    'smtp_password', 'smtp_encryption', 'smtp_debug',
                    'email_from_address', 'email_from_name', 'email_reply_to'
                )
            ");
            $stmt->execute();
            
            $this->config = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
            
            // Valores por defecto si no existen en la BD
            $defaults = [
                'smtp_enabled' => 'true',
                'smtp_host' => 'localhost',
                'smtp_port' => '587',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'smtp_debug' => 'false',
                'email_from_address' => 'noreply@eventix.com.ar',
                'email_from_name' => 'Eventix',
                'email_reply_to' => 'soporte@eventix.com.ar'
            ];
            
            foreach ($defaults as $key => $value) {
                if (!isset($this->config[$key]) || $this->config[$key] === '') {
                    $this->config[$key] = $value;
                }
            }
            
            error_log("EmailService Config Loaded:");
            error_log("  SMTP Enabled: " . $this->config['smtp_enabled']);
            error_log("  SMTP Host: " . $this->config['smtp_host']);
            error_log("  SMTP Port: " . $this->config['smtp_port']);
            
        } catch (Exception $e) {
            error_log("Error cargando configuración de email: " . $e->getMessage());
            throw new Exception("No se pudo cargar la configuración de email: " . $e->getMessage());
        }
    }
    
    /**
     * ✅ NUEVO: Envía email de activación de cuenta
     */
    public function sendAccountActivation($to_email, $verification_token, $user_name) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $activation_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/public/activate-account.php?token=" . $verification_token;
        
        $subject = "Activa tu cuenta - " . $this->config['email_from_name'];
        
        $html_body = $this->getAccountActivationTemplate($activation_link, $user_name);
        $text_body = $this->getAccountActivationTextTemplate($activation_link, $user_name);
        
        return $this->send($to_email, $subject, $html_body, $text_body, 'account_activation');
    }
    
    /**
     * ✅ NUEVO: Envía email de bienvenida después de activar la cuenta
     */
    public function sendWelcomeEmail($to_email, $user_name) {
        $subject = "¡Bienvenido a " . $this->config['email_from_name'] . "!";
        
        $html_body = $this->getWelcomeTemplate($user_name);
        $text_body = $this->getWelcomeTextTemplate($user_name);
        
        return $this->send($to_email, $subject, $html_body, $text_body, 'welcome');
    }
    
    /**
     * Envía un email de recuperación de contraseña
     */
    public function sendPasswordReset($to_email, $reset_token, $user_name = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/public/reset-password.php?token=" . $reset_token;
        
        $subject = "Recuperación de Contraseña - " . $this->config['email_from_name'];
        
        $html_body = $this->getPasswordResetTemplate($reset_link, $user_name);
        $text_body = $this->getPasswordResetTextTemplate($reset_link, $user_name);
        
        return $this->send($to_email, $subject, $html_body, $text_body, 'password_reset');
    }
    
    /**
     * Envía un email genérico
     */
    public function send($to_email, $subject, $html_body, $text_body = '', $type = 'general') {
        $email_id = null;
        
        try {
            // 1. Registrar en email_logs
            $email_id = $this->logEmail($to_email, $subject, $html_body, $type, 'pending');
            error_log("Email logged with ID: $email_id");
            
            // 2. Verificar si SMTP está habilitado
            $smtp_enabled = ($this->config['smtp_enabled'] === 'true' || $this->config['smtp_enabled'] === '1');
            error_log("SMTP Enabled: " . ($smtp_enabled ? 'YES' : 'NO'));
            
            // 3. Intentar enviar el email
            if ($smtp_enabled) {
                error_log("Attempting to send via PHPMailer...");
                $sent = $this->sendWithPHPMailer($to_email, $subject, $html_body, $text_body);
            } else {
                error_log("SMTP disabled, using mail() function...");
                $sent = $this->sendWithMailFunction($to_email, $subject, $html_body, $text_body);
            }
            
            error_log("Send result: " . ($sent ? 'SUCCESS' : 'FAILED'));
            
            // 4. Actualizar el log
            if ($sent) {
                $this->updateEmailLog($email_id, 'sent', 'Email enviado correctamente');
                return true;
            } else {
                $this->updateEmailLog($email_id, 'failed', 'Error al enviar email');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Exception in EmailService::send: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($email_id) {
                $this->updateEmailLog($email_id, 'failed', 'Excepción: ' . $e->getMessage());
            }
            
            throw $e;
        }
    }
    
    /**
     * Envía email usando PHPMailer con configuración SMTP
     */
    private function sendWithPHPMailer($to_email, $subject, $html_body, $text_body) {
        try {
            $autoload_path = __DIR__ . '/../../vendor/autoload.php';
            if (!file_exists($autoload_path)) {
                throw new Exception("PHPMailer no está instalado. Ejecuta: composer require phpmailer/phpmailer");
            }
            require_once $autoload_path;
            
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                throw new Exception("No se pudo cargar la clase PHPMailer");
            }
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Debug mode
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer [$level]: $str");
            };
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = (int)$this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->Timeout = 30;
            
            // Remitente
            $mail->setFrom(
                $this->config['email_from_address'], 
                $this->config['email_from_name']
            );
            
            // Reply-To
            if (!empty($this->config['email_reply_to'])) {
                $mail->addReplyTo($this->config['email_reply_to']);
            }
            
            // Destinatario
            $mail->addAddress($to_email);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = $text_body ?: strip_tags($html_body);
            
            $result = $mail->send();
            error_log("PHPMailer send() returned: " . ($result ? 'true' : 'false'));
            error_log("Email enviado exitosamente a: $to_email");
            
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . (isset($mail) ? $mail->ErrorInfo : 'N/A'));
            error_log("Exception: " . $e->getMessage());
            throw new Exception("Error al enviar email con PHPMailer: " . $e->getMessage());
        }
    }
    
    /**
     * Envía email usando la función mail() de PHP (fallback)
     */
    private function sendWithMailFunction($to_email, $subject, $html_body, $text_body) {
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$this->config['email_from_name']} <{$this->config['email_from_address']}>\r\n";
            
            if (!empty($this->config['email_reply_to'])) {
                $headers .= "Reply-To: {$this->config['email_reply_to']}\r\n";
            }
            
            $result = mail($to_email, $subject, $html_body, $headers);
            
            if ($result) {
                error_log("Email enviado usando mail() a: $to_email");
                return true;
            } else {
                error_log("Error al enviar email usando mail() a: $to_email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error en sendWithMailFunction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un email en la tabla email_logs
     */
    private function logEmail($to_email, $subject, $body, $type, $status) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (
                    recipient_email, subject, body, type, status, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $to_email,
                $subject,
                $body,
                $type,
                $status
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error logging email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualiza el estado de un email en email_logs
     */
    private function updateEmailLog($email_id, $status, $error_message = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_logs 
                SET status = ?, 
                    error_message = ?,
                    sent_at = IF(? = 'sent', NOW(), sent_at),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $status,
                $error_message,
                $status,
                $email_id
            ]);
            
        } catch (Exception $e) {
            error_log("Error updating email log: " . $e->getMessage());
        }
    }
    
    /**
     * ✅ NUEVO: Template HTML para activación de cuenta
     */
    private function getAccountActivationTemplate($activation_link, $user_name) {
        $site_name = $this->config['email_from_name'];
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { font-size: 28px; margin: 10px 0; }
                .icon { font-size: 60px; margin-bottom: 10px; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 40px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #999; font-size: 12px; }
                .info-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>📧</div>
                    <h1>¡Confirma tu Email!</h1>
                    <p>Estás a un paso de completar tu registro</p>
                </div>
                <div class='content'>
                    <p>¡Hola <strong>{$user_name}</strong>!</p>
                    
                    <p>Gracias por registrarte en <strong>{$site_name}</strong>. Para completar tu registro y activar tu cuenta, necesitamos verificar tu dirección de email.</p>
                    
                    <p>Por favor, haz clic en el siguiente botón para activar tu cuenta:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$activation_link}' class='button'>✅ Activar Mi Cuenta</a>
                    </div>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; color: #667eea; font-size: 14px;'>{$activation_link}</p>
                    
                    <div class='info-box'>
                        <strong>ℹ️ Importante:</strong>
                        <ul style='margin: 10px 0;'>
                            <li>Este enlace es válido por <strong>24 horas</strong></li>
                            <li>No podrás acceder a tu cuenta hasta que la actives</li>
                            <li>Si no fuiste tú quien se registró, ignora este email</li>
                        </ul>
                    </div>
                    
                    <p>Una vez activada tu cuenta, podrás disfrutar de todos nuestros servicios.</p>
                    
                    <p>¡Nos vemos pronto!<br><strong>Equipo de {$site_name}</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                    <p>&copy; " . date('Y') . " {$site_name}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * ✅ NUEVO: Template de texto plano para activación de cuenta
     */
    private function getAccountActivationTextTemplate($activation_link, $user_name) {
        $site_name = $this->config['email_from_name'];
        
        return "¡Hola {$user_name}!

Gracias por registrarte en {$site_name}. Para completar tu registro y activar tu cuenta, necesitamos verificar tu dirección de email.

Por favor, visita el siguiente enlace para activar tu cuenta:
{$activation_link}

IMPORTANTE:
- Este enlace es válido por 24 horas
- No podrás acceder a tu cuenta hasta que la actives
- Si no fuiste tú quien se registró, ignora este email

Una vez activada tu cuenta, podrás disfrutar de todos nuestros servicios.

¡Nos vemos pronto!
Equipo de {$site_name}

---
Este es un email automático, por favor no respondas a este mensaje.
© " . date('Y') . " {$site_name}. Todos los derechos reservados.";
    }
    
    /**
     * ✅ NUEVO: Template HTML de bienvenida
     */
    private function getWelcomeTemplate($user_name) {
        $site_name = $this->config['email_from_name'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $site_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { font-size: 32px; margin: 10px 0; }
                .icon { font-size: 70px; margin-bottom: 15px; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 40px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .features { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 25px 0; }
                .feature { background: white; padding: 15px; border-radius: 8px; text-align: center; }
                .feature-icon { font-size: 30px; margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 30px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>🎉</div>
                    <h1>¡Bienvenido a {$site_name}!</h1>
                    <p>Tu cuenta ha sido activada exitosamente</p>
                </div>
                <div class='content'>
                    <p>¡Hola <strong>{$user_name}</strong>!</p>
                    
                    <p>¡Estamos muy emocionados de tenerte con nosotros! Tu cuenta ha sido activada y ya puedes disfrutar de todas las funcionalidades de <strong>{$site_name}</strong>.</p>
                    
                    <div class='features'>
                        <div class='feature'>
                            <div class='feature-icon'>🎥</div>
                            <strong>Streaming en Vivo</strong>
                            <p style='font-size: 13px; color: #666;'>Accede a eventos en directo</p>
                        </div>
                        <div class='feature'>
                            <div class='feature-icon'>💬</div>
                            <strong>Chat en Vivo</strong>
                            <p style='font-size: 13px; color: #666;'>Interactúa con otros usuarios</p>
                        </div>
                        <div class='feature'>
                            <div class='feature-icon'>🎟️</div>
                            <strong>Eventos Exclusivos</strong>
                            <p style='font-size: 13px; color: #666;'>Contenido premium</p>
                        </div>
                        <div class='feature'>
                            <div class='feature-icon'>📱</div>
                            <strong>Multi-dispositivo</strong>
                            <p style='font-size: 13px; color: #666;'>Mira desde cualquier lugar</p>
                        </div>
                    </div>
                    
                    <p style='text-align: center;'>¿Listo para comenzar?</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$site_url}/public/login.php' class='button'>🚀 Iniciar Sesión</a>
                    </div>
                    
                    <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                        <strong>Próximos pasos:</strong><br>
                        ✓ Completa tu perfil<br>
                        ✓ Explora los eventos disponibles<br>
                        ✓ Configura tus preferencias<br>
                        ✓ ¡Empieza a disfrutar!
                    </p>
                    
                    <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos. Estamos aquí para ayudarte.</p>
                    
                    <p>¡Bienvenido a la familia {$site_name}!<br><strong>El Equipo de {$site_name}</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                    <p>&copy; " . date('Y') . " {$site_name}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * ✅ NUEVO: Template de texto plano de bienvenida
     */
    private function getWelcomeTextTemplate($user_name) {
        $site_name = $this->config['email_from_name'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $site_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
        
        return "¡Hola {$user_name}!

¡Estamos muy emocionados de tenerte con nosotros! Tu cuenta ha sido activada y ya puedes disfrutar de todas las funcionalidades de {$site_name}.

QUÉ PUEDES HACER:
• Streaming en Vivo - Accede a eventos en directo
• Chat en Vivo - Interactúa con otros usuarios
• Eventos Exclusivos - Contenido premium
• Multi-dispositivo - Mira desde cualquier lugar

PRÓXIMOS PASOS:
✓ Completa tu perfil
✓ Explora los eventos disponibles
✓ Configura tus preferencias
✓ ¡Empieza a disfrutar!

Inicia sesión ahora:
{$site_url}/public/login.php

Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos. Estamos aquí para ayudarte.

¡Bienvenido a la familia {$site_name}!
El Equipo de {$site_name}

---
Este es un email automático, por favor no respondas a este mensaje.
© " . date('Y') . " {$site_name}. Todos los derechos reservados.";
    }
    
    /**
     * Template HTML para recuperación de contraseña
     */
    private function getPasswordResetTemplate($reset_link, $user_name = '') {
        $greeting = $user_name ? "Hola {$user_name}" : "Hola";
        $site_name = $this->config['email_from_name'];
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #999; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Recuperación de Contraseña</h1>
                </div>
                <div class='content'>
                    <p>{$greeting},</p>
                    
                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en {$site_name}.</p>
                    
                    <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Restablecer Contraseña</a>
                    </div>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; color: #667eea;'>{$reset_link}</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <ul>
                            <li>Este enlace es válido por <strong>1 hora</strong></li>
                            <li>Si no solicitaste este cambio, ignora este email</li>
                            <li>Tu contraseña actual sigue siendo válida hasta que la cambies</li>
                        </ul>
                    </div>
                    
                    <p>Si tienes algún problema, contacta a nuestro equipo de soporte.</p>
                    
                    <p>Saludos,<br><strong>Equipo de {$site_name}</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                    <p>&copy; " . date('Y') . " {$site_name}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de texto plano para recuperación de contraseña
     */
    private function getPasswordResetTextTemplate($reset_link, $user_name = '') {
        $greeting = $user_name ? "Hola {$user_name}" : "Hola";
        $site_name = $this->config['email_from_name'];
        
        return "{$greeting},

Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en {$site_name}.

Para crear una nueva contraseña, visita el siguiente enlace:
{$reset_link}

IMPORTANTE:
- Este enlace es válido por 1 hora
- Si no solicitaste este cambio, ignora este email
- Tu contraseña actual sigue siendo válida hasta que la cambies

Si tienes algún problema, contacta a nuestro equipo de soporte.

Saludos,
Equipo de {$site_name}

---
Este es un email automático, por favor no respondas a este mensaje.
© " . date('Y') . " {$site_name}. Todos los derechos reservados.";
    }
    
    /**
     * Obtiene el estado de la configuración SMTP
     */
    public function getSmtpStatus() {
        return [
            'enabled' => ($this->config['smtp_enabled'] === 'true' || $this->config['smtp_enabled'] === '1'),
            'host' => $this->config['smtp_host'],
            'port' => $this->config['smtp_port'],
            'username' => $this->config['smtp_username'],
            'encryption' => $this->config['smtp_encryption'],
            'from_address' => $this->config['email_from_address'],
            'from_name' => $this->config['email_from_name']
        ];
    }
    
    /**
     * Método para verificar la configuración SMTP
     */
    public function testSmtpConnection() {
        try {
            $autoload_path = __DIR__ . '/../../vendor/autoload.php';
            if (!file_exists($autoload_path)) {
                return [
                    'success' => false,
                    'message' => 'PHPMailer no está instalado'
                ];
            }
            require_once $autoload_path;
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = (int)$this->config['smtp_port'];
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->SMTPDebug = 0;
            $mail->Timeout = 10;
            
            if (!$mail->smtpConnect()) {
                return [
                    'success' => false,
                    'message' => 'No se pudo conectar al servidor SMTP: ' . $mail->ErrorInfo
                ];
            }
            
            $mail->smtpClose();
            
            return [
                'success' => true,
                'message' => 'Conexión SMTP exitosa'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}