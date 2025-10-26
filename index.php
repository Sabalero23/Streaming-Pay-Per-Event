<?php
// index.php (en raíz del proyecto)
// Este archivo redirige todo a la carpeta public/

// Obtener la URI solicitada
$request_uri = $_SERVER['REQUEST_URI'];

// Remover el query string
$path = parse_url($request_uri, PHP_URL_PATH);

// Si se está accediendo a la raíz, redirigir a public/index.php
if ($path === '/' || $path === '') {
    require __DIR__ . '/public/index.php';
    exit;
}

// Verificar si el archivo existe en public/
$file = __DIR__ . '/public' . $path;

// Si es un archivo PHP y existe, ejecutarlo
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// Si es un archivo estático y existe, servirlo
if (file_exists($file) && is_file($file)) {
    // Determinar el tipo MIME
    $mime_types = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mime = $mime_types[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mime);
    readfile($file);
    exit;
}

// Si el archivo no existe, mostrar 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
        }
        h1 {
            font-size: 120px;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        a {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        a:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Página no encontrada</h2>
        <p>Lo sentimos, la página que buscas no existe.</p>
        <a href="/">Volver al inicio</a>
    </div>
</body>
</html>
