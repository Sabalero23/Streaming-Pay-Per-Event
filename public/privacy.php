<?php
// public/privacy.php
session_start();

$page_title = "Política de Privacidad";

require_once 'header.php';
require_once 'styles.php';
?>

<div class="section">
    <div class="container">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <h1 style="margin-bottom: 30px;">Política de Privacidad</h1>
            
            <p style="color: #999; margin-bottom: 30px;">
                Última actualización: <?= date('d/m/Y') ?>
            </p>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">1. Información que Recopilamos</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Recopilamos información que nos proporcionas directamente cuando creas una cuenta, realizas una compra o utilizas nuestros servicios. Esto incluye:
            </p>
            <ul style="color: #ccc; line-height: 1.8; margin-left: 20px; margin-bottom: 20px;">
                <li>Nombre completo y dirección de correo electrónico</li>
                <li>Información de pago (procesada de forma segura por terceros)</li>
                <li>Historial de compras y visualizaciones</li>
                <li>Preferencias de cuenta</li>
            </ul>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">2. Uso de la Información</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Utilizamos la información recopilada para:
            </p>
            <ul style="color: #ccc; line-height: 1.8; margin-left: 20px; margin-bottom: 20px;">
                <li>Proporcionar y mejorar nuestros servicios</li>
                <li>Procesar transacciones y enviar confirmaciones</li>
                <li>Comunicarnos contigo sobre tu cuenta</li>
                <li>Personalizar tu experiencia</li>
                <li>Cumplir con obligaciones legales</li>
            </ul>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">3. Protección de Datos</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Implementamos medidas de seguridad técnicas y organizativas para proteger tu información personal contra acceso no autorizado, alteración, divulgación o destrucción.
            </p>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">4. Cookies</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Utilizamos cookies y tecnologías similares para mejorar tu experiencia de usuario, analizar el uso del sitio y personalizar contenido.
            </p>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">5. Compartir Información</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                No vendemos ni compartimos tu información personal con terceros, excepto cuando sea necesario para procesar pagos o cumplir con la ley.
            </p>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">6. Tus Derechos</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Tienes derecho a:
            </p>
            <ul style="color: #ccc; line-height: 1.8; margin-left: 20px; margin-bottom: 20px;">
                <li>Acceder a tu información personal</li>
                <li>Corregir información incorrecta</li>
                <li>Solicitar la eliminación de tu cuenta</li>
                <li>Oponerte al procesamiento de tus datos</li>
                <li>Exportar tus datos</li>
            </ul>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">7. Contacto</h2>
            <p style="color: #ccc; line-height: 1.6; margin-bottom: 20px;">
                Si tienes preguntas sobre esta política de privacidad, contáctanos en:
                <a href="mailto:privacy@streamingplatform.com" style="color: #667eea;">privacy@streamingplatform.com</a>
            </p>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #333;">
                <a href="/public/" class="btn btn-primary">← Volver al Inicio</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
