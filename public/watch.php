<?php
// public/watch.php
// Página para ver el evento en vivo - CON SESIÓN ÚNICA
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

// ========================================
// VALIDACIÓN DE SESIÓN ÚNICA
// ========================================

// Generar token de sesión único
$session_token = bin2hex(random_bytes(32));
$_SESSION['current_stream_token'] = $session_token;

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Generar fingerprint del dispositivo (básico)
$device_fingerprint = md5($user_agent . $ip_address);

try {
    // Verificar si ya existe una sesión activa para este usuario y evento
    $stmt = $db->prepare("
        SELECT session_token, ip_address, user_agent, last_heartbeat
        FROM active_sessions 
        WHERE user_id = ? AND event_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $event_id]);
    $existing_session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $session_conflict = false;
    
    if ($existing_session) {
        // Verificar si la sesión existente sigue activa (heartbeat < 1 minuto)
        $last_heartbeat = strtotime($existing_session['last_heartbeat']);
        $now = time();
        $time_diff = $now - $last_heartbeat;
        
        if ($time_diff < 60) {
            // Hay una sesión activa reciente
            $session_conflict = true;
            
            // Registrar conflicto
            $stmt = $db->prepare("
                INSERT INTO session_conflicts 
                (user_id, event_id, old_session_token, new_session_token, 
                 old_ip_address, new_ip_address, old_user_agent, new_user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $event_id, 
                $existing_session['session_token'], 
                $session_token,
                $existing_session['ip_address'], 
                $ip_address,
                $existing_session['user_agent'], 
                $user_agent
            ]);
        }
    }
    
    if (!$session_conflict) {
        // Registrar o actualizar sesión activa
        $stmt = $db->prepare("
            INSERT INTO active_sessions 
            (user_id, event_id, session_token, ip_address, user_agent, device_fingerprint, last_heartbeat) 
            VALUES (?, ?, ?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
                session_token = VALUES(session_token),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                device_fingerprint = VALUES(device_fingerprint),
                last_heartbeat = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $event_id, $session_token, $ip_address, $user_agent, $device_fingerprint]);
    }
} catch (Exception $e) {
    error_log("Error registrando sesión: " . $e->getMessage());
    $session_conflict = false; // En caso de error, permitir acceso
}

// Obtener espectadores activos
$stmt = $db->prepare("SELECT COUNT(*) as count FROM active_sessions WHERE event_id = ? AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute([$event_id]);
$viewers = $stmt->fetch()['count'] ?? 0;

// Construir URL del stream
$stream_url = '';
$stream_available = false;

if ($event['status'] === 'live' || ($event['status'] === 'ended' && $event['enable_recording'])) {
    $stream_url = "https://streaming.cellcomweb.com.ar:8889/live/" . $event['stream_key'] . "/index.m3u8";
    $stream_available = true;
}

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
    position: relative;
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

.stream-status {
    padding: 15px;
    background: #f39c12;
    color: white;
    border-radius: 8px;
    margin: 20px;
    text-align: center;
    font-weight: bold;
}

.stream-status.error {
    background: #e74c3c;
}

.stream-status.success {
    background: #27ae60;
}

/* Overlay de sesión bloqueada */
.session-blocked-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 40px;
    text-align: center;
}

.session-blocked-overlay h2 {
    color: #e74c3c;
    margin-bottom: 20px;
    font-size: 28px;
}

.session-blocked-overlay p {
    color: #ccc;
    line-height: 1.8;
    margin-bottom: 15px;
    max-width: 500px;
}

