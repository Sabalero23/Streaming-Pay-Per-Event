<?php
// admin/ajax/send-reply.php
// Endpoint para enviar respuesta a un mensaje de contacto

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Services/EmailService.php';

// Verificar que sea admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$messageId = (int)($_POST['message_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$userName = trim($_POST['user_name'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validaciones
if ($messageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de mensaje inválido']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email inválido']);
    exit;
}

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Asunto y mensaje son obligatorios']);
    exit;
}

if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'error' => 'La respuesta debe tener al menos 10 caracteres']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el mensaje existe
    $stmt = $db->prepare("SELECT id, name, email FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $contactMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contactMessage) {
        echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
        exit;
    }
    
    // Obtener nombre del admin
    $adminName = $_SESSION['user_name'] ?? 'Soporte';
    
    // Enviar email de respuesta
    try {
        $emailService = new EmailService();
        
        $emailSent = $emailService->sendContactReply(
            $email,
            $userName,
            $subject,
            $message,
            $adminName
        );
        
        if ($emailSent) {
            // Marcar mensaje como respondido
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$messageId]);
            
            // Registrar en logs (opcional)
            error_log("Respuesta enviada al mensaje #$messageId por " . $_SESSION['user_email']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Respuesta enviada exitosamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo enviar el email. Verifica la configuración SMTP.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error enviando respuesta al mensaje #$messageId: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al enviar el email: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en send-reply.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor'
    ]);
}