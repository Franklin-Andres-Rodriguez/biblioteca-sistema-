<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración optimizada para desarrollo con frontend React separado.
    | Siguiendo las mejores prácticas de seguridad de OWASP mientras
    | permitimos el desarrollo local fluido.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Métodos HTTP permitidos para requests cross-origin.
    |
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Origins permitidos explícitamente.
    | Se recomienda especificar en producción.
    |
    */
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Evitado para evitar conflictos con 'allowed_origins'.
    | Si usas patrones, Laravel ignorará 'allowed_origins'.
    |
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Headers permitidos. Se permite todo para flexibilidad en desarrollo.
    |
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers accesibles desde el frontend (ej. paginación, rate limit).
    |
    */
    'exposed_headers' => [
        'X-Total-Count',
        'X-Per-Page',
        'X-Current-Page',
        'X-Last-Page',
        'X-Rate-Limit-Limit',
        'X-Rate-Limit-Remaining',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Cacheo de respuesta preflight en segundos (1 hora).
    |
    */
    'max_age' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Requerido si tu frontend incluye cookies o auth headers.
    |
    */
    'supports_credentials' => true,

];
