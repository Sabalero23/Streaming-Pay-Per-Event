<?php
// config/payment.php
// Configuración de pagos leída desde la base de datos

require_once __DIR__ . '/database.php';

class PaymentConfig {
    private static $instance = null;
    private $config = [];
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        try {
            // Cargar todas las configuraciones de la BD
            $stmt = $this->db->query("SELECT config_key, config_value FROM system_config");
            $dbConfig = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // MercadoPago - usando configuración de BD o fallback a ENV
            $this->config['mercadopago'] = [
                'enabled' => true,
                'public_key' => $dbConfig['mp_public_key'] ?? getenv('MP_PUBLIC_KEY') ?: '',
                'access_token' => $dbConfig['mp_access_token'] ?? getenv('MP_ACCESS_TOKEN') ?: '',
                'webhook_secret' => $dbConfig['mp_webhook_secret'] ?? getenv('MP_WEBHOOK_SECRET') ?: '',
                'sandbox' => ($dbConfig['mp_sandbox'] ?? 'true') === 'true',
                'success_url' => $this->getAppUrl() . '/public/payment/success.php',
                'failure_url' => $this->getAppUrl() . '/public/payment/failure.php',
                'pending_url' => $this->getAppUrl() . '/public/payment/pending.php',
                'notification_url' => $this->getAppUrl() . '/api/webhooks/mercadopago.php',
            ];
            
            // Configuración general
            $this->config['settings'] = [
                'currency' => $dbConfig['default_currency'] ?? 'ARS',
                'tax_rate' => floatval($dbConfig['tax_rate'] ?? 21) / 100, // Convertir a decimal
                'min_amount' => 100, // En centavos
                'max_amount' => 1000000,
                'auto_refund_on_error' => false,
                'refund_window_hours' => 24,
                'store_payment_methods' => false
            ];
            
            // Comisiones
            $this->config['commissions'] = [
                'default_streamer_percentage' => floatval($dbConfig['default_commission_percentage'] ?? 70),
                'platform_percentage' => floatval($dbConfig['platform_commission'] ?? 30),
                'min_payout' => floatval($dbConfig['min_payout_amount'] ?? 1000)
            ];
            
            // Proveedor activo
            $this->config['default_provider'] = $dbConfig['payment_provider'] ?? 'mercadopago';
            
        } catch (Exception $e) {
            error_log("Error loading payment config: " . $e->getMessage());
            // Configuración por defecto si falla la BD
            $this->setDefaultConfig();
        }
    }
    
    private function setDefaultConfig() {
        $this->config = [
            'default_provider' => 'mercadopago',
            'mercadopago' => [
                'enabled' => true,
                'public_key' => '',
                'access_token' => '',
                'webhook_secret' => '',
                'sandbox' => true,
                'success_url' => $this->getAppUrl() . '/public/payment/success.php',
                'failure_url' => $this->getAppUrl() . '/public/payment/failure.php',
                'pending_url' => $this->getAppUrl() . '/public/payment/pending.php',
                'notification_url' => $this->getAppUrl() . '/api/webhooks/mercadopago.php',
            ],
            'settings' => [
                'currency' => 'ARS',
                'tax_rate' => 0.21,
                'min_amount' => 100,
                'max_amount' => 1000000,
                'auto_refund_on_error' => false,
                'refund_window_hours' => 24,
                'store_payment_methods' => false
            ],
            'commissions' => [
                'default_streamer_percentage' => 70,
                'platform_percentage' => 30,
                'min_payout' => 1000
            ]
        ];
    }
    
    private function getAppUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    public function get($key = null) {
        if ($key === null) {
            return $this->config;
        }
        
        // Soporte para notación de punto: 'mercadopago.public_key'
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function getMercadoPago() {
        return $this->config['mercadopago'];
    }
    
    public function getSettings() {
        return $this->config['settings'];
    }
    
    public function getCommissions() {
        return $this->config['commissions'];
    }
    
    public function getDefaultProvider() {
        return $this->config['default_provider'];
    }
    
    // Obtener comisión específica de un streamer
    public function getStreamerCommission($streamerId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    commission_percentage,
                    platform_percentage,
                    min_payout,
                    payment_method,
                    payment_details
                FROM streamer_commissions 
                WHERE streamer_id = ? AND is_active = 1
            ");
            $stmt->execute([$streamerId]);
            $commission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($commission) {
                return [
                    'streamer_percentage' => floatval($commission['commission_percentage']),
                    'platform_percentage' => floatval($commission['platform_percentage']),
                    'min_payout' => floatval($commission['min_payout']),
                    'payment_method' => $commission['payment_method'],
                    'payment_details' => json_decode($commission['payment_details'], true)
                ];
            }
            
            // Si no tiene comisión personalizada, usar la por defecto
            return [
                'streamer_percentage' => $this->config['commissions']['default_streamer_percentage'],
                'platform_percentage' => $this->config['commissions']['platform_percentage'],
                'min_payout' => $this->config['commissions']['min_payout'],
                'payment_method' => 'bank_transfer',
                'payment_details' => []
            ];
            
        } catch (Exception $e) {
            error_log("Error getting streamer commission: " . $e->getMessage());
            return [
                'streamer_percentage' => $this->config['commissions']['default_streamer_percentage'],
                'platform_percentage' => $this->config['commissions']['platform_percentage'],
                'min_payout' => $this->config['commissions']['min_payout'],
                'payment_method' => 'bank_transfer',
                'payment_details' => []
            ];
        }
    }
    
    // Calcular distribución de ganancias
    public function calculateEarnings($amount, $streamerId) {
        $commission = $this->getStreamerCommission($streamerId);
        
        $streamerEarnings = $amount * ($commission['streamer_percentage'] / 100);
        $platformEarnings = $amount * ($commission['platform_percentage'] / 100);
        
        return [
            'total' => $amount,
            'streamer_earnings' => round($streamerEarnings, 2),
            'platform_earnings' => round($platformEarnings, 2),
            'streamer_percentage' => $commission['streamer_percentage'],
            'platform_percentage' => $commission['platform_percentage']
        ];
    }
    
    // Validar si las credenciales de MercadoPago están configuradas
    public function isMercadoPagoConfigured() {
        $mp = $this->config['mercadopago'];
        return !empty($mp['public_key']) && !empty($mp['access_token']);
    }
    
    // Obtener mensaje de estado de configuración
    public function getConfigurationStatus() {
        $status = [
            'configured' => false,
            'provider' => $this->config['default_provider'],
            'message' => '',
            'warnings' => []
        ];
        
        if ($this->config['default_provider'] === 'mercadopago') {
            if ($this->isMercadoPagoConfigured()) {
                $status['configured'] = true;
                $status['message'] = 'MercadoPago configurado correctamente';
                
                if ($this->config['mercadopago']['sandbox']) {
                    $status['warnings'][] = 'Modo sandbox activado - usar solo para pruebas';
                }
            } else {
                $status['message'] = 'MercadoPago no está configurado. Configura las credenciales en Settings.';
            }
        }
        
        return $status;
    }
    
    // Refrescar configuración (útil después de cambios en BD)
    public function refresh() {
        $this->loadConfig();
    }
}

// Función helper para acceso rápido
function payment_config($key = null) {
    return PaymentConfig::getInstance()->get($key);
}

// Retornar instancia para compatibilidad con require
return PaymentConfig::getInstance()->get();