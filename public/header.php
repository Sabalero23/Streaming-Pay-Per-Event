<?php
// public/header.php
// Header compartido para todo el sitio público

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
    <title><?= $page_title ?? 'Streaming Platform' ?> - Eventos en Vivo</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .public-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
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
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 5px;
            order: -1;
        }
        
        .public-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .public-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 15px;
            white-space: nowrap;
        }
        
        .public-nav a:hover,
        .public-nav a.active {
            background: rgba(255,255,255,0.1);
        }
        
        .public-nav a.btn-login {
            background: rgba(255,255,255,0.2);
        }
        
        .public-nav a.btn-register {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        
        .public-nav a.btn-admin {
            background: rgba(244, 67, 54, 0.8);
            font-weight: bold;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .public-nav a.btn-admin:hover {
            background: rgba(244, 67, 54, 1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
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
            border: 2px solid rgba(255,255,255,0.3);
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
        @media (max-width: 768px) {
            .logo {
                font-size: 18px;
            }
            
            .logo span {
                display: none;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .public-nav {
                position: fixed;
                top: 60px;
                left: -100%;
                width: 80%;
                max-width: 300px;
                height: calc(100vh - 60px);
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.5);
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            
            .public-nav.active {
                left: 0;
            }
            
            .public-nav a {
                width: 100%;
                padding: 12px 15px;
            }
            
            .user-info {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                margin-top: 10px;
                padding-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.2);
            }
        }
        
        @media (max-width: 480px) {
            .public-header {
                padding: 10px 0;
            }
            
            .logo {
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
            background: rgba(0,0,0,0.7);
            z-index: 999;
        }
        
        .nav-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="public-header">
        <div class="header-container">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                ☰
            </button>
            
            <a href="/public/" class="logo">
                🎥 <span>Streaming Platform</span>
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
                                🔑 Panel Admin
                            <?php else: ?>
                                🎬 Panel Streamer
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span>
                    </div>
                    
                    <a href="/public/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                        👤 Mi Cuenta
                    </a>
                    <a href="/public/logout.php" style="background: rgba(244, 67, 54, 0.2);">
                        🚪 Salir
                    </a>
                <?php else: ?>
                    <a href="/public/login.php" class="btn-login">🔐 Iniciar Sesión</a>
                    <a href="/public/register.php" class="btn-register">✨ Registrarse</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <div class="nav-overlay" id="navOverlay"></div>
    
    <div class="main-content">
