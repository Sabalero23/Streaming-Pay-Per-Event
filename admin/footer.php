        </div><!-- /.container -->
    </div><!-- /.main-content -->
    
    <footer style="background: #2c3e50; color: white; padding: 20px; text-align: center; margin-top: auto;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <p style="margin-bottom: 10px;">
                &copy; <?= date('Y') ?> Streaming Platform - Panel de Administración
            </p>
            <p style="font-size: 13px; opacity: 0.8;">
                Usuario: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong> | 
                Rol: <strong><?= strtoupper($_SESSION['user_role'] ?? 'admin') ?></strong>
            </p>
        </div>
    </footer>
    
    <script>
        // Script para menú móvil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const adminNav = document.getElementById('adminNav');
            const navOverlay = document.getElementById('navOverlay');
            
            if (menuToggle && adminNav && navOverlay) {
                // Abrir/cerrar menú
                menuToggle.addEventListener('click', function() {
                    adminNav.classList.toggle('active');
                    navOverlay.classList.toggle('active');
                });
                
                // Cerrar menú al hacer click en overlay
                navOverlay.addEventListener('click', function() {
                    adminNav.classList.remove('active');
                    navOverlay.classList.remove('active');
                });
                
                // Cerrar menú al hacer click en un link
                const navLinks = adminNav.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        adminNav.classList.remove('active');
                        navOverlay.classList.remove('active');
                    });
                });
                
                // Cerrar menú con tecla Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        adminNav.classList.remove('active');
                        navOverlay.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
