<?php
// config/streaming.php

return [
    // Configuración del servidor RTMP
    'rtmp' => [
        'host' => getenv('RTMP_HOST') ?: 'localhost',
        'port' => getenv('RTMP_PORT') ?: 1935,
        'app' => 'live', // Nombre de la aplicación RTMP
        'url' => function() {
            $host = getenv('RTMP_HOST') ?: 'localhost';
            $port = getenv('RTMP_PORT') ?: 1935;
            return "rtmp://{$host}:{$port}/live";
        }
    ],
    
    // Configuración de HLS
    'hls' => [
        'path' => '/var/www/streaming/hls', // Ruta donde se guardan los segmentos
        'segment_duration' => 4, // Duración de cada segmento en segundos
        'playlist_length' => 10, // Número de segmentos en la playlist
        'base_url' => getenv('HLS_BASE_URL') ?: 'https://tu-dominio.com/hls'
    ],
    
    // Configuración de transcodificación
    'transcoding' => [
        'profiles' => [
            '1080p' => [
                'resolution' => '1920x1080',
                'video_bitrate' => '5000k',
                'audio_bitrate' => '192k',
                'fps' => 30
            ],
            '720p' => [
                'resolution' => '1280x720',
                'video_bitrate' => '3000k',
                'audio_bitrate' => '128k',
                'fps' => 30
            ],
            '480p' => [
                'resolution' => '854x480',
                'video_bitrate' => '1500k',
                'audio_bitrate' => '128k',
                'fps' => 30
            ],
            '360p' => [
                'resolution' => '640x360',
                'video_bitrate' => '800k',
                'audio_bitrate' => '96k',
                'fps' => 30
            ]
        ],
        'ffmpeg_path' => '/usr/bin/ffmpeg',
        'preset' => 'veryfast', // ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow
        'codec' => 'libx264'
    ],
    
    // Configuración de grabación
    'recording' => [
        'enabled' => true,
        'path' => '/var/www/streaming/vod',
        'format' => 'mp4',
        'keep_raw' => false // Mantener archivo raw o solo el procesado
    ],
    
    // Configuración de watermark
    'watermark' => [
        'enabled' => true,
        'type' => 'text', // 'text' o 'image'
        'text_template' => '{email} - {ip}', // Variables: {email}, {ip}, {user_id}, {timestamp}
        'font_size' => 24,
        'font_color' => 'white',
        'background_color' => 'black@0.5',
        'position' => 'top-right', // top-left, top-right, bottom-left, bottom-right
        'margin' => 10
    ],
    
    // Configuración de CDN
    'cdn' => [
        'enabled' => false,
        'provider' => 'cloudflare', // cloudflare, cloudfront, bunny
        'url' => getenv('CDN_URL') ?: '',
        'purge_on_end' => true
    ],
    
    // Límites y restricciones
    'limits' => [
        'max_concurrent_viewers' => 1000,
        'max_stream_duration' => 14400, // 4 horas en segundos
        'session_timeout' => 300, // 5 minutos sin heartbeat
        'max_bitrate' => 8000 // kbps
    ],
    
    // Configuración de tokens de acceso
    'tokens' => [
        'algorithm' => 'HS256',
        'secret' => getenv('JWT_SECRET') ?: 'cambiar-este-secreto-en-produccion',
        'expiration' => 86400, // 24 horas
        'refresh_enabled' => true,
        'refresh_expiration' => 604800 // 7 días
    ],
    
    // Configuración de seguridad
    'security' => [
        'enable_ip_validation' => true,
        'enable_device_validation' => true,
        'max_failed_heartbeats' => 3,
        'signed_urls' => true,
        'signed_url_expiration' => 3600, // 1 hora
        'hotlink_protection' => true,
        'allowed_referers' => [
            'tu-dominio.com',
            'www.tu-dominio.com'
        ]
    ],
    
    // YouTube Live como fuente alternativa
    'youtube' => [
        'enabled' => true,
        'api_key' => getenv('YOUTUBE_API_KEY') ?: '',
        'extract_url_pattern' => '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/'
    ],
    
    // Notificaciones
    'notifications' => [
        'email' => [
            'enabled' => true,
            'on_purchase' => true,
            'on_stream_start' => true,
            'on_stream_end' => false
        ],
        'webhook' => [
            'enabled' => false,
            'url' => getenv('WEBHOOK_URL') ?: ''
        ]
    ]
];