.session-blocked-overlay .icon {
    font-size: 80px;
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

.chat-message {
    margin-bottom: 12px;
    word-wrap: break-word;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-message .username {
    font-weight: bold;
    margin-right: 5px;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #0f0f0f;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 3px;
}

.chat-input input:focus {
    outline: none;
    border-color: #667eea;
}

.chat-input input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<!-- HLS.js para reproducción HLS -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<div class="section">
    <div class="watch-container">
        <?php if ($event['status'] === 'live'): ?>
        <div class="alert alert-success">
            🔴 <strong>EN VIVO AHORA</strong> · <?= $viewers ?> espectadores
        </div>
        <?php elseif ($event['status'] === 'ended'): ?>
        <div class="alert alert-info">
            🔹 <strong>GRABACIÓN</strong> · Este evento ha finalizado
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            ⏰ <strong>PRÓXIMAMENTE</strong> · El evento comenzará el <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
        </div>
        <?php endif; ?>

        <div class="video-section">
            <div>
                <div class="video-main">
                    <?php if ($session_conflict): ?>
                        <!-- Overlay de sesión bloqueada -->
                        <div class="session-blocked-overlay" id="sessionBlockedOverlay">
                            <div class="icon">🔒</div>
                            <h2>Sesión Activa en Otro Dispositivo</h2>
                            <p>
                                Ya estás viendo este evento en otro dispositivo o navegador.
                            </p>
                            <p>
                                <strong>Última conexión:</strong><br>
                                IP: <?= htmlspecialchars(substr($existing_session['ip_address'], 0, 20)) ?>...<br>
                                Hace <?= floor($time_diff / 60) ?> minutos
                            </p>
                            <p style="color: #f39c12; font-weight: bold;">
                                ⚠️ Por razones de seguridad y licencia, solo puedes ver el evento en un dispositivo a la vez.
                            </p>
                            <div style="margin-top: 30px;">
                                <button onclick="forceNewSession()" class="btn btn-primary" style="margin-right: 10px;">
                                    🔄 Cerrar otra sesión y ver aquí
                                </button>
                                <a href="/public/events.php" class="btn btn-secondary">
                                    ← Volver a Eventos
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="video-player" id="videoPlayer" <?= $session_conflict ? 'style="display:none;"' : '' ?>>
                        <?php if ($stream_available): ?>
                            <video id="video" controls autoplay muted style="width: 100%; height: 100%;">
                                Tu navegador no soporta el reproductor de video.
                            </video>
                            <div id="streamStatus" class="stream-status" style="display:none;">
                                Conectando al stream...
                            </div>
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
                            <span id="viewerCount">👁️ <?= $viewers ?> espectadores</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($event['enable_chat'] && $event['status'] === 'live' && !$session_conflict): ?>
            <div class="chat-section">
                <div class="chat-header">
                    <h3>💬 Chat en Vivo</h3>
                    <p style="color: #999; font-size: 13px;">
                        <span id="chatViewers"><?= $viewers ?></span> espectadores conectados
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
// Variables globales
const sessionToken = '<?= $session_token ?>';
const eventId = <?= $event_id ?>;
const userId = <?= $_SESSION['user_id'] ?>;
let sessionBlocked = <?= $session_conflict ? 'true' : 'false' ?>;
let isSessionActive = true;

// Función para forzar nueva sesión
function forceNewSession() {
    if (confirm('¿Estás seguro de que quieres cerrar la otra sesión y ver el evento aquí?')) {
        fetch('/api/force_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_id: eventId,
                user_id: userId,
                session_token: sessionToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar página para mostrar el video
                location.reload();
            } else {
                alert('Error al forzar nueva sesión: ' + data.message);
            }
        })
        .catch(error => {
            console.error('[Force Session Error]', error);
            alert('Error de conexión. Intenta de nuevo.');
        });
    }
}

<?php if ($stream_available && !$session_conflict): ?>
// Configurar reproductor HLS
const video = document.getElementById('video');
const videoSrc = '<?= $stream_url ?>';
const streamStatus = document.getElementById('streamStatus');
let hls = null;
let retryCount = 0;
const maxRetries = 10;

