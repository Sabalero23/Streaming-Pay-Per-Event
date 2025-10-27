<?php
// public/test_api_simple.php
// Prueba simple para verificar que chat.php funciona

session_start();

// Simular sesión si no existe (solo para testing)
if (!isset($_SESSION['user_id'])) {
    echo "<h2>⚠️ No hay sesión activa</h2>";
    echo "<p>Para probar, necesitas estar logueado o descomentar la línea siguiente:</p>";
    echo "<pre>// \$_SESSION['user_id'] = 1; // Descomentar para testing</pre>";
    
    // DESCOMENTAR LA SIGUIENTE LÍNEA SOLO PARA TESTING:
    // $_SESSION['user_id'] = 1;
    
    if (!isset($_SESSION['user_id'])) {
        echo "<p><a href='/public/login.php'>Ir a login</a></p>";
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test API Chat</title>
    <style>
        body { 
            font-family: monospace; 
            background: #1a1a1a; 
            color: #fff; 
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #667eea; }
        h2 { color: #f39c12; margin-top: 30px; }
        pre { 
            background: #0f0f0f; 
            padding: 15px; 
            border-radius: 5px; 
            overflow: auto;
            border: 1px solid #333;
        }
        .ok { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #5568d3; }
        #results { margin-top: 20px; }
        .test-section {
            background: #0f0f0f;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #333;
        }
    </style>
</head>
<body>
    <h1>🧪 Prueba de API de Chat</h1>
    <p>Usuario ID: <strong><?= $_SESSION['user_id'] ?></strong></p>

    <div class="test-section">
        <h2>1️⃣ Prueba GET (Obtener Mensajes)</h2>
        <p>Event ID: <input type="number" id="eventId1" value="5" style="background:#333;color:#fff;border:1px solid #667eea;padding:5px;"></p>
        <button onclick="testGetMessages()">📥 Obtener Mensajes</button>
        <div id="getResult"></div>
    </div>

    <div class="test-section">
        <h2>2️⃣ Prueba POST (Enviar Mensaje)</h2>
        <p>Event ID: <input type="number" id="eventId2" value="5" style="background:#333;color:#fff;border:1px solid #667eea;padding:5px;"></p>
        <p>Mensaje: <input type="text" id="message" value="Hola 😀🎉" style="background:#333;color:#fff;border:1px solid #667eea;padding:5px;width:300px;"></p>
        <button onclick="testSendMessage()">📤 Enviar Mensaje</button>
        <div id="postResult"></div>
    </div>

    <div class="test-section">
        <h2>3️⃣ Verificación de Rutas</h2>
        <button onclick="checkRoutes()">🔍 Verificar Rutas</button>
        <div id="routeResult"></div>
    </div>

    <script>
        async function testGetMessages() {
            const eventId = document.getElementById('eventId1').value;
            const resultDiv = document.getElementById('getResult');
            resultDiv.innerHTML = '<p class="warning">⏳ Cargando...</p>';

            try {
                const url = `/api/chat.php?action=get_messages&event_id=${eventId}`;
                console.log('🔗 URL:', url);
                
                const response = await fetch(url);
                console.log('📊 Status:', response.status);
                console.log('📋 Headers:', [...response.headers.entries()]);
                
                const contentType = response.headers.get('content-type');
                console.log('📄 Content-Type:', contentType);
                
                const text = await response.text();
                console.log('📝 Respuesta completa:', text);
                
                resultDiv.innerHTML = `
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Content-Type:</strong> ${contentType}</p>
                    <p><strong>Respuesta:</strong></p>
                    <pre>${escapeHtml(text.substring(0, 1000))}</pre>
                `;
                
                // Intentar parsear JSON
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML += `
                        <p class="ok">✅ JSON válido</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultDiv.innerHTML += `<p class="error">❌ No es JSON válido: ${e.message}</p>`;
                }
                
            } catch (error) {
                console.error('❌ Error:', error);
                resultDiv.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }

        async function testSendMessage() {
            const eventId = document.getElementById('eventId2').value;
            const message = document.getElementById('message').value;
            const resultDiv = document.getElementById('postResult');
            resultDiv.innerHTML = '<p class="warning">⏳ Enviando...</p>';

            try {
                const response = await fetch('/api/chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&event_id=${eventId}&message=${encodeURIComponent(message)}`
                });
                
                console.log('📊 Status:', response.status);
                const contentType = response.headers.get('content-type');
                console.log('📄 Content-Type:', contentType);
                
                const text = await response.text();
                console.log('📝 Respuesta:', text);
                
                resultDiv.innerHTML = `
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Content-Type:</strong> ${contentType}</p>
                    <p><strong>Respuesta:</strong></p>
                    <pre>${escapeHtml(text.substring(0, 1000))}</pre>
                `;
                
                // Intentar parsear JSON
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML += `
                        <p class="ok">✅ JSON válido</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultDiv.innerHTML += `<p class="error">❌ No es JSON válido: ${e.message}</p>`;
                }
                
            } catch (error) {
                console.error('❌ Error:', error);
                resultDiv.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }

        async function checkRoutes() {
            const resultDiv = document.getElementById('routeResult');
            resultDiv.innerHTML = '<p class="warning">⏳ Verificando...</p>';
            
            const routes = [
                '/api/chat.php',
                '../api/chat.php',
                '/api/chat.php?action=get_messages&event_id=1'
            ];
            
            let html = '<h3>Rutas probadas:</h3>';
            
            for (const route of routes) {
                try {
                    const response = await fetch(route);
                    const exists = response.status !== 404;
                    const icon = exists ? '✅' : '❌';
                    html += `<p>${icon} ${route} - Status: ${response.status}</p>`;
                } catch (e) {
                    html += `<p>❌ ${route} - Error: ${e.message}</p>`;
                }
            }
            
            resultDiv.innerHTML = html;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        console.log('✅ Test de API cargado');
        console.log('👤 User ID:', <?= $_SESSION['user_id'] ?>);
    </script>
</body>
</html>