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
    | Referencias:
    | - OWASP CORS Security Cheat Sheet
    | - Laravel CORS Package Documentation
    | - Mozilla Developer Network CORS Guide
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Métodos HTTP permitidos para requests cross-origin.
    | Incluimos todos los métodos REST necesarios para nuestra API.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Origins permitidos para desarrollo local.
    | En producción, especificar dominios exactos por seguridad.
    |
    | Desarrollo: React típicamente corre en puerto 3000
    | Producción: Cambiar a dominios específicos como ['https://miapp.com']
    |
    */

    'allowed_origins' => [
        'http://localhost:3000',    // React development server
        'http://127.0.0.1:3000',   // Alternative localhost
        'http://localhost:3001',   // Backup port
        'http://127.0.0.1:3001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Patrones regex para origins dinámicos.
    | Útil para subdominios o puertos variables en desarrollo.
    |
    */

    'allowed_origins_patterns' => [
        // Permitir puertos 3000-3010 en localhost para desarrollo
        '/^http:\/\/(localhost|127\.0\.0\.1):(300[0-9]|301[0])$/'
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Headers permitidos en requests cross-origin.
    | Incluimos headers estándar para APIs REST con autenticación.
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers que el browser puede acceder desde JavaScript.
    | Útil para pagination links, rate limiting info, etc.
    |
    */

    'exposed_headers' => [
        'X-Total-Count',        // Total de registros (paginación)
        'X-Per-Page',          // Registros por página
        'X-Current-Page',      // Página actual
        'X-Last-Page',         // Última página
        'X-Rate-Limit-Limit',  // Límite de rate limiting
        'X-Rate-Limit-Remaining' // Requests restantes
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Tiempo en segundos que el browser puede cachear la respuesta preflight.
    | 1 hora = 3600 segundos (balance entre performance y flexibilidad)
    |
    */

    'max_age' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Permitir cookies y headers de autenticación en requests cross-origin.
    | Necesario para autenticación con sessions o tokens.
    |
    */

    'supports_credentials' => true,

];
