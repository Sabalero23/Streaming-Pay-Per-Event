<?php
// public/logout.php
// Cerrar sesión del usuario

session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destruir la sesión
session_destroy();

// Eliminar cookie de "remember me" si existe
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time()-42000, '/');
}

// Redirigir al inicio
header('Location: /public/?logout=1');
exit;
