# Plataforma de Streaming Pay-Per-Event

## 🚀 Características

- ✅ Transmisión en vivo RTMP/HLS
- ✅ Pago por evento (integración con MercadoPago/Stripe)
- ✅ Control de acceso único (1 dispositivo por usuario)
- ✅ Watermarks dinámicos personalizados
- ✅ Grabación automática (VOD)
- ✅ Panel de administración
- ✅ Analíticas en tiempo real
- ✅ Chat en vivo opcional

## 📁 Estructura del Proyecto

```
streaming-platform/
├── config/
│   ├── database.php
│   ├── streaming.php
│   └── payment.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── player.php
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Middleware/
├── api/
│   ├── auth.php
│   ├── events.php
│   ├── payment.php
│   └── streaming.php
├── admin/
│   ├── dashboard.php
│   ├── events.php
│   └── users.php
├── scripts/
│   ├── transcode.sh
│   └── monitor.php
├── storage/
│   ├── streams/
│   ├── vod/
│   └── logs/
└── vendor/
```

## 🛠️ Requisitos del Servidor

### Software Necesario
- PHP 8.1+ (con extensiones: pdo, mysqli, curl, gd, mbstring)
- MySQL 8.0+ / MariaDB 10.5+
- Nginx con módulo RTMP
- FFmpeg 4.4+
- Redis (para gestión de sesiones)
- Node.js 16+ (para chat en tiempo real)

### Instalación en Ubuntu/Debian

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP y extensiones
sudo apt install php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-redis -y

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar Nginx con RTMP
sudo apt install build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev -y
cd /tmp
wget http://nginx.org/download/nginx-1.24.0.tar.gz
wget https://github.com/arut/nginx-rtmp-module/archive/master.zip
tar -xzf nginx-1.24.0.tar.gz
unzip master.zip
cd nginx-1.24.0
./configure --with-http_ssl_module --add-module=../nginx-rtmp-module-master
make && sudo make install

# Instalar FFmpeg
sudo apt install ffmpeg -y

# Instalar Redis
sudo apt install redis-server -y
sudo systemctl enable redis-server

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## ⚙️ Configuración

### 1. Base de Datos
```bash
mysql -u root -p < database/schema.sql
```

### 2. Variables de Entorno
Copiar `.env.example` a `.env` y configurar:
```bash
cp .env.example .env
nano .env
```

### 3. Nginx RTMP
Configurar `/usr/local/nginx/conf/nginx.conf` según `config/nginx-rtmp.conf`

### 4. Instalar Dependencias
```bash
composer install
npm install
```

## 🎥 Uso

### Iniciar Transmisión (OBS)
- **Servidor**: `rtmp://tu-servidor.com/live`
- **Clave de Stream**: `{event_stream_key}` (generada en el panel admin)

### URL de Reproducción
- **HLS**: `https://tu-servidor.com/hls/{event_id}/index.m3u8`
- **DASH**: `https://tu-servidor.com/dash/{event_id}/index.mpd`

## 💳 Pasarelas de Pago Soportadas

- MercadoPago (Argentina/LATAM)
- Stripe (Internacional)
- PayPal
- Integración personalizada

## 📊 Panel de Administración

Acceder a: `https://tu-servidor.com/admin`

Usuario por defecto:
- Email: admin@streaming.com
- Password: admin123

## 🔒 Seguridad

- Tokens JWT para autenticación
- Sesiones únicas por dispositivo
- URLs firmadas temporalmente
- Protección contra hotlinking
- Watermarks dinámicos
- Rate limiting en API

## 📈 Monitoreo

Ver estadísticas en tiempo real:
```bash
php scripts/monitor.php
```

## 🆘 Soporte

Para problemas o consultas, revisar:
- Logs: `storage/logs/`
- Documentación: `docs/`

## 📝 Licencia

MIT License - Uso libre para proyectos comerciales
