<?php
// public/header.php
// Header compartido para todo el sitio público

// Cargar configuración
require_once __DIR__ . '/../src/Helpers/SiteConfig.php';
$site_name = SiteConfig::siteName();
$site_tagline = SiteConfig::get('site_tagline', 'Vive la Emoción del Deporte en Vivo');
$site_logo = SiteConfig::get('site_logo_path', '');

// Verificar si el usuario tiene acceso al panel admin
$showAdminAccess = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $showAdminAccess = in_array($_SESSION['user_role'], ['admin', 'streamer']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? htmlspecialchars($site_name) ?> - <?= htmlspecialchars($site_tagline) ?></title>
    <meta name="description" content="<?= htmlspecialchars($site_tagline) ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .public-header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
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
            padding: 0 20px;
            height: 70px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #e50914;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            transition: opacity 0.3s;
        }
        
        .logo:hover {
            opacity: 0.8;
        }
        
        .logo-icon {
            width: 150px;
            height: 45px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .logo-icon-emoji {
            font-size: 45px;
            line-height: 1;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .logo-name {
            font-size: 22px;
            font-weight: 700;
            color: #222;
        }
        
        .logo-tagline {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #333;
            font-size: 28px;
            cursor: pointer;
            padding: 5px;
            order: -1;
            transition: color 0.3s;
        }
        
        .menu-toggle:hover {
            color: #e50914;
        }
        
        .public-nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .public-nav a {
            color: #666;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 15px;
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .public-nav a:hover {
            background: #f5f5f5;
            color: #333;
        }
        
        .public-nav a.active {
            background: #fee;
            color: #e50914;
        }
        
        .public-nav a.btn-login {
            color: #e50914;
            border: 1px solid #e0e0e0;
        }
        
        .public-nav a.btn-login:hover {
            background: #fee;
            border-color: #e50914;
        }
        
        .public-nav a.btn-register {
            background: #e50914;
            color: white;
            font-weight: 600;
        }
        
        .public-nav a.btn-register:hover {
            background: #b8070f;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(229, 9, 20, 0.3);
        }
        
        .public-nav a.btn-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .public-nav a.btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .public-nav a.btn-logout {
            color: #e50914;
            border: 1px solid #e0e0e0;
        }
        
        .public-nav a.btn-logout:hover {
            background: #fee;
            border-color: #e50914;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: #f5f5f5;
            border-radius: 20px;
            font-size: 14px;
            color: #333;
            margin-left: 5px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: white;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .user-name {
            font-weight: 500;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .main-content {
            flex: 1;
            width: 100%;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Mobile Styles */
        @media (max-width: 968px) {
            .logo-tagline {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                height: 60px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .logo-icon {
                width: 36px;
                height: 36px;
            }
            
            .logo-icon-emoji {
                font-size: 36px;
            }
            
            .logo-name {
                font-size: 18px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .public-nav {
                position: fixed;
                top: 60px;
                left: -100%;
                width: 85%;
                max-width: 320px;
                height: calc(100vh - 60px);
                background: white;
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
                overflow-y: auto;
                gap: 0;
            }
            
            .public-nav.active {
                left: 0;
            }
            
            .public-nav a {
                width: 100%;
                padding: 14px 16px;
                border-radius: 8px;
                margin-bottom: 4px;
                justify-content: flex-start;
            }
            
            .user-info {
                width: 100%;
                flex-direction: row;
                justify-content: flex-start;
                margin: 10px 0;
                padding: 12px 16px;
                border-radius: 8px;
                background: #f5f5f5;
            }
            
            .user-name {
                max-width: none;
            }
            
            .public-nav .btn-logout {
                margin-top: 10px;
                border-top: 1px solid #e0e0e0;
                padding-top: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .header-container {
                padding: 0 15px;
            }
            
            .logo {
                font-size: 18px;
            }
            
            .logo-icon {
                width: 32px;
                height: 32px;
            }
            
            .logo-icon-emoji {
                font-size: 32px;
            }
            
            .logo-name {
                font-size: 16px;
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
            backdrop-filter: blur(2px);
        }
        
        .nav-overlay.active {
            display: block;
        }
        
        /* Animaciones suaves */
        @media (prefers-reduced-motion: no-preference) {
            .public-nav a,
            .menu-toggle,
            .logo {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        }
    </style>
</head>
<body>
    <header class="public-header">
        <div class="header-container">
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                ☰
            </button>
            
            <a href="/public/" class="logo">
                <?php if (!empty($site_logo) && file_exists($_SERVER['DOCUMENT_ROOT'] . $site_logo)): ?>
                    <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="logo-icon">
                <?php else: ?>
                    <span class="logo-icon-emoji">🎥</span>
                <?php endif; ?>
                <div class="logo-text">
                    <span class="logo-tagline"><?= htmlspecialchars($site_tagline) ?></span>
                </div>
            </a>
            
            <nav class="public-nav" id="publicNav">
                <a href="/public/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    🏠 Inicio
                </a>
                <a href="/public/events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
                    🎬 Eventos
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($showAdminAccess): ?>
                        <a href="/admin/dashboard.php" class="btn-admin">
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                🔐 Panel Admin
                            <?php else: ?>
                                🎬 Panel Streamer
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span>
                    </div>
                    
                    <a href="/public/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                        👤 Mi Cuenta
                    </a>
                    <a href="/public/logout.php" class="btn-logout">
                        🚪 Salir
                    </a>
                <?php else: ?>
                    <a href="/public/login.php" class="btn-login">🔓 Iniciar Sesión</a>
                    <a href="/public/register.php" class="btn-register">✨ Registrarse</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <div class="nav-overlay" id="navOverlay"></div>
    
    <div class="main-content">