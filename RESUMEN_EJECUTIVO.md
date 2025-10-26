# 🎥 Plataforma de Streaming Pay-Per-Event
## Resumen Ejecutivo del Proyecto

---

## 📦 ¿Qué incluye este paquete?

Una plataforma completa de streaming en vivo con sistema de pago por evento, ideal para transmitir partidos deportivos, conciertos, conferencias y cualquier evento en vivo.

### ✨ Características Principales

**Sistema de Streaming:**
- ✅ Transmisión RTMP/HLS en tiempo real
- ✅ Transcodificación automática a múltiples calidades (1080p, 720p, 480p, 360p)
- ✅ Streaming adaptativo según conexión del usuario
- ✅ Soporte para OBS Studio y encoders profesionales
- ✅ Grabación automática (VOD) de eventos
- ✅ Latencia ultra baja (< 10 segundos)

**Sistema de Pagos:**
- ✅ Integración con MercadoPago (Argentina/LATAM)
- ✅ Soporte para Stripe y PayPal
- ✅ Webhooks para confirmación automática
- ✅ Emails de confirmación personalizados
- ✅ Panel de gestión de compras

**Seguridad y Control:**
- ✅ **1 dispositivo por usuario simultáneamente**
- ✅ Watermarks dinámicos con email + IP del usuario
- ✅ Validación de sesión cada 30 segundos
- ✅ Tokens únicos y seguros (JWT)
- ✅ URLs firmadas con expiración
- ✅ Protección contra hotlinking

**Panel de Administración:**
- ✅ Gestión de eventos
- ✅ Monitoreo de espectadores en tiempo real
- ✅ Estadísticas y analíticas detalladas
- ✅ Gestión de usuarios y compras
- ✅ Control de transmisiones

---

## 🗂️ Estructura del Proyecto

```
streaming-platform/
├── 📄 README.md                    # Documentación completa
├── 📄 QUICKSTART.md                # Guía de inicio rápido
├── 🔧 install.sh                   # Script de instalación automatizada
├── 📄 .env.example                 # Variables de entorno
├── 📄 composer.json                # Dependencias PHP
│
├── 📁 config/
│   ├── database.php                # Configuración de BD
│   ├── streaming.php               # Config de streaming
│   ├── payment.php                 # Config de pagos
│   └── nginx-rtmp.conf            # Config de Nginx RTMP
│
├── 📁 database/
│   └── schema.sql                  # Schema de base de datos
│
├── 📁 src/
│   ├── 📁 Models/
│   │   ├── User.php               # Modelo de usuario
│   │   └── Event.php              # Modelo de evento
│   └── 📁 Services/
│       ├── AuthService.php        # Autenticación y sesiones
│       └── PaymentService.php     # Procesamiento de pagos
│
├── 📁 api/
│   ├── validate-access.php        # Validar acceso a evento
│   ├── heartbeat.php              # Mantener sesión activa
│   ├── end-session.php            # Finalizar sesión
│   ├── viewers-count.php          # Contar espectadores
│   └── 📁 webhooks/
│       ├── validate-stream.php    # Validar stream key
│       ├── stream-start.php       # Notificar inicio
│       ├── stream-end.php         # Notificar fin
│       └── mercadopago.php        # Webhook de pagos
│
├── 📁 public/
│   ├── index.php                  # Página principal
│   └── player.php                 # Reproductor de video
│
└── 📁 docs/
    └── OBS_SETUP.md               # Guía de configuración de OBS
```

---

## 🚀 Instalación Rápida (3 pasos)

### 1️⃣ Subir archivos al servidor

```bash
# Conectar por SSH
ssh usuario@tu-servidor.com

# Clonar o subir el proyecto
cd /var/www
# (subir archivos aquí)
```

### 2️⃣ Ejecutar instalación automatizada

```bash
cd /var/www/streaming-platform
chmod +x install.sh
sudo ./install.sh
```

El script instalará automáticamente:
- PHP 8.1 + extensiones
- MySQL/MariaDB
- Redis
- Nginx con módulo RTMP
- FFmpeg
- Todas las dependencias

### 3️⃣ Configurar credenciales

Editar `.env`:
```bash
nano .env
```

Configurar:
- Credenciales de MercadoPago
- Dominio
- Email SMTP (opcional)

---

## 💰 Integración con MercadoPago

### Obtener Credenciales

1. Ir a: https://www.mercadopago.com.ar/developers/
2. Crear aplicación
3. Obtener:
   - Public Key
   - Access Token
4. Configurar Webhook:
   - URL: `https://tu-dominio.com/api/webhooks/mercadopago.php`

### Configurar en .env

```env
MP_PUBLIC_KEY="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
MP_ACCESS_TOKEN="APP_USR-xxxxxxxxxxxx-xxxxxx-xxxxxxxx"
MP_SANDBOX=false  # true para testing
```

---

## 🎥 Transmitir con OBS

### Configuración OBS (5 minutos)

1. **Settings → Stream:**
   - Server: `rtmp://tu-dominio.com:1935/live`
   - Stream Key: (copiar del panel admin)

2. **Settings → Output:**
   - Bitrate: 3000-5000 Kbps
   - Keyframe: 2
   - Preset: veryfast

