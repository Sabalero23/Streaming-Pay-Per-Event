<?php
// admin/settings.php
// Panel de configuración del sistema (solo admin)

session_start();

$page_title = "Configuración del Sistema";
$page_icon = "⚙️";

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Solo admin puede acceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

$success_message = '';
$error_message = '';
$active_tab = $_GET['tab'] ?? 'payment';

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
        INSERT INTO system_config (config_key, config_value, updated_by) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?
    ");
    return $stmt->execute([$key, $value, $userId, $value, $userId]);
}

// Procesar formulario de pagos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
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
    'mp_public_key' => getConfig($db, 'mp_public_key'),
    'mp_access_token' => getConfig($db, 'mp_access_token'),
    'mp_webhook_secret' => getConfig($db, 'mp_webhook_secret'),
    'mp_sandbox' => getConfig($db, 'mp_sandbox', 'true') === 'true',
    'default_currency' => getConfig($db, 'default_currency', 'ARS'),
    'tax_rate' => getConfig($db, 'tax_rate', '21'),
    'default_commission' => getConfig($db, 'default_commission_percentage', '70'),
    'platform_commission' => getConfig($db, 'platform_commission', '30'),
    'min_payout' => getConfig($db, 'min_payout_amount', '1000')
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
    }
    
    .tab {
        white-space: nowrap;
    }
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
    <button class="tab <?= $active_tab === 'payment' ? 'active' : '' ?>" onclick="switchTab('payment')">
        💳 Configuración de Pagos
    </button>
    <button class="tab <?= $active_tab === 'commission' ? 'active' : '' ?>" onclick="switchTab('commission')">
        💰 Comisiones Generales
    </button>
    <button class="tab <?= $active_tab === 'streamers' ? 'active' : '' ?>" onclick="switchTab('streamers')">
        🎬 Comisiones por Streamer
    </button>
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

<!-- TAB: Comisiones Generales -->
<div class="tab-content <?= $active_tab === 'commission' ? 'active' : '' ?>" id="commission">
    <div class="config-section">
        <h3>💰 Configuración de Comisiones por Defecto</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Estas comisiones se aplicarán automáticamente a todos los streamers nuevos. 
            Puedes personalizar las comisiones individualmente en la pestaña "Comisiones por Streamer".
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

<!-- TAB: Comisiones por Streamer -->
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

<?php require_once 'footer.php'; ?>