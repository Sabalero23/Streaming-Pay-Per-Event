<?php
// src/Services/AuthService.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService {
    private $jwtSecret;
    private $jwtAlgorithm;
    private $jwtExpiry;
    private $db;
    
    public function __construct() {
        // Cargar configuración JWT desde variables de entorno con valores por defecto
        $this->jwtSecret = getEnvVar('JWT_SECRET', 'tu_clave_secreta_super_segura_cambiar_en_produccion');
        $this->jwtAlgorithm = getEnvVar('JWT_ALGORITHM', 'HS256');
        $this->jwtExpiry = (int)getEnvVar('JWT_EXPIRY', 86400); // 24 horas por defecto
        
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generar token JWT
     */
    public function generateToken($userId, $email, $role = 'user') {
        $issuedAt = time();
        $expire = $issuedAt + $this->jwtExpiry;
        
        $payload = [
            'iat' => $issuedAt,              // Issued at
            'exp' => $expire,                // Expiration
            'iss' => getEnvVar('APP_URL', 'streaming-platform'), // Issuer
            'data' => [
                'user_id' => $userId,
                'email' => $email,
                'role' => $role
            ]
        ];
        
        try {
            return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
        } catch (Exception $e) {
            error_log("Error generating JWT token: " . $e->getMessage());
            throw new Exception("No se pudo generar el token de autenticación");
        }
    }
    
    /**
     * Validar token JWT
     */
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            return (array)$decoded->data;
        } catch (Exception $e) {
            error_log("Error validating JWT token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Refrescar token
     */
    public function refreshToken($oldToken) {
        $decoded = $this->validateToken($oldToken);
        
        if (!$decoded) {
            throw new Exception("Token inválido");
        }
        
        return $this->generateToken(
            $decoded['user_id'],
            $decoded['email'],
            $decoded['role']
        );
    }
    
    /**
     * Verificar si el usuario tiene una sesión activa en otro dispositivo
     */
    public function checkActiveSession($userId, $currentToken) {
        try {
            $sql = "SELECT session_token, device_info, last_activity 
                    FROM user_sessions 
                    WHERE user_id = ? 
                    AND is_active = 1 
                    AND expires_at > NOW()
                    ORDER BY last_activity DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return false; // No hay sesión activa
            }
            
            // Si el token es diferente, hay otra sesión activa
            return $session['session_token'] !== $currentToken ? $session : false;
            
        } catch (PDOException $e) {
            error_log("Error checking active session: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear sesión de usuario
     */
    public function createSession($userId, $token, $deviceInfo = null) {
        try {
            // Invalidar sesiones anteriores del mismo usuario
            $this->invalidateUserSessions($userId);
            
            $sql = "INSERT INTO user_sessions 
                    (user_id, session_token, device_info, ip_address, user_agent, expires_at, created_at, last_activity) 
                    VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $token,
                $deviceInfo,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $this->jwtExpiry
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error creating session: " . $e->getMessage());
            throw new Exception("No se pudo crear la sesión");
        }
    }
    
    /**
     * Actualizar última actividad de la sesión
     */
    public function updateSessionActivity($token) {
        try {
            $sql = "UPDATE user_sessions 
                    SET last_activity = NOW() 
                    WHERE session_token = ? 
                    AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            
        } catch (PDOException $e) {
            error_log("Error updating session activity: " . $e->getMessage());
        }
    }
    
    /**
     * Invalidar sesiones de un usuario
     */
    public function invalidateUserSessions($userId, $exceptToken = null) {
        try {
            if ($exceptToken) {
                $sql = "UPDATE user_sessions 
                        SET is_active = 0 
                        WHERE user_id = ? 
                        AND session_token != ?";
                $params = [$userId, $exceptToken];
            } else {
                $sql = "UPDATE user_sessions 
                        SET is_active = 0 
                        WHERE user_id = ?";
                $params = [$userId];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error invalidating sessions: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout($token) {
        try {
            $sql = "UPDATE user_sessions 
                    SET is_active = 0, 
                        logout_at = NOW() 
                    WHERE session_token = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error during logout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpiar sesiones expiradas
     */
    public function cleanExpiredSessions() {
        try {
            $sql = "UPDATE user_sessions 
                    SET is_active = 0 
                    WHERE expires_at < NOW() 
                    AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error cleaning expired sessions: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generar token de "recordarme"
     */
    public function generateRememberToken($userId) {
        return bin2hex(random_bytes(32)) . '-' . $userId . '-' . time();
    }
    
    /**
     * Validar token de "recordarme"
     */
    public function validateRememberToken($token) {
        try {
            $parts = explode('-', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            list($hash, $userId, $timestamp) = $parts;
            
            // Verificar que no haya expirado (30 días)
            if (time() - $timestamp > 2592000) {
                return false;
            }
            
            // Verificar en base de datos
            $sql = "SELECT id, email, role FROM users WHERE id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error validating remember token: " . $e->getMessage());
            return false;
        }
    }
}