function showStatus(message, type = 'warning') {
    if (streamStatus) {
        streamStatus.textContent = message;
        streamStatus.className = 'stream-status ' + type;
        streamStatus.style.display = 'block';
    }
    console.log('[Stream Status]', message);
}

function hideStatus() {
    if (streamStatus) {
        streamStatus.style.display = 'none';
    }
}

function initializePlayer() {
    if (Hls.isSupported()) {
        hls = new Hls({
            debug: false,
            enableWorker: true,
            lowLatencyMode: true,
            maxBufferLength: 10,
            maxMaxBufferLength: 20,
            maxBufferSize: 60 * 1000 * 1000,
            maxBufferHole: 0.5,
            highBufferWatchdogPeriod: 2,
            nudgeMaxRetry: 5,
            backBufferLength: 10,
            abrEwmaDefaultEstimate: 500000,
            abrBandWidthFactor: 0.8,
            abrBandWidthUpFactor: 0.7,
            manifestLoadingTimeOut: 10000,
            manifestLoadingMaxRetry: 4,
            manifestLoadingRetryDelay: 1000,
            levelLoadingTimeOut: 10000,
            levelLoadingMaxRetry: 4,
            levelLoadingRetryDelay: 1000,
            fragLoadingTimeOut: 20000,
            fragLoadingMaxRetry: 6,
            fragLoadingRetryDelay: 1000,
            startLevel: -1,
            startPosition: -1,
            liveSyncDurationCount: 3,
            liveMaxLatencyDurationCount: 10,
            liveDurationInfinity: false,
            enableSoftwareAES: true,
            maxFragLookUpTolerance: 0.25,
            maxLoadingDelay: 4,
            minAutoBitrate: 0
        });
        
        hls.loadSource(videoSrc);
        hls.attachMedia(video);
        
        showStatus('Conectando al stream...', 'warning');
        
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            console.log('[HLS] Stream cargado correctamente');
            hideStatus();
            video.play().catch(e => {
                console.log('[HLS] Autoplay bloqueado:', e);
                showStatus('Click para reproducir', 'warning');
            });
            retryCount = 0;
        });
        
        hls.on(Hls.Events.ERROR, function(event, data) {
            console.error('[HLS Error]', data.type, data.details, data);
            
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.log('[HLS] Error de red fatal, intentando recuperar...');
                        retryCount++;
                        
                        if (retryCount < maxRetries) {
                            showStatus(`Error de conexión. Reintentando (${retryCount}/${maxRetries})...`, 'error');
                            setTimeout(() => {
                                hls.startLoad();
                            }, 2000 * retryCount);
                        } else {
                            showStatus('No se puede conectar al stream.', 'error');
                            hls.destroy();
                        }
                        break;
                        
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.log('[HLS] Error de media, recuperando...');
                        showStatus('Recuperando reproducción...', 'warning');
                        hls.recoverMediaError();
                        break;
                        
                    default:
                        console.log('[HLS] Error fatal irrecuperable');
                        showStatus('Error de reproducción. Recarga la página.', 'error');
                        hls.destroy();
                        break;
                }
            }
        });
        
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        console.log('[HLS] Usando reproducción nativa de Safari');
        video.src = videoSrc;
        video.addEventListener('loadedmetadata', function() {
            hideStatus();
            video.play();
        });
    } else {
        showStatus('Tu navegador no soporta HLS.', 'error');
    }
}

initializePlayer();
<?php endif; ?>


// ==========================================
// SISTEMA DE CHAT EN TIEMPO REAL CON EMOJIS
// Agregar DESPUÉS del código del reproductor HLS
// ==========================================

// Variables del chat
let lastMessageId = 0;
let chatPollInterval = null;
let isLoadingMessages = false;
let emojiPickerVisible = false;

