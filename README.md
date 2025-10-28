# 🎬 Plataforma de Streaming Pay-Per-Event

Una plataforma completa de streaming en vivo con pagos por evento, gestión de usuarios multinivel, sistema de comisiones y control de sesiones únicas.

## ✨ Características Principales

### 🎥 Streaming
- ✅ Transmisión en vivo vía **MediaMTX (RTMP/HLS/WebRTC)**
- ✅ Soporte para **OBS Studio** y otros encoders RTMP
- ✅ Integración con **YouTube (videos sin listar)**
- ✅ Grabación automática (VOD)
- ✅ Control de sesiones activas en tiempo real
- ✅ Detección de múltiples dispositivos simultáneos

### 💰 Sistema de Pagos
- ✅ Integración con **MercadoPago**
- ✅ Múltiples monedas (ARS, USD, EUR, MXN, BRL)
- ✅ Acceso gratuito opcional
- ✅ Sistema de comisiones configurable por streamer

### 👥 Gestión de Usuarios
- ✅ **3 roles**: Admin, Streamer, Usuario
- ✅ **Admins**: Control total del sistema
- ✅ **Streamers**: Crear eventos, transmitir y ver sus ganancias
- ✅ **Usuarios**: Comprar y ver eventos

### 📊 Analíticas y Reportes
- ✅ Dashboard con métricas en tiempo real
- ✅ Estadísticas de ventas y ganancias
- ✅ Monitor de sesiones activas y conflictos
- ✅ Reportes por streamer con historial de pagos
- ✅ Top eventos y streamers

### 🔒 Seguridad
- ✅ Control de acceso único (1 dispositivo por usuario)
- ✅ Detección de conflictos de sesión
- ✅ Expulsión manual de sesiones
- ✅ Logs de conflictos (últimas 24 horas)

### ⚙️ Configuración Flexible
- ✅ Comisiones globales y por streamer
- ✅ Métodos de pago personalizables
- ✅ Mínimos de retiro configurables
- ✅ Modo sandbox para pruebas

## 📁 Estructura del Proyecto

```
streaming-platform/
├── config/
│   ├── database.php           # Configuración de base de datos
│   ├── streaming.php          # Configuración de MediaMTX
│   └── payment.php            # Configuración de MercadoPago
├── public/
│   ├── index.php              # Página principal
│   ├── login.php              # Inicio de sesión
│   ├── register.php           # Registro de usuarios
│   ├── event.php              # Página del evento
│   ├── player.php             # Reproductor de video
│   └── assets/                # CSS, JS, imágenes
├── admin/
│   ├── header.php             # Header compartido
│   ├── footer.php             # Footer compartido
│   ├── styles.php             # Estilos compartidos
│   ├── dashboard.php          # Dashboard principal
│   ├── events.php             # Gestión de eventos
│   ├── users.php              # Gestión de usuarios
│   ├── purchases.php          # Historial de compras/ventas
│   ├── analytics.php          # Analíticas y reportes
│   ├── sessions_monitor.php   # Monitor de sesiones activas
│   ├── settings.php           # Configuración del sistema
│   ├── streamer_detail.php    # Detalle de streamer
│   └── stream-settings.php    # Guía de configuración OBS
├── api/
│   ├── auth/                  # Endpoints de autenticación
│   ├── events/                # Endpoints de eventos
│   ├── payment/               # Endpoints de pagos
│   ├── streaming/             # Endpoints de streaming
│   └── admin/                 # Endpoints administrativos
├── src/
│   ├── Models/                # Modelos de datos
│   ├── Services/              # Lógica de negocio
│   └── Middleware/            # Middleware de autenticación
├── storage/
│   ├── streams/               # Streams en vivo
│   ├── vod/                   # Videos grabados
│   └── logs/                  # Logs del sistema
└── database/
    └── schema.sql             # Esquema de base de datos
```

## 🛠️ Requisitos del Sistema

### Software Necesario
- **PHP**: 8.1+ con extensiones:
  - pdo, mysqli, curl, gd, mbstring, xml
- **MySQL/MariaDB**: 8.0+ / 10.5+
- **Nginx**: 1.20+
- **MediaMTX**: Última versión
- **FFmpeg**: 4.4+ (opcional, para transcodificación)

### Hardware Recomendado
- CPU: 4 cores mínimo
- RAM: 8GB mínimo
- Disco: SSD con 100GB+ espacio libre
- Ancho de banda: 100 Mbps+ simétrico

## 🚀 Instalación

### 1. Instalar Dependencias (Ubuntu/Debian)

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP y extensiones
sudo apt install php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd \
                 php8.1-mbstring php8.1-xml php8.1-zip -y

# Instalar MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Instalar Nginx
sudo apt install nginx -y

# Instalar FFmpeg (opcional)
sudo apt install ffmpeg -y
```

### 2. Instalar MediaMTX

```bash
# Descargar la última versión de MediaMTX
cd /opt
sudo wget https://github.com/bluenviron/mediamtx/releases/latest/download/mediamtx_linux_amd64.tar.gz
sudo tar -xzf mediamtx_linux_amd64.tar.gz
sudo mv mediamtx /usr/local/bin/

