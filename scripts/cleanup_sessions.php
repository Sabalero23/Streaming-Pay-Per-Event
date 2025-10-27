<?php
// scripts/cleanup_sessions.php
// Script para limpiar sesiones antiguas (ejecutar cada 5 minutos vía CRON)

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Eliminar sesiones con heartbeat mayor a 5 minutos
    $stmt = $db->prepare("
        DELETE FROM active_sessions 
        WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    // Limpiar conflictos antiguos (más de 7 días)
    $stmt = $db->prepare("
        DELETE FROM session_conflicts 
        WHERE conflict_time < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $deleted_conflicts = $stmt->rowCount();
    
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] Limpieza completada:\n";
    echo "- Sesiones eliminadas: $deleted\n";
    echo "- Conflictos eliminados: $deleted_conflicts\n";
    
    // Log para debugging
    error_log("Session cleanup: Eliminated $deleted sessions and $deleted_conflicts conflicts");
    
} catch (Exception $e) {
    error_log("Error en cleanup_sessions: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}