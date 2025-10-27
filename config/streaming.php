<?php
// config/streaming.php
// Configuración de streaming RTMP y HLS

// Cargar variables de entorno si no están cargadas
if (!function_exists('getEnvVar')) {
    require_once __DIR__ . '/database.php';
}

return [
    // Configuración RTMP
    'rtmp' => [
        'host' => getEnvVar('RTMP_HOST', 'rtmp://streaming.cellcomweb.com.ar/live'),
        'port' => getEnvVar('RTMP_PORT', 1935),
        'app' => 'live', // Nombre de la aplicación RTMP
        
        // URL completa del servidor RTMP
        'server_url' => getEnvVar('RTMP_HOST', 'rtmp://streaming.cellcomweb.com.ar/live'),
    ],
    
    // Configuración HLS
    'hls' => [
        'base_url' => getEnvVar('HLS_BASE_URL', 'https://streaming.cellcomweb.com.ar/hls'),
        'segment_duration' => 6, // Duración de cada segmento en segundos
        'playlist_length' => 5, // Número de segmentos en el playlist
    ],
    
    // Configuración de calidad de video
    'video_quality' => [
        'profiles' => [
            '1080p' => [
                'resolution' => '1920x1080',
                'bitrate' => '5000k',
                'fps' => 30,
            ],
            '720p' => [
                'resolution' => '1280x720',
                'bitrate' => '3000k',
                'fps' => 30,
            ],
            '480p' => [
                'resolution' => '854x480',
                'bitrate' => '1500k',
                'fps' => 30,
            ],
            '360p' => [
                'resolution' => '640x360',
                'bitrate' => '800k',
                'fps' => 30,
            ],
        ],
        
        // Calidad por defecto
        'default_profile' => '720p',
    ],
    
    // Configuración de audio
    'audio' => [
        'bitrate' => '128k',
        'sample_rate' => 44100,
        'channels' => 2,
    ],
    
    // Configuración de grabación
    'recording' => [
        'enabled' => true,
        'path' => getEnvVar('LOG_PATH', '/www/wwwroot/streaming.cellcomweb.com.ar/storage') . '/recordings',
        'format' => 'mp4',
        'auto_delete_days' => 30, // Eliminar grabaciones después de X días
    ],
    
    // Límites
    'limits' => [
        'max_viewers_per_stream' => 10000,
        'max_concurrent_streams' => 100,
        'max_stream_duration_hours' => 12,
    ],
    
    // Configuración de CDN (opcional)
    'cdn' => [
        'enabled' => !empty(getEnvVar('CDN_URL')),
        'url' => getEnvVar('CDN_URL', ''),
    ],
];