# Guía de Configuración de OBS Studio

## 📥 Descarga e Instalación

1. Descargar OBS Studio desde: https://obsproject.com/
2. Instalar siguiendo las instrucciones del instalador

## ⚙️ Configuración Inicial

### 1. Configuración de Stream

1. Abrir OBS Studio
2. Ir a **File → Settings → Stream**

**Configurar:**
- Service: `Custom...`
- Server: `rtmp://tu-dominio.com:1935/live`
- Stream Key: `[copiar desde panel admin]`

⚠️ **IMPORTANTE**: Nunca compartas tu Stream Key públicamente

### 2. Configuración de Video

**Settings → Video:**

```
Base (Canvas) Resolution: 1920x1080
Output (Scaled) Resolution: 1920x1080 (o 1280x720 si tienes internet lento)
Downscale Filter: Lanczos (mejor calidad)
Common FPS Values: 30
```

**Recomendaciones por velocidad de internet:**
- 📶 10+ Mbps upload: 1080p @ 30fps
- 📶 5-10 Mbps upload: 720p @ 30fps  
- 📶 3-5 Mbps upload: 480p @ 30fps

### 3. Configuración de Output (Audio/Video)

**Settings → Output → Streaming:**

#### Modo Simple (Recomendado para principiantes):
```
Video Bitrate: 3500 Kbps (ajustar según tu internet)
Encoder: Software (x264) o Hardware (NVENC si tienes Nvidia)
Audio Bitrate: 160
```

#### Modo Avanzado (Para usuarios expertos):
```
Output Mode: Advanced
Encoder: x264 (CPU) o NVENC/AMD (GPU)
Rate Control: CBR
Bitrate: 3000-5000 Kbps
Keyframe Interval: 2
CPU Usage Preset: veryfast (ajustar según tu PC)
Profile: high
Tune: zerolatency (importante para streaming en vivo)
```

### 4. Configuración de Audio

**Settings → Audio:**

```
Sample Rate: 48 kHz
Channels: Stereo

Desktop Audio: Default (para capturar audio del sistema)
Mic/Auxiliary Audio: Tu micrófono
```

**Audio → Advanced:**
```
Monitoring Device: Default
```

## 🎥 Configuración de Escenas

### Escena Básica para Partido de Fútbol

1. **Crear nueva escena**: Click en `+` en la sección Scenes

2. **Agregar fuente de video**:
   - Click `+` en Sources
   - Seleccionar:
     - `Video Capture Device` (cámara externa)
     - `Display Capture` (captura de pantalla)
     - `Window Capture` (ventana específica)

3. **Agregar marcador/overlay** (opcional):
   - Click `+` → `Image`
   - Seleccionar imagen PNG con transparencia
   - Posicionar en esquina superior

4. **Agregar texto** (opcional):
   - Click `+` → `Text (GDI+)`
   - Escribir marcador o información
   - Ajustar fuente, tamaño, color

### Ejemplo de Layout Profesional

```
┌─────────────────────────────────┐
│  LOGO      VS      LOGO         │  ← Overlay superior
├─────────────────────────────────┤
│                                 │
│        VIDEO PRINCIPAL          │  ← Cámara del partido
│                                 │
├─────────────────────────────────┤
│  Equipo A: 0    🕐 45:00  Equipo B: 0  │  ← Marcador
└─────────────────────────────────┘
```

## 🎛️ Configuración de Audio Avanzada

### Mezclar múltiples fuentes de audio:

1. **Audio Mixer** (panel inferior):
   - Desktop Audio: Audio del sistema/música
   - Mic/Aux: Tu comentario
   - Cada fuente con control de volumen individual

2. **Filtros de Audio** (click derecho en fuente):
   - Noise Suppression: Reduce ruido de fondo
   - Gain: Aumentar volumen
   - Compressor: Normalizar audio
   - Noise Gate: Eliminar silencio

## 📊 Monitoreo Durante Transmisión

### Panel de Estadísticas

**View → Stats** para ver en tiempo real:
- FPS actual
- Frames perdidos
- Bitrate de salida
- CPU usage

### Indicadores de Problemas:

🔴 **Círculo rojo en la esquina**: Problemas de conexión
🟡 **Frames perdidos > 1%**: Internet inestable
🟢 **Todo verde**: Transmisión óptima

## ⚠️ Solución de Problemas Comunes

### El stream se ve pixelado
- **Solución**: Reducir bitrate o resolución
- Reducir a 720p y bitrate a 2500 Kbps

### El stream se corta (buffering)
- **Solución**: 
  - Verificar velocidad de internet (usar speedtest.net)
  - Cerrar programas que usen internet
  - Conectar PC por cable ethernet (no WiFi)

### CPU al 100%
- **Solución**:
  - Cambiar Encoder a NVENC/AMD (hardware)
  - Reducir CPU Preset a "ultrafast"
  - Cerrar programas innecesarios

### Audio desincronizado
- **Solución**:
  - Settings → Advanced → Audio Delay Sync: ajustar en ms
  - Reiniciar OBS

### Frames perdidos
- **Solución**:
  - Verificar que otros dispositivos no estén usando internet
  - Reducir bitrate
  - Verificar firewall/antivirus no bloquee OBS

## 🎬 Checklist Pre-Transmisión

Antes de iniciar el stream, verificar:

- [ ] Stream Key configurado correctamente
- [ ] Test de velocidad de internet (min 5 Mbps upload)
- [ ] Todas las escenas configuradas
- [ ] Audio de todas las fuentes funciona
- [ ] Iluminación adecuada (si usas cámara)
- [ ] Batería de cámara/dispositivos cargada
- [ ] Notificar a usuarios que el evento comenzará

## 🚀 Iniciar Transmisión

1. **Verificar setup completo**
2. Click en **"Start Streaming"** (esquina inferior derecha)
3. El botón cambiará a **"Stop Streaming"**
4. Aparecerá indicador de tiempo de transmisión
5. En el panel admin, el evento pasará a estado "LIVE"
6. Los usuarios recibirán email de notificación

## 🛑 Finalizar Transmisión

1. Click en **"Stop Streaming"**
2. Confirmar que quieres detener
3. El evento se marcará como "Finalizado"
4. La grabación se procesará automáticamente (si está habilitado)

## 💡 Tips Profesionales

### Para mejor calidad:
- ✅ Usar cable ethernet (no WiFi)
- ✅ Cerrar navegadores y apps innecesarias
- ✅ Transmitir en horarios de bajo tráfico de internet
- ✅ Tener iluminación frontal (si usas cámara)
- ✅ Usar micrófono externo (mejor que el de la cámara)

### Para mejor engagement:
- ✅ Agregar overlays profesionales
- ✅ Usar múltiples cámaras (escenas diferentes)
- ✅ Interactuar con el chat (si está habilitado)
- ✅ Tener marcador visible en todo momento
- ✅ Agregar replays/highlights

## 📞 Soporte Técnico

Si tienes problemas durante la transmisión:

1. Verificar logs de OBS: **Help → Log Files → View Current Log**
2. Contactar soporte: soporte@tu-dominio.com
3. Revisar documentación de OBS: https://obsproject.com/wiki/

## 🎓 Recursos Adicionales

- OBS Studio Quickstart: https://obsproject.com/wiki/OBS-Studio-Quickstart
- Community Forums: https://obsproject.com/forum/
- YouTube tutorials: Buscar "OBS Studio tutorial español"

---

**¿Listo para transmitir?** ¡Adelante! 🎥🔴
