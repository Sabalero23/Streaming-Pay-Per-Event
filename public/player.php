<?php
// public/player.php
// Reproductor embebido simple
session_start();

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$event_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die('Evento no encontrado');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; color: #fff; overflow: hidden; }
        .player-container { width: 100vw; height: 100vh; display: flex; flex-direction: column; }
        .video-wrapper { flex: 1; position: relative; }
        video { width: 100%; height: 100%; object-fit: contain; }
        .controls { background: rgba(0,0,0,0.8); padding: 15px; display: flex; align-items: center; gap: 15px; }
        .title { flex: 1; font-size: 14px; }
    </style>
</head>
<body>
    <div class="player-container">
        <div class="video-wrapper">
            <?php if ($event['status'] === 'live'): ?>
            <video id="videoPlayer" controls autoplay>
                <source src="<?= htmlspecialchars($event['stream_url'] ?? '') ?>" type="application/x-mpegURL">
            </video>
            <?php else: ?>
            <div style="display:flex; align-items:center; justify-content:center; height:100%; text-align:center;">
                <div>
                    <h2>El evento no está disponible</h2>
                    <p style="color:#999; margin-top:10px;">Estado: <?= $event['status'] ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="controls">
            <div class="title"><?= htmlspecialchars($event['title']) ?></div>
            <a href="/public/event.php?id=<?= $event_id ?>" style="color:#667eea; text-decoration:none;">Ver detalles</a>
        </div>
    </div>
</body>
</html>
