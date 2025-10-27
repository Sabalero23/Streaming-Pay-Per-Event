<?php
// api/admin/kill_session.php
// API para que admin expulse sesiones manualmente

header('Content-Type: application/json');
session_start();

// Verificar que es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $session_id = $data['session_id'] ?? 0;
    
    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => 'Session ID requerido']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Eliminar la sesión
    $stmt = $db->prepare("DELETE FROM active_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Sesión eliminada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión no encontrada'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en kill_session: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}