# Crear servicio systemd
sudo nano /etc/systemd/system/mediamtx.service
```

Contenido del archivo `mediamtx.service`:
```ini
[Unit]
Description=MediaMTX RTMP/HLS/WebRTC Server
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/local/bin/mediamtx /opt/mediamtx.yml
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar e iniciar MediaMTX
sudo systemctl daemon-reload
sudo systemctl enable mediamtx
sudo systemctl start mediamtx
```

### 3. Configurar MediaMTX

Crear archivo `/opt/mediamtx.yml`:

```yaml
# Configuración de MediaMTX
rtmp: yes
rtmpAddress: :1935
hls: yes
hlsAddress: :8888
hlsVariant: lowLatency
hlsSegmentCount: 7
hlsSegmentDuration: 1s
hlsPartDuration: 200ms
hlsSegmentMaxSize: 50M
hlsAllowOrigin: '*'
hlsAlwaysRemux: no
hlsTrustedProxies: []

# Configuración de paths
paths:
  all:
    readUser: ""
    readPass: ""
    publishUser: ""
    publishPass: ""
```

### 4. Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p

CREATE DATABASE streaming_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'streaming_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
GRANT ALL PRIVILEGES ON streaming_platform.* TO 'streaming_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar esquema
mysql -u streaming_user -p streaming_platform < database/schema.sql
```

### 5. Configurar Nginx

Crear archivo `/etc/nginx/sites-available/streaming`:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/streaming-platform/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/streaming-access.log;
    error_log /var/log/nginx/streaming-error.log;

    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # HLS Streaming (proxy a MediaMTX)
    location /hls/ {
        proxy_pass http://localhost:8888/;
        add_header Cache-Control no-cache;
        add_header Access-Control-Allow-Origin *;
    }

    # Archivos estáticos
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/streaming /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 6. Configurar la Aplicación

```bash
# Clonar o subir el proyecto
cd /var/www
sudo git clone https://github.com/tu-usuario/streaming-platform.git
# O subir vía FTP/SFTP

# Establecer permisos
sudo chown -R www-data:www-data streaming-platform
sudo chmod -R 755 streaming-platform
sudo chmod -R 777 streaming-platform/storage

# Configurar base de datos
cd streaming-platform/config
sudo cp database.php.example database.php
sudo nano database.php
```

Editar `config/database.php`:
```php
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'localhost';
    private $database = 'streaming_platform';
    private $username = 'streaming_user';
    private $password = 'tu_password_seguro';
    
    // ... resto del código
}
```

### 7. Configurar MercadoPago

Editar `config/payment.php` con tus credenciales:
```php
<?php
return [
    'mercadopago' => [
        'public_key' => 'APP_USR-xxxxxxxx-xxxxxxxx',
        'access_token' => 'APP_USR-xxxxxxxx-xxxxxxxx',
        'sandbox' => true, // Cambiar a false en producción
    ]
];
```

### 8. Crear Usuario Administrador

```sql
INSERT INTO users (email, password_hash, full_name, role, status, email_verified) 
VALUES (
    'admin@tudominio.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'Administrador',
    'admin',
    'active',
    1
);
```

## 🎥 Configuración de OBS Studio para Streamers

### Configuración Rápida

1. **Abrir OBS Studio**
2. **Ir a**: Configuración → Emisión
3. **Configurar**:
   - Servicio: **Personalizado**
   - Servidor: `rtmp://tu-dominio.com/live`
   - Clave de transmisión: `[copiar del panel de eventos]`

### Configuración Recomendada

**Salida**:
- Codificador: x264
- Bitrate: 3000-5000 Kbps
- Keyframe Interval: 2 segundos

**Video**:
- Resolución base: 1920x1080
- Resolución de salida: 1280x720 o 1920x1080
- FPS: 30 o 60

**Audio**:
- Bitrate: 160 Kbps
- Sample Rate: 48 kHz

## 📊 Uso del Panel Administrativo

### Acceso
- URL: `https://tu-dominio.com/admin`
- Usuario: admin@tudominio.com
- Password: admin123

### Para Administradores

**Dashboard**:
- Ver métricas globales
- Usuarios, eventos, ventas
- Gráficos de actividad

**Eventos**:
- Crear/editar/eliminar eventos
- Activar/finalizar transmisiones
- Configurar OBS o YouTube

**Usuarios**:
- Gestionar roles (Admin/Streamer/Usuario)
- Suspender/banear usuarios
- Ver historial de compras

**Analíticas**:
- Ganancias por streamer
- Pagos pendientes
- Top eventos y streamers

**Monitor de Sesiones**:
- Ver sesiones activas en tiempo real
- Detectar conflictos de dispositivos
- Expulsar sesiones manualmente

**Configuración**:
- Credenciales de MercadoPago
- Comisiones globales
- Comisiones por streamer
- Métodos de pago

### Para Streamers

**Dashboard**:
- Ver métricas personales
- Eventos propios
- Ventas y ganancias

**Mis Eventos**:
- Crear nuevos eventos
- Iniciar/finalizar transmisiones
- Elegir entre OBS o YouTube

