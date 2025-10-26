<?php
// public/watch.php
// Wrapper para redirigir al reproductor con validación

session_start();

$eventId = null;
$token = null;

// Extraer ID del evento de la URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\/watch\/(\d+)/', $path, $matches)) {
    $eventId = $matches[1];
}

// Obtener token de query string
$token = $_GET['token'] ?? null;

// Si no hay ID o token, redirigir al inicio
if (!$eventId || !$token) {
    header('Location: /');
    exit;
}

// Redirigir al reproductor
header('Location: /player.php?event_id=' . $eventId . '&token=' . $token);
exit;
