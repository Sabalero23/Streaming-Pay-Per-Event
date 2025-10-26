<?php
// config/payment.php

return [
    // Proveedor activo (mercadopago, stripe, paypal)
    'default_provider' => getenv('PAYMENT_PROVIDER') ?: 'mercadopago',
    
    // MercadoPago (ideal para Argentina/LATAM)
    'mercadopago' => [
        'enabled' => true,
        'public_key' => getenv('MP_PUBLIC_KEY') ?: '',
        'access_token' => getenv('MP_ACCESS_TOKEN') ?: '',
        'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
        'success_url' => getenv('APP_URL') . '/payment/success',
        'failure_url' => getenv('APP_URL') . '/payment/failure',
        'pending_url' => getenv('APP_URL') . '/payment/pending',
        'notification_url' => getenv('APP_URL') . '/api/webhooks/mercadopago',
        'sandbox' => getenv('MP_SANDBOX') === 'true',
        // Credenciales de sandbox para testing
        'sandbox_public_key' => 'TEST-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'sandbox_access_token' => 'TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxx-xxxxxxxxxxxx',
    ],
    
    // Stripe (internacional)
    'stripe' => [
        'enabled' => false,
        'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
        'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
        'success_url' => getenv('APP_URL') . '/payment/success',
        'cancel_url' => getenv('APP_URL') . '/payment/cancel',
        'webhook_url' => getenv('APP_URL') . '/api/webhooks/stripe',
        'currency' => 'usd',
        'sandbox' => getenv('STRIPE_SANDBOX') === 'true'
    ],
    
    // PayPal
    'paypal' => [
        'enabled' => false,
        'client_id' => getenv('PAYPAL_CLIENT_ID') ?: '',
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: '',
        'webhook_id' => getenv('PAYPAL_WEBHOOK_ID') ?: '',
        'mode' => getenv('PAYPAL_MODE') ?: 'sandbox', // sandbox o live
        'success_url' => getenv('APP_URL') . '/payment/success',
        'cancel_url' => getenv('APP_URL') . '/payment/cancel',
        'webhook_url' => getenv('APP_URL') . '/api/webhooks/paypal'
    ],
    
    // Configuración general
    'settings' => [
        'currency' => getenv('DEFAULT_CURRENCY') ?: 'ARS', // ARS, USD, EUR, etc.
        'tax_rate' => 0.21, // IVA 21% para Argentina
        'min_amount' => 100, // Monto mínimo en centavos/centésimos
        'max_amount' => 1000000, // Monto máximo
        'auto_refund_on_error' => false,
        'refund_window_hours' => 24, // Ventana para reembolsos
        'store_payment_methods' => false // Guardar métodos de pago para futuros usos
    ],
    
    // Descuentos y promociones
    'promotions' => [
        'enabled' => true,
        'coupon_enabled' => true,
        'bulk_discount' => [
            'enabled' => false,
            'min_quantity' => 5,
            'discount_percentage' => 10
        ]
    ],
    
    // Facturación (AFIP para Argentina)
    'invoicing' => [
        'enabled' => false,
        'afip' => [
            'cuit' => getenv('AFIP_CUIT') ?: '',
            'cert_path' => '/path/to/cert.pem',
            'key_path' => '/path/to/key.pem',
            'production' => false
        ]
    ]
];
