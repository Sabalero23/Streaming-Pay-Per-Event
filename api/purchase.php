<?php
// api/purchase.php
// API para iniciar proceso de compra con MercadoPago

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Debes iniciar sesión para comprar');
    }
    
    $userId = $_SESSION['user_id'];
    $eventId = $_POST['event_id'] ?? null;
    
    if (!$eventId) {
        throw new Exception('ID de evento requerido');
    }
    
    $paymentService = new PaymentService();
    $preference = $paymentService->createMercadoPagoPreference($userId, $eventId);
    
    // Redirigir al checkout de MercadoPago
    header('Location: ' . $preference['init_point']);
    exit;
    
} catch (Exception $e) {
    // Redirigir con error
    header('Location: /event.php?id=' . ($eventId ?? '') . '&error=' . urlencode($e->getMessage()));
    exit;
}
