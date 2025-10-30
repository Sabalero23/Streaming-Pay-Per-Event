<?php
// admin/upload_logo.php
// Script para manejar la subida del logo

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];
    
    // Validar el archivo
    $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PNG o JPG']);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'El archivo es muy grande (máximo 2MB)']);
        exit;
    }
    
    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../public/assets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo-' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Mover el archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Guardar en la base de datos
        $db = Database::getInstance()->getConnection();
        $logoPath = '/public/assets/' . $filename;
        
        $stmt = $db->prepare("
            INSERT INTO system_config (config_key, config_value, updated_by, updated_at) 
            VALUES ('site_logo_path', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()
        ");
        $stmt->execute([$logoPath, $_SESSION['user_id'], $logoPath, $_SESSION['user_id']]);
        
        // Eliminar logo anterior si existe
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = 'site_logo_path_old'");
        $stmt->execute();
        $oldLogo = $stmt->fetchColumn();
        
        if ($oldLogo && file_exists(__DIR__ . '/..' . $oldLogo)) {
            unlink(__DIR__ . '/..' . $oldLogo);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Logo subido correctamente',
            'path' => $logoPath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
}