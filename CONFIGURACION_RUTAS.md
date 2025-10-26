# 🔧 Configuración de Rutas y Estructura

## Problema Identificado ✅

Los archivos están en `public/` pero las URLs apuntan a la raíz. Ejemplo:
- Archivo real: `/public/login.php`
- URL en enlaces: `/login.php` ❌

## ✅ Soluciones Implementadas

Hay **3 opciones** para configurar tu servidor. Elige la que mejor se adapte a tu hosting:

---

## 📌 OPCIÓN 1: Usar Document Root en /public (RECOMENDADA)

Esta es la opción más segura y profesional.

### Configuración en Nginx:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    
    # Document root apunta a /public
    root /var/www/streaming-platform/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}
```

### Configuración en Apache:

Editar archivo de virtual host:

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    
    # Document root apunta a /public
    DocumentRoot /var/www/streaming-platform/public
    
    <Directory /var/www/streaming-platform/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Ventajas:
✅ Más seguro (archivos sensibles fuera del root)
✅ URLs limpias
✅ Estructura profesional
✅ Mejor organización

### Desventajas:
⚠️ Requiere acceso al servidor para cambiar document root

---

## 📌 OPCIÓN 2: Usar index.php en Raíz (Incluido)

He creado un `index.php` en la raíz que actúa como router.

### Estructura:
```
streaming-platform/
├── index.php              ← Router principal (NUEVO)
├── .htaccess             ← Reescritura de URLs (NUEVO)
├── public/
│   ├── index.php         ← Página principal
│   ├── login.php
│   ├── register.php
│   └── ...
```

### Cómo funciona:
1. Todas las peticiones llegan a `/index.php` (raíz)
2. El router busca el archivo en `/public/`
3. Si existe, lo ejecuta
4. Si no existe, muestra 404

### Configuración necesaria:

**Para Apache** - El `.htaccess` ya está incluido, solo asegúrate que `mod_rewrite` esté activo:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Para Nginx** - Agregar a tu configuración:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Ventajas:
✅ No requiere cambiar document root
✅ Funciona en hosting compartido
✅ URLs limpias
✅ Fácil de implementar

### Desventajas:
⚠️ Menos seguro que Opción 1
⚠️ Archivos sensibles accesibles por URL

---

## 📌 OPCIÓN 3: Agregar /public/ en todas las URLs

La opción más simple pero menos profesional.

### Cambiar todas las URLs en el código:

En lugar de:
```php
<a href="/login.php">Iniciar Sesión</a>
```

Usar:
```php
<a href="/public/login.php">Iniciar Sesión</a>
```

### Ventajas:
✅ Funciona inmediatamente
✅ No requiere configuración

### Desventajas:
❌ URLs feas (`/public/login.php`)
❌ Menos profesional
❌ Expone estructura de carpetas

---

## 🎯 ¿Cuál Elegir?

### Si tienes VPS o servidor dedicado:
👉 **OPCIÓN 1** (Document Root en /public)

### Si tienes hosting compartido:
👉 **OPCIÓN 2** (index.php en raíz) - Ya incluido ✅

### Si quieres la solución más rápida:
👉 **OPCIÓN 3** (URLs con /public/)

---

## 🚀 Implementación Rápida

### Para OPCIÓN 1 (VPS/Dedicado):

```bash
# 1. Subir archivos al servidor
cd /var/www/streaming-platform

# 2. Editar configuración de Nginx
sudo nano /etc/nginx/sites-available/streaming

# 3. Cambiar DocumentRoot a:
root /var/www/streaming-platform/public;

# 4. Reiniciar Nginx
sudo systemctl restart nginx
```

### Para OPCIÓN 2 (Hosting Compartido):

```bash
# 1. Subir todos los archivos incluyendo index.php y .htaccess en raíz

# 2. Verificar que mod_rewrite esté activo (Apache)
# Ya está listo! ✅
```

### Para OPCIÓN 3 (Rápida):

Necesitarías modificar todos los archivos PHP agregando `/public/` a las rutas.
No recomendado.

---

## 📝 Archivos Incluidos para Facilitar

### ✅ .htaccess (raíz)
Configuración de Apache para reescritura de URLs.

### ✅ index.php (raíz)
Router que maneja todas las peticiones y las redirige a `/public/`.

### ✅ Página 404
Incluida en el router para URLs no encontradas.

---

## 🔒 Seguridad

### OPCIÓN 1 es la más segura porque:
- Archivos de configuración (.env, composer.json) no son accesibles por web
- Solo la carpeta public/ es accesible
- Archivos sensibles protegidos por defecto

### OPCIÓN 2 tiene protección adicional:
- .htaccess bloquea archivos .env, .sql, .sh, .md
- index.php valida extensiones de archivo
- Solo archivos PHP en /public/ son ejecutables

---

## 🧪 Probar la Configuración

### Test 1: Acceder a la home
```
http://tu-dominio.com/
```
✅ Debe mostrar index.php

### Test 2: Acceder a login
```
http://tu-dominio.com/login.php
```
✅ Debe mostrar página de login

### Test 3: Acceder a archivo sensible
```
http://tu-dominio.com/.env
```
❌ Debe dar 403 Forbidden o 404

### Test 4: Acceder directamente a public
```
http://tu-dominio.com/public/index.php
```
Con OPCIÓN 1: ❌ 404
Con OPCIÓN 2: ✅ Funciona (pero no recomendado)

---

## 🆘 Troubleshooting

### Problema: "404 Not Found" en todas las páginas

**Solución para Apache:**
```bash
# Verificar mod_rewrite
sudo a2enmod rewrite

# Verificar AllowOverride en Apache config
# Debe ser: AllowOverride All

sudo systemctl restart apache2
```

**Solución para Nginx:**
```bash
# Agregar try_files en configuración
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

sudo systemctl restart nginx
```

### Problema: Las rutas muestran código PHP

**Solución:**
```bash
# Verificar que PHP-FPM esté corriendo
sudo systemctl status php8.1-fpm

# Reiniciar PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Problema: CSS/JS no cargan

**Solución:**
```bash
# Verificar permisos de carpeta public/assets/
sudo chmod -R 755 /var/www/streaming-platform/public/assets/
```

---

## 📋 Checklist de Configuración

- [ ] Elegir opción (1, 2 o 3)
- [ ] Configurar servidor según opción elegida
- [ ] Subir todos los archivos
- [ ] Verificar .htaccess (si usas Apache)
- [ ] Probar acceso a home
- [ ] Probar acceso a login
- [ ] Probar que archivos sensibles estén bloqueados
- [ ] Verificar que CSS/JS carguen correctamente

---

## 💡 Recomendación Final

Para **producción profesional**, usa **OPCIÓN 1**.
Para **hosting compartido** o **pruebas rápidas**, usa **OPCIÓN 2** (ya incluida).

Todos los archivos necesarios están incluidos en el proyecto ✅

---

**Última actualización**: Octubre 2025
**Archivos incluidos**: index.php (raíz), .htaccess
