<?php
// admin/header.php
// Header compartido para todo el panel admin

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Panel Admin' ?> - Streaming Platform</title>
    
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
            background: #2c3e50;
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
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.1);
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
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
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
                background: #2c3e50;
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
                display: none;
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
                <?= $page_icon ?? '🎛️' ?> 
                <span><?= $page_title ?? 'Panel Admin' ?></span>
            </h1>
            
            <nav class="admin-nav" id="adminNav">
                <a href="/admin/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    📊 Dashboard
                </a>
                <a href="/admin/events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
                    🎬 Eventos
                </a>
                <a href="/admin/users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                    👥 Usuarios
                </a>
                <a href="/admin/purchases.php" class="<?= basename($_SERVER['PHP_SELF']) === 'purchases.php' ? 'active' : '' ?>">
                    💰 Compras
                </a>
                <a href="/admin/analytics.php" class="<?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                    📈 Analíticas
                </a>
                <a href="/public/" style="border-left: 1px solid rgba(255,255,255,0.2); margin-left: 10px; padding-left: 15px;">
                    🌐 Ver Sitio
                </a>
                <a href="/public/logout.php" style="background: rgba(244, 67, 54, 0.2);">
                    🚪 Salir
                </a>
            </nav>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
            </div>
        </div>
    </div>
    
    <div class="nav-overlay" id="navOverlay"></div>
    
    <div class="main-content">
        <div class="container">
