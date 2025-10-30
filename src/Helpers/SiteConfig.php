<?php
// src/Helpers/SiteConfig.php
// Helper para obtener configuraciones del sitio de forma fácil

class SiteConfig {
    private static $config = null;
    
    /**
     * Cargar todas las configuraciones desde la base de datos
     */
    private static function load() {
        if (self::$config !== null) {
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT config_key, config_value FROM system_config");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            self::$config = [];
            foreach ($results as $row) {
                self::$config[$row['config_key']] = $row['config_value'];
            }
        } catch (Exception $e) {
            self::$config = [];
        }
    }
    
    /**
     * Obtener una configuración
     */
    public static function get($key, $default = '') {
        self::load();
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Obtener nombre del sitio
     */
    public static function siteName() {
        return self::get('site_name', 'Eventix');
    }
    
    /**
     * Obtener dominio del sitio
     */
    public static function siteDomain() {
        return self::get('site_domain', 'www.eventix.com.ar');
    }
    
    /**
     * Obtener eslogan del sitio
     */
    public static function siteTagline() {
        return self::get('site_tagline', 'Vive la Emoción del Deporte en Vivo');
    }
    
    /**
     * Obtener logo SVG
     */
    public static function siteLogo() {
        return self::get('site_logo_svg', '');
    }
    
    /**
     * Obtener email de contacto
     */
    public static function contactEmail() {
        return self::get('contact_email', 'info@eventix.com.ar');
    }
    
    /**
     * Obtener teléfono de contacto
     */
    public static function contactPhone() {
        return self::get('contact_phone', '');
    }
    
    /**
     * Obtener WhatsApp
     */
    public static function contactWhatsApp() {
        return self::get('contact_whatsapp', '');
    }
    
    /**
     * Obtener dirección
     */
    public static function contactAddress() {
        return self::get('contact_address', '');
    }
    
    /**
     * Obtener todas las redes sociales
     */
    public static function socialLinks() {
        return [
            'facebook' => self::get('social_facebook', ''),
            'instagram' => self::get('social_instagram', ''),
            'twitter' => self::get('social_twitter', ''),
            'youtube' => self::get('social_youtube', ''),
            'tiktok' => self::get('social_tiktok', '')
        ];
    }
    
    /**
     * Limpiar caché de configuraciones
     */
    public static function clearCache() {
        self::$config = null;
    }
}