<?php
// admin/ajax/get-message.php
// Endpoint para obtener detalles de un mensaje de contacto

session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar que sea admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$messageId = (int)($_GET['id'] ?? 0);

if ($messageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($message) {
        // Formatear fecha
        $message['created_at'] = date('d/m/Y H:i:s', strtotime($message['created_at']));
        
        // Escapar HTML
        $message['name'] = htmlspecialchars($message['name']);
        $message['email'] = htmlspecialchars($message['email']);
        $message['subject'] = htmlspecialchars($message['subject']);
        $message['message'] = htmlspecialchars($message['message']);
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Mensaje no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en get-message.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor'
    ]);
}