<?php
// admin/settings.php
// Panel de configuración del sistema (solo admin)

session_start();

$page_title = "Configuración del Sistema";
$page_icon = "⚙️";

require_once __DIR__ . '/../src/Services/EmailService.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Solo admin puede acceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

$success_message = '';
$error_message = '';
$active_tab = $_GET['tab'] ?? 'branding';

// Función para obtener configuración
function getConfig($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['config_value'] : $default;
}

// Función para guardar configuración
function setConfig($db, $key, $value, $userId) {
    $stmt = $db->prepare("
        INSERT INTO system_config (config_key, config_value, updated_by, updated_at) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()
    ");
    return $stmt->execute([$key, $value, $userId, $value, $userId]);
}

// Procesar formulario de branding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'save_branding') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'site_name', $_POST['site_name'] ?? 'Eventix', $_SESSION['user_id']);
            setConfig($db, 'site_domain', $_POST['site_domain'] ?? 'www.eventix.com.ar', $_SESSION['user_id']);
            setConfig($db, 'site_tagline', $_POST['site_tagline'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'site_logo_path', $_POST['site_logo_path'] ?? '/assets/logo.png', $_SESSION['user_id']);
            setConfig($db, 'site_favicon', $_POST['site_favicon'] ?? '/assets/favicon.ico', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Configuración de marca guardada correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_contact') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'contact_email', $_POST['contact_email'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'contact_phone', $_POST['contact_phone'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'contact_whatsapp', $_POST['contact_whatsapp'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'contact_address', $_POST['contact_address'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'support_email', $_POST['support_email'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'support_hours', $_POST['support_hours'] ?? '', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Información de contacto guardada correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_social') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'social_facebook', $_POST['social_facebook'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'social_instagram', $_POST['social_instagram'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'social_twitter', $_POST['social_twitter'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'social_youtube', $_POST['social_youtube'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'social_tiktok', $_POST['social_tiktok'] ?? '', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Redes sociales guardadas correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_legal') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'company_name', $_POST['company_name'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'company_cuit', $_POST['company_cuit'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'terms_url', $_POST['terms_url'] ?? '/public/terms.php', $_SESSION['user_id']);
            setConfig($db, 'privacy_url', $_POST['privacy_url'] ?? '/public/privacy.php', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Información legal guardada correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_payment') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'mp_public_key', $_POST['mp_public_key'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'mp_access_token', $_POST['mp_access_token'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'mp_webhook_secret', $_POST['mp_webhook_secret'] ?? '', $_SESSION['user_id']);
            setConfig($db, 'mp_sandbox', isset($_POST['mp_sandbox']) ? 'true' : 'false', $_SESSION['user_id']);
            setConfig($db, 'default_currency', $_POST['default_currency'] ?? 'ARS', $_SESSION['user_id']);
            setConfig($db, 'tax_rate', $_POST['tax_rate'] ?? '21', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Configuración de pagos guardada correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_commission') {
        try {
            $db->beginTransaction();
            
            setConfig($db, 'default_commission_percentage', $_POST['default_commission'] ?? '70', $_SESSION['user_id']);
            setConfig($db, 'platform_commission', $_POST['platform_commission'] ?? '30', $_SESSION['user_id']);
            setConfig($db, 'min_payout_amount', $_POST['min_payout'] ?? '1000', $_SESSION['user_id']);
            
            $db->commit();
            $success_message = "✅ Configuración de comisiones guardada correctamente";
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_email') {
    try {
        $db->beginTransaction();
        
        setConfig($db, 'smtp_enabled', isset($_POST['smtp_enabled']) ? 'true' : 'false', $_SESSION['user_id']);
        setConfig($db, 'smtp_host', $_POST['smtp_host'] ?? 'smtp.gmail.com', $_SESSION['user_id']);
        setConfig($db, 'smtp_port', $_POST['smtp_port'] ?? '587', $_SESSION['user_id']);
        setConfig($db, 'smtp_username', $_POST['smtp_username'] ?? '', $_SESSION['user_id']);
        
        // Solo actualizar password si se proporcionó uno nuevo
        if (!empty($_POST['smtp_password'])) {
            setConfig($db, 'smtp_password', $_POST['smtp_password'], $_SESSION['user_id']);
        }
        
        setConfig($db, 'smtp_encryption', $_POST['smtp_encryption'] ?? 'tls', $_SESSION['user_id']);
        setConfig($db, 'smtp_debug', isset($_POST['smtp_debug']) ? 'true' : 'false', $_SESSION['user_id']);
        setConfig($db, 'email_from_address', $_POST['email_from_address'] ?? '', $_SESSION['user_id']);
        setConfig($db, 'email_from_name', $_POST['email_from_name'] ?? 'Eventix', $_SESSION['user_id']);
        setConfig($db, 'email_reply_to', $_POST['email_reply_to'] ?? '', $_SESSION['user_id']);
        
        $db->commit();
        $success_message = "✅ Configuración de email guardada correctamente";
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "❌ Error al guardar: " . $e->getMessage();
    }
}

if ($_POST['action'] === 'test_email') {
    try {
        $emailService = new EmailService();
        $testEmail = $_POST['test_email'] ?? '';
        
        if (empty($testEmail)) {
            $error_message = "❌ Por favor ingresa un email de prueba";
        } elseif (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error_message = "❌ Email inválido";
        } else {
            $result = $emailService->testConnection($testEmail);
            if ($result) {
                $success_message = "✅ Email de prueba enviado exitosamente a {$testEmail}";
            } else {
                $error_message = "❌ Error al enviar email de prueba";
            }
        }
    } catch (Exception $e) {
        $error_message = "❌ Error: " . $e->getMessage();
    }
}
    
    if ($_POST['action'] === 'save_streamer_commission') {
        try {
            $streamer_id = $_POST['streamer_id'];
            $commission = $_POST['commission_percentage'];
            $platform = 100 - $commission;
            $min_payout = $_POST['min_payout'] ?? 1000;
            $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
            $payment_details = json_encode([
                'cbu' => $_POST['cbu'] ?? '',
                'alias' => $_POST['alias'] ?? '',
                'bank' => $_POST['bank'] ?? '',
                'account_holder' => $_POST['account_holder'] ?? ''
            ]);
            
            $stmt = $db->prepare("
                INSERT INTO streamer_commissions 
                (streamer_id, commission_percentage, platform_percentage, min_payout, payment_method, payment_details, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    commission_percentage = ?, 
                    platform_percentage = ?,
                    min_payout = ?,
                    payment_method = ?,
                    payment_details = ?
            ");
            
            $stmt->execute([
                $streamer_id, $commission, $platform, $min_payout, $payment_method, $payment_details, $_SESSION['user_id'],
                $commission, $platform, $min_payout, $payment_method, $payment_details
            ]);
            
            $success_message = "✅ Comisión del streamer actualizada correctamente";
        } catch (Exception $e) {
            $error_message = "❌ Error al guardar: " . $e->getMessage();
        }
    }
}

// Obtener configuración actual
$config = [
    // Branding
    'site_name' => getConfig($db, 'site_name', 'Eventix'),
    'site_domain' => getConfig($db, 'site_domain', 'www.eventix.com.ar'),
    'site_tagline' => getConfig($db, 'site_tagline', 'Vive la Emoción del Deporte en Vivo'),
    'site_logo_path' => getConfig($db, 'site_logo_path', '/assets/logo.png'),
    'site_favicon' => getConfig($db, 'site_favicon', '/assets/favicon.ico'),
    
    // Contacto
    'contact_email' => getConfig($db, 'contact_email', 'info@eventix.com.ar'),
    'contact_phone' => getConfig($db, 'contact_phone', '+54 9 11 1234-5678'),
    'contact_whatsapp' => getConfig($db, 'contact_whatsapp', '5491112345678'),
    'contact_address' => getConfig($db, 'contact_address', 'Avellaneda, Santa Fe, Argentina'),
    'support_email' => getConfig($db, 'support_email', 'soporte@eventix.com.ar'),
    'support_hours' => getConfig($db, 'support_hours', 'Lun-Dom 9:00-23:00'),
    
    // Redes sociales
    'social_facebook' => getConfig($db, 'social_facebook', ''),
    'social_instagram' => getConfig($db, 'social_instagram', ''),
    'social_twitter' => getConfig($db, 'social_twitter', ''),
    'social_youtube' => getConfig($db, 'social_youtube', ''),
    'social_tiktok' => getConfig($db, 'social_tiktok', ''),
    
    // Legal
    'company_name' => getConfig($db, 'company_name', 'Eventix S.R.L.'),
    'company_cuit' => getConfig($db, 'company_cuit', ''),
    'terms_url' => getConfig($db, 'terms_url', '/public/terms.php'),
    'privacy_url' => getConfig($db, 'privacy_url', '/public/privacy.php'),
    
    // Pagos
    'mp_public_key' => getConfig($db, 'mp_public_key'),
    'mp_access_token' => getConfig($db, 'mp_access_token'),
    'mp_webhook_secret' => getConfig($db, 'mp_webhook_secret'),
    'mp_sandbox' => getConfig($db, 'mp_sandbox', 'true') === 'true',
    'default_currency' => getConfig($db, 'default_currency', 'ARS'),
    'tax_rate' => getConfig($db, 'tax_rate', '21'),
    
    // Comisiones
    'default_commission' => getConfig($db, 'default_commission_percentage', '70'),
    'platform_commission' => getConfig($db, 'platform_commission', '30'),
    'min_payout' => getConfig($db, 'min_payout_amount', '1000'),
    
    // Email
'smtp_enabled' => getConfig($db, 'smtp_enabled', 'false') === 'true',
'smtp_host' => getConfig($db, 'smtp_host', 'smtp.gmail.com'),
'smtp_port' => getConfig($db, 'smtp_port', '587'),
'smtp_username' => getConfig($db, 'smtp_username', ''),
'smtp_password' => getConfig($db, 'smtp_password', ''),
'smtp_encryption' => getConfig($db, 'smtp_encryption', 'tls'),
'smtp_debug' => getConfig($db, 'smtp_debug', 'false') === 'true',
'email_from_address' => getConfig($db, 'email_from_address', 'noreply@eventix.com.ar'),
'email_from_name' => getConfig($db, 'email_from_name', 'Eventix'),
'email_reply_to' => getConfig($db, 'email_reply_to', 'soporte@eventix.com.ar')
];

// Obtener streamers
$stmt = $db->query("
    SELECT 
        u.id, u.full_name, u.email, u.created_at,
        COALESCE(sc.commission_percentage, {$config['default_commission']}) as commission_percentage,
        COALESCE(sc.platform_percentage, {$config['platform_commission']}) as platform_percentage,
        COALESCE(sc.min_payout, {$config['min_payout']}) as min_payout,
        sc.payment_method,
        sc.payment_details,
        COUNT(DISTINCT e.id) as total_events,
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_revenue
    FROM users u
    LEFT JOIN streamer_commissions sc ON u.id = sc.streamer_id
    LEFT JOIN events e ON u.id = e.created_by
    LEFT JOIN purchases p ON e.id = p.event_id
    WHERE u.role = 'streamer'
    GROUP BY u.id, u.full_name, u.email, u.created_at, sc.commission_percentage, sc.platform_percentage, sc.min_payout, sc.payment_method, sc.payment_details
    ORDER BY u.created_at DESC
");
$streamers = $stmt->fetchAll();

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #ddd;
    flex-wrap: wrap;
    overflow-x: auto;
}

.tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    white-space: nowrap;
}

.tab:hover {
    color: #333;
    background: rgba(0,0,0,0.05);
}

.tab.active {
    color: #2c3e50;
    border-bottom-color: #3498db;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.form-grid {
    display: grid;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.config-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.config-section h3 {
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.streamer-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
}

.streamer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.streamer-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.streamer-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.stat-mini {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    text-align: center;
}

.stat-mini-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.stat-mini-value {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
}

.commission-form {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.commission-form.active {
    display: block;
}

.help-text {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.key-input {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

@media (max-width: 768px) {
    .tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab {
        white-space: nowrap;
    }
    
    .config-section {
        padding: 20px;
    }
}

/* Estilos adicionales para el upload de logo */
.upload-progress {
    width: 100%;
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 10px;
}

.upload-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s;
}
</style>

<?php if ($success_message): ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <?= $error_message ?>
</div>
<?php endif; ?>

<div class="tabs">
    <button class="tab <?= $active_tab === 'branding' ? 'active' : '' ?>" onclick="switchTab('branding')">
        🎨 Marca
    </button>
    <button class="tab <?= $active_tab === 'contact' ? 'active' : '' ?>" onclick="switchTab('contact')">
        📞 Contacto
    </button>
    <button class="tab <?= $active_tab === 'social' ? 'active' : '' ?>" onclick="switchTab('social')">
        📱 Redes Sociales
    </button>
    <button class="tab <?= $active_tab === 'legal' ? 'active' : '' ?>" onclick="switchTab('legal')">
        ⚖️ Legal
    </button>
    <button class="tab <?= $active_tab === 'payment' ? 'active' : '' ?>" onclick="switchTab('payment')">
        💳 Pagos
    </button>
    <button class="tab <?= $active_tab === 'commission' ? 'active' : '' ?>" onclick="switchTab('commission')">
        💰 Comisiones
    </button>
    <button class="tab <?= $active_tab === 'streamers' ? 'active' : '' ?>" onclick="switchTab('streamers')">
        🎬 Streamers
    </button>
    <button class="tab <?= $active_tab === 'email' ? 'active' : '' ?>" onclick="switchTab('email')">
    📧 Email / SMTP
</button>
</div>

<!-- TAB: Marca -->
<div class="tab-content <?= $active_tab === 'branding' ? 'active' : '' ?>" id="branding">
    <div class="config-section">
        <h3><i class="fas fa-palette"></i> Configuración de Marca</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_branding">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre del Sitio *</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($config['site_name']) ?>" required>
                    <div class="help-text">Aparece en el header y en todos los títulos</div>
                </div>
                
                <div class="form-group">
                    <label>Dominio del Sitio *</label>
                    <input type="text" name="site_domain" value="<?= htmlspecialchars($config['site_domain']) ?>" required>
                    <div class="help-text">Ejemplo: www.eventix.com.ar</div>
                </div>
                
                <div class="form-group">
                    <label>Eslogan/Tagline</label>
                    <input type="text" name="site_tagline" value="<?= htmlspecialchars($config['site_tagline']) ?>">
                    <div class="help-text">Frase descriptiva corta que aparece debajo del logo</div>
                </div>
                
                <!-- NUEVO: Upload de Logo -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label><i class="fas fa-image"></i> Logo del Sitio (PNG/JPG)</label>
                    
                    <div style="display: flex; gap: 20px; align-items: start; margin-top: 10px;">
                        <!-- Preview del logo actual -->
                        <div style="flex-shrink: 0;">
                            <div style="width: 200px; height: 100px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; overflow: hidden;">
                                <?php if (!empty($config['site_logo_path']) && file_exists(__DIR__ . '/..' . $config['site_logo_path'])): ?>
                                    <img id="logo-preview" src="<?= $config['site_logo_path'] ?>" 
                                         style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <div id="logo-preview" style="text-align: center; color: #999;">
                                        <i class="fas fa-image" style="font-size: 32px; margin-bottom: 5px;"></i><br>
                                        <small>Sin logo</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: center; margin-top: 5px; font-size: 11px; color: #666;">
                                Vista previa
                            </div>
                        </div>
                        
                        <!-- Upload form -->
                        <div style="flex: 1;">
                            <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #b3d9ff; margin-bottom: 10px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600; color: #0066cc;">
                                    <i class="fas fa-info-circle"></i> Requisitos del Logo:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
                                    <li>Formato: PNG o JPG</li>
                                    <li>Tamaño máximo: 2MB</li>
                                    <li>Dimensiones recomendadas: 300x100px o similar</li>
                                    <li>Fondo transparente (PNG) para mejor resultado</li>
                                </ul>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="file" id="logo-upload" accept="image/png,image/jpeg,image/jpg" 
                                       style="display: none;" onchange="uploadLogo(this)">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('logo-upload').click()">
                                    <i class="fas fa-upload"></i> Subir Nuevo Logo
                                </button>
                                <?php if (!empty($config['site_logo_path'])): ?>
                                <button type="button" class="btn btn-danger" onclick="deleteLogo()">
                                    <i class="fas fa-trash"></i> Eliminar Logo
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <div id="upload-status" style="margin-top: 10px; padding: 10px; border-radius: 6px; display: none;"></div>
                            
                            <input type="hidden" name="site_logo_path" id="logo-path-input" value="<?= htmlspecialchars($config['site_logo_path']) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ruta del Favicon</label>
                    <input type="text" name="site_favicon" value="<?= htmlspecialchars($config['site_favicon']) ?>">
                    <div class="help-text">Ruta del favicon (ej: /assets/favicon.ico)</div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Configuración de Marca
                </button>
            </div>
        </form>
    </div>
</div>


<!-- TAB: Contacto -->
<div class="tab-content <?= $active_tab === 'contact' ? 'active' : '' ?>" id="contact">
    <div class="config-section">
        <h3>📞 Información de Contacto</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_contact">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email de Contacto *</label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($config['contact_email']) ?>" required>
                    <div class="help-text">Email principal para contacto general</div>
                </div>
                
                <div class="form-group">
                    <label>Email de Soporte</label>
                    <input type="email" name="support_email" value="<?= htmlspecialchars($config['support_email']) ?>">
                    <div class="help-text">Email específico para soporte técnico</div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($config['contact_phone']) ?>">
                    <div class="help-text">Formato: +54 9 11 1234-5678</div>
                </div>
                
                <div class="form-group">
                    <label>WhatsApp (solo números)</label>
                    <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($config['contact_whatsapp']) ?>">
                    <div class="help-text">Sin + ni espacios: 5491112345678</div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="contact_address" value="<?= htmlspecialchars($config['contact_address']) ?>">
                <div class="help-text">Dirección física de la empresa</div>
            </div>
            
            <div class="form-group">
                <label>Horario de Atención</label>
                <input type="text" name="support_hours" value="<?= htmlspecialchars($config['support_hours']) ?>">
                <div class="help-text">Ejemplo: Lun-Vie 9:00-18:00, Sáb 10:00-14:00</div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">💾 Guardar Información de Contacto</button>
            </div>
        </form>
    </div>
</div>

<!-- TAB: Redes Sociales -->
<div class="tab-content <?= $active_tab === 'social' ? 'active' : '' ?>" id="social">
    <div class="config-section">
        <h3>📱 Redes Sociales</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Las redes sociales configuradas aparecerán automáticamente en el footer y página de contacto.
            Deja en blanco las que no uses.
        </p>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_social">
            
            <div class="form-group">
                <label>Facebook</label>
                <input type="url" name="social_facebook" value="<?= htmlspecialchars($config['social_facebook']) ?>" placeholder="https://facebook.com/tupagina">
            </div>
            
            <div class="form-group">
                <label>Instagram</label>
                <input type="url" name="social_instagram" value="<?= htmlspecialchars($config['social_instagram']) ?>" placeholder="https://instagram.com/tuusuario">
            </div>
            
            <div class="form-group">
                <label>Twitter / X</label>
                <input type="url" name="social_twitter" value="<?= htmlspecialchars($config['social_twitter']) ?>" placeholder="https://twitter.com/tuusuario">
            </div>
            
            <div class="form-group">
                <label>YouTube</label>
                <input type="url" name="social_youtube" value="<?= htmlspecialchars($config['social_youtube']) ?>" placeholder="https://youtube.com/@tucanal">
            </div>
            
            <div class="form-group">
                <label>TikTok</label>
                <input type="url" name="social_tiktok" value="<?= htmlspecialchars($config['social_tiktok']) ?>" placeholder="https://tiktok.com/@tuusuario">
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">💾 Guardar Redes Sociales</button>
            </div>
        </form>
    </div>
</div>

<!-- TAB: Legal -->
<div class="tab-content <?= $active_tab === 'legal' ? 'active' : '' ?>" id="legal">
    <div class="config-section">
        <h3>⚖️ Información Legal</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_legal">
            
            <div class="form-group">
                <label>Nombre Legal de la Empresa *</label>
                <input type="text" name="company_name" value="<?= htmlspecialchars($config['company_name']) ?>" required>
                <div class="help-text">Razón social completa (ej: Eventix S.R.L.)</div>
            </div>
            
            <div class="form-group">
                <label>CUIT / CUIL</label>
                <input type="text" name="company_cuit" value="<?= htmlspecialchars($config['company_cuit']) ?>">
                <div class="help-text">Número de identificación fiscal</div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>URL Términos y Condiciones</label>
                    <input type="text" name="terms_url" value="<?= htmlspecialchars($config['terms_url']) ?>">
                    <div class="help-text">Ruta relativa o URL completa</div>
                </div>
                
                <div class="form-group">
                    <label>URL Política de Privacidad</label>
                    <input type="text" name="privacy_url" value="<?= htmlspecialchars($config['privacy_url']) ?>">
                    <div class="help-text">Ruta relativa o URL completa</div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">💾 Guardar Información Legal</button>
            </div>
        </form>
    </div>
</div>

<!-- TAB: Configuración de Pagos -->
<div class="tab-content <?= $active_tab === 'payment' ? 'active' : '' ?>" id="payment">
    <div class="config-section">
        <h3>💳 Configuración de MercadoPago</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_payment">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Public Key *</label>
                    <input type="text" name="mp_public_key" class="key-input" 
                           value="<?= htmlspecialchars($config['mp_public_key']) ?>" required>
                    <div class="help-text">Tu clave pública de MercadoPago (APP_USR-...)</div>
                </div>
                
                <div class="form-group">
                    <label>Access Token *</label>
                    <input type="password" name="mp_access_token" class="key-input"
                           value="<?= htmlspecialchars($config['mp_access_token']) ?>" required>
                    <div class="help-text">Tu token de acceso de MercadoPago (APP_USR-...)</div>
                </div>
                
                <div class="form-group">
                    <label>Webhook Secret</label>
                    <input type="password" name="mp_webhook_secret" class="key-input"
                           value="<?= htmlspecialchars($config['mp_webhook_secret']) ?>">
                    <div class="help-text">Secret para validar webhooks (opcional)</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Moneda por Defecto</label>
                        <select name="default_currency">
                            <option value="ARS" <?= $config['default_currency'] === 'ARS' ? 'selected' : '' ?>>ARS - Peso Argentino</option>
                            <option value="USD" <?= $config['default_currency'] === 'USD' ? 'selected' : '' ?>>USD - Dólar</option>
                            <option value="EUR" <?= $config['default_currency'] === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                            <option value="BRL" <?= $config['default_currency'] === 'BRL' ? 'selected' : '' ?>>BRL - Real Brasileño</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tasa de Impuesto (%)</label>
                        <input type="number" name="tax_rate" step="0.01" min="0" max="100"
                               value="<?= $config['tax_rate'] ?>">
                        <div class="help-text">IVA u otros impuestos aplicables</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="mp_sandbox" value="1" 
                               <?= $config['mp_sandbox'] ? 'checked' : '' ?>>
                        Modo Sandbox (Pruebas)
                    </label>
                    <div class="help-text">Activar para usar credenciales de prueba</div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">💾 Guardar Configuración de Pagos</button>
                <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank" class="btn" style="background: #009ee3;">
                    🔗 Abrir Panel de MercadoPago
                </a>
            </div>
        </form>
    </div>
</div>

<!-- TAB: COMISIONES (código existente) -->
<div class="tab-content <?= $active_tab === 'commission' ? 'active' : '' ?>" id="commission">
    <div class="config-section">
        <h3>💰 Configuración de Comisiones por Defecto</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Estas comisiones se aplicarán automáticamente a todos los streamers nuevos. 
            Puedes personalizar las comisiones individualmente en la pestaña "Streamers".
        </p>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_commission">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Comisión del Streamer (%)</label>
                    <input type="number" name="default_commission" step="0.01" min="0" max="100"
                           value="<?= $config['default_commission'] ?>" required>
                    <div class="help-text">Porcentaje que recibe el streamer por cada venta</div>
                </div>
                
                <div class="form-group">
                    <label>Comisión de la Plataforma (%)</label>
                    <input type="number" name="platform_commission" step="0.01" min="0" max="100"
                           value="<?= $config['platform_commission'] ?>" required>
                    <div class="help-text">Porcentaje que retiene la plataforma</div>
                </div>
                
                <div class="form-group">
                    <label>Monto Mínimo para Retiro</label>
                    <input type="number" name="min_payout" step="0.01" min="0"
                           value="<?= $config['min_payout'] ?>" required>
                    <div class="help-text">Mínimo acumulado para solicitar retiro de ganancias</div>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                <strong>📊 Ejemplo de distribución:</strong><br>
                Si un evento se vende en $1000:<br>
                • Streamer recibe: $<?= number_format(1000 * ($config['default_commission'] / 100), 2) ?> (<?= $config['default_commission'] ?>%)<br>
                • Plataforma recibe: $<?= number_format(1000 * ($config['platform_commission'] / 100), 2) ?> (<?= $config['platform_commission'] ?>%)
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">💾 Guardar Comisiones por Defecto</button>
            </div>
        </form>
    </div>
</div>



<!-- TAB: STREAMERS (código existente) -->
<div class="tab-content <?= $active_tab === 'streamers' ? 'active' : '' ?>" id="streamers">
    <div class="config-section">
        <h3>🎬 Comisiones Individuales por Streamer</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Personaliza las comisiones y configuración de pago para cada streamer.
        </p>
        
        <?php if (empty($streamers)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎬</div>
            <h3>No hay streamers registrados</h3>
            <p>Los streamers aparecerán aquí cuando se registren en la plataforma</p>
        </div>
        <?php else: ?>
            <?php foreach ($streamers as $streamer): ?>
            <div class="streamer-card">
                <div class="streamer-header">
                    <div class="streamer-info">
                        <h4><?= htmlspecialchars($streamer['full_name']) ?></h4>
                        <small style="color: #666;"><?= htmlspecialchars($streamer['email']) ?></small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="toggleCommissionForm(<?= $streamer['id'] ?>)">
                        ⚙️ Configurar
                    </button>
                </div>
                
                <div class="streamer-stats">
                    <div class="stat-mini">
                        <div class="stat-mini-label">Comisión Streamer</div>
                        <div class="stat-mini-value"><?= number_format($streamer['commission_percentage'], 2) ?>%</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-label">Comisión Plataforma</div>
                        <div class="stat-mini-value"><?= number_format($streamer['platform_percentage'], 2) ?>%</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-label">Total Eventos</div>
                        <div class="stat-mini-value"><?= $streamer['total_events'] ?></div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-label">Revenue Total</div>
                        <div class="stat-mini-value">$<?= number_format($streamer['total_revenue'], 2) ?></div>
                    </div>
                </div>
                
                <div class="commission-form" id="form-<?= $streamer['id'] ?>">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_streamer_commission">
                        <input type="hidden" name="streamer_id" value="<?= $streamer['id'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Comisión del Streamer (%)</label>
                                <input type="number" name="commission_percentage" step="0.01" min="0" max="100"
                                       value="<?= $streamer['commission_percentage'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Monto Mínimo para Retiro</label>
                                <input type="number" name="min_payout" step="0.01" min="0"
                                       value="<?= $streamer['min_payout'] ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Método de Pago</label>
                                <select name="payment_method">
                                    <?php
                                    $payment_details = json_decode($streamer['payment_details'] ?? '{}', true);
                                    $current_method = $streamer['payment_method'] ?? 'bank_transfer';
                                    ?>
                                    <option value="bank_transfer" <?= $current_method === 'bank_transfer' ? 'selected' : '' ?>>Transferencia Bancaria</option>
                                    <option value="mercadopago" <?= $current_method === 'mercadopago' ? 'selected' : '' ?>>MercadoPago</option>
                                    <option value="paypal" <?= $current_method === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>CBU / CVU</label>
                                <input type="text" name="cbu" placeholder="0000003100010000000000"
                                       value="<?= htmlspecialchars($payment_details['cbu'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Alias</label>
                                <input type="text" name="alias" placeholder="mi.alias.mp"
                                       value="<?= htmlspecialchars($payment_details['alias'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Banco</label>
                                <input type="text" name="bank" placeholder="Banco Nación"
                                       value="<?= htmlspecialchars($payment_details['bank'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Titular de la Cuenta</label>
                                <input type="text" name="account_holder" placeholder="Juan Pérez"
                                       value="<?= htmlspecialchars($payment_details['account_holder'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <button type="submit" class="btn btn-success">💾 Guardar Configuración</button>
                            <button type="button" class="btn" onclick="toggleCommissionForm(<?= $streamer['id'] ?>)">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
</div>
        
        <!-- TAB: Email -->
<div class="tab-content <?= $active_tab === 'email' ? 'active' : '' ?>" id="email">
    <div class="config-section">
        <h3>📧 Configuración de Email y SMTP</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Configura el envío de emails para recuperación de contraseña, verificaciones y notificaciones.
        </p>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_email">
            
            <!-- Estado SMTP -->
            <div class="form-group">
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="smtp_enabled" value="1" 
                           <?= $config['smtp_enabled'] ? 'checked' : '' ?>>
                    <span><strong>Activar envío de emails por SMTP</strong></span>
                </label>
                <div class="help-text">
                    Si está desactivado, se usará la función mail() de PHP (menos confiable)
                </div>
            </div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <h4 style="margin-bottom: 20px; color: #2c3e50;">🔧 Configuración del Servidor SMTP</h4>
            
            <!-- Proveedor común -->
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #90caf9;">
                <p style="margin: 0 0 10px 0; font-weight: 600; color: #1565c0;">
                    📌 Configuraciones comunes:
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px;">
                    <div>
                        <strong>Gmail:</strong><br>
                        Host: smtp.gmail.com<br>
                        Puerto: 587 (TLS)<br>
                        <a href="https://support.google.com/accounts/answer/185833" target="_blank" style="color: #1565c0;">
                            → Crear contraseña de aplicación
                        </a>
                    </div>
                    <div>
                        <strong>Outlook/Hotmail:</strong><br>
                        Host: smtp-mail.outlook.com<br>
                        Puerto: 587 (TLS)
                    </div>
                    <div>
                        <strong>Yahoo:</strong><br>
                        Host: smtp.mail.yahoo.com<br>
                        Puerto: 465 (SSL)
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Servidor SMTP (Host) *</label>
                    <input type="text" name="smtp_host" 
                           value="<?= htmlspecialchars($config['smtp_host']) ?>" 
                           placeholder="smtp.gmail.com" required>
                    <div class="help-text">Dirección del servidor SMTP</div>
                </div>
                
                <div class="form-group">
                    <label>Puerto SMTP *</label>
                    <input type="number" name="smtp_port" 
                           value="<?= $config['smtp_port'] ?>" 
                           placeholder="587" required>
                    <div class="help-text">587 para TLS, 465 para SSL</div>
                </div>
                
                <div class="form-group">
                    <label>Encriptación *</label>
                    <select name="smtp_encryption">
                        <option value="tls" <?= $config['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>
                            TLS (Puerto 587)
                        </option>
                        <option value="ssl" <?= $config['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>
                            SSL (Puerto 465)
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Usuario SMTP (Email) *</label>
                    <input type="email" name="smtp_username" 
                           value="<?= htmlspecialchars($config['smtp_username']) ?>" 
                           placeholder="tu-email@gmail.com" required>
                    <div class="help-text">Tu dirección de email completa</div>
                </div>
                
                <div class="form-group">
                    <label>Contraseña SMTP *</label>
                    <input type="password" name="smtp_password" 
                           placeholder="<?= !empty($config['smtp_password']) ? '••••••••••••' : 'Contraseña o App Password' ?>">
                    <div class="help-text">
                        <?= !empty($config['smtp_password']) ? 'Dejar vacío para mantener la actual' : 'Para Gmail, usa una "Contraseña de Aplicación"' ?>
                    </div>
                </div>
            </div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <h4 style="margin-bottom: 20px; color: #2c3e50;">✉️ Información del Remitente</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email Remitente *</label>
                    <input type="email" name="email_from_address" 
                           value="<?= htmlspecialchars($config['email_from_address']) ?>" 
                           placeholder="noreply@eventix.com.ar" required>
                    <div class="help-text">Email que aparecerá como remitente</div>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Remitente *</label>
                    <input type="text" name="email_from_name" 
                           value="<?= htmlspecialchars($config['email_from_name']) ?>" 
                           placeholder="Eventix" required>
                    <div class="help-text">Nombre que verán los destinatarios</div>
                </div>
                
                <div class="form-group">
                    <label>Email de Respuesta</label>
                    <input type="email" name="email_reply_to" 
                           value="<?= htmlspecialchars($config['email_reply_to']) ?>" 
                           placeholder="soporte@eventix.com.ar">
                    <div class="help-text">Email para que los usuarios respondan</div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="smtp_debug" value="1" 
                           <?= $config['smtp_debug'] ? 'checked' : '' ?>>
                    Modo Debug (solo para desarrollo)
                </label>
                <div class="help-text">Muestra información detallada en los logs</div>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-success">
                    💾 Guardar Configuración de Email
                </button>
            </div>
        </form>
        
        <!-- Test de conexión -->
        <hr style="margin: 40px 0; border: none; border-top: 2px solid #e0e0e0;">
        
        <h4 style="margin-bottom: 20px; color: #2c3e50;">🧪 Probar Configuración</h4>
        <p style="color: #666; margin-bottom: 15px;">
            Envía un email de prueba para verificar que la configuración funciona correctamente.
        </p>
        
        <form method="POST" style="display: flex; gap: 10px; max-width: 600px;">
            <input type="hidden" name="action" value="test_email">
            <input type="email" name="test_email" placeholder="email@ejemplo.com" 
                   style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;" required>
            <button type="submit" class="btn btn-primary">
                🚀 Enviar Email de Prueba
            </button>
        </form>
        
        <!-- Logs de emails -->
        <hr style="margin: 40px 0; border: none; border-top: 2px solid #e0e0e0;">
        
        <h4 style="margin-bottom: 20px; color: #2c3e50;">📊 Últimos Emails Enviados</h4>
        
        <?php
        $stmt = $db->query("
            SELECT * FROM email_logs 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $emailLogs = $stmt->fetchAll();
        ?>
        
        <?php if (empty($emailLogs)): ?>
        <p style="color: #999; text-align: center; padding: 20px;">
            No hay emails registrados aún
        </p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Destinatario</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Asunto</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">Estado</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emailLogs as $log): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?= htmlspecialchars($log['recipient']) ?></td>
                        <td style="padding: 12px;">
                            <?= htmlspecialchars($log['subject']) ?>
                            <?php if ($log['error_message']): ?>
                            <br><small style="color: #dc3545;"><?= htmlspecialchars($log['error_message']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($log['status'] === 'sent'): ?>
                            <span style="background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                ✓ Enviado
                            </span>
                            <?php elseif ($log['status'] === 'failed'): ?>
                            <span style="background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                ✗ Fallido
                            </span>
                            <?php else: ?>
                            <span style="background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                ⏳ Pendiente
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <small><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
    
    

<script>
function switchTab(tabName) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Mostrar el tab seleccionado
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
    document.getElementById(tabName).classList.add('active');
    
    // Actualizar URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

function toggleCommissionForm(streamerId) {
    const form = document.getElementById(`form-${streamerId}`);
    form.classList.toggle('active');
}

// Validar que comisión + plataforma = 100%
document.querySelectorAll('input[name="default_commission"]').forEach(input => {
    input.addEventListener('input', function() {
        const platformInput = this.closest('form').querySelector('input[name="platform_commission"]');
        if (platformInput) {
            platformInput.value = (100 - parseFloat(this.value || 0)).toFixed(2);
        }
    });
});

document.querySelectorAll('input[name="platform_commission"]').forEach(input => {
    input.addEventListener('input', function() {
        const streamerInput = this.closest('form').querySelector('input[name="default_commission"]');
        if (streamerInput) {
            streamerInput.value = (100 - parseFloat(this.value || 0)).toFixed(2);
        }
    });
});
</script>
<script>
function uploadLogo(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validaciones en cliente
    const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        showUploadStatus('Solo se permiten archivos PNG o JPG', 'error');
        return;
    }
    
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSize) {
        showUploadStatus('El archivo es muy grande (máximo 2MB)', 'error');
        return;
    }
    
    // Mostrar preview temporal
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('logo-preview');
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
        } else {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
        }
    };
    reader.readAsDataURL(file);
    
    // Subir archivo
    const formData = new FormData();
    formData.append('logo', file);
    
    showUploadStatus('Subiendo logo...', 'loading');
    
    fetch('/admin/upload_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showUploadStatus(data.message, 'success');
            document.getElementById('logo-path-input').value = data.path;
            
            // Actualizar preview con la ruta real
            const preview = document.getElementById('logo-preview');
            if (preview.tagName === 'IMG') {
                preview.src = data.path + '?t=' + Date.now();
            } else {
                preview.innerHTML = `<img src="${data.path}?t=${Date.now()}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
            }
            
            // Auto-submit del formulario para guardar en config
            setTimeout(() => {
                document.querySelector('input[name="action"][value="save_branding"]').closest('form').submit();
            }, 1000);
        } else {
            showUploadStatus(data.message, 'error');
        }
    })
    .catch(error => {
        showUploadStatus('Error al subir el archivo', 'error');
        console.error('Error:', error);
    });
}

function showUploadStatus(message, type) {
    const statusDiv = document.getElementById('upload-status');
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = message;
    
    if (type === 'success') {
        statusDiv.style.background = '#d4edda';
        statusDiv.style.color = '#155724';
        statusDiv.style.border = '1px solid #c3e6cb';
        statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    } else if (type === 'error') {
        statusDiv.style.background = '#f8d7da';
        statusDiv.style.color = '#721c24';
        statusDiv.style.border = '1px solid #f5c6cb';
        statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    } else if (type === 'loading') {
        statusDiv.style.background = '#cce5ff';
        statusDiv.style.color = '#004085';
        statusDiv.style.border = '1px solid #b8daff';
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + message;
    }
}

function deleteLogo() {
    if (!confirm('¿Estás seguro de eliminar el logo actual?')) return;
    
    fetch('/admin/delete_logo.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const preview = document.getElementById('logo-preview');
            preview.innerHTML = `
                <div style="text-align: center; color: #999;">
                    <i class="fas fa-image" style="font-size: 32px; margin-bottom: 5px;"></i><br>
                    <small>Sin logo</small>
                </div>
            `;
            document.getElementById('logo-path-input').value = '';
            showUploadStatus('Logo eliminado correctamente', 'success');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showUploadStatus(data.message, 'error');
        }
    })
    .catch(error => {
        showUploadStatus('Error al eliminar el logo', 'error');
        console.error('Error:', error);
    });
}
</script>

<?php require_once 'footer.php'; ?>