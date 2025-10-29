<?php
// public/privacy.php
session_start();

$page_title = "Política de Privacidad";

require_once 'header.php';
require_once 'styles.php';
?>

<style>
.legal-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.legal-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.legal-header h1 {
    color: #333;
    font-size: 32px;
    margin-bottom: 10px;
}

.legal-header p {
    color: #666;
    font-size: 14px;
}

.legal-content {
    color: #444;
    line-height: 1.8;
}

.legal-content h2 {
    color: #222;
    font-size: 24px;
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.legal-content h3 {
    color: #333;
    font-size: 18px;
    margin-top: 20px;
    margin-bottom: 10px;
}

.legal-content p {
    margin-bottom: 15px;
}

.legal-content ul, .legal-content ol {
    margin: 15px 0;
    padding-left: 30px;
}

.legal-content li {
    margin-bottom: 10px;
}

.legal-content strong {
    color: #222;
}

.legal-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.legal-content table th,
.legal-content table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.legal-content table th {
    background: #f5f5f5;
    font-weight: bold;
}

.highlight-box {
    background: #f9f9f9;
    border-left: 4px solid #e50914;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 5px;
}

.legal-footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
    text-align: center;
    color: #666;
    font-size: 14px;
}

.legal-footer a {
    color: #e50914;
    text-decoration: none;
}

.legal-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .legal-container {
        margin: 20px;
        padding: 20px;
    }
    
    .legal-header h1 {
        font-size: 24px;
    }
    
    .legal-content h2 {
        font-size: 20px;
    }
    
    .legal-content table {
        font-size: 14px;
    }
}
</style>

