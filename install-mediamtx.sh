#!/bin/bash

# Script de instalación automática de MediaMTX
# Para streaming.cellcomweb.com.ar

set -e

echo "============================================"
echo "   INSTALACIÓN DE MEDIAMTX"
echo "   Streaming Platform"
echo "============================================"
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Este script debe ejecutarse como root"
    echo "   Usa: sudo bash install-mediamtx.sh"
    exit 1
fi

echo "✓ Ejecutando como root"
echo ""

# 1. Crear directorio
echo "📁 Creando directorio /opt/mediamtx..."
mkdir -p /opt/mediamtx
cd /opt/mediamtx

# 2. Descargar MediaMTX
echo "⬇️  Descargando MediaMTX v1.8.3..."
wget -q --show-progress https://github.com/bluenviron/mediamtx/releases/download/v1.8.3/mediamtx_v1.8.3_linux_amd64.tar.gz

# 3. Extraer
echo "📦 Extrayendo archivos..."
tar -xzf mediamtx_v1.8.3_linux_amd64.tar.gz
rm mediamtx_v1.8.3_linux_amd64.tar.gz

# 4. Dar permisos
echo "🔐 Configurando permisos..."
chmod +x mediamtx

# 5. Crear directorio de grabaciones
echo "📹 Creando directorio de grabaciones..."
mkdir -p /opt/mediamtx/recordings
chmod 755 /opt/mediamtx/recordings

# 6. Crear directorio de logs
echo "📝 Creando directorio de logs..."
mkdir -p /var/log
touch /var/log/mediamtx.log
chmod 644 /var/log/mediamtx.log

# 7. Copiar archivo de configuración
echo "⚙️  Copiando configuración..."
if [ -f "./mediamtx.yml" ]; then
    cp ./mediamtx.yml /opt/mediamtx/mediamtx.yml
    echo "   ✓ mediamtx.yml copiado"
else
    echo "   ⚠️  mediamtx.yml no encontrado en el directorio actual"
    echo "   📥 Descarga mediamtx.yml y colócalo en /opt/mediamtx/"
fi

# 8. Crear servicio systemd
echo "🔧 Creando servicio systemd..."
cat > /etc/systemd/system/mediamtx.service << 'EOF'
[Unit]
Description=MediaMTX RTMP/HLS/WebRTC Server
Documentation=https://github.com/bluenviron/mediamtx
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/mediamtx
ExecStart=/opt/mediamtx/mediamtx /opt/mediamtx/mediamtx.yml
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=mediamtx

LimitNOFILE=65536
LimitNPROC=4096

NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/mediamtx/recordings /var/log

[Install]
WantedBy=multi-user.target
EOF

# 9. Recargar systemd
echo "🔄 Recargando systemd..."
systemctl daemon-reload

# 10. Habilitar servicio
echo "✅ Habilitando auto-inicio..."
systemctl enable mediamtx

# 11. Configurar firewall (si UFW está instalado)
if command -v ufw &> /dev/null; then
    echo "🔥 Configurando firewall..."
    ufw allow 1935/tcp comment 'MediaMTX RTMP'
    ufw allow 8888/tcp comment 'MediaMTX HLS'
    ufw allow 8889/tcp comment 'MediaMTX WebRTC'
    ufw reload
    echo "   ✓ Puertos abiertos: 1935, 8888, 8889"
else
    echo "⚠️  UFW no encontrado, configura el firewall manualmente:"
    echo "   - Puerto 1935/tcp (RTMP)"
    echo "   - Puerto 8888/tcp (HLS)"
    echo "   - Puerto 8889/tcp (WebRTC)"
fi

echo ""
echo "============================================"
echo "   ✅ INSTALACIÓN COMPLETADA"
echo "============================================"
echo ""
echo "📋 Próximos pasos:"
echo ""
echo "1. Verificar configuración:"
echo "   sudo nano /opt/mediamtx/mediamtx.yml"
echo ""
echo "2. Iniciar MediaMTX:"
echo "   sudo systemctl start mediamtx"
echo ""
echo "3. Ver estado:"
echo "   sudo systemctl status mediamtx"
echo ""
echo "4. Ver logs:"
echo "   sudo journalctl -u mediamtx -f"
echo ""
echo "5. Configurar OBS:"
echo "   Servidor: rtmp://streaming.cellcomweb.com.ar:1935/live"
echo "   Clave: tu_stream_key"
echo ""
echo "6. Ver stream en navegador:"
echo "   http://streaming.cellcomweb.com.ar:8888/live/tu_stream_key"
echo ""
echo "============================================"
echo ""

# Preguntar si iniciar ahora
read -p "¿Iniciar MediaMTX ahora? (s/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Ss]$ ]]; then
    echo "🚀 Iniciando MediaMTX..."
    systemctl start mediamtx
    sleep 2
    systemctl status mediamtx --no-pager
    echo ""
    echo "✅ MediaMTX está corriendo!"
    echo ""
    echo "Ver logs en tiempo real:"
    echo "sudo journalctl -u mediamtx -f"
fi

echo ""
echo "¡Instalación completa! 🎉"