**Mis Ventas**:
- Ver historial de ventas
- Comisiones ganadas
- Compradores únicos

**Analíticas**:
- Gráficos de ventas
- Eventos más vendidos
- Ganancias acumuladas

## 💳 Sistema de Comisiones

### Configuración Global
- Comisión streamer: **70-80%** (configurable)
- Comisión plataforma: **20-30%** (configurable)
- Mínimo de retiro: **$1000** (configurable)

### Configuración Individual
Los administradores pueden asignar comisiones personalizadas a cada streamer:
- Porcentaje diferente al global
- Mínimo de retiro personalizado
- Método de pago preferido (Transferencia/MercadoPago/PayPal)
- Datos bancarios (CBU/CVU/Alias)

### Procesamiento de Pagos
1. Los streamers acumulan ganancias por cada venta
2. Cuando alcanzan el mínimo de retiro, el pago queda disponible
3. El admin procesa el pago desde **Analíticas → Pagos Pendientes**
4. Se registra en el historial con fecha y notas

## 🔒 Control de Sesiones Únicas

### Funcionamiento
- **1 usuario = 1 dispositivo simultáneo**
- Detección automática de conflictos
- Notificación al usuario
- Sesión anterior se cierra automáticamente

### Monitoreo
En **Monitor de Sesiones** (solo admins):
- Sesiones activas en tiempo real
- IP y dispositivo de cada sesión
- Conflictos detectados (últimas 24h)
- Usuarios con más conflictos
- Acción: Expulsar sesión manualmente

## 🌐 URLs Importantes

### Frontend
- **Inicio**: `https://tu-dominio.com`
- **Evento**: `https://tu-dominio.com/event.php?id={event_id}`
- **Reproductor**: `https://tu-dominio.com/player.php?event={event_id}`

### Admin
- **Dashboard**: `https://tu-dominio.com/admin/dashboard.php`
- **Eventos**: `https://tu-dominio.com/admin/events.php`
- **Analíticas**: `https://tu-dominio.com/admin/analytics.php`
- **Monitor**: `https://tu-dominio.com/admin/sessions_monitor.php`

### Streaming
- **RTMP**: `rtmp://tu-dominio.com/live/{stream_key}`
- **HLS**: `http://tu-dominio.com/hls/{stream_key}/index.m3u8`

## 🐛 Solución de Problemas

### MediaMTX no inicia
```bash
# Ver logs
sudo journalctl -u mediamtx -f

# Verificar puerto 1935
sudo netstat -tulpn | grep 1935

# Reiniciar servicio
sudo systemctl restart mediamtx
```

### Streaming no se ve
1. Verificar que MediaMTX esté corriendo
2. Comprobar stream key correcta en OBS
3. Revisar firewall (puertos 1935, 8888)
4. Ver logs de Nginx: `/var/log/nginx/streaming-error.log`

### Pagos no funcionan
1. Verificar credenciales de MercadoPago en `admin/settings.php`
2. Comprobar modo sandbox vs producción
3. Revisar logs en `storage/logs/`

### Sesiones múltiples no detectadas
1. Verificar tabla `active_sessions` en MySQL
2. Comprobar heartbeat en `player.php`
3. Revisar API en `api/streaming/heartbeat.php`

## 📈 Monitoreo y Logs

### Logs del Sistema
- **Nginx**: `/var/log/nginx/streaming-*.log`
- **PHP**: `/var/log/php8.1-fpm.log`
- **MediaMTX**: `sudo journalctl -u mediamtx`
- **App**: `storage/logs/`

### Métricas en Tiempo Real
```bash
# Ver sesiones activas
mysql -u streaming_user -p -e "SELECT COUNT(*) FROM streaming_platform.active_sessions WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE);"

# Ver eventos en vivo
mysql -u streaming_user -p -e "SELECT id, title, status FROM streaming_platform.events WHERE status='live';"
```

## 🔐 Seguridad

### Recomendaciones
- ✅ Cambiar contraseña del admin por defecto
- ✅ Usar HTTPS (Let's Encrypt)
- ✅ Actualizar PHP y MySQL regularmente
- ✅ Configurar firewall (UFW)
- ✅ Backups automáticos diarios
- ✅ Cambiar credenciales de base de datos
- ✅ Usar modo producción en MercadoPago

### Firewall
```bash
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 1935/tcp  # RTMP
sudo ufw allow 8888/tcp  # HLS
sudo ufw enable
```

## 📞 Soporte

Para problemas o consultas:
- **Documentación**: Revisar este README
- **Logs**: Siempre revisar logs primero
- **GitHub Issues**: Reportar bugs
- **Email**: soporte@tudominio.com

## 📝 Licencia

MIT License - Uso libre para proyectos comerciales y personales.

## 🙏 Créditos

- **MediaMTX**: https://github.com/bluenviron/mediamtx
- **Video.js**: Reproductor de video
- **Chart.js**: Gráficos de analíticas
- **MercadoPago SDK**: Integración de pagos

---

**Desarrollado con ❤️ para la comunidad de streaming**