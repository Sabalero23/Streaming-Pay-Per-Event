<?php
// public/index.php - ESTILO ESTUDIOS MAX
session_start();

$page_title = "Inicio";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';
require_once __DIR__ . '/../src/Helpers/SiteConfig.php';

$eventModel = new Event();
$upcomingEvents = $eventModel->getUpcomingEvents(12);
$liveEvents = $eventModel->getLiveEvents();

// Obtener configuraciones para el hero
$siteName = SiteConfig::siteName();
$siteTagline = SiteConfig::siteTagline();

require_once 'header.php';
require_once 'styles.php';
?>

<!-- HERO SECTION -->
<div class="hero">
    <div class="container">
        <h1><?= htmlspecialchars($siteTagline) ?></h1>
        <p>Con la máxima calidad, estabilidad y compatible con todos tus dispositivos.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="/public/register.php" class="btn btn-lg" style="background: #ffffff; color: #1e3c72; margin-top: 20px;">
            Comenzar Ahora
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- EVENTOS EN VIVO -->
<?php if (!empty($liveEvents)): ?>
<div class="section">
    <div class="container">
        <div class="live-badge">Transmisiones en Vivo Ahora</div>
        <div class="events-grid">
            <?php foreach ($liveEvents as $event): ?>
            <?php $isFree = (float)$event['price'] === 0.0; ?>
            <div class="event-card" onclick="location.href='/public/event.php?id=<?= $event['id'] ?>'">
                <div class="event-thumbnail">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        🎥
                    <?php endif; ?>
                    <?php if ($isFree): ?>
                    <div style="position: absolute; top: 10px; right: 10px;" class="free-badge">
                        GRATIS
                    </div>
                    <?php endif; ?>
                </div>
                <div class="event-info">
                    <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    <p class="event-date">
                        🔴 En vivo · <?= $event['current_viewers'] ?? 0 ?> espectadores
                    </p>
                    <div class="event-price">
                        <?php if ($isFree): ?>
                        <span class="price" style="color: #27ae60;">GRATIS</span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn btn-success">Ver Ahora</a>
                        <?php else: ?>
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn">Ver Ahora</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- PRÓXIMOS EVENTOS -->
<div class="section">
    <div class="container">
        <h2 class="section-title">Próximos Eventos</h2>
        <p class="section-subtitle">Descubre nuestros eventos programados y asegura tu acceso</p>
        
        <?php if (!empty($upcomingEvents)): ?>
        <div class="events-grid">
            <?php foreach ($upcomingEvents as $event): ?>
            <?php $isFree = (float)$event['price'] === 0.0; ?>
            <div class="event-card" onclick="location.href='/public/event.php?id=<?= $event['id'] ?>'">
                <div class="event-thumbnail">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        🎬
                    <?php endif; ?>
                    <?php if ($isFree): ?>
                    <div style="position: absolute; top: 10px; right: 10px;" class="free-badge">
                        GRATIS
                    </div>
                    <?php endif; ?>
                </div>
                <div class="event-info">
                    <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    <p class="event-date">
                        📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                    </p>
                    <div class="event-price">
                        <?php if ($isFree): ?>
                        <span class="price" style="color: #27ae60;">GRATIS</span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn btn-success">Registrarse</a>
                        <?php else: ?>
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn">Comprar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-events">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <h3>No hay eventos programados</h3>
            <p>Pronto estaremos transmitiendo nuevos eventos</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CARACTERÍSTICAS DEL SERVICIO -->
<div class="section" style="background: #f7f7f7;">
    <div class="container">
        <h2 class="section-title">Características de Nuestro Servicio</h2>
        <p class="section-subtitle">Tecnología de punta para la mejor experiencia de streaming</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#3498db" opacity="0.1"/>
                        <path d="M25 35L40 25L55 35V55C55 56.1046 54.1046 57 53 57H27C25.8954 57 25 56.1046 25 55V35Z" stroke="#3498db" stroke-width="3"/>
                        <path d="M35 57V42H45V57" stroke="#3498db" stroke-width="3"/>
                    </svg>
                </div>
                <h3>Calidad HD</h3>
                <p>Transmisiones en alta definición con la mejor calidad de video disponible</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#27ae60" opacity="0.1"/>
                        <circle cx="40" cy="40" r="15" stroke="#27ae60" stroke-width="3"/>
                        <path d="M40 28V40L48 48" stroke="#27ae60" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>100% Online</h3>
                <p>Disponibilidad garantizada 24/7 con servidores de alta confiabilidad</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#e74c3c" opacity="0.1"/>
                        <path d="M25 50L35 35L45 45L55 28" stroke="#e74c3c" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="35" cy="35" r="3" fill="#e74c3c"/>
                        <circle cx="45" cy="45" r="3" fill="#e74c3c"/>
                        <circle cx="55" cy="28" r="3" fill="#e74c3c"/>
                    </svg>
                </div>
                <h3>Estadísticas</h3>
                <p>Panel de control con análisis detallado de tu audiencia en tiempo real</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#f39c12" opacity="0.1"/>
                        <path d="M30 40L37 47L50 33" stroke="#f39c12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="40" cy="40" r="17" stroke="#f39c12" stroke-width="3"/>
                    </svg>
                </div>
                <h3>Alto Rendimiento</h3>
                <p>Tecnología optimizada para streaming sin interrupciones ni buffering</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#9b59b6" opacity="0.1"/>
                        <rect x="28" y="30" width="24" height="20" rx="2" stroke="#9b59b6" stroke-width="3"/>
                        <path d="M35 50V55H45V50" stroke="#9b59b6" stroke-width="3"/>
                        <line x1="30" y1="55" x2="50" y2="55" stroke="#9b59b6" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Panel de Control</h3>
                <p>Interfaz intuitiva y fácil de usar para gestionar tus transmisiones</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="80" height="80" rx="4" fill="#1abc9c" opacity="0.1"/>
                        <path d="M40 25C40 25 50 30 50 40C50 50 40 55 40 55C40 55 30 50 30 40C30 30 40 25 40 25Z" stroke="#1abc9c" stroke-width="3"/>
                        <path d="M40 32V40H48" stroke="#1abc9c" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Soporte 24/7</h3>
                <p>Equipo técnico profesional disponible para ayudarte en todo momento</p>
            </div>
        </div>
    </div>
</div>

<!-- POR QUÉ ELEGIRNOS -->
<div class="section">
    <div class="container">
        <h2 class="section-title">¿Por Qué Elegirnos?</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">HD</div>
                <div class="stat-label">Alta Definición</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Soporte Disponible</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Pago Seguro</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Lag o Buffering</div>
            </div>
        </div>
    </div>
</div>

<!-- CTA SECTION -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div class="section" style="background: #ffffff;">
    <div class="container">
        <div class="cta-section">
            <h2>¿Listo para Comenzar?</h2>
            <p>Únete a nuestra plataforma y comienza a disfrutar de transmisiones en vivo de alta calidad</p>
            <a href="/public/register.php" class="btn btn-lg">Registrarse Gratis</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>