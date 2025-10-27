<?php
// api/admin/sessions_count.php
// API para obtener número de sesiones activas (para badge en header)

header('Content-Type: application/json');
session_start();

// Verificar que es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Contar sesiones activas (últimos 2 minutos)
    $stmt = $db->query("
        SELECT 
            COUNT(*) as active_sessions,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT event_id) as active_events
        FROM active_sessions
        WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'active_sessions' => (int)$stats['active_sessions'],
        'unique_users' => (int)$stats['unique_users'],
        'active_events' => (int)$stats['active_events'],
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Error en sessions_count: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}