// Lista de emojis populares para streaming
const emojis = [
    '😀', '😂', '🤣', '😍', '😎', '🤔', '😮', '😱', '😭', '😡',
    '👍', '👎', '👏', '🙌', '🤝', '💪', '🔥', '⭐', '❤️', '💯',
    '🎉', '🎊', '🎁', '🎮', '🎯', '🎪', '🎭', '🎨', '🎬', '🎤',
    '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🎾', '🏐', '🏈', '⚾',
    '👀', '👂', '👃', '🤳', '💬', '💭', '🗨️', '🙏', '✌️', '🤘',
    '🎵', '🎶', '🎸', '🎹', '🎺', '🎷', '🥁', '🎻', '🎼', '🎧',
    '☀️', '⭐', '🌟', '✨', '💫', '🌈', '🔴', '🟠', '🟡', '🟢',
    '🟣', '⚪', '⚫', '🟤', '💥', '💢', '💦', '💨', '🌊', '🔔'
];

// Inicializar chat solo si existe
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');

if (chatMessages && chatInput) {
    console.log('[Chat] Inicializando sistema de chat con emojis...');
    
    // Crear botón de emojis
    createEmojiPicker();
    
    // Cargar mensajes iniciales
    loadChatMessages(true);
    
    // Polling cada 2 segundos para nuevos mensajes
    chatPollInterval = setInterval(() => {
        loadChatMessages(false);
    }, 2000);
    
    // Enviar mensaje con Enter
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage();
        }
    });
    
    // Auto-scroll cuando hay overflow
    chatMessages.addEventListener('DOMNodeInserted', function() {
        if (chatMessages.scrollHeight - chatMessages.scrollTop < chatMessages.clientHeight + 100) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
}

/**
 * Crear selector de emojis
 */
function createEmojiPicker() {
    const chatInputContainer = chatInput.parentElement;
    
    // Crear contenedor para input y botón
    const inputWrapper = document.createElement('div');
    inputWrapper.style.cssText = 'display: flex; gap: 10px; align-items: center; position: relative;';
    
    // Mover input al wrapper
    chatInputContainer.appendChild(inputWrapper);
    inputWrapper.appendChild(chatInput);
    
    // Modificar estilo del input
    chatInput.style.width = 'calc(100% - 50px)';
    
    // Crear botón de emoji
    const emojiButton = document.createElement('button');
    emojiButton.innerHTML = '😊';
    emojiButton.style.cssText = `
        background: #667eea;
        border: none;
        border-radius: 5px;
        width: 40px;
        height: 40px;
        font-size: 20px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    `;
    
    emojiButton.addEventListener('mouseenter', () => {
        emojiButton.style.background = '#5568d3';
        emojiButton.style.transform = 'scale(1.1)';
    });
    
    emojiButton.addEventListener('mouseleave', () => {
        emojiButton.style.background = '#667eea';
        emojiButton.style.transform = 'scale(1)';
    });
    
    emojiButton.addEventListener('click', (e) => {
        e.preventDefault();
        toggleEmojiPicker();
    });
    
    inputWrapper.appendChild(emojiButton);
    
    // Crear panel de emojis
    const emojiPanel = document.createElement('div');
    emojiPanel.id = 'emojiPanel';
    emojiPanel.style.cssText = `
        display: none;
        position: absolute;
        bottom: 60px;
        right: 0;
        background: #1a1a1a;
        border: 1px solid #667eea;
        border-radius: 10px;
        padding: 15px;
        max-width: 320px;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 -4px 20px rgba(102, 126, 234, 0.3);
        z-index: 1000;
        animation: slideUp 0.3s ease;
    `;
    
    // Agregar emojis al panel
    emojis.forEach(emoji => {
        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = emoji;
        emojiSpan.style.cssText = `
            display: inline-block;
            font-size: 24px;
            padding: 8px;
            cursor: pointer;
            transition: transform 0.2s;
            user-select: none;
        `;
        
        emojiSpan.addEventListener('mouseenter', () => {
            emojiSpan.style.transform = 'scale(1.3)';
        });
        
        emojiSpan.addEventListener('mouseleave', () => {
            emojiSpan.style.transform = 'scale(1)';
        });
        
        emojiSpan.addEventListener('click', () => {
            insertEmoji(emoji);
        });
        
        emojiPanel.appendChild(emojiSpan);
    });
    
    // Scrollbar personalizado
    emojiPanel.style.cssText += `
        scrollbar-width: thin;
        scrollbar-color: #667eea #0f0f0f;
    `;
    
    inputWrapper.appendChild(emojiPanel);
    
    // Cerrar panel al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!emojiPanel.contains(e.target) && e.target !== emojiButton) {
            emojiPanel.style.display = 'none';
            emojiPickerVisible = false;
        }
    });
}

