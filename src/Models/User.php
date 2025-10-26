<?php
// src/Models/User.php

require_once __DIR__ . '/../../config/database.php';

class User extends Model {
    protected $table = 'users';
    
    public function __construct() {
        parent::__construct();
    }
    
    // Buscar usuario por email
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->query($sql, [$email]);
        return $stmt->fetch();
    }
    
    // Registrar nuevo usuario
    public function register($email, $password, $fullName, $phone = null) {
        // Verificar si el email ya existe
        if ($this->findByEmail($email)) {
            throw new Exception("El email ya está registrado");
        }
        
        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Token de verificación
        $verificationToken = bin2hex(random_bytes(32));
        
        $data = [
            'email' => $email,
            'password_hash' => $passwordHash,
            'full_name' => $fullName,
            'phone' => $phone,
            'verification_token' => $verificationToken,
            'role' => 'user',
            'status' => 'active'
        ];
        
        $userId = $this->create($data);
        
        // Enviar email de verificación (implementar)
        $this->sendVerificationEmail($email, $verificationToken);
        
        return $userId;
    }
    
    // Login
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            throw new Exception("Credenciales inválidas");
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales inválidas");
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception("Cuenta suspendida o inactiva");
        }
        
        // Actualizar último login
        $this->query(
            "UPDATE {$this->table} SET updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        return $user;
    }
    
    // Verificar email
    public function verifyEmail($token) {
        $sql = "SELECT * FROM {$this->table} WHERE verification_token = ? LIMIT 1";
        $stmt = $this->query($sql, [$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Token inválido");
        }
        
        $this->query(
            "UPDATE {$this->table} SET email_verified = 1, verification_token = NULL WHERE id = ?",
            [$user['id']]
        );
        
        return true;
    }
    
    // Solicitar reset de contraseña
    public function requestPasswordReset($email) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            // No revelar si el email existe o no por seguridad
            return true;
        }
        
        $resetToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->query(
            "UPDATE {$this->table} SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
            [$resetToken, $expires, $user['id']]
        );
        
        // Enviar email con enlace de reset
        $this->sendPasswordResetEmail($email, $resetToken);
        
        return true;
    }
    
    // Reset de contraseña
    public function resetPassword($token, $newPassword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE reset_token = ? 
                AND reset_token_expires > NOW() 
                LIMIT 1";
        $stmt = $this->query($sql, [$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Token inválido o expirado");
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $this->query(
            "UPDATE {$this->table} 
             SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL 
             WHERE id = ?",
            [$passwordHash, $user['id']]
        );
        
        return true;
    }
    
    // Verificar si el usuario tiene acceso a un evento
    public function hasAccessToEvent($userId, $eventId) {
        $sql = "SELECT COUNT(*) as count 
                FROM purchases 
                WHERE user_id = ? 
                AND event_id = ? 
                AND status = 'completed'
                AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $this->query($sql, [$userId, $eventId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    // Obtener eventos comprados por el usuario
    public function getPurchasedEvents($userId) {
        $sql = "SELECT e.*, p.purchased_at, p.expires_at, p.access_token
                FROM events e
                INNER JOIN purchases p ON e.id = p.event_id
                WHERE p.user_id = ? AND p.status = 'completed'
                ORDER BY p.purchased_at DESC";
        
        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetchAll();
    }
    
    // Enviar email de verificación
    private function sendVerificationEmail($email, $token) {
        $verifyUrl = getenv('APP_URL') . "/verify-email?token=" . $token;
        
        $subject = "Verifica tu cuenta";
        $message = "
            <html>
            <body>
                <h2>Bienvenido a nuestra plataforma de streaming</h2>
                <p>Por favor verifica tu cuenta haciendo clic en el siguiente enlace:</p>
                <a href='{$verifyUrl}'>Verificar Email</a>
                <p>Este enlace expira en 24 horas.</p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@tu-dominio.com\r\n";
        
        mail($email, $subject, $message, $headers);
    }
    
    // Enviar email de reset de contraseña
    private function sendPasswordResetEmail($email, $token) {
        $resetUrl = getenv('APP_URL') . "/reset-password?token=" . $token;
        
        $subject = "Restablecer contraseña";
        $message = "
            <html>
            <body>
                <h2>Solicitud de restablecimiento de contraseña</h2>
                <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
                <a href='{$resetUrl}'>Restablecer Contraseña</a>
                <p>Este enlace expira en 1 hora.</p>
                <p>Si no solicitaste este cambio, ignora este email.</p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@tu-dominio.com\r\n";
        
        mail($email, $subject, $message, $headers);
    }
    
    // Obtener estadísticas del usuario
    public function getStats($userId) {
        $sql = "SELECT 
                    COUNT(DISTINCT p.event_id) as events_purchased,
                    SUM(p.amount) as total_spent,
                    COUNT(DISTINCT a.id) as total_views
                FROM users u
                LEFT JOIN purchases p ON u.id = p.user_id AND p.status = 'completed'
                LEFT JOIN analytics a ON u.id = a.user_id AND a.action = 'view_start'
                WHERE u.id = ?
                GROUP BY u.id";
        
        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetch();
    }
}
