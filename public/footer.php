</div><!-- /.main-content -->
    
    <footer style="background: #1a1a1a; padding: 40px 20px; text-align: center; margin-top: 60px;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <p style="color: #999; margin-bottom: 20px;">
                &copy; <?= date('Y') ?> Streaming Platform. Todos los derechos reservados.
            </p>
            <p style="margin-top: 10px;">
                <a href="/public/terms.php" style="color: #667eea; text-decoration: none; margin: 0 10px;">Términos de Servicio</a>
                <a href="/public/privacy.php" style="color: #667eea; text-decoration: none; margin: 0 10px;">Política de Privacidad</a>
                <a href="/public/contact.php" style="color: #667eea; text-decoration: none; margin: 0 10px;">Contacto</a>
            </p>
            <?php if (isset($_SESSION['user_id'])): ?>
            <p style="color: #666; font-size: 13px; margin-top: 15px;">
                Sesión activa: <strong style="color: #999;"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
            </p>
            <?php endif; ?>
        </div>
    </footer>
    
    <script>
        // Script para menú móvil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const publicNav = document.getElementById('publicNav'); // CORREGIDO: era 'navLinks'
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