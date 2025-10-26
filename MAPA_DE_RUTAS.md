# 🗺️ MAPA DE RUTAS - Streaming Platform

## 📍 Estructura de URLs

Tu servidor está configurado con la carpeta `public/` visible en la URL.

### ✅ URLs Correctas (Todas incluyen /public/)

#### Páginas Principales:
- **Home**: `https://streaming.cellcomweb.com.ar/public/` o `/public/index.php`
- **Eventos**: `https://streaming.cellcomweb.com.ar/public/events.php`
- **Detalle Evento**: `https://streaming.cellcomweb.com.ar/public/event.php?id=X`
- **Login**: `https://streaming.cellcomweb.com.ar/public/login.php`
- **Registro**: `https://streaming.cellcomweb.com.ar/public/register.php`
- **Perfil**: `https://streaming.cellcomweb.com.ar/public/profile.php`
- **Logout**: `https://streaming.cellcomweb.com.ar/public/logout.php`
- **Privacidad**: `https://streaming.cellcomweb.com.ar/public/privacy.php`
- **Reproductor**: `https://streaming.cellcomweb.com.ar/public/player.php?event_id=X&token=Y`

#### Panel Admin:
- **Dashboard**: `https://streaming.cellcomweb.com.ar/admin/dashboard.php`
- **Eventos**: `https://streaming.cellcomweb.com.ar/admin/events.php`
- **Usuarios**: `https://streaming.cellcomweb.com.ar/admin/users.php`

#### APIs:
- **Validar Acceso**: `https://streaming.cellcomweb.com.ar/api/validate-access.php`
- **Heartbeat**: `https://streaming.cellcomweb.com.ar/api/heartbeat.php`
- **Compra**: `https://streaming.cellcomweb.com.ar/api/purchase.php`
- **Webhooks**: `https://streaming.cellcomweb.com.ar/api/webhooks/...`

---

## ✅ TODOS LOS ARCHIVOS YA ESTÁN CORREGIDOS

Todos los enlaces en los archivos PHP ahora incluyen `/public/` en las rutas.

### Archivos Corregidos:
✅ public/index.php
✅ public/events.php
✅ public/event.php
✅ public/login.php
✅ public/register.php
✅ public/profile.php
✅ public/logout.php
✅ public/watch.php
✅ public/privacy.php
✅ public/player.php
✅ admin/dashboard.php
✅ api/purchase.php

---

## 🔗 Ejemplos de Enlaces Corregidos

### En HTML:
```html
<!-- ✅ CORRECTO -->
<a href="/public/login.php">Iniciar Sesión</a>
<a href="/public/events.php">Ver Eventos</a>
<a href="/public/event.php?id=123">Ver Evento</a>

<!-- ❌ INCORRECTO -->
<a href="/login.php">Iniciar Sesión</a>
```

### En JavaScript:
```javascript
// ✅ CORRECTO
location.href='/public/event.php?id=123';

// ❌ INCORRECTO  
location.href='/event.php?id=123';
```

### En PHP Redirects:
```php
// ✅ CORRECTO
header('Location: /public/profile.php');

// ❌ INCORRECTO
header('Location: /profile.php');
```

### En Formularios:
```html
<!-- ✅ CORRECTO -->
<form action="/public/login.php" method="POST">

<!-- ❌ INCORRECTO -->
<form action="/login.php" method="POST">
```

---

## 🎯 Cómo Navegar el Sitio

1. **Inicio**: Accede a `https://streaming.cellcomweb.com.ar/public/`
2. **Ver eventos**: Click en "Eventos" → `/public/events.php`
3. **Ver detalle**: Click en un evento → `/public/event.php?id=X`
4. **Iniciar sesión**: Click en "Iniciar Sesión" → `/public/login.php`
5. **Comprar evento**: Click en "Comprar" → `/api/purchase.php` → MercadoPago
6. **Ver transmisión**: Click en "Ver Ahora" → `/public/player.php`

---

## 📂 Estructura de Carpetas en Servidor

```
/var/www/streaming-platform/
├── public/              ← Páginas visibles
│   ├── index.php       ← /public/index.php
│   ├── events.php      ← /public/events.php
│   ├── login.php       ← /public/login.php
│   └── ...
├── admin/              ← Panel admin
│   └── dashboard.php   ← /admin/dashboard.php
├── api/                ← APIs
│   ├── purchase.php    ← /api/purchase.php
│   └── webhooks/       ← /api/webhooks/...
├── src/                ← Clases (no accesibles por web)
├── config/             ← Configuración (no accesible por web)
└── .env                ← Variables (no accesible por web)
```

---

## 🔒 Seguridad

Las siguientes carpetas NO deberían ser accesibles por web:
- ❌ `/src/`
- ❌ `/config/`
- ❌ `/database/`
- ❌ `/.env`
- ❌ `/composer.json`

Si puedes acceder a estos archivos, necesitas configurar tu servidor.

---

## ✅ Verificación Rápida

Prueba estos enlaces en tu navegador:

1. Home: `https://streaming.cellcomweb.com.ar/public/`
   - ✅ Debe mostrar la página principal

2. Eventos: `https://streaming.cellcomweb.com.ar/public/events.php`
   - ✅ Debe mostrar listado de eventos

3. Login: `https://streaming.cellcomweb.com.ar/public/login.php`
   - ✅ Debe mostrar formulario de login

4. Admin: `https://streaming.cellcomweb.com.ar/admin/dashboard.php`
   - ✅ Debe pedir login (si no estás logueado como admin)

5. Archivo protegido: `https://streaming.cellcomweb.com.ar/.env`
   - ❌ NO debe ser accesible (403 o 404)

---

## 💡 Nota Importante

Todos los archivos PHP ya están configurados con las rutas correctas incluyendo `/public/`.

**No necesitas hacer ningún cambio adicional** - Todo está listo para funcionar ✅

