<?php
// public/footer.php
// Cargar configuración para el footer
require_once __DIR__ . '/../src/Helpers/SiteConfig.php';
$site_name = SiteConfig::siteName();
$site_logo = SiteConfig::get('site_logo_path', '');
$company_name = SiteConfig::get('company_name', 'Eventix S.R.L.');
$social_facebook = SiteConfig::get('social_facebook', '');
$social_instagram = SiteConfig::get('social_instagram', '');
$social_twitter = SiteConfig::get('social_twitter', '');
$social_youtube = SiteConfig::get('social_youtube', '');
?>
</div><!-- /.main-content -->

<style>
.site-footer {
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 50px 20px 30px;
    margin-top: 80px;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-section h3 {
    color: #222;
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
}

.footer-about p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.footer-logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.footer-logo {
    width: 200px;
    height: 100px;
    object-fit: contain;
}

.footer-logo-emoji {
    font-size: 45px;
    line-height: 1;
}

.footer-site-name {
    font-size: 20px;
    font-weight: 700;
    color: #222;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
    display: inline-block;
}

.footer-links a:hover {
    color: #e50914;
}

.footer-social {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    text-decoration: none;
    transition: all 0.3s;
}

.social-icon:hover {
    background: #e50914;
    color: white;
    transform: translateY(-3px);
}

.social-icon svg {
    width: 18px;
    height: 18px;
}

.footer-contact p {
    color: #666;
    margin-bottom: 10px;
    line-height: 1.6;
}

.footer-contact a {
    color: #e50914;
    text-decoration: none;
}

.footer-contact a:hover {
    text-decoration: underline;
}

.footer-bottom {
    padding-top: 30px;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

.footer-bottom p {
    color: #999;
    font-size: 14px;
    margin: 5px 0;
}

.footer-bottom a {
    color: #e50914;
    text-decoration: none;
}

.footer-bottom a:hover {
    text-decoration: underline;
}

.footer-session-info {
    background: #f9f9f9;
    padding: 12px 20px;
    border-radius: 8px;
    margin-top: 15px;
    display: inline-block;
}

.footer-session-info strong {
    color: #e50914;
}

@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .site-footer {
        padding: 40px 20px 25px;
        margin-top: 60px;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .footer-logo-container {
        justify-content: center;
    }
    
    .footer-about {
        text-align: center;
    }
}
</style>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <!-- Sección Acerca de -->
            <div class="footer-section footer-about">
                <div class="footer-logo-container">
                    <?php if (!empty($site_logo) && file_exists($_SERVER['DOCUMENT_ROOT'] . $site_logo)): ?>
                        <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="footer-logo">
                    <?php else: ?>
                        <span class="footer-logo-emoji">🎥</span>
                    <?php endif; ?>
                </div>
                <p>Tu plataforma de streaming en vivo para eventos deportivos y entretenimiento. Transmite y disfruta contenido exclusivo con la mejor calidad.</p>
                <?php if ($social_facebook || $social_instagram || $social_twitter || $social_youtube): ?>
                <div class="footer-social">
                    <?php if ($social_facebook): ?>
                    <a href="<?= htmlspecialchars($social_facebook) ?>" class="social-icon" title="Facebook" target="_blank" rel="noopener">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($social_instagram): ?>
                    <a href="<?= htmlspecialchars($social_instagram) ?>" class="social-icon" title="Instagram" target="_blank" rel="noopener">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($social_twitter): ?>
                    <a href="<?= htmlspecialchars($social_twitter) ?>" class="social-icon" title="Twitter" target="_blank" rel="noopener">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($social_youtube): ?>
                    <a href="<?= htmlspecialchars($social_youtube) ?>" class="social-icon" title="YouTube" target="_blank" rel="noopener">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Enlaces Rápidos -->
            <div class="footer-section">
                <h3>Enlaces Rápidos</h3>
                <ul class="footer-links">
                    <li><a href="/public/index.php">Inicio</a></li>
                    <li><a href="/public/events.php">Eventos</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/public/profile.php">Mi Perfil</a></li>
                    <?php else: ?>
                    <li><a href="/public/login.php">Iniciar Sesión</a></li>
                    <li><a href="/public/register.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Legal -->
            <div class="footer-section">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="/public/terms.php">Términos de Servicio</a></li>
                    <li><a href="/public/privacy.php">Política de Privacidad</a></li>
                    <li><a href="/public/contact.php">Contacto</a></li>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="footer-section footer-contact">
                <h3>Contacto</h3>
                <p>
                    <strong>Email:</strong><br>
                    <a href="mailto:<?= htmlspecialchars(SiteConfig::get('contact_email', 'info@eventix.com.ar')) ?>">
                        <?= htmlspecialchars(SiteConfig::get('contact_email', 'info@eventix.com.ar')) ?>
                    </a>
                </p>
                <p>
                    <strong>Ubicación:</strong><br>
                    <?= htmlspecialchars(SiteConfig::get('contact_address', 'Avellaneda, Santa Fe, Argentina')) ?>
                </p>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($company_name) ?>. Todos los derechos reservados. | Desarrollado por <a href="https://www.cellcomweb.com.ar" target="_blank" rel="noopener">CellcomTechnology</a></p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="footer-session-info">
                Sesión activa: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<script>
// Script para menú móvil
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const publicNav = document.getElementById('publicNav');
    const navOverlay = document.getElementById('navOverlay');
    
    if (menuToggle && publicNav && navOverlay) {
        // Abrir/cerrar menú
        menuToggle.addEventListener('click', function() {
            publicNav.classList.toggle('active');
            navOverlay.classList.toggle('active');
        });
        
        // Cerrar menú al hacer click en overlay
        navOverlay.addEventListener('click', function() {
            publicNav.classList.remove('active');
            navOverlay.classList.remove('active');
        });
        
        // Cerrar menú al hacer click en un link
        const links = publicNav.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                publicNav.classList.remove('active');
                navOverlay.classList.remove('active');
            });
        });
        
        // Cerrar menú con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                publicNav.classList.remove('active');
                navOverlay.classList.remove('active');
            }
        });
    }
});
</script>
</body>
</html>