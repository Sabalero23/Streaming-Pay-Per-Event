<?php
// src/Models/Event.php

require_once __DIR__ . '/../../config/database.php';

class Event extends Model {
    protected $table = 'events';
    
    public function __construct() {
        parent::__construct();
    }
    
    // MÉTODO CORREGIDO: Crear nuevo evento
    public function createEvent($data) {
        try {
            // Generar stream key único
            $streamKey = $this->generateStreamKey();
            
            // Preparar datos con valores por defecto
            $sql = "INSERT INTO events (
                title, 
                description, 
                category, 
                thumbnail_url, 
                price, 
                currency, 
                stream_key, 
                stream_url, 
                scheduled_start, 
                enable_recording, 
                enable_chat, 
                enable_dvr, 
                created_by,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['category'] ?? null,
                $data['thumbnail_url'] ?? null,
                $data['price'],
                $data['currency'] ?? 'ARS',
                $streamKey,
                $data['stream_url'] ?? null,
                $data['scheduled_start'],
                $data['enable_recording'] ?? 1,
                $data['enable_chat'] ?? 1,
                $data['enable_dvr'] ?? 0,
                $data['created_by']
            ]);
            
            if (!$result) {
                throw new Exception("Error al insertar el evento en la base de datos");
            }
            
            $eventId = $this->db->lastInsertId();
            
            // Crear configuración de streaming por defecto
            try {
                $this->createDefaultStreamSettings($eventId);
            } catch (Exception $e) {
                // Si falla la configuración de streaming, continuar igual
                error_log("Error al crear stream settings: " . $e->getMessage());
            }
            
