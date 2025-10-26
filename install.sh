#!/bin/bash
# install.sh - Script de instalación automatizada para Streaming Platform

set -e

echo "================================================"
echo "  Instalación de Streaming Platform"
echo "  Pay-Per-Event System"
echo "================================================"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
    print_error "Este script debe ejecutarse como root (sudo)"
    exit 1
fi

# Solicitar información
echo "Por favor ingresa la siguiente información:"
read -p "Dominio (ejemplo: streaming.midominio.com): " DOMAIN
read -p "Email del administrador: " ADMIN_EMAIL
read -sp "Contraseña de MySQL root: " MYSQL_ROOT_PASS
echo ""
read -sp "Contraseña para usuario de base de datos: " DB_PASS
echo ""

print_info "Actualizando sistema..."
apt update && apt upgrade -y

print_info "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget git unzip

# Instalar PHP 8.1
print_info "Instalando PHP 8.1 y extensiones..."
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring \
    php8.1-xml php8.1-zip php8.1-redis php8.1-intl php8.1-bcmath

# Instalar MySQL
print_info "Instalando MySQL..."
apt install -y mysql-server
mysql -uroot -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE IF NOT EXISTS streaming_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p"${MYSQL_ROOT_PASS}" -e "CREATE USER IF NOT EXISTS 'streaming_user'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -uroot -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON streaming_platform.* TO 'streaming_user'@'localhost';"
mysql -uroot -p"${MYSQL_ROOT_PASS}" -e "FLUSH PRIVILEGES;"

# Instalar Redis
print_info "Instalando Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Instalar FFmpeg
print_info "Instalando FFmpeg..."
apt install -y ffmpeg

# Instalar Nginx con módulo RTMP
print_info "Instalando Nginx con RTMP..."
apt install -y build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev libaio1 libaio-dev

# Descargar Nginx y módulo RTMP
cd /tmp
NGINX_VERSION="1.24.0"
wget "http://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz"
wget "https://github.com/arut/nginx-rtmp-module/archive/master.zip" -O nginx-rtmp.zip

tar -xzf "nginx-${NGINX_VERSION}.tar.gz"
unzip nginx-rtmp.zip

cd "nginx-${NGINX_VERSION}"

# Compilar Nginx con RTMP
./configure \
    --with-http_ssl_module \
    --with-http_v2_module \
    --with-http_realip_module \
    --with-http_addition_module \
    --with-http_sub_module \
    --with-http_dav_module \
    --with-http_flv_module \
    --with-http_mp4_module \
    --with-http_gunzip_module \
    --with-http_gzip_static_module \
    --with-http_random_index_module \
    --with-http_secure_link_module \
    --with-http_stub_status_module \
    --with-http_auth_request_module \
    --with-threads \
    --with-stream \
    --with-stream_ssl_module \
    --with-http_slice_module \
    --with-file-aio \
    --add-module=../nginx-rtmp-module-master

make -j$(nproc)
make install

# Crear servicio systemd para Nginx
cat > /etc/systemd/system/nginx.service << 'EOF'
[Unit]
Description=Nginx HTTP Server
After=network.target

[Service]
Type=forking
PIDFile=/usr/local/nginx/logs/nginx.pid
ExecStartPre=/usr/local/nginx/sbin/nginx -t
ExecStart=/usr/local/nginx/sbin/nginx
ExecReload=/bin/kill -s HUP $MAINPID
ExecStop=/bin/kill -s QUIT $MAINPID
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable nginx

# Crear directorios necesarios
print_info "Creando estructura de directorios..."
mkdir -p /var/www/streaming-platform
mkdir -p /var/www/streaming/hls
mkdir -p /var/www/streaming/vod
mkdir -p /var/www/streaming-platform/storage/logs

# Permisos
chown -R www-data:www-data /var/www/streaming-platform
chown -R www-data:www-data /var/www/streaming
chmod -R 755 /var/www/streaming-platform
chmod -R 755 /var/www/streaming

# Instalar Composer
print_info "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Copiar archivos del proyecto
print_info "Configurando proyecto..."
cd /var/www/streaming-platform

# Si hay archivos en el directorio actual, copiarlos
if [ -f "composer.json" ]; then
    print_info "Instalando dependencias de Composer..."
    composer install --no-dev --optimize-autoloader
fi

# Configurar .env
if [ ! -f ".env" ]; then
    cp .env.example .env
    
    # Generar JWT secret
    JWT_SECRET=$(openssl rand -base64 32)
    
    # Actualizar .env
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
    sed -i "s|DB_PASS=.*|DB_PASS=${DB_PASS}|" .env
    sed -i "s|JWT_SECRET=.*|JWT_SECRET=${JWT_SECRET}|" .env
    sed -i "s|RTMP_HOST=.*|RTMP_HOST=${DOMAIN}|" .env
    sed -i "s|HLS_BASE_URL=.*|HLS_BASE_URL=https://${DOMAIN}/hls|" .env
fi

# Importar schema de base de datos
print_info "Importando schema de base de datos..."
if [ -f "database/schema.sql" ]; then
    mysql -ustreaming_user -p"${DB_PASS}" streaming_platform < database/schema.sql
fi

# Configurar Nginx
print_info "Configurando Nginx..."
if [ -f "config/nginx-rtmp.conf" ]; then
    # Actualizar domain y document root en el archivo de configuración
    sed "s/tu-dominio.com/${DOMAIN}/g" config/nginx-rtmp.conf > /usr/local/nginx/conf/nginx.conf
    sed -i "s|root /var/www/streaming-platform/public;|root /var/www/streaming-platform/public;|g" /usr/local/nginx/conf/nginx.conf
fi

# Configurar PHP-FPM
print_info "Configurando PHP-FPM..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.1/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.1/fpm/php.ini
systemctl restart php8.1-fpm

# Iniciar Nginx
print_info "Iniciando Nginx..."
systemctl start nginx

# Configurar firewall
print_info "Configurando firewall..."
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1935/tcp
ufw --force enable

# Instalar Certbot para SSL
print_info "Instalando Certbot para SSL..."
apt install -y certbot

print_info "Para obtener certificado SSL, ejecuta:"
echo "certbot certonly --standalone -d ${DOMAIN}"

echo ""
echo "================================================"
echo -e "${GREEN}¡Instalación completada exitosamente!${NC}"
echo "================================================"
echo ""
echo "Información importante:"
echo "- Dominio: ${DOMAIN}"
echo "- URL de la aplicación: https://${DOMAIN}"
echo "- URL RTMP: rtmp://${DOMAIN}:1935/live"
echo "- Base de datos: streaming_platform"
echo "- Usuario DB: streaming_user"
echo ""
echo "Usuario administrador por defecto:"
echo "- Email: admin@streaming.com"
echo "- Contraseña: changeme123"
echo ""
echo -e "${YELLOW}IMPORTANTE:${NC}"
echo "1. Cambia la contraseña del administrador inmediatamente"
echo "2. Configura tus credenciales de pago en el archivo .env"
echo "3. Obtén un certificado SSL con: certbot certonly --standalone -d ${DOMAIN}"
echo "4. Actualiza la configuración de Nginx para usar HTTPS"
echo ""
echo "Logs:"
echo "- Nginx: /usr/local/nginx/logs/"
echo "- Aplicación: /var/www/streaming-platform/storage/logs/"
echo ""
echo "Para iniciar un stream, usa:"
echo "Servidor: rtmp://${DOMAIN}:1935/live"
echo "Clave: Obtener desde el panel de administración"
echo ""
