<?php
// public/watch.php
// Página para ver el evento en vivo
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$event_id = $_GET['id'] ?? 0;

// Verificar que el usuario compró el evento
$stmt = $db->prepare("
    SELECT e.*, p.id as purchase_id
    FROM events e
    JOIN purchases p ON e.id = p.event_id
    WHERE e.id = ? AND p.user_id = ? AND p.status = 'completed'
");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: /public/event.php?id=' . $event_id);
    exit;
}

$page_title = "Viendo: " . $event['title'];

// Registrar espectador activo
$stmt = $db->prepare("INSERT INTO active_sessions (user_id, event_id, last_heartbeat) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_heartbeat = NOW()");
$stmt->execute([$_SESSION['user_id'], $event_id]);

// Obtener espectadores activos
$stmt = $db->prepare("SELECT COUNT(*) as count FROM active_sessions WHERE event_id = ? AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute([$event_id]);
$viewers = $stmt->fetch()['count'] ?? 0;

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.watch-container {
    max-width: 1400px;
    margin: 0 auto;
}

.video-section {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 20px;
    margin-bottom: 30px;
}

.video-main {
    background: #000;
    border-radius: 12px;
    overflow: hidden;
}

.video-player {
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.video-info {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 0 0 12px 12px;
}

.chat-section {
    background: #1a1a1a;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    height: 600px;
}

.chat-header {
    padding: 20px;
    border-bottom: 1px solid #333;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.chat-message {
    margin-bottom: 15px;
}

.chat-message .username {
    font-weight: bold;
    color: #667eea;
    margin-right: 5px;
}

.chat-input {
    padding: 20px;
    border-top: 1px solid #333;
}

.chat-input input {
    width: 100%;
    padding: 12px;
    background: #0f0f0f;
    border: 1px solid #333;
    border-radius: 5px;
    color: white;
}

.event-description {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .video-section {
        grid-template-columns: 1fr;
    }
    
    .chat-section {
        height: 400px;
    }
}
</style>

<div class="section">
    <div class="watch-container">
        <?php if ($event['status'] === 'live'): ?>
        <div class="alert alert-success">
            🔴 <strong>EN VIVO AHORA</strong> · <?= $viewers ?> espectadores
        </div>
        <?php elseif ($event['status'] === 'ended'): ?>
        <div class="alert alert-info">
            📹 <strong>GRABACIÓN</strong> · Este evento ha finalizado
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            ⏰ <strong>PRÓXIMAMENTE</strong> · El evento comenzará el <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
        </div>
        <?php endif; ?>

        <div class="video-section">
            <div>
                <div class="video-main">
                    <div class="video-player" id="videoPlayer">
                        <?php if ($event['status'] === 'live' || ($event['status'] === 'ended' && $event['enable_recording'])): ?>
                            <video controls autoplay style="width: 100%; height: 100%;">
                                <source src="<?= htmlspecialchars($event['stream_url'] ?? '') ?>" type="application/x-mpegURL">
                                Tu navegador no soporta el reproductor de video.
                            </video>
                        <?php else: ?>
                            <div style="text-align: center;">
                                <div style="font-size: 64px; margin-bottom: 20px;">⏰</div>
                                <h2>El evento aún no ha comenzado</h2>
                                <p style="color: #999; margin-top: 10px;">
                                    Vuelve el <?= date('d/m/Y', strtotime($event['scheduled_start'])) ?><br>
                                    a las <?= date('H:i', strtotime($event['scheduled_start'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="video-info">
                        <h1 style="margin-bottom: 10px;"><?= htmlspecialchars($event['title']) ?></h1>
                        <div style="display: flex; align-items: center; gap: 15px; color: #999;">
                            <span><?= htmlspecialchars($event['category']) ?></span>
                            <span>·</span>
                            <span><?= date('d/m/Y', strtotime($event['scheduled_start'])) ?></span>
                            <?php if ($event['status'] === 'live'): ?>
                            <span>·</span>
                            <span>👁️ <?= $viewers ?> espectadores</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($event['enable_chat'] && $event['status'] === 'live'): ?>
            <div class="chat-section">
                <div class="chat-header">
                    <h3>💬 Chat en Vivo</h3>
                    <p style="color: #999; font-size: 13px;">
                        <?= $viewers ?> espectadores conectados
                    </p>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message">
                        <span class="username">Sistema</span>
                        <span style="color: #999;">Bienvenido al chat</span>
                    </div>
                </div>
                
                <div class="chat-input">
                    <input type="text" id="chatInput" placeholder="Escribe un mensaje...">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="event-description">
            <h2 style="margin-bottom: 20px;">Sobre este evento</h2>
            <p style="color: #ccc; line-height: 1.8;">
                <?= nl2br(htmlspecialchars($event['description'])) ?>
            </p>
        </div>

        <div style="text-align: center;">
            <a href="/public/events.php" class="btn btn-secondary">← Volver a Eventos</a>
            <a href="/public/profile.php" class="btn btn-primary">Ver Mis Compras</a>
        </div>
    </div>
</div>

<script>
// Heartbeat para mantener sesión activa
setInterval(() => {
    fetch('/api/heartbeat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            event_id: <?= $event_id ?>,
            user_id: <?= $_SESSION['user_id'] ?>
        })
    });
}, 30000); // Cada 30 segundos

// Chat básico (requiere WebSocket o polling)
const chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && chatInput.value.trim()) {
            // Aquí iría la lógica de enviar mensaje
            const message = chatInput.value;
            console.log('Mensaje:', message);
            chatInput.value = '';
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
