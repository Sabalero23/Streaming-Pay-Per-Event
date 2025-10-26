<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproducir Evento - Streaming Platform</title>
    
    <!-- HLS.js para reproducción HLS en navegadores -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.4.12"></script>
    
    <!-- Video.js para controles personalizados (opcional) -->
    <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: #fff;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #333;
        }
        
        .event-title {
            font-size: 24px;
            font-weight: bold;
        }
        
        .live-badge {
            background: #ff0000;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            animation: pulse 2s infinite;
        }
        
        .live-badge::before {
            content: "●";
            margin-right: 8px;
            font-size: 18px;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .viewers-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .video-container {
            position: relative;
            background: #000;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #videoPlayer {
            width: 100%;
            max-height: 80vh;
            display: block;
        }
        
        .watermark {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            pointer-events: none;
            z-index: 10;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 5;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-panel {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .alert {
            background: #ff5252;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .quality-selector {
            position: absolute;
            bottom: 60px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 10px;
            border-radius: 5px;
            z-index: 10;
            display: none;
        }
        
        .quality-selector.show {
            display: block;
        }
        
        .quality-option {
            padding: 8px 15px;
            cursor: pointer;
            color: white;
            border-radius: 3px;
        }
        
        .quality-option:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .quality-option.active {
            background: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="event-title" id="eventTitle">Cargando...</div>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <span class="live-badge" id="liveBadge" style="display: none;">EN VIVO</span>
                <span class="viewers-count" id="viewersCount">
                    <span id="viewerNumber">0</span> espectadores
                </span>
            </div>
        </div>
        
        <div class="alert" id="alertBox"></div>
        
        <div class="video-container">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
                <p style="margin-top: 15px;">Cargando transmisión...</p>
            </div>
            
            <div class="watermark" id="watermark"></div>
            
            <video id="videoPlayer" controls playsinline></video>
            
            <div class="quality-selector" id="qualitySelector"></div>
        </div>
        
        <div class="info-panel">
            <h3>Estadísticas de Reproducción</h3>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-label">Calidad</div>
                    <div class="stat-value" id="statQuality">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Buffer</div>
                    <div class="stat-value" id="statBuffer">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Bitrate</div>
                    <div class="stat-value" id="statBitrate">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Tiempo de Visualización</div>
                    <div class="stat-value" id="statWatchTime">00:00</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Configuración global
        const CONFIG = {
            apiBaseUrl: '<?php echo getenv('APP_URL'); ?>/api',
            heartbeatInterval: 30000, // 30 segundos
            maxReconnectAttempts: 5,
            reconnectDelay: 2000
        };
        
        // Estado de la aplicación
        const state = {
            eventId: null,
            sessionToken: null,
            accessToken: null,
            hls: null,
            heartbeatTimer: null,
            watchTimeTimer: null,
            watchTimeSeconds: 0,
            reconnectAttempts: 0
        };
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', init);
        
        function init() {
            // Obtener parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);
            state.accessToken = urlParams.get('token');
            state.eventId = getEventIdFromPath();
            
            if (!state.accessToken || !state.eventId) {
                showAlert('Acceso no autorizado. Token o evento no válido.');
                return;
            }
            
            // Validar acceso e iniciar sesión
            validateAccessAndStart();
            
            // Manejar cierre de página
            window.addEventListener('beforeunload', handlePageUnload);
            
            // Detectar cambios de visibilidad (cambio de pestaña)
            document.addEventListener('visibilitychange', handleVisibilityChange);
        }
        
        function getEventIdFromPath() {
            const path = window.location.pathname;
            const match = path.match(/\/watch\/(\d+)/);
            return match ? match[1] : null;
        }
        
        async function validateAccessAndStart() {
            try {
                const response = await fetch(`${CONFIG.apiBaseUrl}/validate-access.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: state.eventId,
                        access_token: state.accessToken
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Acceso denegado');
                }
                
                // Guardar token de sesión
                state.sessionToken = data.session_token;
                
                // Cargar información del evento
                loadEventInfo(data.event);
                
                // Iniciar reproducción
                startPlayback(data.hls_url);
                
                // Configurar watermark
                setupWatermark(data.user);
                
                // Iniciar heartbeat
                startHeartbeat();
                
                // Iniciar contador de tiempo
                startWatchTimeCounter();
                
                // Actualizar conteo de espectadores
                updateViewersCount();
                
            } catch (error) {
                console.error('Validation error:', error);
                showAlert(error.message);
            }
        }
        
        function loadEventInfo(event) {
            document.getElementById('eventTitle').textContent = event.title;
            document.title = event.title + ' - Streaming Platform';
            
            if (event.status === 'live') {
                document.getElementById('liveBadge').style.display = 'inline-flex';
            }
        }
        
        function startPlayback(hlsUrl) {
            const video = document.getElementById('videoPlayer');
            
            if (Hls.isSupported()) {
                state.hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backBufferLength: 90
                });
                
                state.hls.loadSource(hlsUrl);
                state.hls.attachMedia(video);
                
                state.hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    video.play();
                    
                    // Configurar selector de calidad
                    setupQualitySelector(state.hls.levels);
                });
                
                state.hls.on(Hls.Events.LEVEL_SWITCHED, function(event, data) {
                    const level = state.hls.levels[data.level];
                    updateQualityStats(level);
                });
                
                state.hls.on(Hls.Events.ERROR, handleHlsError);
                
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Soporte nativo HLS (Safari)
                video.src = hlsUrl;
                video.addEventListener('loadedmetadata', function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    video.play();
                });
            } else {
                showAlert('Tu navegador no soporta reproducción HLS');
            }
            
            // Eventos del video
            video.addEventListener('timeupdate', updateBufferStats);
        }
        
        function setupQualitySelector(levels) {
            const selector = document.getElementById('qualitySelector');
            
            levels.forEach((level, index) => {
                const option = document.createElement('div');
                option.className = 'quality-option';
                option.textContent = `${level.height}p`;
                option.onclick = () => switchQuality(index);
                selector.appendChild(option);
            });
            
            // Opción de auto
            const autoOption = document.createElement('div');
            autoOption.className = 'quality-option active';
            autoOption.textContent = 'Auto';
            autoOption.onclick = () => switchQuality(-1);
            selector.insertBefore(autoOption, selector.firstChild);
        }
        
        function switchQuality(levelIndex) {
            if (!state.hls) return;
            
            state.hls.currentLevel = levelIndex;
            
            // Actualizar UI
            document.querySelectorAll('.quality-option').forEach(el => {
                el.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        function setupWatermark(user) {
            const watermark = document.getElementById('watermark');
            watermark.textContent = `${user.email} - ${user.ip}`;
        }
        
        function startHeartbeat() {
            // Enviar heartbeat cada 30 segundos
            state.heartbeatTimer = setInterval(async () => {
                try {
                    const response = await fetch(`${CONFIG.apiBaseUrl}/heartbeat.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            session_token: state.sessionToken
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message);
                    }
                    
                    state.reconnectAttempts = 0;
                    
                } catch (error) {
                    console.error('Heartbeat error:', error);
                    handleHeartbeatFailure();
                }
            }, CONFIG.heartbeatInterval);
        }
        
        function handleHeartbeatFailure() {
            state.reconnectAttempts++;
            
            if (state.reconnectAttempts >= CONFIG.maxReconnectAttempts) {
                showAlert('Se perdió la conexión. La sesión ha sido cerrada.');
                endSession();
            }
        }
        
        function startWatchTimeCounter() {
            state.watchTimeTimer = setInterval(() => {
                const video = document.getElementById('videoPlayer');
                if (!video.paused) {
                    state.watchTimeSeconds++;
                    updateWatchTimeDisplay();
                }
            }, 1000);
        }
        
        function updateWatchTimeDisplay() {
            const minutes = Math.floor(state.watchTimeSeconds / 60);
            const seconds = state.watchTimeSeconds % 60;
            const display = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            document.getElementById('statWatchTime').textContent = display;
        }
        
        async function updateViewersCount() {
            try {
                const response = await fetch(`${CONFIG.apiBaseUrl}/viewers-count.php?event_id=${state.eventId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('viewerNumber').textContent = data.count;
                }
            } catch (error) {
                console.error('Error fetching viewers count:', error);
            }
            
            // Actualizar cada minuto
            setTimeout(updateViewersCount, 60000);
        }
        
        function updateQualityStats(level) {
            if (level) {
                document.getElementById('statQuality').textContent = `${level.height}p`;
                document.getElementById('statBitrate').textContent = 
                    `${Math.round(level.bitrate / 1000)} kbps`;
            }
        }
        
        function updateBufferStats() {
            const video = document.getElementById('videoPlayer');
            if (video.buffered.length > 0) {
                const bufferedEnd = video.buffered.end(video.buffered.length - 1);
                const buffer = bufferedEnd - video.currentTime;
                document.getElementById('statBuffer').textContent = `${buffer.toFixed(1)}s`;
            }
        }
        
        function handleHlsError(event, data) {
            console.error('HLS error:', data);
            
            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        showAlert('Error de red. Intentando reconectar...');
                        state.hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        showAlert('Error de medios. Intentando recuperar...');
                        state.hls.recoverMediaError();
                        break;
                    default:
                        showAlert('Error fatal. No se puede reproducir el video.');
                        endSession();
                        break;
                }
            }
        }
        
        function handlePageUnload(e) {
            // Enviar beacon para cerrar sesión
            if (state.sessionToken) {
                navigator.sendBeacon(
                    `${CONFIG.apiBaseUrl}/end-session.php`,
                    JSON.stringify({ session_token: state.sessionToken })
                );
            }
        }
        
        function handleVisibilityChange() {
            if (document.hidden) {
                // Usuario cambió de pestaña - pausar video
                const video = document.getElementById('videoPlayer');
                if (!video.paused) {
                    video.pause();
                }
            }
        }
        
        async function endSession() {
            // Limpiar timers
            if (state.heartbeatTimer) clearInterval(state.heartbeatTimer);
            if (state.watchTimeTimer) clearInterval(state.watchTimeTimer);
            
            // Detener HLS
            if (state.hls) {
                state.hls.destroy();
            }
            
            // Cerrar sesión en el servidor
            if (state.sessionToken) {
                await fetch(`${CONFIG.apiBaseUrl}/end-session.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        session_token: state.sessionToken,
                        watch_time: state.watchTimeSeconds
                    })
                });
            }
        }
        
        function showAlert(message) {
            const alert = document.getElementById('alertBox');
            alert.textContent = message;
            alert.classList.add('show');
        }
    </script>
</body>
</html>
