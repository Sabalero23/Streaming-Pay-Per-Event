<?php
// admin/streamer_detail.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$streamer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$streamer_id) {
    header('Location: /admin/analytics.php');
    exit;
}

// Obtener configuración del sistema
$systemConfig = $db->query("
    SELECT config_key, config_value
    FROM system_config 
    WHERE config_key IN ('default_commission_percentage', 'platform_commission')
")->fetchAll(PDO::FETCH_KEY_PAIR);

$defaultStreamerCommission = isset($systemConfig['default_commission_percentage']) ? (float)$systemConfig['default_commission_percentage'] : 80.00;
$defaultPlatformCommission = isset($systemConfig['platform_commission']) ? (float)$systemConfig['platform_commission'] : 20.00;

// Obtener información del streamer
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.status,
        u.created_at,
        COALESCE(sc.commission_percentage, ?) as commission_percentage,
        COALESCE(sc.platform_percentage, ?) as platform_percentage,
        COALESCE(sc.min_payout, 1000.00) as min_payout,
        sc.payment_method,
        sc.payment_details,
        sc.is_active as commission_active
    FROM users u
    LEFT JOIN streamer_commissions sc ON u.id = sc.streamer_id AND sc.is_active = 1
    WHERE u.id = ?
");
$stmt->execute([$defaultStreamerCommission, $defaultPlatformCommission, $streamer_id]);
$streamer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$streamer) {
    header('Location: /admin/analytics.php');
    exit;
}

// Obtener estadísticas del streamer
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT e.id) as total_events,
        COUNT(p.id) as total_sales,
        COUNT(DISTINCT p.user_id) as unique_buyers,
        COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as total_revenue,
        SUM(CASE WHEN p.payment_method = 'free' THEN 1 ELSE 0 END) as free_access,
        COALESCE(
            SUM(CASE WHEN p.payment_method != 'free' 
                THEN p.amount * (? / 100) ELSE 0 END), 0
        ) as total_earned,
        MIN(p.purchased_at) as first_sale,
        MAX(p.purchased_at) as last_sale
    FROM events e
    LEFT JOIN purchases p ON e.id = p.event_id AND p.status = 'completed'
    WHERE e.created_by = ?
