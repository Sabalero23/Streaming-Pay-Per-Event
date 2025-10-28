<?php
// admin/analytics.php
session_start();

// Permitir admin, streamer y moderator
$allowedRoles = ['admin', 'streamer', 'moderator'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /public/login.php');
    exit;
}

$isAdmin = $_SESSION['user_role'] === 'admin';
$isStreamer = $_SESSION['user_role'] === 'streamer';
$userId = $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// Obtener configuración de comisiones del sistema
$systemConfig = $db->query("
    SELECT config_key, config_value
    FROM system_config 
    WHERE config_key IN ('default_commission_percentage', 'platform_commission', 'min_payout_amount')
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Debug: ver qué valores se están obteniendo
error_log("=== SYSTEM CONFIG DEBUG ===");
error_log("default_commission_percentage: " . ($systemConfig['default_commission_percentage'] ?? 'NOT SET'));
error_log("platform_commission: " . ($systemConfig['platform_commission'] ?? 'NOT SET'));
error_log("min_payout_amount: " . ($systemConfig['min_payout_amount'] ?? 'NOT SET'));

$defaultStreamerCommission = isset($systemConfig['default_commission_percentage']) ? (float)$systemConfig['default_commission_percentage'] : 80.00;
$defaultPlatformCommission = isset($systemConfig['platform_commission']) ? (float)$systemConfig['platform_commission'] : 20.00;
$minPayoutDefault = isset($systemConfig['min_payout_amount']) ? (float)$systemConfig['min_payout_amount'] : 1000;

if ($isAdmin) {
    // Admin: métricas globales con separación de ganancias
    $metrics = $db->query("SELECT 
        (SELECT COUNT(*) FROM events) as total_events,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM purchases WHERE status='completed') as total_sales,
        (SELECT COALESCE(SUM(amount), 0) FROM purchases WHERE status='completed' AND payment_method != 'free') as total_revenue,
        (SELECT COUNT(*) FROM purchases WHERE status='completed' AND payment_method = 'free') as free_access,
        (SELECT COUNT(DISTINCT user_id) FROM purchases WHERE status='completed') as paying_users
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Calcular ganancias de plataforma y streamers
    $earningsQuery = $db->query("
        SELECT 
            SUM(
                p.amount * (COALESCE(sc.platform_percentage, {$defaultPlatformCommission}) / 100)
            ) as platform_earnings,
            SUM(
                p.amount * (COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) / 100)
            ) as streamer_earnings
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        LEFT JOIN streamer_commissions sc ON e.created_by = sc.streamer_id AND sc.is_active = 1
        WHERE p.status = 'completed' AND p.payment_method != 'free'
    ")->fetch(PDO::FETCH_ASSOC);
    
    $metrics['platform_earnings'] = isset($earningsQuery['platform_earnings']) ? $earningsQuery['platform_earnings'] : 0;
    $metrics['streamer_earnings'] = isset($earningsQuery['streamer_earnings']) ? $earningsQuery['streamer_earnings'] : 0;
    
    // Top streamers por ganancias
    $top_streamers = $db->query("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            COUNT(DISTINCT e.id) as total_events,
            COUNT(p.id) as total_sales,
            COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as revenue,
            COALESCE(
                SUM(
                    CASE WHEN p.payment_method != 'free' 
                    THEN p.amount * (COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) / 100)
                    ELSE 0 END
                ), 0
            ) as streamer_earnings,
            COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) as commission_percentage
        FROM users u
        LEFT JOIN events e ON u.id = e.created_by
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status = 'completed'
        LEFT JOIN streamer_commissions sc ON u.id = sc.streamer_id AND sc.is_active = 1
        WHERE u.role IN ('streamer', 'admin')
        GROUP BY u.id, u.full_name, u.email, sc.commission_percentage
        HAVING total_sales > 0
        ORDER BY streamer_earnings DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular pagos pendientes por streamer
    $pending_payments = $db->query("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.phone,
            COALESCE(sc.payment_method, 'bank_transfer') as payment_method,
            COALESCE(sc.min_payout, {$minPayoutDefault}) as min_payout,
            COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) as commission_percentage,
            COALESCE(
                SUM(
                    CASE WHEN p.payment_method != 'free' 
                    THEN p.amount * (COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) / 100)
                    ELSE 0 END
                ), 0
            ) as total_earned,
            COALESCE(
                (SELECT SUM(streamer_earnings) 
                 FROM earnings 
                 WHERE streamer_id = u.id AND status = 'paid'), 0
            ) as total_paid,
            COALESCE(
                SUM(
                    CASE WHEN p.payment_method != 'free' 
                    THEN p.amount * (COALESCE(sc.commission_percentage, {$defaultStreamerCommission}) / 100)
                    ELSE 0 END
                ), 0
            ) - COALESCE(
                (SELECT SUM(streamer_earnings) 
                 FROM earnings 
                 WHERE streamer_id = u.id AND status = 'paid'), 0
            ) as pending_payment,
            COUNT(DISTINCT p.id) as total_transactions,
            MAX(p.purchased_at) as last_sale_date
        FROM users u
        LEFT JOIN events e ON u.id = e.created_by
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status = 'completed'
        LEFT JOIN streamer_commissions sc ON u.id = sc.streamer_id AND sc.is_active = 1
        WHERE u.role IN ('streamer', 'admin') AND e.id IS NOT NULL
        GROUP BY u.id, u.full_name, u.email, u.phone, sc.payment_method, sc.min_payout, sc.commission_percentage
        HAVING pending_payment > 0
        ORDER BY pending_payment DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Total general pendiente de pago
    $total_pending_all = array_sum(array_column($pending_payments, 'pending_payment'));
    
    // Eventos más vendidos
    $top_events = $db->query("
        SELECT 
            e.title,
            u.full_name as creator,
            COUNT(p.id) as sales,
            COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as revenue,
            SUM(CASE WHEN p.payment_method = 'free' THEN 1 ELSE 0 END) as free_access
        FROM events e
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status='completed'
        LEFT JOIN users u ON e.created_by = u.id
        GROUP BY e.id, e.title, u.full_name
        ORDER BY sales DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Ventas por día
    $daily_sales = $db->query("
        SELECT 
            DATE(purchased_at) as date,
            COUNT(*) as sales,
            COALESCE(SUM(CASE WHEN payment_method != 'free' THEN amount ELSE 0 END), 0) as revenue,
            SUM(CASE WHEN payment_method = 'free' THEN 1 ELSE 0 END) as free_access
        FROM purchases
        WHERE status='completed' AND purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(purchased_at)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
        
} else {
    // Streamer: solo sus métricas
    
    // Obtener comisión personalizada del streamer o usar la predeterminada
    $commissionQuery = $db->prepare("
        SELECT 
            COALESCE(commission_percentage, ?) as commission_percentage,
            COALESCE(platform_percentage, ?) as platform_percentage
        FROM streamer_commissions 
        WHERE streamer_id = ? AND is_active = 1
    ");
    $commissionQuery->execute([$defaultStreamerCommission, $defaultPlatformCommission, $userId]);
    $commission = $commissionQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$commission) {
        $commission = [
            'commission_percentage' => $defaultStreamerCommission,
            'platform_percentage' => $defaultPlatformCommission
        ];
    }
    
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM events WHERE created_by = ?) as total_events,
            (SELECT COUNT(DISTINCT p.user_id) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed') as paying_users,
            (SELECT COUNT(*) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed') as total_sales,
            (SELECT COALESCE(SUM(p.amount), 0) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed' AND p.payment_method != 'free') as total_revenue,
            (SELECT COUNT(*) FROM purchases p JOIN events e ON p.event_id = e.id WHERE e.created_by = ? AND p.status='completed' AND p.payment_method = 'free') as free_access
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    $metrics['total_users'] = 0;
    
    // Calcular ganancias del streamer
    $metrics['streamer_earnings'] = $metrics['total_revenue'] * ($commission['commission_percentage'] / 100);
    $metrics['platform_earnings'] = $metrics['total_revenue'] * ($commission['platform_percentage'] / 100);
    $metrics['commission_percentage'] = $commission['commission_percentage'];
    
    // Sus eventos más vendidos
    $stmt = $db->prepare("
        SELECT 
            e.title,
            COUNT(p.id) as sales,
            COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as revenue,
            SUM(CASE WHEN p.payment_method = 'free' THEN 1 ELSE 0 END) as free_access,
            COALESCE(
                SUM(CASE WHEN p.payment_method != 'free' THEN p.amount * (? / 100) ELSE 0 END), 0
            ) as streamer_earnings
        FROM events e
        LEFT JOIN purchases p ON e.id = p.event_id AND p.status='completed'
        WHERE e.created_by = ?
        GROUP BY e.id, e.title
        ORDER BY sales DESC
        LIMIT 10
    ");
    $stmt->execute([$commission['commission_percentage'], $userId]);
    $top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sus ventas por día
    $stmt = $db->prepare("
        SELECT 
            DATE(p.purchased_at) as date,
            COUNT(*) as sales,
            COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as revenue,
            SUM(CASE WHEN p.payment_method = 'free' THEN 1 ELSE 0 END) as free_access,
            COALESCE(
                SUM(CASE WHEN p.payment_method != 'free' THEN p.amount * (? / 100) ELSE 0 END), 0
            ) as streamer_earnings
        FROM purchases p
        JOIN events e ON p.event_id = e.id
        WHERE e.created_by = ? AND p.status='completed' AND p.purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(p.purchased_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$commission['commission_percentage'], $userId]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Asegurar que no hay NULL
$metrics['total_events'] = isset($metrics['total_events']) ? $metrics['total_events'] : 0;
$metrics['total_users'] = isset($metrics['total_users']) ? $metrics['total_users'] : 0;
$metrics['total_sales'] = isset($metrics['total_sales']) ? $metrics['total_sales'] : 0;
$metrics['total_revenue'] = isset($metrics['total_revenue']) ? $metrics['total_revenue'] : 0;
$metrics['paying_users'] = isset($metrics['paying_users']) ? $metrics['paying_users'] : 0;
$metrics['free_access'] = isset($metrics['free_access']) ? $metrics['free_access'] : 0;
$metrics['platform_earnings'] = isset($metrics['platform_earnings']) ? $metrics['platform_earnings'] : 0;
$metrics['streamer_earnings'] = isset($metrics['streamer_earnings']) ? $metrics['streamer_earnings'] : 0;

// Calcular tasa de conversión (solo para admin)
$conversion_rate = 0;
if ($isAdmin && $metrics['total_users'] > 0) {
    $conversion_rate = round(($metrics['paying_users'] / $metrics['total_users']) * 100, 1);
}

$page_title = "Analíticas";
$page_icon = "📊";

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.commission-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.commission-item {
    text-align: center;
}

.commission-item .label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.commission-item .value {
    font-size: 32px;
    font-weight: bold;
}

.earnings-breakdown {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.earnings-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.earnings-card .label {
    font-size: 13px;
    color: #999;
    margin-bottom: 10px;
}

.earnings-card .amount {
    font-size: 28px;
    font-weight: bold;
    color: #4CAF50;
}

.earnings-card .percentage {
    font-size: 14px;
    color: #999;
    margin-top: 5px;
}

.payment-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.payment-status.ready {
    background: #4CAF50;
    color: white;
}

.payment-status.below-min {
    background: #ff9800;
    color: white;
}

.total-pending-banner {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}

.total-pending-banner h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    opacity: 0.9;
}

.total-pending-banner .amount {
    font-size: 48px;
    font-weight: bold;
    margin: 0;
}

.payment-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 10px;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
}

.btn-success {
    background: #4CAF50;
    color: white;
}

.btn-info {
    background: #2196F3;
    color: white;
}
</style>

<?php if ($isStreamer): ?>
<div class="commission-info">
    <div class="commission-item">
        <div class="label">Tu Comisión (Libre de impuestos)</div>
        <div class="value"><?= number_format($metrics['commission_percentage'], 0) ?>%</div>
    </div>
    <div class="commission-item">
        <div class="label">Plataforma</div>
        <div class="value"><?= number_format(100 - $metrics['commission_percentage'], 0) ?>%</div>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🎬</div>
        <div class="stat-value"><?= number_format($metrics['total_events']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Eventos' : 'Eventos Creados' ?></div>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($metrics['total_users']) ?></div>
        <div class="stat-label">Usuarios Totales</div>
    </div>
    <?php endif; ?>
    
    <div class="stat-card">
        <div class="stat-icon">💳</div>
        <div class="stat-value"><?= number_format($metrics['paying_users']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Compradores' : 'Usuarios Pagadores' ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-value"><?= number_format($metrics['total_sales']) ?></div>
        <div class="stat-label"><?= $isStreamer ? 'Mis Ventas' : 'Ventas Totales' ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🎁</div>
        <div class="stat-value"><?= number_format($metrics['free_access']) ?></div>
        <div class="stat-label">Accesos Gratuitos</div>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-value"><?= $conversion_rate ?>%</div>
        <div class="stat-label">Tasa de Conversión</div>
    </div>
    <?php endif; ?>
</div>

<div class="section">
    <h2>💰 Resumen de Ganancias</h2>
    <div class="earnings-breakdown">
        <div class="earnings-card">
            <div class="label">Revenue Total</div>
            <div class="amount">$<?= number_format((float)$metrics['total_revenue'], 2) ?></div>
            <div class="percentage">100% del total vendido</div>
        </div>
        
        <?php if ($isStreamer): ?>
        <div class="earnings-card">
            <div class="label">Tus Ganancias</div>
            <div class="amount">$<?= number_format((float)$metrics['streamer_earnings'], 2) ?></div>
            <div class="percentage"><?= number_format($metrics['commission_percentage'], 0) ?>% de comisión</div>
        </div>
        
        <div class="earnings-card">
            <div class="label">Comisión Plataforma</div>
            <div class="amount">$<?= number_format((float)$metrics['platform_earnings'], 2) ?></div>
            <div class="percentage"><?= number_format(100 - $metrics['commission_percentage'], 0) ?>% del total</div>
        </div>
        <?php else: ?>
        <div class="earnings-card">
            <div class="label">Ganancias Streamers</div>
            <div class="amount">$<?= number_format((float)$metrics['streamer_earnings'], 2) ?></div>
            <div class="percentage">Comisiones pagadas</div>
        </div>
        
        <div class="earnings-card">
            <div class="label">Ganancias Plataforma</div>
            <div class="amount">$<?= number_format((float)$metrics['platform_earnings'], 2) ?></div>
            <div class="percentage">Comisión retenida</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin && !empty($pending_payments)): ?>
<div class="section">
    <div class="total-pending-banner">
        <h3>💳 Total Pendiente de Pago a Streamers</h3>
        <p class="amount">$<?= number_format($total_pending_all, 2) ?></p>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">
            <?= count($pending_payments) ?> streamer<?= count($pending_payments) != 1 ? 's' : '' ?> con pagos pendientes
        </p>
    </div>

    <h2>💰 Pagos Pendientes por Streamer</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Streamer</th>
                    <th>Contacto</th>
                    <th>Transacciones</th>
                    <th>Total Ganado</th>
                    <th>Ya Pagado</th>
                    <th>Pendiente</th>
                    <th>Mínimo Retiro</th>
                    <th>Estado</th>
                    <th>Última Venta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_payments as $payment): ?>
                <?php 
                    $isReady = $payment['pending_payment'] >= $payment['min_payout'];
                    $statusClass = $isReady ? 'ready' : 'below-min';
                    $statusText = $isReady ? '✓ Listo para pagar' : 'Por debajo del mínimo';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($payment['full_name']) ?></strong><br>
                        <small style="color: #999;"><?= number_format($payment['commission_percentage'], 0) ?>% comisión</small>
                    </td>
                    <td>
                        <div style="font-size: 13px;">
                            📧 <?= htmlspecialchars($payment['email']) ?><br>
                            <?php if ($payment['phone']): ?>
                            📱 <?= htmlspecialchars($payment['phone']) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= number_format($payment['total_transactions']) ?></td>
                    <td><strong style="color: #2196F3;">$<?= number_format((float)$payment['total_earned'], 2) ?></strong></td>
                    <td><strong style="color: #4CAF50;">$<?= number_format((float)$payment['total_paid'], 2) ?></strong></td>
                    <td>
                        <strong style="color: <?= $isReady ? '#ff9800' : '#999' ?>; font-size: 16px;">
                            $<?= number_format((float)$payment['pending_payment'], 2) ?>
                        </strong>
                    </td>
                    <td>$<?= number_format((float)$payment['min_payout'], 2) ?></td>
                    <td>
                        <span class="payment-status <?= $statusClass ?>">
                            <?= $statusText ?>
                        </span>
                    </td>
                    <td><?= $payment['last_sale_date'] ? date('d/m/Y', strtotime($payment['last_sale_date'])) : 'N/A' ?></td>
                    <td>
                        <div class="payment-actions">
                            <?php if ($isReady): ?>
                            <a href="/admin/process_payment.php?streamer_id=<?= $payment['id'] ?>" 
                               class="btn-small btn-success"
                               onclick="return confirm('¿Confirmar pago de $<?= number_format((float)$payment['pending_payment'], 2) ?> a <?= htmlspecialchars($payment['full_name']) ?>?')">
                                💳 Pagar
                            </a>
                            <?php endif; ?>
                            <a href="/admin/streamer_detail.php?id=<?= $payment['id'] ?>" 
                               class="btn-small btn-info">
                                👁️ Detalle
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: rgba(255,255,255,0.05); font-weight: bold;">
                    <td colspan="3">TOTAL</td>
                    <td>$<?= number_format(array_sum(array_column($pending_payments, 'total_earned')), 2) ?></td>
                    <td>$<?= number_format(array_sum(array_column($pending_payments, 'total_paid')), 2) ?></td>
                    <td style="color: #ff9800; font-size: 16px;">
                        $<?= number_format($total_pending_all, 2) ?>
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
        <h4 style="margin: 0 0 10px 0;">📋 Leyenda</h4>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
            <li><strong>Total Ganado:</strong> Suma de todas las comisiones de ventas completadas</li>
            <li><strong>Ya Pagado:</strong> Monto que ya fue transferido al streamer</li>
            <li><strong>Pendiente:</strong> Diferencia entre total ganado y pagado</li>
            <li><strong>Mínimo Retiro:</strong> Umbral configurado para procesar pagos (configurable por streamer)</li>
            <li><span class="payment-status ready">✓ Listo para pagar</span> El pendiente supera el mínimo de retiro</li>
            <li><span class="payment-status below-min">Por debajo del mínimo</span> Aún no alcanza el umbral mínimo</li>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin && !empty($top_streamers)): ?>
<div class="section">
    <h2>🌟 Top Streamers por Ganancias</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Streamer</th>
                    <th>Eventos</th>
                    <th>Ventas</th>
                    <th>Revenue</th>
                    <th>Comisión</th>
                    <th>Ganancias</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_streamers as $streamer): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($streamer['full_name']) ?></strong><br>
                        <small style="color: #999;"><?= htmlspecialchars($streamer['email']) ?></small>
                    </td>
                    <td><?= number_format($streamer['total_events']) ?></td>
                    <td><?= number_format($streamer['total_sales']) ?></td>
                    <td><strong>$<?= number_format((float)$streamer['revenue'], 2) ?></strong></td>
                    <td><?= number_format($streamer['commission_percentage'], 0) ?>%</td>
                    <td><strong style="color: #4CAF50;">$<?= number_format((float)$streamer['streamer_earnings'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($daily_sales)): ?>
<div class="section">
    <h2>📈 <?= $isStreamer ? 'Mis Ventas' : 'Ventas' ?> Últimos 30 Días</h2>
    <canvas id="salesChart" height="80"></canvas>
</div>
<?php else: ?>
<div class="section">
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <h3>No hay datos de ventas</h3>
        <p>Los gráficos aparecerán cuando haya transacciones completadas</p>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <h2>🏆 <?= $isStreamer ? 'Mis Eventos' : 'Eventos' ?> Más Vendidos</h2>
    <?php if (!empty($top_events) && $top_events[0]['sales'] > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <?php if ($isAdmin): ?>
                    <th>Creador</th>
                    <?php endif; ?>
                    <th>Ventas</th>
                    <th>Accesos Gratis</th>
                    <th>Revenue</th>
                    <?php if ($isStreamer): ?>
                    <th>Tus Ganancias</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_events as $event): ?>
                    <?php if ($event['sales'] > 0): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['title']) ?></td>
                        <?php if ($isAdmin): ?>
                        <td><?= htmlspecialchars($event['creator']) ?></td>
                        <?php endif; ?>
                        <td><strong><?= number_format($event['sales']) ?></strong></td>
                        <td><?= number_format($event['free_access']) ?></td>
                        <td><strong style="color: #4CAF50;">$<?= number_format((float)$event['revenue'], 2) ?></strong></td>
                        <?php if ($isStreamer): ?>
                        <td><strong style="color: #4CAF50;">$<?= number_format((float)$event['streamer_earnings'], 2) ?></strong></td>
                        <?php endif; ?>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <h3>No hay ventas de eventos</h3>
        <p>Esta tabla mostrará los eventos más vendidos cuando haya compras</p>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($daily_sales)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const datasets = [{
        label: 'Ventas',
        data: <?= json_encode(array_column($daily_sales, 'sales')) ?>,
        borderColor: '#667eea',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y'
    }];
    
    <?php if ($isStreamer): ?>
    datasets.push({
        label: 'Tus Ganancias ($)',
        data: <?= json_encode(array_column($daily_sales, 'streamer_earnings')) ?>,
        borderColor: '#4CAF50',
        backgroundColor: 'rgba(76, 175, 80, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y1'
    });
    <?php else: ?>
    datasets.push({
        label: 'Revenue Total ($)',
        data: <?= json_encode(array_column($daily_sales, 'revenue')) ?>,
        borderColor: '#4CAF50',
        backgroundColor: 'rgba(76, 175, 80, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y1'
    });
    <?php endif; ?>
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($daily_sales, 'date')) ?>,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: { 
                legend: { 
                    position: 'top',
                    labels: { 
                        font: { size: 14 },
                        color: '#fff'
                    }
                },
                tooltip: { 
                    backgroundColor: 'rgba(0,0,0,0.8)', 
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: { 
                        font: { size: 12 },
                        color: '#999'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    ticks: { 
                        font: { size: 12 },
                        color: '#999',
                        callback: function(value) {
                            return ' + value.toFixed(2);
                        }
                    },
                    grid: {
                        drawOnChartArea: false,
                    }
                },
                x: {
                    ticks: { 
                        font: { size: 12 },
                        color: '#999'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>