<?php
// admin/stream-settings.php
// Página de configuración de streaming para OBS
session_start();

$allowedRoles = ['admin', 'streamer', 'moderator'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /public/login.php');
    exit;
}

$isAdmin = $_SESSION['user_role'] === 'admin';
$isStreamer = $_SESSION['user_role'] === 'streamer';
$userId = $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/streaming.php';

$streamingConfig = require __DIR__ . '/../config/streaming.php';
$rtmpServer = $streamingConfig['rtmp']['server_url'];

// Obtener eventos del streamer
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM events WHERE created_by = ? AND status IN ('scheduled', 'live') ORDER BY scheduled_start DESC LIMIT 10");
$stmt->execute([$userId]);
$myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Configuración de Streaming";
$page_icon = "⚙️";

require_once 'header.php';
require_once 'styles.php';
?>

<style>
    .guide-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .guide-section h2 {
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .step-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    
    .step-number {
        display: inline-block;
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        text-align: center;
        line-height: 40px;
        font-weight: bold;
        font-size: 20px;
        margin-right: 15px;
    }
    
    .code-box {
        background: #2c3e50;
        color: #4CAF50;
        padding: 15px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 14px;
        margin: 10px 0;
        word-break: break-all;
        position: relative;
    }
    
    .copy-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #4CAF50;
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .copy-btn:hover {
        background: #45a049;
    }
    
    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196F3;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .success-box {
        background: #d4edda;
        border-left: 4px solid #28a745;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .video-guide {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        overflow: hidden;
        background: #000;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .video-guide iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .event-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 2px solid #e0e0e0;
    }
    
    .event-card h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .stream-key-display {
        background: #2c3e50;
        color: #4CAF50;
        padding: 10px;
        border-radius: 5px;
        font-family: monospace;
        display: inline-block;
        margin: 5px 0;
    }
</style>

<div class="guide-section">
    <h2>🎬 Configuración Rápida de OBS Studio</h2>
    
    <div class="info-box">
        <strong>📋 Servidor RTMP:</strong><br>
        <div class="code-box">
            <?= htmlspecialchars($rtmpServer) ?>
            <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($rtmpServer) ?>')">Copiar</button>
        </div>
    </div>
    
    <div class="step-card">
        <h3><span class="step-number">1</span> Descarga OBS Studio</h3>
        <p>Si aún no tienes OBS Studio, descárgalo gratuitamente desde:</p>
        <a href="https://obsproject.com/download" target="_blank" style="color: white; text-decoration: underline;">
            https://obsproject.com/download
        </a>
    </div>
    
    <div class="step-card">
        <h3><span class="step-number">2</span> Configura OBS</h3>
        <p><strong>Ve a:</strong> Configuración → Emisión</p>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>Servicio: <strong>Personalizado</strong></li>
            <li>Servidor: <strong><?= htmlspecialchars($rtmpServer) ?></strong></li>
            <li>Clave de transmisión: <strong>Usa la clave de tu evento (ver abajo)</strong></li>
        </ul>
    </div>
    
    <div class="step-card">
        <h3><span class="step-number">3</span> Configuración Recomendada</h3>
        <p><strong>Configuración → Salida:</strong></p>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>Codificador: x264</li>
            <li>Velocidad de bits: 3000-5000 Kbps (según tu internet)</li>
            <li>Intervalo de fotograma clave: 2</li>
        </ul>
        <p style="margin-top: 15px;"><strong>Configuración → Video:</strong></p>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>Resolución: 1920x1080 o 1280x720</li>
            <li>FPS: 30 o 60</li>
        </ul>
    </div>
    
    <div class="step-card">
        <h3><span class="step-number">4</span> Inicia tu Transmisión</h3>
        <p>Una vez configurado todo:</p>
        <ol style="margin-top: 10px; margin-left: 20px;">
            <li>Haz clic en "Iniciar transmisión" en OBS</li>
            <li>Verifica que el estado del evento cambie a "EN VIVO" en el dashboard</li>
            <li>Los usuarios que compraron acceso podrán ver tu transmisión</li>
        </ol>
    </div>
</div>

<?php if (!empty($myEvents)): ?>
<div class="guide-section">
    <h2>🔑 Tus Stream Keys</h2>
    
    <div class="warning-box">
        <strong>⚠️ Importante:</strong> Nunca compartas tus Stream Keys públicamente. Son personales e intransferibles.
    </div>
    
    <?php foreach ($myEvents as $event): ?>
    <div class="event-card">
        <h3><?= htmlspecialchars($event['title']) ?></h3>
        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?></p>
        <p><strong>Estado:</strong> 
            <span class="badge badge-<?= $event['status'] === 'live' ? 'live' : 'success' ?>">
                <?= strtoupper($event['status']) ?>
            </span>
        </p>
        <p style="margin-top: 15px;"><strong>Stream Key:</strong></p>
        <div class="code-box">
            <?= $event['stream_key'] ?>
            <button class="copy-btn" onclick="copyToClipboard('<?= $event['stream_key'] ?>')">Copiar</button>
        </div>
        <p style="margin-top: 10px; font-size: 13px; color: #666;">
            <strong>URL completa:</strong>
        </p>
        <div class="code-box" style="font-size: 12px;">
            <?= htmlspecialchars($rtmpServer) ?>/<?= $event['stream_key'] ?>
            <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($rtmpServer) ?>/<?= $event['stream_key'] ?>')">Copiar</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="guide-section">
    <div class="info-box">
        <strong>📝 No tienes eventos programados</strong><br>
        Crea un evento primero para obtener tu Stream Key.
        <br><br>
        <a href="/admin/events.php?action=create" class="btn btn-primary">Crear Evento</a>
    </div>
</div>
<?php endif; ?>

<div class="guide-section">
    <h2>🎥 Video Tutorial</h2>
    <p>Mira este tutorial de cómo configurar OBS Studio para streaming:</p>
    <div class="video-guide">
        <iframe src="https://www.youtube.com/embed/EuSUPpoi0Vs" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
</div>

<div class="guide-section">
    <h2>❓ Preguntas Frecuentes</h2>
    
    <div style="margin-bottom: 20px;">
        <h3 style="color: #667eea;">¿Qué velocidad de internet necesito?</h3>
        <p>Para streaming en 720p a 30fps, necesitas al menos 5 Mbps de subida. Para 1080p, recomendamos 10 Mbps o más.</p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="color: #667eea;">¿Puedo usar otro software además de OBS?</h3>
        <p>Sí, cualquier software compatible con RTMP funcionará (Streamlabs OBS, XSplit, vMix, etc.).</p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="color: #667eea;">¿Cómo sé si mi stream está funcionando?</h3>
        <p>Una vez que inicies la transmisión en OBS, el estado de tu evento cambiará a "EN VIVO" automáticamente en el dashboard.</p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="color: #667eea;">¿Puedo hacer pruebas antes del evento?</h3>
        <p>Sí, puedes crear un evento de prueba y transmitir para verificar que todo funcione correctamente.</p>
    </div>
</div>

<div class="success-box">
    <strong>✅ ¿Necesitas ayuda?</strong><br>
    Si tienes problemas con la configuración, contacta al soporte técnico.
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('✅ Copiado al portapapeles: ' + text);
    }, function(err) {
        console.error('Error al copiar: ', err);
        // Fallback para navegadores viejos
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✅ Copiado al portapapeles');
    });
}
</script>

<?php require_once 'footer.php'; ?>