");
$stmt->execute([$streamer['commission_percentage'], $streamer_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener pagos realizados
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(streamer_earnings), 0) as total_paid,
        COUNT(*) as payment_count
    FROM earnings
    WHERE streamer_id = ? AND status = 'paid'
");
$stmt->execute([$streamer_id]);
$payments = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular pendiente
$pending_payment = $stats['total_earned'] - $payments['total_paid'];
$can_payout = $pending_payment >= $streamer['min_payout'];

// Obtener eventos del streamer
$stmt = $db->prepare("
    SELECT 
        e.id,
        e.title,
        e.status,
        e.price,
        e.currency,
        e.scheduled_start,
        e.created_at,
        COUNT(p.id) as sales,
        COALESCE(SUM(CASE WHEN p.payment_method != 'free' THEN p.amount ELSE 0 END), 0) as revenue,
        SUM(CASE WHEN p.payment_method = 'free' THEN 1 ELSE 0 END) as free_access,
        COALESCE(
            SUM(CASE WHEN p.payment_method != 'free' 
                THEN p.amount * (? / 100) ELSE 0 END), 0
        ) as earnings
    FROM events e
    LEFT JOIN purchases p ON e.id = p.event_id AND p.status = 'completed'
    WHERE e.created_by = ?
    GROUP BY e.id
    ORDER BY e.created_at DESC
    LIMIT 20
");
$stmt->execute([$streamer['commission_percentage'], $streamer_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de pagos
$stmt = $db->prepare("
    SELECT 
        e.id,
        e.purchase_id,
        e.streamer_earnings,
        e.platform_earnings,
        e.currency,
        e.status,
        e.paid_at,
        e.notes,
        e.created_at,
        p.transaction_id,
        ev.title as event_title
    FROM earnings e
    LEFT JOIN purchases p ON e.purchase_id = p.id
    LEFT JOIN events ev ON p.event_id = ev.id
    WHERE e.streamer_id = ?
    ORDER BY e.created_at DESC
    LIMIT 50
");
$stmt->execute([$streamer_id]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Detalle: " . $streamer['full_name'];
$page_icon = "👤";

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.streamer-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.streamer-info h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.streamer-info p {
    margin: 5px 0;
    opacity: 0.9;
}

.streamer-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-box {
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.info-box h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #999;
}

.info-box .value {
    font-size: 28px;
    font-weight: bold;
    color: #4CAF50;
    margin-bottom: 5px;
}

.info-box .label {
    font-size: 13px;
    color: #666;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-active { background: #4CAF50; color: white; }
.status-suspended { background: #ff9800; color: white; }
.status-banned { background: #f44336; color: white; }
.status-live { background: #f44336; color: white; }
.status-scheduled { background: #2196F3; color: white; }
.status-ended { background: #666; color: white; }
.status-cancelled { background: #999; color: white; }

.payment-ready {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}

.payment-ready h3 {
    margin: 0 0 10px 0;
}

.payment-ready .amount {
    font-size: 36px;
    font-weight: bold;
    margin: 10px 0;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.tab {
    padding: 12px 24px;
    cursor: pointer;
    border: none;
    background: none;
    color: #999;
    font-size: 16px;
    transition: all 0.3s;
}

.tab:hover {
    color: #fff;
}

.tab.active {
    color: #fff;
    border-bottom: 2px solid #667eea;
    margin-bottom: -2px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>

<div class="streamer-header">
    <div class="streamer-info">
        <h1>👤 <?= htmlspecialchars($streamer['full_name']) ?></h1>
        <p>📧 <?= htmlspecialchars($streamer['email']) ?></p>
        <?php if ($streamer['phone']): ?>
        <p>📱 <?= htmlspecialchars($streamer['phone']) ?></p>
        <?php endif; ?>
        <p>
            <span class="status-badge status-<?= $streamer['status'] ?>">
                <?= strtoupper($streamer['status']) ?>
            </span>
            <span class="status-badge" style="background: #2196F3; margin-left: 10px;">
                <?= strtoupper($streamer['role']) ?>
            </span>
        </p>
        <p style="font-size: 13px; opacity: 0.7; margin-top: 10px;">
            Miembro desde: <?= date('d/m/Y', strtotime($streamer['created_at'])) ?>
        </p>
    </div>
    
    <div class="streamer-actions">
        <a href="/admin/users.php?edit=<?= $streamer_id ?>" class="btn" style="background: #2196F3;">
            ✏️ Editar Usuario
        </a>
        <a href="/admin/analytics.php" class="btn" style="background: #666;">
            ⬅️ Volver
        </a>
    </div>
</div>

<div class="info-grid">
    <div class="info-box">
        <h3>Comisión</h3>
        <div class="value"><?= number_format($streamer['commission_percentage'], 0) ?>%</div>
        <div class="label">Recibe el streamer</div>
    </div>
    
    <div class="info-box">
        <h3>Plataforma</h3>
        <div class="value"><?= number_format($streamer['platform_percentage'], 0) ?>%</div>
        <div class="label">Comisión retenida</div>
    </div>
    
    <div class="info-box">
        <h3>Mínimo Retiro</h3>
        <div class="value">$<?= number_format($streamer['min_payout'], 2) ?></div>
        <div class="label">Para procesar pagos</div>
    </div>
    
    <div class="info-box">
        <h3>Método de Pago</h3>
        <div class="value" style="font-size: 20px;">
            <?= $streamer['payment_method'] ? strtoupper(str_replace('_', ' ', $streamer['payment_method'])) : 'NO CONFIG.' ?>
        </div>
        <div class="label">Preferencia de pago</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🎬</div>
        <div class="stat-value"><?= number_format($stats['total_events']) ?></div>
        <div class="stat-label">Eventos Creados</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-value"><?= number_format($stats['total_sales']) ?></div>
        <div class="stat-label">Ventas Totales</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($stats['unique_buyers']) ?></div>
        <div class="stat-label">Compradores Únicos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🎁</div>
        <div class="stat-value"><?= number_format($stats['free_access']) ?></div>
        <div class="stat-label">Accesos Gratis</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
        <div class="stat-label">Revenue Total</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-value">$<?= number_format($stats['total_earned'], 2) ?></div>
        <div class="stat-label">Total Ganado</div>
    </div>
</div>

<div class="section">
    <h2>💳 Resumen de Pagos</h2>
    
    <?php if ($can_payout): ?>
    <div class="payment-ready">
        <h3>✅ Pago Disponible</h3>
        <p class="amount">$<?= number_format($pending_payment, 2) ?></p>
        <a href="/admin/process_payment.php?streamer_id=<?= $streamer_id ?>" 
           class="btn btn-primary"
           onclick="return confirm('¿Confirmar pago de $<?= number_format($pending_payment, 2) ?> a <?= htmlspecialchars($streamer['full_name']) ?>?')">
            💳 Procesar Pago Ahora
        </a>
    </div>
    <?php endif; ?>
    
    <div class="info-grid">
        <div class="info-box">
            <h3>Total Ganado</h3>
            <div class="value">$<?= number_format($stats['total_earned'], 2) ?></div>
            <div class="label">Comisiones generadas</div>
        </div>
        
        <div class="info-box">
            <h3>Ya Pagado</h3>
            <div class="value" style="color: #4CAF50;">$<?= number_format($payments['total_paid'], 2) ?></div>
            <div class="label"><?= $payments['payment_count'] ?> pago(s) realizado(s)</div>
        </div>
        
        <div class="info-box">
            <h3>Pendiente</h3>
            <div class="value" style="color: <?= $can_payout ? '#ff9800' : '#999' ?>;">
                $<?= number_format($pending_payment, 2) ?>
            </div>
            <div class="label">
                <?= $can_payout ? '✅ Listo para pagar' : '⏳ Por debajo del mínimo' ?>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Actividad</h3>
            <div class="value" style="font-size: 16px;">
                <?php if ($stats['first_sale']): ?>
                    📅 <?= date('d/m/Y', strtotime($stats['first_sale'])) ?>
                <?php else: ?>
                    Sin ventas
                <?php endif; ?>
            </div>
            <div class="label">Primera venta</div>
        </div>
    </div>
</div>

<div class="section">
    <div class="tabs">
        <button class="tab active" onclick="switchTab('events')">🎬 Eventos (<?= count($events) ?>)</button>
        <button class="tab" onclick="switchTab('payments')">💰 Historial de Pagos (<?= count($payment_history) ?>)</button>
    </div>
    
    <div id="tab-events" class="tab-content active">
        <h2>🎬 Eventos del Streamer</h2>
        <?php if (!empty($events)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th>Fecha</th>
                        <th>Ventas</th>
                        <th>Gratis</th>
                        <th>Revenue</th>
                        <th>Ganancias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                            <small style="color: #999;">ID: <?= $event['id'] ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $event['status'] ?>">
                                <?= strtoupper($event['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($event['price'] > 0): ?>
                                <?= $event['currency'] ?> <?= number_format($event['price'], 2) ?>
                            <?php else: ?>
                                <span style="color: #4CAF50; font-weight: bold;">GRATIS</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?></td>
                        <td><strong><?= number_format($event['sales']) ?></strong></td>
                        <td><?= number_format($event['free_access']) ?></td>
                        <td><strong style="color: #2196F3;">$<?= number_format($event['revenue'], 2) ?></strong></td>
                        <td><strong style="color: #4CAF50;">$<?= number_format($event['earnings'], 2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎬</div>
            <h3>Sin eventos</h3>
            <p>Este streamer aún no ha creado eventos</p>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="tab-payments" class="tab-content">
        <h2>💰 Historial de Pagos</h2>
        <?php if (!empty($payment_history)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>Transacción</th>
                        <th>Ganancias Streamer</th>
                        <th>Comisión Plataforma</th>
                        <th>Estado</th>
                        <th>Pagado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $payment): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                        <td><?= htmlspecialchars($payment['event_title'] ?: 'N/A') ?></td>
                        <td><small><?= htmlspecialchars($payment['transaction_id']) ?></small></td>
                        <td><strong style="color: #4CAF50;">$<?= number_format($payment['streamer_earnings'], 2) ?></strong></td>
                        <td>$<?= number_format($payment['platform_earnings'], 2) ?></td>
                        <td>
                            <span class="status-badge" style="background: <?= $payment['status'] === 'paid' ? '#4CAF50' : ($payment['status'] === 'pending' ? '#ff9800' : '#f44336') ?>;">
                                <?= strtoupper($payment['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $payment['paid_at'] ? date('d/m/Y', strtotime($payment['paid_at'])) : '-' ?>
                        </td>
                        <td><?= htmlspecialchars($payment['notes'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">💰</div>
            <h3>Sin historial de pagos</h3>
            <p>No hay registros de pagos para este streamer</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Desactivar todos los tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Activar el tab y contenido seleccionado
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>