<?php
// public/privacy.php
// Política de Privacidad

session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - Streaming Platform</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #fff;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 30px;
        }
        
        .content {
            padding: 60px 20px;
        }
        
        .content h1 {
            font-size: 42px;
            margin-bottom: 20px;
        }
        
        .last-updated {
            color: #999;
            margin-bottom: 40px;
        }
        
        .content h2 {
            font-size: 28px;
            margin-top: 40px;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        .content h3 {
            font-size: 20px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        .content p {
            margin-bottom: 20px;
            color: #ccc;
        }
        
        .content ul {
            margin-bottom: 20px;
            padding-left: 30px;
        }
        
        .content ul li {
            margin-bottom: 10px;
            color: #ccc;
        }
        
        .highlight-box {
            background: #1a1a1a;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        
        .contact-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 12px;
            margin-top: 40px;
            text-align: center;
        }
        
        .contact-box h3 {
            margin-bottom: 15px;
        }
        
        .contact-box a {
            color: white;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .footer {
            background: #1a1a1a;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer p {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <nav class="nav">
                <a href="/public/" class="logo">🎥 Streaming Platform</a>
                <div class="nav-links">
                    <a href="/public/">Inicio</a>
                    <a href="/public/events.php">Eventos</a>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="content">
        <div class="container">
            <h1>Política de Privacidad</h1>
            <p class="last-updated">Última actualización: Octubre 2025</p>
            
            <p>
                En Streaming Platform, nos tomamos muy en serio la privacidad de nuestros usuarios. 
                Esta Política de Privacidad describe cómo recopilamos, usamos, compartimos y protegemos 
                tu información personal cuando utilizas nuestra plataforma.
            </p>
            
            <div class="highlight-box">
                <strong>Importante:</strong> Al utilizar nuestros servicios, aceptas las prácticas descritas 
                en esta Política de Privacidad. Si no estás de acuerdo, por favor no utilices nuestra plataforma.
            </div>
            
            <h2>1. Información que Recopilamos</h2>
            
            <h3>1.1 Información que nos Proporcionas</h3>
            <p>Cuando te registras y utilizas nuestra plataforma, recopilamos:</p>
            <ul>
                <li><strong>Datos de cuenta:</strong> Nombre completo, dirección de email, teléfono (opcional)</li>
                <li><strong>Información de pago:</strong> Procesada de forma segura por MercadoPago, Stripe o PayPal</li>
                <li><strong>Datos de contacto:</strong> Para comunicaciones relacionadas con los eventos</li>
            </ul>
            
            <h3>1.2 Información Recopilada Automáticamente</h3>
            <p>Cuando utilizas nuestra plataforma, recopilamos automáticamente:</p>
            <ul>
                <li><strong>Dirección IP:</strong> Para seguridad y control de acceso</li>
                <li><strong>Información del dispositivo:</strong> Tipo de navegador, sistema operativo</li>
                <li><strong>Datos de uso:</strong> Eventos vistos, tiempo de visualización, interacciones</li>
                <li><strong>Cookies y tecnologías similares:</strong> Para mejorar tu experiencia</li>
            </ul>
            
            <h3>1.3 Información de Transmisión</h3>
            <p>Durante la visualización de eventos en vivo:</p>
            <ul>
                <li><strong>Watermarks:</strong> Tu email y dirección IP se muestran como medida anti-piratería</li>
                <li><strong>Sesiones activas:</strong> Controlamos que solo veas desde un dispositivo a la vez</li>
                <li><strong>Analíticas de visualización:</strong> Tiempo de visualización, calidad de video seleccionada</li>
            </ul>
            
            <h2>2. Cómo Utilizamos tu Información</h2>
            
            <p>Utilizamos la información recopilada para:</p>
            
            <h3>2.1 Prestación de Servicios</h3>
            <ul>
                <li>Procesar tus compras y accesos a eventos</li>
                <li>Gestionar tu cuenta y preferencias</li>
                <li>Proporcionar acceso a transmisiones en vivo</li>
                <li>Enviar notificaciones sobre eventos que compraste</li>
            </ul>
            
            <h3>2.2 Seguridad y Anti-Piratería</h3>
            <ul>
                <li>Prevenir el uso no autorizado de cuentas</li>
                <li>Detectar y prevenir fraudes</li>
                <li>Controlar acceso de un solo dispositivo por usuario</li>
                <li>Aplicar watermarks de identificación en streams</li>
            </ul>
            
            <h3>2.3 Mejora del Servicio</h3>
            <ul>
                <li>Analizar el uso de la plataforma</li>
                <li>Mejorar la calidad de transmisión</li>
                <li>Desarrollar nuevas funcionalidades</li>
                <li>Personalizar tu experiencia</li>
            </ul>
            
            <h3>2.4 Comunicaciones</h3>
            <ul>
                <li>Enviarte emails de confirmación de compra</li>
                <li>Notificarte cuando un evento comienza</li>
                <li>Enviar actualizaciones sobre cambios en eventos</li>
                <li>Comunicaciones de soporte técnico</li>
            </ul>
            
            <h2>3. Compartir tu Información</h2>
            
            <p>No vendemos tu información personal. Compartimos tu información solo en los siguientes casos:</p>
            
            <h3>3.1 Proveedores de Servicios</h3>
            <ul>
                <li><strong>Procesadores de pago:</strong> MercadoPago, Stripe, PayPal</li>
                <li><strong>Servicios de hosting:</strong> Para almacenar datos y transmisiones</li>
                <li><strong>Servicios de email:</strong> Para enviar notificaciones</li>
                <li><strong>CDN:</strong> Para distribuir contenido de video</li>
            </ul>
            
            <h3>3.2 Obligaciones Legales</h3>
            <p>Podemos divulgar tu información si:</p>
            <ul>
                <li>Es requerido por ley o proceso legal</li>
                <li>Es necesario para proteger nuestros derechos</li>
                <li>Es necesario para prevenir fraude o abuso</li>
            </ul>
            
            <h3>3.3 Organizadores de Eventos</h3>
            <p>
                Los organizadores de eventos pueden ver información agregada y anónima sobre la 
                audiencia (número de espectadores, tiempo de visualización), pero no tienen acceso 
                a tu información personal identificable.
            </p>
            
            <h2>4. Seguridad de los Datos</h2>
            
            <p>Implementamos medidas de seguridad técnicas y organizativas para proteger tu información:</p>
            <ul>
                <li><strong>Encriptación SSL/TLS:</strong> Todas las comunicaciones están encriptadas</li>
                <li><strong>Contraseñas hasheadas:</strong> Utilizamos bcrypt para almacenar contraseñas</li>
                <li><strong>Tokens seguros:</strong> JWT para autenticación y autorización</li>
                <li><strong>Acceso limitado:</strong> Solo personal autorizado accede a datos personales</li>
                <li><strong>Monitoreo constante:</strong> Sistemas de detección de intrusiones</li>
            </ul>
            
            <div class="highlight-box">
                <strong>Nota:</strong> Ningún sistema es 100% seguro. Aunque implementamos las mejores 
                prácticas de seguridad, no podemos garantizar la seguridad absoluta de tu información.
            </div>
            
            <h2>5. Tus Derechos</h2>
            
            <p>Como usuario, tienes los siguientes derechos sobre tu información personal:</p>
            
            <h3>5.1 Acceso y Portabilidad</h3>
            <ul>
                <li>Solicitar una copia de tu información personal</li>
                <li>Descargar tus datos en formato estructurado</li>
            </ul>
            
            <h3>5.2 Rectificación</h3>
            <ul>
                <li>Corregir información inexacta o incompleta</li>
                <li>Actualizar tus datos de perfil en cualquier momento</li>
            </ul>
            
            <h3>5.3 Eliminación</h3>
            <ul>
                <li>Solicitar la eliminación de tu cuenta y datos asociados</li>
                <li>Nota: Podemos retener ciertos datos por obligaciones legales</li>
            </ul>
            
            <h3>5.4 Objeción y Restricción</h3>
            <ul>
                <li>Oponerte a ciertos usos de tu información</li>
                <li>Solicitar restricción del procesamiento de tus datos</li>
            </ul>
            
            <h3>5.5 Revocación de Consentimiento</h3>
            <ul>
                <li>Retirar tu consentimiento en cualquier momento</li>
                <li>Desuscribirte de emails de marketing</li>
            </ul>
            
            <h2>6. Cookies y Tecnologías de Seguimiento</h2>
            
            <p>Utilizamos cookies y tecnologías similares para:</p>
            <ul>
                <li><strong>Cookies esenciales:</strong> Necesarias para el funcionamiento del sitio</li>
                <li><strong>Cookies de sesión:</strong> Para mantener tu sesión activa</li>
                <li><strong>Cookies analíticas:</strong> Para entender cómo usas la plataforma</li>
                <li><strong>Cookies de preferencias:</strong> Para recordar tus configuraciones</li>
            </ul>
            
            <p>Puedes controlar las cookies desde tu navegador, pero esto puede afectar la funcionalidad del sitio.</p>
            
            <h2>7. Retención de Datos</h2>
            
            <p>Retenemos tu información personal mientras:</p>
            <ul>
                <li>Tu cuenta esté activa</li>
                <li>Sea necesario para proporcionar nuestros servicios</li>
                <li>Sea requerido por obligaciones legales</li>
                <li>Sea necesario para resolver disputas</li>
            </ul>
            
            <p>
                Cuando eliminas tu cuenta, comenzamos el proceso de eliminación de tus datos personales 
                dentro de 30 días, excepto donde debamos retener información por razones legales.
            </p>
            
            <h2>8. Privacidad de Menores</h2>
            
            <p>
                Nuestra plataforma está destinada a usuarios mayores de 18 años. No recopilamos 
                intencionalmente información de menores de 18 años. Si descubrimos que hemos 
                recopilado información de un menor, eliminaremos esos datos inmediatamente.
            </p>
            
            <h2>9. Transferencias Internacionales</h2>
            
            <p>
                Tu información puede ser transferida y procesada en servidores ubicados fuera de tu país. 
                Tomamos medidas para garantizar que tu información reciba el mismo nivel de protección 
                que en tu país de origen.
            </p>
            
            <h2>10. Cambios a esta Política</h2>
            
            <p>
                Podemos actualizar esta Política de Privacidad ocasionalmente. Te notificaremos sobre 
                cambios significativos por email o mediante un aviso destacado en la plataforma. 
                El uso continuado de nuestros servicios después de los cambios constituye tu 
                aceptación de la nueva política.
            </p>
            
            <h2>11. Legislación Aplicable</h2>
            
            <p>
                Esta Política de Privacidad se rige por las leyes de Argentina y las normativas 
                internacionales de protección de datos aplicables, incluyendo la Ley de Protección 
                de Datos Personales Nº 25.326.
            </p>
            
            <div class="contact-box">
                <h3>¿Preguntas sobre tu Privacidad?</h3>
                <p>
                    Si tienes preguntas sobre esta Política de Privacidad o sobre cómo manejamos 
                    tus datos personales, contáctanos:
                </p>
                <p>
                    Email: <a href="mailto:privacy@tu-dominio.com">privacy@tu-dominio.com</a><br>
                    Responsable de Datos: Streaming Platform<br>
                    Dirección: [Tu dirección]
                </p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; 2025 Streaming Platform. Todos los derechos reservados.</p>
            <p style="margin-top: 10px;">
                <a href="/public/terms.php" style="color: #667eea; text-decoration: none;">Términos y Condiciones</a> · 
                <a href="/public/privacy.php" style="color: #667eea; text-decoration: none;">Privacidad</a> · 
                <a href="/public/contact.php" style="color: #667eea; text-decoration: none;">Contacto</a>
            </p>
        </div>
    </div>
</body>
</html>
