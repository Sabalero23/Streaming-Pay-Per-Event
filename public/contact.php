<?php
// public/contact.php
session_start();

$page_title = "Contacto";

require_once 'header.php';
require_once 'styles.php';

// Procesar formulario
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validaciones
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Todos los campos son obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email inválido';
    } elseif (strlen($message) < 10) {
        $error_message = 'El mensaje debe tener al menos 10 caracteres';
    } else {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();
            
            // Verificar si la tabla existe, si no, crearla
            $checkTable = $db->query("SHOW TABLES LIKE 'contact_messages'");
            if ($checkTable->rowCount() === 0) {
                $createTable = "CREATE TABLE `contact_messages` (
                    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `email` varchar(255) NOT NULL,
                    `subject` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `status` enum('new','read','replied') DEFAULT 'new',
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` text DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->exec($createTable);
            }
            
            // Insertar mensaje
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([$name, $email, $subject, $message, $ip, $userAgent]);
            
            // Opcional: Enviar email al administrador
            $to = 'eventix@cellcomweb.com.ar';
            $email_subject = "Eventix - Nuevo mensaje de contacto: " . $subject;
            $email_body = "Has recibido un nuevo mensaje de contacto en Eventix\n\n";
            $email_body .= "Nombre: $name\n";
            $email_body .= "Email: $email\n";
            $email_body .= "Asunto: $subject\n\n";
            $email_body .= "Mensaje:\n$message\n\n";
            $email_body .= "---\n";
            $email_body .= "IP: $ip\n";
            $email_body .= "Fecha: " . date('d/m/Y H:i:s');
            
            $headers = "From: noreply@eventix.com.ar\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Descomentar para enviar email real
            // mail($to, $email_subject, $email_body, $headers);
            
            $success_message = '¡Mensaje enviado exitosamente! Te responderemos pronto a ' . htmlspecialchars($email);
            
            // Limpiar campos
            $name = $email = $subject = $message = '';
            
        } catch (Exception $e) {
            error_log("Error en contact.php: " . $e->getMessage());
            $error_message = 'Error al enviar el mensaje. Por favor, intenta nuevamente o escríbenos directamente a eventix@cellcomweb.com.ar';
        }
    }
}
?>

<style>
.contact-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.contact-header {
    text-align: center;
    margin-bottom: 50px;
}

.contact-header h1 {
    color: #333;
    font-size: 36px;
    margin-bottom: 10px;
}

.contact-header p {
    color: #666;
    font-size: 18px;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 60px;
}

.contact-form-section {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.contact-form-section h2 {
    color: #222;
    font-size: 24px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #e50914;
}

.form-group textarea {
    min-height: 150px;
    resize: vertical;
}

.alert {
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.btn-submit {
    background: #e50914;
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.btn-submit:hover {
    background: #b8070f;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
}

.btn-submit:active {
    transform: translateY(0);
}

.contact-info-section {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.contact-info-section h2 {
    color: #222;
    font-size: 24px;
    margin-bottom: 20px;
}

.contact-info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid #f0f0f0;
}

.contact-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.contact-info-icon {
    width: 50px;
    height: 50px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.contact-info-icon svg {
    width: 24px;
    height: 24px;
    color: #e50914;
}

.contact-info-content h3 {
    color: #333;
    font-size: 18px;
    margin-bottom: 5px;
}

.contact-info-content p {
    color: #666;
    margin: 0;
    line-height: 1.6;
}

.contact-info-content a {
    color: #e50914;
    text-decoration: none;
}

.contact-info-content a:hover {
    text-decoration: underline;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.social-link {
    width: 45px;
    height: 45px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.social-link:hover {
    background: #e50914;
    color: white;
    transform: translateY(-3px);
}

.social-link svg {
    width: 20px;
    height: 20px;
}

.faq-section {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.faq-section h2 {
    color: #222;
    font-size: 28px;
    margin-bottom: 30px;
    text-align: center;
}

.faq-item {
    margin-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 20px;
}

.faq-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.faq-question {
    color: #333;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.faq-question::before {
    content: "Q:";
    color: #e50914;
    font-weight: bold;
    margin-right: 10px;
}

.faq-answer {
    color: #666;
    line-height: 1.6;
    margin-left: 30px;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .contact-header h1 {
        font-size: 28px;
    }
    
    .contact-form-section,
    .contact-info-section,
    .faq-section {
        padding: 25px;
    }
    
    .social-links {
        justify-content: center;
    }
}
</style>

<div class="contact-container">
    <div class="contact-header">
        <h1>Contacto</h1>
        <p>¿Tienes preguntas? Estamos aquí para ayudarte</p>
    </div>

    <div class="contact-grid">
        <!-- Formulario de contacto -->
        <div class="contact-form-section">
            <h2>Envíanos un Mensaje</h2>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nombre Completo *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required placeholder="Tu nombre completo">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required placeholder="tu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="subject">Asunto *</label>
                    <select id="subject" name="subject" required>
                        <option value="">Selecciona un asunto</option>
                        <option value="Soporte Técnico" <?= (($subject ?? '') === 'Soporte Técnico') ? 'selected' : '' ?>>Soporte Técnico</option>
                        <option value="Consulta de Pago" <?= (($subject ?? '') === 'Consulta de Pago') ? 'selected' : '' ?>>Consulta de Pago</option>
                        <option value="Ser Streamer" <?= (($subject ?? '') === 'Ser Streamer') ? 'selected' : '' ?>>Quiero ser Streamer</option>
                        <option value="Reembolso" <?= (($subject ?? '') === 'Reembolso') ? 'selected' : '' ?>>Solicitud de Reembolso</option>
                        <option value="Sugerencia" <?= (($subject ?? '') === 'Sugerencia') ? 'selected' : '' ?>>Sugerencia o Mejora</option>
                        <option value="Problema de Sesión" <?= (($subject ?? '') === 'Problema de Sesión') ? 'selected' : '' ?>>Problema de Sesión</option>
                        <option value="Otro" <?= (($subject ?? '') === 'Otro') ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Mensaje *</label>
                    <textarea id="message" name="message" required placeholder="Describe tu consulta con el mayor detalle posible..."><?= htmlspecialchars($message ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Enviar Mensaje</button>
            </form>
        </div>

        <!-- Información de contacto -->
        <div class="contact-info-section">
            <h2>Información de Contacto</h2>
            
            <div class="contact-info-item">
                <div class="contact-info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="contact-info-content">
                    <h3>Email</h3>
                    <p><a href="mailto:eventix@cellcomweb.com.ar">eventix@cellcomweb.com.ar</a></p>
                    <p style="font-size: 14px; color: #999; margin-top: 5px;">Respuesta en 24-48 horas</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <div class="contact-info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="contact-info-content">
                    <h3>Ubicación</h3>
                    <p>Avellaneda, Santa Fe<br>Argentina</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <div class="contact-info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="contact-info-content">
                    <h3>Horario de Atención</h3>
                    <p>Lunes a Viernes: 9:00 - 18:00<br>
                    Sábados: 10:00 - 14:00<br>
                    Domingos: Cerrado</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <div class="contact-info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <div class="contact-info-content">
                    <h3>Sitio Web</h3>
                    <p><a href="https://www.eventix.com.ar" target="_blank">www.eventix.com.ar</a></p>
                </div>
            </div>
            
            <h3 style="margin-top: 30px; margin-bottom: 15px; color: #333;">Síguenos</h3>
            <div class="social-links">
                <a href="https://facebook.com/eventix" class="social-link" title="Facebook" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <a href="https://twitter.com/eventix" class="social-link" title="Twitter" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                </a>
                <a href="https://instagram.com/eventix" class="social-link" title="Instagram" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                    </svg>
                </a>
                <a href="https://youtube.com/@eventix" class="social-link" title="YouTube" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Sección de preguntas frecuentes -->
    <div class="faq-section">
        <h2>Preguntas Frecuentes</h2>
        
        <div class="faq-item">
            <div class="faq-question">¿Cómo puedo comprar un evento?</div>
            <div class="faq-answer">
                Regístrate en Eventix, busca el evento que deseas ver, haz clic en "Comprar" y completa el pago mediante MercadoPago. Recibirás acceso inmediato al evento y podrás verlo desde cualquier dispositivo.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">¿Puedo solicitar un reembolso?</div>
            <div class="faq-answer">
                Los reembolsos solo se otorgan en casos de cancelación del evento por parte del organizador o problemas técnicos graves que impidan la visualización. No se otorgan reembolsos por eventos ya iniciados o por problemas de conexión del usuario. Consulta nuestros <a href="/public/terms.php">Términos y Condiciones</a> para más detalles.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">¿Cómo puedo ser streamer en Eventix?</div>
            <div class="faq-answer">
                Regístrate en la plataforma y contáctanos a través de este formulario seleccionando "Quiero ser Streamer". Revisaremos tu solicitud y te daremos acceso a las herramientas de streaming y al panel de control donde podrás crear y gestionar tus eventos.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">¿Puedo ver eventos desde múltiples dispositivos?</div>
            <div class="faq-answer">
                No, cada cuenta permite una única sesión activa por razones de seguridad y protección de contenido. Si inicias sesión en otro dispositivo, la sesión anterior se cerrará automáticamente. Esto garantiza que solo el propietario de la cuenta pueda acceder al contenido.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">¿Qué métodos de pago aceptan?</div>
            <div class="faq-answer">
                Aceptamos todos los métodos de pago disponibles en MercadoPago: tarjetas de crédito/débito, transferencias bancarias, y efectivo en puntos de pago.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">¿Los eventos quedan grabados?</div>
            <div class="faq-answer">
                Algunos eventos quedan disponibles como VOD (Video On Demand) después de la transmisión en vivo. Esto depende de cada streamer y será indicado en la descripción del evento.
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>