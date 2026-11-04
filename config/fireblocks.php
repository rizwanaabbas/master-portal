<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fireblocks API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Fireblocks PHP SDK.
    | You can obtain your API key and secret from the Fireblocks Console.
    |
    */

    // API Base URL
    'base_url' => env('FIREBLOCKS_BASE_URL', 'https://api.fireblocks.io'),

    // API Key from Fireblocks Console
    'api_key' => env('FIREBLOCKS_API_KEY', ''),

    // API Secret (private key) for JWT signing
    'api_secret' => env('FIREBLOCKS_API_SECRET', ''),

    // Path to API secret file (alternative to api_secret)
    'api_secret_path' => env('FIREBLOCKS_API_SECRET_PATH', ''),

    // Timeout for API requests (seconds)
    'timeout' => env('FIREBLOCKS_TIMEOUT', 30),

    // Connection timeout (seconds)
    'connect_timeout' => env('FIREBLOCKS_CONNECT_TIMEOUT', 10),

    // Retry attempts for failed requests
    'max_retries' => env('FIREBLOCKS_MAX_RETRIES', 3),

    // Retry delay in milliseconds
    'retry_delay' => env('FIREBLOCKS_RETRY_DELAY', 500),

    // Enable debug logging
    'debug' => env('FIREBLOCKS_DEBUG', false),

    // Proxy configuration
    'proxy' => env('FIREBLOCKS_PROXY', null),

    // Custom headers
    'headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the public key used to validate Fireblocks webhook signatures.
    | Supported types: production, sandbox, eu
    |
    */

    'webhook_verify_signature' => env('FIREBLOCKS_WEBHOOK_VERIFY_SIGNATURE', true),

    // Fireblocks environments are production, sandbox, eu, and eu2.
    // APP_ENV=staging does not identify a Fireblocks workspace, so set this
    // explicitly when staging receives production workspace webhooks.
    'webhook_public_key_type' => env(
        'FIREBLOCKS_WEBHOOK_PUBLIC_KEY_TYPE',
        env('APP_ENV') === 'production' ? 'production' : 'sandbox'
    ),

    // Optional: override with a custom public key PEM (useful for debugging or V2 JWKS keys)
    'webhook_custom_public_key' => env('FIREBLOCKS_WEBHOOK_CUSTOM_PUBLIC_KEY', ''),

    // Optional: JWKS endpoint for dynamic key fetching (V2 webhooks)
    // Note: Fireblocks JWKS endpoints typically require API authentication
    'webhook_jwks_url' => env('FIREBLOCKS_WEBHOOK_JWKS_URL', ''),

    'webhook_public_keys' => [
        'production' => "-----BEGIN PUBLIC KEY-----\n" .
            "MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA0+6wd9OJQpK60ZI7qnZG\n" .
            "jjQ0wNFUHfRv85Tdyek8+ahlg1Ph8uhwl4N6DZw5LwLXhNjzAbQ8LGPxt36RUZl5\n" .
            "YlxTru0jZNKx5lslR+H4i936A4pKBjgiMmSkVwXD9HcfKHTp70GQ812+J0Fvti/v\n" .
            "4nrrUpc011Wo4F6omt1QcYsi4GTI5OsEbeKQ24BtUd6Z1Nm/EP7PfPxeb4CP8KOH\n" .
            "clM8K7OwBUfWrip8Ptljjz9BNOZUF94iyjJ/BIzGJjyCntho64ehpUYP8UJykLVd\n" .
            "CGcu7sVYWnknf1ZGLuqqZQt4qt7cUUhFGielssZP9N9x7wzaAIFcT3yQ+ELDu1SZ\n" .
            "dE4lZsf2uMyfj58V8GDOLLE233+LRsRbJ083x+e2mW5BdAGtGgQBusFfnmv5Bxqd\n" .
            "HgS55hsna5725/44tvxll261TgQvjGrTxwe7e5Ia3d2Syc+e89mXQaI/+cZnylNP\n" .
            "SwCCvx8mOM847T0XkVRX3ZrwXtHIA25uKsPJzUtksDnAowB91j7RJkjXxJcz3Vh1\n" .
            "4k182UFOTPRW9jzdWNSyWQGl/vpe9oQ4c2Ly15+/toBo4YXJeDdDnZ5c/O+KKadc\n" .
            "IMPBpnPrH/0O97uMPuED+nI6ISGOTMLZo35xJ96gPBwyG5s2QxIkKPXIrhgcgUnk\n" .
            "tSM7QYNhlftT4/yVvYnk0YcCAwEAAQ==\n" .
            "-----END PUBLIC KEY-----",

        // Embedded Wallet / NCW sandbox key (used for webhook signature validation in sandbox)
        // Source: https://developers.fireblocks.com/docs/embedded-wallet-webhook-configuration
        'sandbox' => "-----BEGIN PUBLIC KEY-----\n" .
            "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw+fZuC+0vDYTf8fYnCN6\n" .
            "71iHg98lPHBmafmqZqb+TUexn9sH6qNIBZ5SgYFxFK6dYXIuJ5uoORzihREvZVZP\n" .
            "8DphdeKOMUrMr6b+Cchb2qS8qz8WS7xtyLU9GnBn6M5mWfjkjQr1jbilH15Zvcpz\n" .
            "ECC8aPUAy2EbHpnr10if2IHkIAWLYD+0khpCjpWtsfuX+LxqzlqQVW9xc6z7tshK\n" .
            "eCSEa6Oh8+ia7Zlu0b+2xmy2Arb6xGl+s+Rnof4lsq9tZS6f03huc+XVTmd6H2We\n" .
            "WxFMfGyDCX2akEg2aAvx7231/6S0vBFGiX0C+3GbXlieHDplLGoODHUt5hxbPJnK\n" .
            "IwIDAQAB\n" .
            "-----END PUBLIC KEY-----",

        'eu' => "-----BEGIN PUBLIC KEY-----\n" .
            "MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA6hLRQL0jPf5OEuaDYGjO\n" .
            "xSyaYIlv08S0+4giiwgKSfV3Onc5hn03mvE0znzaUq2ReSxi9KYDdMYFfzf1uwF7\n" .
            "7kYy2MY0oTYGdQb+PS4Ym4R4tgZ2otuoAXt8YRKq2maWyguFiaowMcYwwAVQv8JB\n" .
            "afIm6Jq1nI6v1mEDVX065ePlBlAt+BGAqr6ahPxnaIz3L4eztpuNrt5nTbSxs7eF\n" .
            "aqQx1p56W1nl3Hl0V3tLkaXbuVtbFNR/mGMInrkPnpsG+mt35b9vmqAOvLPI0Cx1\n" .
            "59uVeEs62Hj1AOCRyT6SuwIaFynRj2KnD42ioQtkodHQ0xDtgdiYGsxuwQ9vTIe7\n" .
            "5oLsL8gBDeX5gdcTfSZhfGjZ7RggLNJ7vCAbYKMuUOdgWVMYnJfrhNLCq3zDSZPO\n" .
            "+H0x5m/Yeq/Hn5o7xCmLNT3qARfwDd5IHfQyXqVYB6TMU75xqH5fdSRw0iMdoPyL\n" .
            "ALnr9/JT0av3qssNMRdWCXr+j9Ys3NkfcbU/a49657mg8e2QGSkl9w39csEKojnr\n" .
            "omUz25szIL8CcXLmc5cAmnimFCe4L7UT4mvVP3+fOo+cbc/82zqA8tsSwd2Y93/6\n" .
            "ueGnNZD9V5rewrKjmdPfrwoI2gntzc8QJUu+nxAWhoqHV91AQeglu6WIF/DiEJC5\n" .
            "WPoNk2SdlAuA6RYmgB2YyikCAwEAAQ==\n" .
            "-----END PUBLIC KEY-----",
    ],
];
