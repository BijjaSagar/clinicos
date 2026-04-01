<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'token' => env('WHATSAPP_TOKEN', ''),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay Payment Gateway
    |--------------------------------------------------------------------------
    */

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', ''),
        'secret' => env('RAZORPAY_KEY_SECRET', ''),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
        /** Optional: clinic SaaS billing via Razorpay Subscriptions (store plan id in clinics.settings too if needed) */
        'subscription_plan_id' => env('RAZORPAY_SUBSCRIPTION_PLAN_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | ABDM / Ayushman Bharat Digital Mission
    |--------------------------------------------------------------------------
    */

    'abdm' => [
        'client_id' => env('ABDM_CLIENT_ID', ''),
        'client_secret' => env('ABDM_CLIENT_SECRET', ''),
        'base_url' => env('ABDM_BASE_URL', 'https://healthidsbx.abdm.gov.in'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Services
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lab Integrations
    |--------------------------------------------------------------------------
    */
    'labs' => [
        'lal_pathlabs' => [
            'api_base' => env('LAB_LAL_API_BASE', 'https://api.lalpathlabs.com/v1'),
            'api_key' => env('LAB_LAL_API_KEY', ''),
        ],
        'srl' => [
            'api_base' => env('LAB_SRL_API_BASE', 'https://api.srl.in/v1'),
            'api_key' => env('LAB_SRL_API_KEY', ''),
        ],
        'thyrocare' => [
            'api_base' => env('LAB_THYROCARE_API_BASE', 'https://api.thyrocare.com/v3'),
            'api_key' => env('LAB_THYROCARE_API_KEY', ''),
        ],
        'metropolis' => [
            'api_base' => env('LAB_METROPOLIS_API_BASE', 'https://api.metropolisindia.com/v1'),
            'api_key' => env('LAB_METROPOLIS_API_KEY', ''),
        ],
        'pathkind' => [
            'api_base' => env('LAB_PATHKIND_API_BASE', 'https://api.pathkindlabs.com/v1'),
            'api_key' => env('LAB_PATHKIND_API_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo vault — optional at-rest encryption (local disk + APP_KEY)
    |--------------------------------------------------------------------------
    */
    'photo_vault' => [
        'encrypt_uploads' => env('PHOTO_VAULT_ENCRYPT_UPLOADS', false),
    ],

];