<div class="legal-container">
    <div class="legal-header">
        <h1>Política de Privacidad</h1>
        <p>Última actualización: <?= date('d/m/Y') ?></p>
    </div>

    <div class="legal-content">
        <div class="highlight-box">
            <p><strong>Tu privacidad es importante para nosotros.</strong> Esta política explica qué información recopilamos, cómo la utilizamos y cuáles son tus derechos respecto a tus datos personales.</p>
        </div>

        <h2>1. Información que Recopilamos</h2>
        
        <h3>1.1 Información que Proporcionas Directamente</h3>
        <p>Cuando te registras y utilizas nuestra plataforma, recopilamos:</p>
        <ul>
            <li><strong>Datos de cuenta:</strong> Nombre completo, correo electrónico, contraseña (encriptada)</li>
            <li><strong>Datos de perfil:</strong> Foto de perfil, biografía (para streamers)</li>
            <li><strong>Datos de pago:</strong> Información procesada por MercadoPago (no almacenamos datos de tarjetas)</li>
            <li><strong>Información de streamer:</strong> Datos bancarios (CBU/CVU/Alias) para pagos de comisiones</li>
            <li><strong>Comunicaciones:</strong> Mensajes que nos envíes a través de formularios de contacto</li>
        </ul>

        <h3>1.2 Información Recopilada Automáticamente</h3>
        <p>Cuando utilizas nuestra plataforma, recopilamos automáticamente:</p>
        <ul>
            <li><strong>Datos de sesión:</strong> Dirección IP, tipo de navegador, sistema operativo</li>
            <li><strong>Datos de uso:</strong> Páginas visitadas, eventos visualizados, tiempo de visualización</li>
            <li><strong>Datos de dispositivo:</strong> Identificador de dispositivo, modelo, versión del software</li>
            <li><strong>Cookies y tecnologías similares:</strong> Para mantener tu sesión activa y mejorar la experiencia</li>
            <li><strong>Geolocalización aproximada:</strong> Basada en tu dirección IP (país/ciudad)</li>
        </ul>

        <h3>1.3 Información de Terceros</h3>
        <ul>
            <li><strong>MercadoPago:</strong> Estado de pagos, información de transacciones</li>
            <li><strong>YouTube:</strong> Cuando un streamer usa YouTube, pueden aplicarse sus políticas</li>
        </ul>

        <h2>2. Cómo Utilizamos tu Información</h2>
        <p>Utilizamos la información recopilada para:</p>

        <h3>2.1 Proporcionar y Mejorar el Servicio</h3>
        <ul>
            <li>Crear y gestionar tu cuenta</li>
            <li>Procesar pagos y proporcionar acceso a eventos</li>
            <li>Enviar confirmaciones de compra y tickets de acceso</li>
            <li>Proporcionar soporte técnico</li>
            <li>Mejorar la calidad de las transmisiones</li>
            <li>Desarrollar nuevas funcionalidades</li>
        </ul>

        <h3>2.2 Seguridad y Control de Acceso</h3>
        <ul>
            <li><strong>Control de sesiones únicas:</strong> Detectar y prevenir uso simultáneo de cuentas</li>
            <li>Prevenir fraudes y actividades no autorizadas</li>
            <li>Proteger los derechos de propiedad intelectual</li>
            <li>Cumplir con obligaciones legales</li>
        </ul>

        <h3>2.3 Comunicaciones</h3>
        <ul>
            <li>Enviarte notificaciones sobre eventos que compraste</li>
            <li>Informarte sobre cambios en el servicio</li>
            <li>Responder a tus consultas</li>
            <li>Enviar actualizaciones importantes (seguridad, términos)</li>
            <li>Newsletter y promociones (solo si diste consentimiento)</li>
        </ul>

        <h3>2.4 Análisis y Estadísticas</h3>
        <ul>
            <li>Generar reportes de uso para streamers</li>
            <li>Analizar tendencias y preferencias de usuarios</li>
            <li>Medir el rendimiento de la plataforma</li>
            <li>Optimizar la experiencia del usuario</li>
        </ul>

        <h2>3. Base Legal para el Procesamiento de Datos</h2>
        <p>Procesamos tus datos personales basándonos en:</p>
        <ul>
            <li><strong>Ejecución del contrato:</strong> Para proporcionarte el servicio que solicitaste</li>
            <li><strong>Consentimiento:</strong> Cuando aceptas estos términos y políticas</li>
            <li><strong>Interés legítimo:</strong> Para mejorar el servicio, prevenir fraudes y garantizar la seguridad</li>
            <li><strong>Obligación legal:</strong> Para cumplir con requisitos fiscales y legales</li>
        </ul>

        <h2>4. Compartir tu Información</h2>
        <p>No vendemos ni alquilamos tu información personal. Solo compartimos datos con:</p>

        <h3>4.1 Proveedores de Servicios</h3>
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Propósito</th>
                    <th>Datos Compartidos</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>MercadoPago</td>
                    <td>Procesamiento de pagos</td>
                    <td>Email, monto, datos de transacción</td>
                </tr>
                <tr>
                    <td>Hosting</td>
                    <td>Almacenamiento de datos</td>
                    <td>Todos los datos almacenados</td>
                </tr>
                <tr>
                    <td>CDN</td>
                    <td>Entrega de contenido</td>
                    <td>IP, ubicación, contenido solicitado</td>
                </tr>
            </tbody>
        </table>

        <h3>4.2 Streamers</h3>
        <p>Los streamers pueden ver información limitada de sus compradores:</p>
        <ul>
            <li>Nombre y email (solo para eventos que organizan)</li>
            <li>Fecha de compra</li>
            <li>Estadísticas agregadas (sin datos personales)</li>
        </ul>

        <h3>4.3 Requisitos Legales</h3>
        <p>Podemos divulgar información si es requerido por ley o en respuesta a:</p>
        <ul>
            <li>Órdenes judiciales o citaciones</li>
            <li>Investigaciones de fraude o seguridad</li>
            <li>Protección de nuestros derechos legales</li>
        </ul>

        <h2>5. Retención de Datos</h2>
        <p>Conservamos tu información mientras:</p>
        <ul>
            <li>Tu cuenta esté activa</li>
            <li>Sea necesario para proporcionar servicios</li>
            <li>Sea requerido por obligaciones legales o fiscales (mínimo 5 años para transacciones)</li>
        </ul>
        <p>Después de la eliminación de la cuenta, conservamos datos agregados y anonimizados para análisis estadísticos.</p>

        <h2>6. Seguridad de los Datos</h2>
        <p>Implementamos medidas de seguridad para proteger tu información:</p>
        <ul>
            <li><strong>Encriptación:</strong> Contraseñas hasheadas con bcrypt</li>
            <li><strong>HTTPS:</strong> Comunicación encriptada entre tu navegador y nuestros servidores</li>
            <li><strong>Control de acceso:</strong> Acceso limitado a datos personales por personal autorizado</li>
            <li><strong>Monitoreo:</strong> Detección de actividades sospechosas</li>
            <li><strong>Backups regulares:</strong> Para prevenir pérdida de datos</li>
            <li><strong>Sesiones únicas:</strong> Sistema de detección de accesos simultáneos no autorizados</li>
        </ul>

        <div class="highlight-box">
            <p><strong>Importante:</strong> Aunque implementamos medidas de seguridad, ningún sistema es 100% seguro. Mantén tu contraseña segura y notifícanos inmediatamente si detectas actividad sospechosa.</p>
        </div>

        <h2>7. Tus Derechos</h2>
        <p>Tienes derecho a:</p>

        <h3>7.1 Acceso y Portabilidad</h3>
        <ul>
            <li>Solicitar una copia de tus datos personales</li>
            <li>Recibir tus datos en formato estructurado y legible</li>
        </ul>

        <h3>7.2 Rectificación</h3>
        <ul>
            <li>Actualizar información incorrecta o incompleta</li>
            <li>Modificar tus preferencias de comunicación</li>
        </ul>

        <h3>7.3 Eliminación</h3>
        <ul>
            <li>Solicitar la eliminación de tu cuenta y datos asociados</li>
            <li>Excepciones: datos requeridos legalmente o para resolver disputas</li>
        </ul>

        <h3>7.4 Restricción y Oposición</h3>
        <ul>
            <li>Limitar el procesamiento de tus datos</li>
            <li>Oponerte a ciertos usos (marketing, por ejemplo)</li>
        </ul>

        <h3>7.5 Retirar Consentimiento</h3>
        <ul>
            <li>Cancelar suscripción a emails promocionales</li>
            <li>Deshabilitar cookies no esenciales</li>
        </ul>

        <p><strong>Para ejercer estos derechos, contáctanos en:</strong> eventix@cellcomweb.com.ar</p>

        <h2>8. Cookies y Tecnologías de Seguimiento</h2>
        <p>Utilizamos cookies para:</p>
        <ul>
            <li><strong>Cookies esenciales:</strong> Mantener tu sesión activa (requeridas)</li>
            <li><strong>Cookies de rendimiento:</strong> Analizar el uso de la plataforma</li>
            <li><strong>Cookies de funcionalidad:</strong> Recordar tus preferencias</li>
        </ul>
        <p>Puedes gestionar las cookies desde la configuración de tu navegador. Ten en cuenta que deshabilitar cookies esenciales puede afectar la funcionalidad del sitio.</p>

        <h2>9. Control de Sesiones Únicas</h2>
        <p>Como parte de nuestro compromiso con la seguridad y los derechos de los creadores de contenido:</p>
        <ul>
            <li>Monitoreamos sesiones activas mediante heartbeat cada 20 segundos</li>
            <li>Detectamos accesos simultáneos desde diferentes dispositivos/IPs</li>
            <li>Registramos conflictos de sesión por 24 horas (solo para análisis de seguridad)</li>
            <li>Cerramos automáticamente sesiones anteriores cuando se detecta un nuevo acceso</li>
        </ul>
        <p>Estos datos se utilizan exclusivamente para garantizar el acceso legítimo y prevenir el uso no autorizado de cuentas.</p>

        <h2>10. Transferencias Internacionales</h2>
        <p>Tus datos pueden ser transferidos y almacenados en servidores ubicados fuera de tu país de residencia. Garantizamos que estas transferencias cumplan con las leyes aplicables de protección de datos.</p>

        <h2>11. Privacidad de Menores</h2>
        <p>Nuestra plataforma no está dirigida a menores de 18 años. No recopilamos intencionalmente información de menores. Si descubrimos que hemos recopilado datos de un menor, los eliminaremos de inmediato.</p>

        <h2>12. Cambios en esta Política</h2>
        <p>Podemos actualizar esta política ocasionalmente. Te notificaremos sobre cambios significativos mediante:</p>
        <ul>
            <li>Email a tu dirección registrada</li>
            <li>Aviso destacado en la plataforma</li>
            <li>Actualización de la fecha "Última actualización"</li>
        </ul>
        <p>Tu uso continuado del servicio después de los cambios constituye tu aceptación de la nueva política.</p>

        <h2>13. Contacto y Consultas</h2>
        <p>Para preguntas sobre esta política o el tratamiento de tus datos:</p>
        <ul>
            <li><strong>Email:</strong> privacidad@cellcomweb.com.ar</li>
            <li><strong>Email de soporte:</strong> eventix@cellcomweb.com.ar</li>
            <li><strong>Formulario:</strong> <a href="/public/contact.php">Página de Contacto</a></li>
        </ul>

        <h2>14. Autoridad de Control</h2>
        <p>Si consideras que tus derechos de protección de datos han sido violados, tienes derecho a presentar una queja ante la autoridad de protección de datos de tu jurisdicción.</p>

        <div class="highlight-box">
            <p><strong>Compromiso de Transparencia:</strong> Creemos en la transparencia total. Si tienes alguna pregunta sobre cómo manejamos tus datos, no dudes en contactarnos. Estamos aquí para ayudarte.</p>
        </div>
    </div>

    <div class="legal-footer">
        <p><strong>Última actualización:</strong> <?= date('d/m/Y') ?></p>
        <p>Desarrollado por <a href="https://www.cellcomweb.com.ar" target="_blank" rel="noopener">Cellcom Technology</a></p>
        <p>
            <a href="/public/terms.php">Términos y Condiciones</a> | 
            <a href="/public/contact.php">Contacto</a>
        </p>
    </div>
</div>

<?php require_once 'footer.php'; ?>