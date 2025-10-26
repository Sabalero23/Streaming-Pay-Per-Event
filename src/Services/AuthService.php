<?php
// src/Services/AuthService.php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService {
    private $secret;
    private $algorithm;
    private $expiration;
    
    public function __construct() {
        $config = require __DIR__ . '/../../config/streaming.php';
        $this->secret = $config['tokens']['secret'];
        $this->algorithm = $config['tokens']['algorithm'];
        $this->expiration = $config['tokens']['expiration'];
    }
    
    // Generar JWT token
    public function generateToken($userId, $email, $role = 'user') {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->expiration;
        
        $payload = [
            'iss' => getenv('APP_URL'),
            'aud' => getenv('APP_URL'),
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'email' => $email,
            'role' => $role
        ];
        
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }
    
    // Validar JWT token
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return [
                'valid' => true,
                'data' => (array) $decoded
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Generar token de acceso para un evento específico
    public function generateEventAccessToken($userId, $eventId, $purchaseId) {
        $accessToken = bin2hex(random_bytes(32));
        
        // Guardar en Redis para validación rápida
        $redis = $this->getRedisConnection();
        $key = "event_access:{$accessToken}";
        $data = json_encode([
            'user_id' => $userId,
            'event_id' => $eventId,
            'purchase_id' => $purchaseId,
            'created_at' => time()
        ]);
        
        $redis->setex($key, 86400, $data); // 24 horas
        
        return $accessToken;
    }
    
    // Validar token de acceso a evento
    public function validateEventAccessToken($token) {
        $redis = $this->getRedisConnection();
        $key = "event_access:{$token}";
        $data = $redis->get($key);
        
        if (!$data) {
            return ['valid' => false, 'error' => 'Token inválido o expirado'];
        }
        
        return [
            'valid' => true,
            'data' => json_decode($data, true)
        ];
    }
    
    // Iniciar sesión para ver un evento
    public function startViewingSession($userId, $eventId, $ipAddress, $userAgent) {
        $db = Database::getInstance()->getConnection();
        
        // Verificar si ya hay una sesión activa para este usuario y evento
        $sql = "SELECT * FROM active_sessions 
                WHERE user_id = ? AND event_id = ? 
                AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $eventId]);
        $existingSession = $stmt->fetch();
        
        if ($existingSession) {
            // Si la IP es diferente, bloquear
            if ($existingSession['ip_address'] !== $ipAddress) {
                throw new Exception("Ya existe una sesión activa en otro dispositivo");
            }
            
            // Actualizar sesión existente
            return $existingSession['session_token'];
        }
        
        // Crear nueva sesión
        $sessionToken = bin2hex(random_bytes(32));
        $deviceInfo = $this->extractDeviceInfo($userAgent);
        
        $sql = "INSERT INTO active_sessions 
                (user_id, event_id, session_token, device_info, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                session_token = VALUES(session_token),
                ip_address = VALUES(ip_address),
                last_heartbeat = NOW()";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userId, 
            $eventId, 
            $sessionToken, 
            json_encode($deviceInfo), 
            $ipAddress, 
            $userAgent
        ]);
        
        // Guardar en Redis para validación rápida
        $this->cacheActiveSession($sessionToken, $userId, $eventId, $ipAddress);
        
        return $sessionToken;
    }
    
    // Validar sesión de visualización
    public function validateViewingSession($sessionToken, $ipAddress) {
        // Primero intentar desde Redis (más rápido)
        $redis = $this->getRedisConnection();
        $key = "session:{$sessionToken}";
        $cached = $redis->get($key);
        
        if ($cached) {
            $data = json_decode($cached, true);
            
            // Validar IP
            if ($data['ip_address'] !== $ipAddress) {
                return [
                    'valid' => false,
                    'error' => 'Sesión iniciada desde otra IP'
                ];
            }
            
            return [
                'valid' => true,
                'data' => $data
            ];
        }
        
        // Si no está en cache, buscar en base de datos
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM active_sessions 
                WHERE session_token = ? 
                AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return ['valid' => false, 'error' => 'Sesión inválida o expirada'];
        }
        
        if ($session['ip_address'] !== $ipAddress) {
            return ['valid' => false, 'error' => 'Sesión iniciada desde otra IP'];
        }
        
        // Re-cachear
        $this->cacheActiveSession(
            $sessionToken, 
            $session['user_id'], 
            $session['event_id'], 
            $session['ip_address']
        );
        
        return [
            'valid' => true,
            'data' => $session
        ];
    }
    
    // Heartbeat - mantener sesión activa
    public function heartbeat($sessionToken, $ipAddress) {
        $validation = $this->validateViewingSession($sessionToken, $ipAddress);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Actualizar timestamp
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE active_sessions 
                SET last_heartbeat = NOW() 
                WHERE session_token = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$sessionToken]);
        
        // Actualizar cache
        $redis = $this->getRedisConnection();
        $key = "session:{$sessionToken}";
        $redis->expire($key, 300); // 5 minutos
        
        return ['valid' => true];
    }
    
    // Terminar sesión
    public function endViewingSession($sessionToken) {
        $db = Database::getInstance()->getConnection();
        $sql = "DELETE FROM active_sessions WHERE session_token = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$sessionToken]);
        
        // Eliminar de cache
        $redis = $this->getRedisConnection();
        $redis->del("session:{$sessionToken}");
        
        return true;
    }
    
    // Cachear sesión activa en Redis
    private function cacheActiveSession($sessionToken, $userId, $eventId, $ipAddress) {
        $redis = $this->getRedisConnection();
        $key = "session:{$sessionToken}";
        $data = json_encode([
            'user_id' => $userId,
            'event_id' => $eventId,
            'ip_address' => $ipAddress,
            'timestamp' => time()
        ]);
        
        $redis->setex($key, 300, $data); // 5 minutos
    }
    
    // Extraer información del dispositivo
    private function extractDeviceInfo($userAgent) {
        $device = [
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'device_type' => 'desktop'
        ];
        
        // Detectar navegador
        if (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $device['browser'] = 'Firefox ' . $matches[1];
        } elseif (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $device['browser'] = 'Chrome ' . $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $device['browser'] = 'Safari ' . $matches[1];
        }
        
        // Detectar OS
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $device['os'] = 'Windows ' . $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
            $device['os'] = 'macOS ' . str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $device['os'] = 'Android ' . $matches[1];
            $device['device_type'] = 'mobile';
        } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            $device['os'] = 'iOS ' . str_replace('_', '.', $matches[1]);
            $device['device_type'] = 'mobile';
        }
        
        return $device;
    }
    
    // Obtener conexión Redis
    private function getRedisConnection() {
        static $redis = null;
        
        if ($redis === null) {
            $redis = new Redis();
            $redis->connect(
                getenv('REDIS_HOST') ?: 'localhost',
                getenv('REDIS_PORT') ?: 6379
            );
            
            $redisPass = getenv('REDIS_PASSWORD');
            if ($redisPass) {
                $redis->auth($redisPass);
            }
        }
        
        return $redis;
    }
    
    // Obtener conteo de espectadores activos
    public function getActiveViewersCount($eventId) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM active_sessions 
                WHERE event_id = ? 
                AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$eventId]);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
}
