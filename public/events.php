<?php
// public/events.php
session_start();

$page_title = "Todos los Eventos";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Event.php';

$eventModel = new Event();

// Filtros
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$allEvents = $eventModel->getAllEvents($category, $status);

require_once 'header.php';
require_once 'styles.php';
?>

<div class="hero" style="padding: 10px 20px;">
    <div class="container">
        <h1>Todos los Eventos</h1>
        <p>Encuentra y disfruta de los mejores eventos en vivo</p>
    </div>
</div>

<div class="section">
    <div class="container">
        <!-- Filtros -->
        <div style="background: #1a1a1a; padding: 20px; border-radius: 12px; margin-bottom: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="/public/events.php" class="<?= $category === '' && $status === '' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                Todos
            </a>
            <a href="/public/events.php?status=live" class="<?= $status === 'live' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                🔴 En Vivo
            </a>
            <a href="/public/events.php?status=scheduled" class="<?= $status === 'scheduled' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                📅 Próximos
            </a>
            <a href="/public/events.php?category=Fútbol" class="<?= $category === 'Fútbol' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                ⚽ Fútbol
            </a>
            <a href="/public/events.php?category=Baloncesto" class="<?= $category === 'Baloncesto' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                🏀 Baloncesto
            </a>
            <a href="/public/events.php?category=Tenis" class="<?= $category === 'Tenis' ? 'btn btn-primary' : 'btn btn-secondary' ?>">
                🎾 Tenis
            </a>
        </div>

        <?php if (!empty($allEvents)): ?>
        <div class="events-grid">
            <?php foreach ($allEvents as $event): ?>
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
                        <?php if ($event['status'] === 'live'): ?>
                            🔴 En vivo
                        <?php else: ?>
                            📅 <?= date('d/m/Y H:i', strtotime($event['scheduled_start'])) ?>
                        <?php endif; ?>
                    </p>
                    <div class="event-price">
                        <span class="price"><?= $event['currency'] ?> <?= number_format($event['price'], 2) ?></span>
                        <a href="/public/event.php?id=<?= $event['id'] ?>" class="btn">
                            <?= $event['status'] === 'live' ? 'Ver Ahora' : 'Comprar' ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-events">
            <h3>No hay eventos disponibles</h3>
            <p>No se encontraron eventos con los filtros seleccionados</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
