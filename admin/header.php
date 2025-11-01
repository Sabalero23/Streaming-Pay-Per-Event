<?php
// admin/header.php
// Header compartido para todo el panel admin

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

// CAMBIO CRÍTICO: Permitir admin, streamer y moderator
$allowedRoles = ['admin', 'streamer', 'moderator'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /public/profile.php');
    exit;
}

// Variables útiles
$isAdmin = $_SESSION['user_role'] === 'admin';
$isStreamer = $_SESSION['user_role'] === 'streamer';
$isModerator = $_SESSION['user_role'] === 'moderator';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Panel Admin' ?> - Streaming Platform</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .admin-header {
            background: <?= $isStreamer ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#2c3e50' ?>;
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .admin-header h1 {
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
        }
        
        .admin-nav {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 14px;
            white-space: nowrap;
            position: relative;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.2);
        }
        
        /* Badge de notificación para sesiones activas */
        .nav-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(255,255,255,0.15);
            border-radius: 5px;
            font-size: 14px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid rgba(255,255,255,0.5);
        }
        
        .user-role-badge {
            font-size: 11px;
            padding: 3px 8px;
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            font-weight: 600;
        }
        
        .main-content {
            flex: 1;
            width: 100%;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 18px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .admin-nav {
                position: fixed;
                top: 60px;
                left: -100%;
                width: 80%;
                max-width: 300px;
                height: calc(100vh - 60px);
                background: <?= $isStreamer ? '#667eea' : '#2c3e50' ?>;
                flex-direction: column;
                padding: 20px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            
            .admin-nav.active {
                left: 0;
            }
            
            .admin-nav a {
                width: 100%;
                padding: 12px 15px;
                border-radius: 5px;
            }
            
            .user-info {
                width: 100%;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.2);
            }
            
            .container {
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 16px;
            }
            
            .admin-header h1 span {
                display: none;
            }
        }
        
        /* Overlay para cerrar menú móvil */
        .nav-overlay {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            height: calc(100vh - 60px);
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .nav-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-container">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                ☰
            </button>
            
            <h1>
                <?php 
                $icon = $page_icon ?? ($isStreamer ? '🎬' : '🎛️');
                // Detectar si es una clase de Font Awesome o un emoji
                if (strpos($icon, 'fa-') !== false || strpos($icon, 'fas ') !== false): 
                ?>
                    <i class="<?= $icon ?>"></i>
                <?php else: ?>
                    <?= $icon ?>
                <?php endif; ?>
                <span><?= $page_title ?? ($isStreamer ? 'Panel Streamer' : 'Panel Admin') ?></span>
            </h1>
            
            <nav class="admin-nav" id="adminNav">
                <a href="/admin/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    📊 Dashboard
                </a>
                <a href="/admin/events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
                    🎬 <?= $isStreamer ? 'Mis Eventos' : 'Eventos' ?>
                </a>
                
                <?php if ($isAdmin): ?>
                <a href="/admin/users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                    👥 Usuarios
                </a>
                <?php endif; ?>
                
                <a href="/admin/purchases.php" class="<?= basename($_SERVER['PHP_SELF']) === 'purchases.php' ? 'active' : '' ?>">
                    💰 <?= $isStreamer ? 'Mis Ventas' : 'Compras' ?>
                </a>
                
                <?php if ($isAdmin || $isStreamer): ?>
                <a href="/admin/analytics.php" class="<?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                    📈 Analíticas
                </a>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- NUEVO: Monitor de Sesiones (solo para admin) -->
                <a href="/admin/sessions_monitor.php" 
                   class="<?= basename($_SERVER['PHP_SELF']) === 'sessions_monitor.php' ? 'active' : '' ?>"
                   id="sessionsLink">
                    📡 Sesiones
                    <span class="nav-badge" id="sessionsBadge" style="display: none;">0</span>
                </a>
                
                <!-- NUEVO: Mensajes de Contacto (solo para admin) -->
                <a href="/admin/contact-messages.php" 
                   class="<?= basename($_SERVER['PHP_SELF']) === 'contact-messages.php' ? 'active' : '' ?>"
                   id="contactLink">
                    📬 Mensajes
                    <?php
                    // Contar mensajes nuevos
                    try {
                        $db_temp = Database::getInstance()->getConnection();
                        $stmt_temp = $db_temp->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
                        $newMessages = $stmt_temp ? $stmt_temp->fetchColumn() : 0;
                        if ($newMessages > 0) {
                            echo '<span class="nav-badge">' . $newMessages . '</span>';
                        }
                    } catch (Exception $e) {
                        // Tabla no existe aún, no mostrar badge
                    }
                    ?>
                </a>
                
                <!-- Configuración del Sistema (solo para admin) -->
                <a href="/admin/settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    ⚙️ Settings
                </a>
                <?php endif; ?>
                
                <a href="/public/" style="border-left: 1px solid rgba(255,255,255,0.2); margin-left: 10px; padding-left: 15px;">
                    🌐 Ver Sitio
                </a>
                <a href="/public/logout.php" style="background: rgba(244, 67, 54, 0.3);">
                    🚪 Salir
                </a>
                
                
            </nav>
        </div>
    </div>
    
    <div class="nav-overlay" id="navOverlay"></div>
    
    <div class="main-content">
        <div class="container">