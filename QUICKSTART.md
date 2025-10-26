# Guía de Inicio Rápido - Streaming Platform

## 🚀 Instalación Rápida

### Opción 1: Instalación Automatizada (Recomendada)

```bash
# 1. Clonar o subir el proyecto al servidor
cd /var/www
git clone <tu-repositorio> streaming-platform
cd streaming-platform

# 2. Ejecutar script de instalación
chmod +x install.sh
sudo ./install.sh

# 3. El script te pedirá:
# - Dominio (ejemplo: streaming.midominio.com)
# - Email del administrador
# - Contraseñas de MySQL
```

### Opción 2: Instalación Manual

Ver `README.md` para instrucciones detalladas.

## 📋 Configuración Post-Instalación

### 1. Configurar Certificado SSL

```bash
sudo certbot certonly --standalone -d tu-dominio.com
```

Luego descomentar la sección HTTPS en `/usr/local/nginx/conf/nginx.conf`

### 2. Configurar MercadoPago

Editar `/var/www/streaming-platform/.env`:

```env
MP_PUBLIC_KEY="TU_PUBLIC_KEY"
MP_ACCESS_TOKEN="TU_ACCESS_TOKEN"
MP_SANDBOX=false  # false para producción
```

Obtener credenciales en: https://www.mercadopago.com.ar/developers/

### 3. Reiniciar servicios

```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

## 🎥 Crear Tu Primer Evento

### 1. Acceder al Panel de Administración

```
URL: https://tu-dominio.com/admin
Email: admin@streaming.com
Contraseña: changeme123
```

**¡IMPORTANTE!** Cambiar contraseña inmediatamente.

### 2. Crear Evento

1. Click en "Nuevo Evento"
2. Completar información:
   - Título: "Mi Primer Partido"
   - Descripción
   - Precio (en pesos argentinos)
   - Fecha y hora programada
3. Guardar y copiar el **Stream Key**

### 3. Configurar OBS Studio

#### Configuración OBS:

1. **Settings → Stream**
   - Service: Custom
   - Server: `rtmp://tu-dominio.com:1935/live`
   - Stream Key: `[el stream key copiado]`

2. **Settings → Output**
   - Output Mode: Advanced
   - Bitrate: 3000-5000 Kbps (dependiendo de tu internet)
   - Keyframe Interval: 2
   - Preset: veryfast
   - Profile: high
   - Tune: zerolatency

3. **Settings → Video**
   - Base Resolution: 1920x1080
   - Output Resolution: 1920x1080 (o 1280x720)
   - FPS: 30

#### Iniciar Transmisión:

1. Click en "Start Streaming"
2. El evento automáticamente pasará a estado "LIVE"
3. Los usuarios que compraron recibirán un email

## 💳 Flujo de Compra de Usuario

### Para el Usuario:

1. Navegar a `https://tu-dominio.com`
2. Ver eventos disponibles
3. Click en "Comprar Acceso"
4. Completar datos y pagar con MercadoPago
5. Recibir email de confirmación con enlace
6. Al iniciar el evento, recibir otro email
7. Click en el enlace para ver el stream

### Características de Seguridad:

- ✅ Solo 1 dispositivo simultáneo por usuario
- ✅ Watermark con email + IP del usuario
- ✅ URLs firmadas temporalmente
- ✅ Validación de sesión cada 30 segundos
- ✅ Tokens únicos no transferibles

## 🔧 Mantenimiento

### Ver Logs

```bash
# Logs de Nginx
tail -f /usr/local/nginx/logs/error.log
tail -f /usr/local/nginx/logs/access.log

# Logs de la aplicación
tail -f /var/www/streaming-platform/storage/logs/app.log

# Logs de webhooks MercadoPago
tail -f /var/www/streaming-platform/storage/logs/mercadopago_webhooks.log
```

### Limpiar Sesiones Antiguas

```bash
# Agregar a crontab
crontab -e

# Agregar línea:
*/5 * * * * mysql -u streaming_user -p'password' streaming_platform -e "DELETE FROM active_sessions WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
```

### Monitorear Espectadores Activos

```bash
# Crear script de monitoreo
php /var/www/streaming-platform/scripts/monitor.php
```

## 📊 Estadísticas y Analíticas

### Ver Estadísticas de un Evento

```sql
mysql -u streaming_user -p streaming_platform

SELECT 
    e.title,
    e.status,
    e.max_viewers,
    COUNT(DISTINCT p.id) as total_purchases,
    SUM(p.amount) as total_revenue
FROM events e
LEFT JOIN purchases p ON e.id = p.event_id AND p.status = 'completed'
WHERE e.id = [EVENT_ID]
GROUP BY e.id;
```

## 🐛 Solución de Problemas

### El stream no se ve

1. Verificar que FFmpeg esté instalado:
   ```bash
   ffmpeg -version
   ```

2. Verificar logs de Nginx:
   ```bash
   tail -f /usr/local/nginx/logs/error.log
   ```

3. Verificar permisos de directorios:
   ```bash
   ls -la /var/www/streaming/hls/
   ```

### Error de pago

1. Verificar credenciales de MercadoPago en `.env`
2. Revisar logs: `storage/logs/mercadopago_webhooks.log`
3. Verificar que la URL de webhook sea accesible públicamente

### Múltiples dispositivos

Si un usuario reporta que no puede ver desde otro dispositivo:

1. Es comportamiento esperado (solo 1 dispositivo)
2. Para permitir múltiples dispositivos, modificar lógica en:
   `src/Services/AuthService.php` → método `startViewingSession()`

## 🔐 Seguridad

### Cambiar Contraseña Admin

```sql
mysql -u streaming_user -p streaming_platform

UPDATE users 
SET password_hash = '$2y$10$[nuevo_hash]' 
WHERE email = 'admin@streaming.com';
```

Generar hash:
```php
php -r "echo password_hash('nueva_password', PASSWORD_BCRYPT);"
```

### Configurar Firewall

```bash
# Permitir solo puertos necesarios
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw allow 1935/tcp # RTMP
ufw enable
```

## 📱 Integración con YouTube

Para usar YouTube como fuente (opcional):

1. Obtener API key: https://console.cloud.google.com/
2. Configurar en `.env`:
   ```env
   YOUTUBE_API_KEY="tu_api_key_aqui"
   ```

3. Al crear evento, pegar URL de YouTube en "Stream URL"

## 🌐 CDN (Opcional)

Para mejorar rendimiento con muchos usuarios:

1. Configurar Cloudflare, Cloudfront o BunnyCDN
2. Actualizar en `.env`:
   ```env
   CDN_URL="https://cdn.tu-dominio.com"
   ```

## 📞 Soporte

Para consultas:
- Email: soporte@tu-dominio.com
- Documentación completa: `/docs`

## 🎉 ¡Listo!

Tu plataforma está configurada y lista para transmitir eventos.

**Próximos pasos sugeridos:**
1. Personalizar diseño en `public/assets/`
2. Configurar emails (SMTP) en `.env`
3. Agregar más categorías de eventos
4. Implementar chat en vivo (opcional)
5. Agregar más métodos de pago (Stripe, PayPal)
