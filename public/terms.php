<?php
// public/terms.php
session_start();

$page_title = "Términos y Condiciones";

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
}
</style>

<div class="legal-container">
    <div class="legal-header">
        <h1>Términos y Condiciones de Uso</h1>
        <p>Última actualización: <?= date('d/m/Y') ?></p>
    </div>

    <div class="legal-content">
        <p>Bienvenido a nuestra plataforma de streaming. Al acceder y utilizar nuestros servicios, aceptas estar vinculado por los siguientes términos y condiciones. Por favor, léelos cuidadosamente.</p>

        <h2>1. Aceptación de los Términos</h2>
        <p>Al crear una cuenta, acceder o utilizar cualquier parte de nuestra plataforma, aceptas cumplir con estos Términos y Condiciones, así como con nuestra Política de Privacidad. Si no estás de acuerdo con alguna parte de estos términos, no debes utilizar nuestros servicios.</p>

        <h2>2. Descripción del Servicio</h2>
        <p>Nuestra plataforma ofrece servicios de streaming en vivo de eventos deportivos y entretenimiento mediante un modelo de pago por evento (Pay-Per-View). Los servicios incluyen:</p>
        <ul>
            <li>Acceso a transmisiones en vivo de eventos</li>
            <li>Eventos gratuitos y de pago</li>
            <li>Visualización en alta definición</li>
            <li>Grabaciones VOD de eventos pasados (cuando estén disponibles)</li>
            <li>Sistema de pagos seguro mediante MercadoPago</li>
        </ul>

        <h2>3. Registro y Cuenta de Usuario</h2>
        <h3>3.1 Requisitos</h3>
        <p>Para utilizar ciertos servicios, debes crear una cuenta proporcionando información precisa y completa. Debes ser mayor de 18 años o contar con autorización parental.</p>
        
        <h3>3.2 Seguridad de la Cuenta</h3>
        <p>Eres responsable de mantener la confidencialidad de tu contraseña y de todas las actividades que ocurran bajo tu cuenta. Debes:</p>
        <ul>
            <li>No compartir tu cuenta con terceros</li>
            <li>Notificarnos inmediatamente sobre cualquier uso no autorizado</li>
            <li>Mantener tu información de contacto actualizada</li>
        </ul>

        <h3>3.3 Control de Sesiones</h3>
        <p><strong>IMPORTANTE:</strong> Nuestra plataforma permite <strong>una única sesión activa por usuario</strong>. Si detectamos que tu cuenta está siendo utilizada simultáneamente en múltiples dispositivos, la sesión anterior será cerrada automáticamente. Esta medida protege tu cuenta y asegura el cumplimiento de los derechos de los creadores de contenido.</p>

        <h2>4. Compra y Acceso a Eventos</h2>
        <h3>4.1 Eventos de Pago</h3>
        <p>Los eventos de pago requieren la compra anticipada de un ticket de acceso. Una vez completado el pago:</p>
        <ul>
            <li>Recibirás acceso inmediato al evento</li>
            <li>Podrás ver la transmisión en vivo durante su duración</li>
            <li>El acceso es personal e intransferible</li>
            <li>Los pagos se procesan mediante MercadoPago de forma segura</li>
        </ul>

        <h3>4.2 Eventos Gratuitos</h3>
        <p>Los eventos gratuitos requieren registro pero no pago. El acceso está sujeto a disponibilidad y puede ser revocado en casos de abuso.</p>

        <h3>4.3 Política de Reembolsos</h3>
        <p>Los reembolsos solo se otorgarán en los siguientes casos:</p>
        <ul>
            <li>Cancelación del evento por parte del organizador</li>
            <li>Problemas técnicos graves que impidan la visualización (a criterio del equipo)</li>
            <li>Cargo duplicado por error del sistema</li>
        </ul>
        <p><strong>No se otorgarán reembolsos por:</strong></p>
        <ul>
            <li>Compras accidentales</li>
            <li>Problemas de conexión del usuario</li>
            <li>Eventos ya iniciados o finalizados</li>
            <li>Insatisfacción con el contenido</li>
        </ul>

        <h2>5. Uso Aceptable</h2>
        <h3>5.1 Prohibiciones</h3>
        <p>Al utilizar nuestra plataforma, te comprometes a NO:</p>
        <ul>
            <li><strong>Compartir accesos:</strong> No compartir tu cuenta, contraseña o enlaces de streaming</li>
            <li><strong>Grabar o redistribuir:</strong> No grabar, capturar o redistribuir contenido de la plataforma</li>
            <li><strong>Evadir pagos:</strong> Intentar acceder a eventos de pago sin realizar el pago correspondiente</li>
            <li><strong>Manipular el sistema:</strong> Interferir con la seguridad o funcionamiento de la plataforma</li>
            <li><strong>Uso comercial:</strong> Utilizar el contenido con fines comerciales sin autorización</li>
            <li><strong>Contenido ilegal:</strong> Transmitir o solicitar contenido que viole leyes o derechos de terceros</li>
        </ul>

        <h3>5.2 Consecuencias</h3>
        <p>El incumplimiento de estas normas puede resultar en:</p>
        <ul>
            <li>Suspensión temporal o permanente de la cuenta</li>
            <li>Pérdida de acceso a eventos pagados sin reembolso</li>
            <li>Acciones legales cuando corresponda</li>
        </ul>

        <h2>6. Propiedad Intelectual</h2>
        <p>Todo el contenido disponible en la plataforma (transmisiones, logos, textos, gráficos) está protegido por derechos de autor y otras leyes de propiedad intelectual. Los streamers y creadores de contenido mantienen los derechos sobre sus transmisiones.</p>

        <h2>7. Limitación de Responsabilidad</h2>
        <p>La plataforma se proporciona "tal cual" y "según disponibilidad". No garantizamos:</p>
        <ul>
            <li>Disponibilidad ininterrumpida del servicio</li>
            <li>Ausencia de errores o interrupciones</li>
            <li>Calidad específica de las transmisiones (depende del streamer)</li>
            <li>Compatibilidad con todos los dispositivos</li>
        </ul>
        <p>No seremos responsables por daños indirectos, incidentales o consecuentes derivados del uso o imposibilidad de uso del servicio.</p>

        <h2>8. Para Streamers</h2>
        <h3>8.1 Requisitos</h3>
        <p>Los streamers deben:</p>
        <ul>
            <li>Tener los derechos necesarios para transmitir su contenido</li>
            <li>Cumplir con todas las leyes aplicables</li>
            <li>No transmitir contenido ofensivo, ilegal o que infrinja derechos de terceros</li>
            <li>Proporcionar información de pago correcta para recibir comisiones</li>
        </ul>

        <h3>8.2 Comisiones</h3>
        <p>Las comisiones se calculan según la configuración establecida por los administradores. Los pagos se procesan cuando se alcanza el mínimo establecido. La plataforma se reserva el derecho de retener pagos en caso de actividad sospechosa.</p>

        <h2>9. Modificaciones del Servicio</h2>
        <p>Nos reservamos el derecho de:</p>
        <ul>
            <li>Modificar o discontinuar servicios temporalmente o permanentemente</li>
            <li>Actualizar estos términos en cualquier momento</li>
            <li>Cambiar precios y comisiones con previo aviso</li>
            <li>Agregar o eliminar funcionalidades</li>
        </ul>
        <p>Las modificaciones importantes serán notificadas por email o mediante avisos en la plataforma.</p>

        <h2>10. Terminación</h2>
        <p>Podemos suspender o terminar tu acceso inmediatamente, sin previo aviso, por cualquier motivo, incluyendo pero no limitado a:</p>
        <ul>
            <li>Violación de estos términos</li>
            <li>Actividad fraudulenta o ilegal</li>
            <li>Uso indebido de la plataforma</li>
            <li>Solicitud del usuario</li>
        </ul>

        <h2>11. Ley Aplicable y Jurisdicción</h2>
        <p>Estos términos se rigen por las leyes de la República Argentina. Cualquier disputa será resuelta en los tribunales de Argentina.</p>

        <h2>12. Contacto</h2>
        <p>Para preguntas sobre estos términos, puedes contactarnos en:</p>
        <ul>
            <li><strong>Email:</strong> eventix@cellcomweb.com.ar</li>
            <li><strong>Formulario:</strong> <a href="/public/contact.php">Página de Contacto</a></li>
        </ul>

        <h2>13. Divisibilidad</h2>
        <p>Si alguna disposición de estos términos se considera inválida o inaplicable, las disposiciones restantes continuarán en pleno vigor y efecto.</p>

        <h2>14. Acuerdo Completo</h2>
        <p>Estos Términos y Condiciones, junto con nuestra Política de Privacidad, constituyen el acuerdo completo entre tú y la plataforma respecto al uso de nuestros servicios.</p>
    </div>

    <div class="legal-footer">
        <p><strong>Última actualización:</strong> <?= date('d/m/Y') ?></p>
        <p>Desarrollado por <a href="https://www.cellcomweb.com.ar" target="_blank" rel="noopener">Cellcom Technology</a></p>
        <p>
            <a href="/public/privacy.php">Política de Privacidad</a> | 
            <a href="/public/contact.php">Contacto</a>
        </p>
    </div>
</div>

<?php require_once 'footer.php'; ?>