/**
 * Toggle del panel de emojis
 */
function toggleEmojiPicker() {
    const emojiPanel = document.getElementById('emojiPanel');
    if (emojiPanel) {
        if (emojiPickerVisible) {
            emojiPanel.style.display = 'none';
            emojiPickerVisible = false;
        } else {
            emojiPanel.style.display = 'block';
            emojiPickerVisible = true;
        }
    }
}

/**
 * Insertar emoji en el input
 */
function insertEmoji(emoji) {
    const cursorPos = chatInput.selectionStart;
    const textBefore = chatInput.value.substring(0, cursorPos);
    const textAfter = chatInput.value.substring(cursorPos);
    
    chatInput.value = textBefore + emoji + textAfter;
    chatInput.focus();
    
    // Colocar cursor después del emoji
    const newPos = cursorPos + emoji.length;
    chatInput.setSelectionRange(newPos, newPos);
    
    // Cerrar panel
    const emojiPanel = document.getElementById('emojiPanel');
    if (emojiPanel) {
        emojiPanel.style.display = 'none';
        emojiPickerVisible = false;
    }
}

/**
 * Cargar mensajes del chat
 * @param {boolean} initial - Si es la carga inicial
 */
function loadChatMessages(initial = false) {
    if (isLoadingMessages) return;
    
    isLoadingMessages = true;
    const eventId = <?= $event_id ?>;
    
    // URL según si es carga inicial o polling
    const url = initial 
        ? `/api/chat.php?action=get_messages&event_id=${eventId}`
        : `/api/chat.php?action=get_messages&event_id=${eventId}&since_id=${lastMessageId}`;
    
    fetch(url)
        .then(response => {
            console.log('[Chat Debug] Status:', response.status);
            console.log('[Chat Debug] Content-Type:', response.headers.get('content-type'));
            
            // Primero obtener el texto sin importar el content-type
            return response.text().then(text => {
                console.log('[Chat Debug] Respuesta completa (primeros 500 chars):', text.substring(0, 500));
                console.log('[Chat Debug] URL llamada:', url);
                
                // Verificar content-type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('[Chat] ❌ Content-Type incorrecto:', contentType);
                    console.error('[Chat] ❌ Respuesta recibida:', text.substring(0, 500));
                    throw new Error('El servidor no devolvió JSON válido. Ver consola para detalles.');
                }
                
                // Intentar parsear JSON
                try {
                    const data = JSON.parse(text);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${data.error || 'Error desconocido'}`);
                    }
                    return data;
                } catch (e) {
                    console.error('[Chat] ❌ Error parseando JSON:', e);
                    console.error('[Chat] ❌ Texto recibido:', text.substring(0, 500));
                    throw new Error('Respuesta no es JSON válido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                if (initial) {
                    // Limpiar chat en carga inicial
                    chatMessages.innerHTML = '';
                }
                
                // Agregar mensajes
                data.messages.forEach(msg => {
                    appendChatMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                
                console.log(`[Chat] ${data.messages.length} mensaje(s) cargado(s)`);
            } else if (initial && (!data.messages || data.messages.length === 0)) {
                // Chat vacío en carga inicial
                chatMessages.innerHTML = `
                    <div class="chat-message" style="color: #999;">
                        <span class="username">Sistema</span>
                        <span>No hay mensajes aún. ¡Sé el primero en escribir!</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('[Chat Error]', error);
            if (initial) {
                chatMessages.innerHTML = `
                    <div class="chat-message" style="color: #e74c3c;">
                        <span class="username">Sistema</span>
                        <span>Error al cargar el chat. Verifica la consola.</span>
                    </div>
                `;
            }
        })
        .finally(() => {
            isLoadingMessages = false;
        });
}

/**
 * Agregar mensaje al chat (con soporte de emojis)
 * @param {Object} msg - Objeto del mensaje
 */
function appendChatMessage(msg) {
    if (!chatMessages) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message';
    messageDiv.dataset.messageId = msg.id;
    
    // Color del nombre según rol
    let usernameColor = '#667eea';
    if (msg.role === 'admin') usernameColor = '#e74c3c';
    else if (msg.role === 'moderator') usernameColor = '#f39c12';
    else if (msg.role === 'streamer') usernameColor = '#9b59b6';
    
    // Badge de rol (si existe)
    let roleBadge = '';
    if (msg.role_badge) {
        const badgeColors = {
            'ADMIN': '#e74c3c',
            'MOD': '#f39c12',
            'STREAMER': '#9b59b6'
        };
        roleBadge = `<span style="
            background: ${badgeColors[msg.role_badge] || '#666'};
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-right: 5px;
        ">${msg.role_badge}</span>`;
    }
    
    // El mensaje ya viene con emojis, solo necesitamos mostrarlo
    // Los emojis se muestran automáticamente ya que son caracteres Unicode
    const messageText = msg.message;
    
    // Construir mensaje
    messageDiv.innerHTML = `
        ${roleBadge}
        <span class="username" style="color: ${usernameColor};">
            ${msg.user_name}
        </span>
        <span style="color: #999; font-size: 11px; margin-left: 5px;">
            ${msg.time_formatted || ''}
        </span>
        <br>
        <span style="color: ${msg.is_own ? '#fff' : '#ccc'}; font-size: 15px; line-height: 1.4;">
            ${messageText}
        </span>
    `;
    
    // Marcar mensajes propios
    if (msg.is_own) {
        messageDiv.style.background = 'rgba(102, 126, 234, 0.1)';
        messageDiv.style.padding = '8px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.marginBottom = '10px';
    }
    
    chatMessages.appendChild(messageDiv);
    
    // Auto-scroll
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

/**
 * Enviar mensaje al chat
 */
function sendChatMessage() {
    if (!chatInput) return;
    
    const message = chatInput.value.trim();
    
    if (!message) {
        return;
    }
    
    // Validar longitud
    if (message.length > 500) {
        showChatError('Mensaje muy largo (máximo 500 caracteres)');
        return;
    }
    
    // Deshabilitar input mientras se envía
    chatInput.disabled = true;
    const originalPlaceholder = chatInput.placeholder;
    chatInput.placeholder = 'Enviando...';
    
    const eventId = <?= $event_id ?>;
    
    fetch('/api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_message&event_id=${eventId}&message=${encodeURIComponent(message)}`
    })
    .then(response => {
        // Verificar content-type antes de parsear JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('[Chat] Respuesta no es JSON al enviar:', text.substring(0, 200));
                throw new Error('El servidor no devolvió JSON válido');
            });
        }
        
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.error || 'Error al enviar mensaje');
            }).catch(() => {
                throw new Error(`Error HTTP ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            // Agregar mensaje propio inmediatamente
            appendChatMessage(data.data);
            lastMessageId = Math.max(lastMessageId, data.data.id);
            
            // Limpiar input
            chatInput.value = '';
            console.log('[Chat] Mensaje enviado');
        }
    })
    .catch(error => {
        console.error('[Chat Send Error]', error);
        showChatError(error.message || 'Error al enviar mensaje');
    })
    .finally(() => {
        // Rehabilitar input
        chatInput.disabled = false;
        chatInput.placeholder = originalPlaceholder;
        chatInput.focus();
    });
}

/**
 * Mostrar error en el chat
 * @param {string} message - Mensaje de error
 */
function showChatError(message) {
    if (!chatMessages) return;
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'chat-message';
    errorDiv.style.background = 'rgba(231, 76, 60, 0.2)';
    errorDiv.style.padding = '8px';
    errorDiv.style.borderRadius = '5px';
    errorDiv.style.marginBottom = '10px';
    
    errorDiv.innerHTML = `
        <span class="username" style="color: #e74c3c;">Sistema</span>
        <br>
        <span style="color: #e74c3c;">${message}</span>
    `;
    
    chatMessages.appendChild(errorDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Eliminar después de 5 segundos
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Limpiar intervalo al salir de la página
window.addEventListener('beforeunload', function() {
    if (chatPollInterval) {
        clearInterval(chatPollInterval);
    }
});

// Agregar animación CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #emojiPanel::-webkit-scrollbar {
        width: 6px;
    }
    
    #emojiPanel::-webkit-scrollbar-track {
        background: #0f0f0f;
    }
    
    #emojiPanel::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 3px;
    }
`;
document.head.appendChild(style);

console.log('[Chat] Sistema de chat con emojis inicializado correctamente');


// Heartbeat mejorado con validación de sesión
let heartbeatInterval = null;

function sendHeartbeat() {
    if (!isSessionActive) return;
    
    fetch('/api/heartbeat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: eventId,
            user_id: userId,
            session_token: sessionToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Verificar si la sesión sigue siendo válida
            if (data.session_valid === false) {
                // Otra sesión ha tomado el control
                console.warn('[Session] ⚠️ Sesión invalidada por otro dispositivo');
                handleSessionKicked();
            }
            
            // Actualizar contador de espectadores
            if (data.viewers !== undefined) {
                const viewerCount = document.getElementById('viewerCount');
                const chatViewers = document.getElementById('chatViewers');
                
                if (viewerCount) {
                    viewerCount.textContent = `👁️ ${data.viewers} espectadores`;
                }
                if (chatViewers) {
                    chatViewers.textContent = data.viewers;
                }
            }
        }
    })
    .catch(error => {
        console.error('[Heartbeat Error]', error);
    });
}

// Manejar cuando la sesión es expulsada
function handleSessionKicked() {
    isSessionActive = false;
    
    // Detener video
    if (hls) {
        hls.destroy();
    }
    if (video) {
        video.pause();
        video.src = '';
    }
    
    // Mostrar overlay de sesión bloqueada
    const videoPlayer = document.getElementById('videoPlayer');
    const overlay = document.createElement('div');
    overlay.className = 'session-blocked-overlay';
    overlay.innerHTML = `
        <div class="icon">🔒</div>
        <h2>Sesión Cerrada</h2>
        <p>Este evento se está reproduciendo en otro dispositivo.</p>
        <p style="color: #f39c12; font-weight: bold;">
            ⚠️ Tu sesión ha sido cerrada porque iniciaste el stream en otro lugar.
        </p>
        <div style="margin-top: 30px;">
            <button onclick="location.reload()" class="btn btn-primary">
                🔄 Recargar y Ver Aquí
            </button>
        </div>
    `;
    
    if (videoPlayer) {
        videoPlayer.style.display = 'none';
        videoPlayer.parentElement.insertBefore(overlay, videoPlayer);
    }
    
    // Detener heartbeat
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
}

// Enviar heartbeat cada 20 segundos
if (!sessionBlocked) {
    sendHeartbeat();
    heartbeatInterval = setInterval(sendHeartbeat, 20000);
}

// Limpiar al salir
window.addEventListener('beforeunload', function() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
    if (hls) {
        hls.destroy();
    }
});
</script>

<?php require_once 'footer.php'; ?>