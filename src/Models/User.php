<?php
// src/Models/User.php

require_once __DIR__ . '/../../config/database.php';

class User extends Model {
    protected $table = 'users';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->query($sql, [$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Registrar nuevo usuario
     */
    public function register($email, $password, $fullName, $phone = null) {
        // Verificar si el email ya existe
        if ($this->findByEmail($email)) {
            throw new Exception("El email ya está registrado");
        }
        
        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Token de verificación
        $verificationToken = bin2hex(random_bytes(32));
        
        $data = [
            'email' => $email,
            'password_hash' => $passwordHash,
            'full_name' => $fullName,
            'phone' => $phone,
            'verification_token' => $verificationToken,
            'role' => 'user',
            'status' => 'active',
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $this->create($data);
        
        return $userId;
    }
    
    /**
     * Login de usuario
     */
    public function login($email, $password) {
        // Buscar usuario por email
        $user = $this->findByEmail($email);
        
        if (!$user) {
            throw new Exception("Credenciales inválidas");
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales inválidas");
        }
        
        // Verificar estado
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
    
    /**
     * Verificar email
     */
    public function verifyEmail($token) {
        $sql = "SELECT * FROM {$this->table} WHERE verification_token = ? LIMIT 1";
        $stmt = $this->query($sql, [$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Token inválido");
        }
        
        $this->query(
            "UPDATE {$this->table} SET email_verified = 1, verification_token = NULL WHERE id = ?",
            [$user['id']]
        );
        
        return true;
    }
    
    /**
     * Solicitar reset de contraseña
     */
    public function requestPasswordReset($email) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return true; // No revelar si existe
        }
        
        $resetToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->query(
            "UPDATE {$this->table} SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
            [$resetToken, $expires, $user['id']]
        );
        
        return true;
    }
    
    /**
     * Reset de contraseña
     */
    public function resetPassword($token, $newPassword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE reset_token = ? 
                AND reset_token_expires > NOW() 
                LIMIT 1";
        $stmt = $this->query($sql, [$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Token inválido o expirado");
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        
        $this->query(
            "UPDATE {$this->table} 
             SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL 
             WHERE id = ?",
            [$passwordHash, $user['id']]
        );
        
        return true;
    }
    
    /**
     * Verificar si el usuario tiene acceso a un evento
     */
    public function hasAccessToEvent($userId, $eventId) {
        $sql = "SELECT COUNT(*) as count 
                FROM purchases 
                WHERE user_id = ? 
                AND event_id = ? 
                AND status = 'completed'
                AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $this->query($sql, [$userId, $eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Obtener eventos comprados por el usuario
     */
    public function getPurchasedEvents($userId) {
        $sql = "SELECT e.*, p.purchased_at, p.expires_at, p.access_token
                FROM events e
                INNER JOIN purchases p ON e.id = p.event_id
                WHERE p.user_id = ? AND p.status = 'completed'
                ORDER BY p.purchased_at DESC";
        
        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    public function getStats($userId) {
        $sql = "SELECT 
                    COUNT(DISTINCT p.event_id) as events_purchased,
                    COALESCE(SUM(p.amount), 0) as total_spent,
                    COUNT(DISTINCT a.id) as total_views
                FROM users u
                LEFT JOIN purchases p ON u.id = p.user_id AND p.status = 'completed'
                LEFT JOIN analytics a ON u.id = a.user_id AND a.action = 'view_start'
                WHERE u.id = ?
                GROUP BY u.id";
        
        $stmt = $this->query($sql, [$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return [
                'events_purchased' => 0,
                'total_spent' => 0,
                'total_views' => 0
            ];
        }
        
        return $result;
    }
}