            return [
                'id' => $eventId,
                'stream_key' => $streamKey
            ];
        } catch (PDOException $e) {
            error_log("Error en createEvent: " . $e->getMessage());
            throw new Exception("Error al crear el evento: " . $e->getMessage());
        }
    }
    
    // Generar stream key único
    private function generateStreamKey() {
        do {
            $key = 'sk_' . bin2hex(random_bytes(16));
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE stream_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch();
        } while ($result['count'] > 0);
        
        return $key;
    }
    
    // Crear configuración de streaming por defecto
    private function createDefaultStreamSettings($eventId) {
        try {
            $sql = "INSERT INTO stream_settings (event_id, video_bitrates, audio_bitrate) 
                    VALUES (?, ?, ?)";
            
            $videoBitrates = json_encode(['1080p', '720p', '480p', '360p']);
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId, $videoBitrates, 128]);
        } catch (PDOException $e) {
            // Si la tabla stream_settings no existe, ignorar
            error_log("Stream settings error (ignorado): " . $e->getMessage());
        }
    }
    
    // MÉTODO AGREGADO: Obtener todos los eventos con filtros
    public function getAllEvents($category = '', $status = '') {
        $sql = "SELECT e.*, u.full_name as organizer_name,
                    (SELECT COUNT(*) FROM purchases p WHERE p.event_id = e.id AND p.status = 'completed') as tickets_sold
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($category)) {
            $sql .= " AND e.category = ?";
            $params[] = $category;
        }
        
        if (!empty($status)) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY 
                    CASE 
                        WHEN e.status = 'live' THEN 1
                        WHEN e.status = 'scheduled' THEN 2
                        ELSE 3
                    END,
                    e.scheduled_start ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Obtener evento por stream key
    public function findByStreamKey($streamKey) {
        $sql = "SELECT * FROM {$this->table} WHERE stream_key = ? LIMIT 1";
        $stmt = $this->query($sql, [$streamKey]);
        return $stmt->fetch();
    }
    
    // Obtener eventos próximos
    public function getUpcomingEvents($limit = 10) {
        $sql = "SELECT e.*, u.full_name as organizer_name,
                    (SELECT COUNT(*) FROM purchases p WHERE p.event_id = e.id AND p.status = 'completed') as tickets_sold
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.scheduled_start > NOW() 
                AND e.status IN ('scheduled', 'live')
                ORDER BY e.scheduled_start ASC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$limit]);
        return $stmt->fetchAll();
    }
    
    // Obtener eventos en vivo
    public function getLiveEvents() {
        $sql = "SELECT e.*, u.full_name as organizer_name,
                    (SELECT COUNT(*) FROM active_sessions a WHERE a.event_id = e.id) as current_viewers
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.status = 'live'
                ORDER BY e.actual_start DESC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }
    
    // Iniciar transmisión
    public function startStream($eventId) {
        $event = $this->findById($eventId);
        
        if (!$event) {
            throw new Exception("Evento no encontrado");
        }
        
        if ($event['status'] === 'live') {
            throw new Exception("El evento ya está en vivo");
        }
        
        $sql = "UPDATE {$this->table} 
                SET status = 'live', actual_start = NOW() 
                WHERE id = ?";
        
        $this->query($sql, [$eventId]);
        
        // Notificar a usuarios que compraron el evento
        $this->notifyPurchasers($eventId);
        
        return true;
    }
    
    // Finalizar transmisión
    public function endStream($eventId) {
        $event = $this->findById($eventId);
        
        if (!$event) {
            throw new Exception("Evento no encontrado");
        }
        
        $sql = "UPDATE {$this->table} 
                SET status = 'ended', actual_end = NOW() 
                WHERE id = ?";
        
        $this->query($sql, [$eventId]);
        
        // Cerrar todas las sesiones activas
        $this->query("DELETE FROM active_sessions WHERE event_id = ?", [$eventId]);
        
        // Iniciar procesamiento de grabación si está habilitado
        if ($event['enable_recording']) {
            $this->processRecording($eventId);
        }
        
        return true;
    }
    
    // Actualizar contador de espectadores máximos
    public function updateMaxViewers($eventId, $currentViewers) {
        $sql = "UPDATE {$this->table} 
                SET max_viewers = GREATEST(max_viewers, ?) 
                WHERE id = ?";
        
        $this->query($sql, [$currentViewers, $eventId]);
    }
    
    // Obtener estadísticas del evento
    public function getStats($eventId) {
        $sql = "SELECT 
                    e.*,
                    COUNT(DISTINCT p.id) as total_purchases,
                    SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as total_revenue,
                    COUNT(DISTINCT a.user_id) as unique_viewers,
                    MAX(e.max_viewers) as peak_viewers,
                    AVG(CASE WHEN a.action = 'view_duration' THEN JSON_EXTRACT(a.details, '$.seconds') END) as avg_watch_time
                FROM {$this->table} e
                LEFT JOIN purchases p ON e.id = p.event_id
                LEFT JOIN analytics a ON e.id = a.event_id
                WHERE e.id = ?
                GROUP BY e.id";
        
        $stmt = $this->query($sql, [$eventId]);
        return $stmt->fetch();
    }
    
    // Buscar eventos
    public function search($query, $category = null, $status = null, $limit = 20) {
        $sql = "SELECT e.*, u.full_name as organizer_name
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($query) {
            $sql .= " AND (e.title LIKE ? OR e.description LIKE ?)";
            $searchTerm = "%{$query}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($category) {
            $sql .= " AND e.category = ?";
            $params[] = $category;
        }
        
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY e.scheduled_start DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Notificar a compradores cuando el evento inicia
    private function notifyPurchasers($eventId) {
        $sql = "SELECT u.email, u.full_name, e.title, e.id, p.access_token
                FROM purchases p
                INNER JOIN users u ON p.user_id = u.id
                INNER JOIN events e ON p.event_id = e.id
                WHERE p.event_id = ? AND p.status = 'completed'";
        
        $stmt = $this->query($sql, [$eventId]);
        $purchasers = $stmt->fetchAll();
        
        foreach ($purchasers as $purchaser) {
            $this->sendEventStartEmail($purchaser);
        }
    }
    
    // Enviar email cuando inicia el evento
    private function sendEventStartEmail($data) {
        $watchUrl = getenv('APP_URL') . "/watch/{$data['id']}?token={$data['access_token']}";
        
        $subject = "¡{$data['title']} está en vivo!";
        $message = "
            <html>
            <body>
                <h2>Hola {$data['full_name']},</h2>
                <p>El evento <strong>{$data['title']}</strong> acaba de comenzar.</p>
                <p><a href='{$watchUrl}' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Ahora</a></p>
                <p>Este enlace es personal e intransferible.</p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@tu-dominio.com\r\n";
        
        mail($data['email'], $subject, $message, $headers);
    }
    
    // Iniciar procesamiento de grabación
    private function processRecording($eventId) {
        // Esto se ejecutará en segundo plano mediante un script
        $cmd = "php " . __DIR__ . "/../../scripts/process_recording.php {$eventId} > /dev/null 2>&1 &";
        exec($cmd);
    }
    
    // Obtener URL de HLS
    public function getHlsUrl($eventId) {
        $config = require __DIR__ . '/../../config/streaming.php';
        $baseUrl = $config['hls']['base_url'];
        return "{$baseUrl}/{$eventId}/index.m3u8";
    }
    
    // Validar que el evento esté listo para transmitir
    public function validateForStreaming($streamKey) {
        $event = $this->findByStreamKey($streamKey);
        
        if (!$event) {
            return ['valid' => false, 'message' => 'Stream key inválido'];
        }
        
        if ($event['status'] === 'cancelled') {
            return ['valid' => false, 'message' => 'Evento cancelado'];
        }
        
        if ($event['status'] === 'ended') {
            return ['valid' => false, 'message' => 'Evento finalizado'];
        }
        
        return ['valid' => true, 'event' => $event];
    }
}