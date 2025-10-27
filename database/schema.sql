-- Base de datos para plataforma de streaming pay-per-event
-- MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS streaming_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE streaming_platform;

-- Tabla de usuarios
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Tabla de eventos/partidos
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    thumbnail_url VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    stream_key VARCHAR(100) UNIQUE NOT NULL,
    stream_url VARCHAR(500),
    status ENUM('scheduled', 'live', 'ended', 'cancelled') DEFAULT 'scheduled',
    scheduled_start DATETIME NOT NULL,
    actual_start DATETIME,
    actual_end DATETIME,
    max_viewers INT DEFAULT 0,
    enable_recording BOOLEAN DEFAULT TRUE,
    enable_chat BOOLEAN DEFAULT TRUE,
    enable_dvr BOOLEAN DEFAULT FALSE,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_start),
    INDEX idx_stream_key (stream_key)
) ENGINE=InnoDB;

-- Tabla de compras/accesos
CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    access_token VARCHAR(255) UNIQUE,
    expires_at DATETIME,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_user_event (user_id, event_id),
    INDEX idx_status (status),
    INDEX idx_token (access_token)
) ENGINE=InnoDB;

-- Tabla de sesiones activas (control de 1 dispositivo por usuario)
CREATE TABLE active_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    device_info TEXT,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_session_token (session_token),
    INDEX idx_heartbeat (last_heartbeat)
) ENGINE=InnoDB;

-- Tabla de analíticas
CREATE TABLE analytics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    action VARCHAR(50) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event (event_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Tabla de configuración de streaming
CREATE TABLE stream_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED UNIQUE NOT NULL,
    video_bitrates JSON, -- ['1080p', '720p', '480p', '360p']
    audio_bitrate INT DEFAULT 128,
    keyframe_interval INT DEFAULT 2,
    enable_watermark BOOLEAN DEFAULT TRUE,
    watermark_position ENUM('top-left', 'top-right', 'bottom-left', 'bottom-right') DEFAULT 'top-right',
    watermark_opacity DECIMAL(3, 2) DEFAULT 0.7,
    dvr_duration INT DEFAULT 7200, -- segundos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de chat (opcional)
CREATE TABLE chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_moderated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_created (event_id, created_at)
) ENGINE=InnoDB;

-- Tabla de grabaciones VOD
CREATE TABLE recordings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED,
    duration INT UNSIGNED, -- segundos
    format VARCHAR(20) DEFAULT 'mp4',
    resolution VARCHAR(20),
    status ENUM('processing', 'ready', 'failed') DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Insertar usuario admin por defecto
INSERT INTO users (email, password_hash, full_name, role, email_verified) 
VALUES ('admin@streaming.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin', TRUE);
-- Password: changeme123

-- Evento de prueba
INSERT INTO events (title, description, category, price, stream_key, scheduled_start, created_by)
VALUES (
    'Partido de Prueba',
    'Evento de prueba para configuración del sistema',
    'Fútbol',
    5.00,
    CONCAT('test_', MD5(RAND())),
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    1
);
