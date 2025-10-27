<?php
// public/index.php
session_start();

$page_title = "Inicio";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';

$eventModel = new Event();
$upcomingEvents = $eventModel->getUpcomingEvents(12);
$liveEvents = $eventModel->getLiveEvents();

require_once 'header.php';
require_once 'styles.php';
?>

<div class="hero">
    <div class="container">
        <h1>Vive la Emoción del Deporte en Vivo</h1>
        <p>Transmisiones en alta calidad de tus partidos favoritos</p>
    </div>
</div>

<?php if (!empty($liveEvents)): ?>
<div class="section">
    <div class="container">
        <div class="live-badge">EN VIVO AHORA</div>
        <div class="events-grid">
            <?php foreach ($liveEvents as $event): ?>
            <div class="event-card" onclick="location.href='/public/event.php?id=<?= $event['id'] ?>'">
                <div class="event-thumbnail">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        ⚽
                    <?php endif; ?>
                </div>
                <div class="event-info">
                    <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    <p class="event-date">
                        🔴 En vivo · <?= $event['current_viewers'] ?? 0 ?> espectadores
                    </p>
                    <div class="event-price">
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn">Ver Ahora</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <div class="container">
        <h2 class="section-title">Próximos Eventos</h2>
        
        <?php if (!empty($upcomingEvents)): ?>
        <div class="events-grid">
            <?php foreach ($upcomingEvents as $event): ?>
            <div class="event-card" onclick="location.href='/public/event.php?id=<?= $event['id'] ?>'">
                <div class="event-thumbnail">
                    <?php if ($event['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($event['thumbnail_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php else: ?>
                        ⚽
                    <?php endif; ?>
                </div>
                <div class="event-info">
                    <div class="event-category"><?= htmlspecialchars($event['category'] ?? 'Deportes') ?></div>
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    <p class="event-date">
                        📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                    </p>
                    <div class="event-price">
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn">Comprar</a>
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

<div class="section" style="background: #1a1a1a;">
    <div class="container">
        <h2 class="section-title" style="text-align: center;">¿Por qué elegirnos?</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">HD</div>
                <div class="stat-label">Calidad Alta Definición</div>
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

<?php require_once 'footer.php'; ?>
