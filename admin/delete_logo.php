<?php
// admin/delete_logo.php
// Script para eliminar el logo

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    // Obtener ruta del logo actual
    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = 'site_logo_path'");
    $stmt->execute();
    $logoPath = $stmt->fetchColumn();
    
    if ($logoPath) {
        // Eliminar archivo físico
        $fullPath = __DIR__ . '/..' . $logoPath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Actualizar base de datos
        $stmt = $db->prepare("
            UPDATE system_config 
            SET config_value = '', updated_by = ?, updated_at = NOW() 
            WHERE config_key = 'site_logo_path'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Logo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay logo para eliminar']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}