3. **Settings → Video:**
   - Resolution: 1920x1080
   - FPS: 30

4. **Start Streaming** ▶️

Ver guía completa en: `docs/OBS_SETUP.md`

---

## 👥 Flujo de Usuario Completo

### Para el Administrador:

1. **Crear evento** en panel admin
2. **Configurar OBS** con stream key
3. **Iniciar transmisión** en OBS
4. El sistema automáticamente:
   - Marca evento como "LIVE"
   - Notifica a compradores por email
   - Inicia grabación (si está habilitado)

### Para el Usuario Final:

1. **Navegar** a https://tu-dominio.com
2. **Ver eventos** disponibles
3. **Comprar acceso** con MercadoPago
4. **Recibir email** con enlace de acceso
5. **Ver stream** cuando esté en vivo
   - Solo 1 dispositivo permitido
   - Watermark con su email + IP
   - Sesión validada cada 30 segundos

---

## 🔐 Seguridad Implementada

### Control de Acceso:
- ✅ Tokens JWT con expiración
- ✅ Una sesión activa por usuario/evento
- ✅ Validación de IP y User-Agent
- ✅ Heartbeat cada 30 segundos
- ✅ Auto-logout si se detecta otro dispositivo

### Protección de Contenido:
- ✅ Watermarks dinámicos por usuario
- ✅ URLs firmadas con expiración
- ✅ Validación de referer
- ✅ Protección contra hotlinking
- ✅ Stream keys únicas por evento

### Datos Sensibles:
- ✅ Contraseñas hasheadas (bcrypt)
- ✅ Datos de pago procesados por MercadoPago
- ✅ HTTPS obligatorio (SSL/TLS)
- ✅ Prepared statements (SQL injection)

---

## 📊 Analíticas Incluidas

El sistema registra automáticamente:
- Número de espectadores en tiempo real
- Pico máximo de espectadores
- Tiempo promedio de visualización
- Tasa de conversión (visitas → compras)
- Revenue por evento
- Dispositivos y ubicaciones de usuarios

Acceder a estadísticas desde el panel admin.

---

## 🛠️ Tecnologías Utilizadas

**Backend:**
- PHP 8.1+ (lenguaje principal)
- MySQL 8.0 (base de datos)
- Redis (caché y sesiones)

**Streaming:**
- Nginx RTMP Module (servidor RTMP)
- FFmpeg (transcodificación)
- HLS.js (reproductor web)

**Pagos:**
- MercadoPago SDK
- Stripe PHP SDK (opcional)
- PayPal SDK (opcional)

**Autenticación:**
- JWT (Firebase PHP-JWT)
- Sesiones Redis

---

## 📈 Escalabilidad

### Configuración Inicial (hasta 100 espectadores):
- 1 servidor VPS (4GB RAM, 2 CPU)
- Nginx + RTMP en mismo servidor
- Sin CDN

### Configuración Media (100-1000 espectadores):
- 2 servidores (app + streaming)
- CDN básico (Cloudflare)
- Redis separado

### Configuración Avanzada (1000+ espectadores):
- Múltiples servidores de streaming
- CDN profesional (Cloudfront/BunnyCDN)
- Load balancer
- Redis Cluster
- Base de datos replicada

---

## 💡 Casos de Uso

✅ **Partidos deportivos** (fútbol, básquet, etc.)
✅ **Conciertos y shows en vivo**
✅ **Conferencias y webinars pagos**
✅ **Clases y cursos en vivo**
✅ **Eventos corporativos**
✅ **Torneos de e-sports**
✅ **Teatro y performances**

---

## 🆘 Soporte y Documentación

### Documentos Incluidos:
- 📄 `README.md` - Documentación técnica completa
- 📄 `QUICKSTART.md` - Guía de inicio rápido
- 📄 `docs/OBS_SETUP.md` - Configuración de OBS Studio

### Recursos Adicionales:
- Comentarios en código
- Ejemplos de configuración
- Scripts de mantenimiento

---

## 📝 Licencia

MIT License - Uso libre para proyectos comerciales

---

## ✅ Checklist de Implementación

- [ ] Subir archivos al servidor
- [ ] Ejecutar `install.sh`
- [ ] Configurar `.env`
- [ ] Obtener certificado SSL
- [ ] Configurar MercadoPago
- [ ] Crear primer evento
- [ ] Configurar OBS
- [ ] Realizar prueba de transmisión
- [ ] Cambiar contraseña admin
- [ ] Personalizar diseño (opcional)

---

## 🎉 ¡Listo para Comenzar!

Tu plataforma de streaming está completa y lista para usar.

**Accesos por defecto:**
- Panel Admin: `https://tu-dominio.com/admin`
- Usuario: `admin@streaming.com`
- Contraseña: `changeme123`

**Próximos pasos:**
1. Cambiar contraseña del admin
2. Crear tu primer evento
3. Configurar OBS
4. ¡Transmitir!

---

**¿Preguntas?** Revisa la documentación completa en `README.md` y `QUICKSTART.md`

**¡Éxito con tu plataforma de streaming!** 🚀🎥
