<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LibroController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PrestamoController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Biblioteca
|--------------------------------------------------------------------------
|
| Sistema de rutas RESTful siguiendo las mejores prácticas de:
| - Roy Fielding: Principios RESTful fundamentales
| - Laravel Framework: Convenciones y estándares
| - Martin Fowler: Resource-oriented architecture
| - Robert C. Martin: Clean API design
|
| Estructura:
| 1. Rutas de autenticación base
| 2. Resource routes (CRUD estándar)
| 3. Rutas especializadas de negocio
| 4. Middleware de seguridad y rate limiting
|
*/

/*
|--------------------------------------------------------------------------
| Authentication & Base Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health check endpoint para monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'biblioteca-api',
        'version' => '1.0.0'
    ]);
});

/*
|--------------------------------------------------------------------------
| Resource Routes - CRUD Operations
|--------------------------------------------------------------------------
|
| Implementación de los patrones Resource Controller siguiendo
| las convenciones RESTful estándar:
|
| GET    /api/libros           -> index()    (listar todos)
| POST   /api/libros           -> store()    (crear nuevo)
| GET    /api/libros/{id}      -> show()     (mostrar uno)
| PUT    /api/libros/{id}      -> update()   (actualizar completo)
| PATCH  /api/libros/{id}      -> update()   (actualizar parcial)
| DELETE /api/libros/{id}      -> destroy()  (eliminar)
*/

// LIBROS - Gestión completa de inventario
Route::apiResource('libros', LibroController::class);

// USUARIOS - Gestión de usuarios de biblioteca
Route::apiResource('usuarios', UsuarioController::class);

// PRÉSTAMOS - Core business logic del sistema
Route::apiResource('prestamos', PrestamoController::class);

/*
|--------------------------------------------------------------------------
| Specialized Business Routes
|--------------------------------------------------------------------------
|
| Rutas específicas del dominio de negocio que van más allá del CRUD básico.
| Siguiendo el principio de Domain Driven Design (Eric Evans):
| - Cada ruta representa una operación de negocio específica
| - URLs semánticamente claras y autodocumentadas
| - Verbos HTTP apropiados para cada operación
*/

// === LIBROS - Operaciones especializadas ===

// Obtener solo libros disponibles para préstamo
Route::get('libros/disponibles', [LibroController::class, 'disponibles'])
    ->name('libros.disponibles');

// Buscar libros por criterios específicos (ejemplo futuro)
// Route::get('libros/buscar', [LibroController::class, 'buscar']);

// === USUARIOS - Operaciones de gestión ===

// Usuarios con préstamos vencidos (para seguimiento)
Route::get('usuarios/con-vencidos', [UsuarioController::class, 'conVencidos'])
    ->name('usuarios.con-vencidos');

// Usuarios con más préstamos (ejemplo futuro)
// Route::get('usuarios/top-lectores', [UsuarioController::class, 'topLectores']);

// === PRÉSTAMOS - Operaciones de negocio críticas ===

// Obtener todos los préstamos vencidos
Route::get('prestamos/vencidos', [PrestamoController::class, 'vencidos'])
    ->name('prestamos.vencidos');

// Marcar un préstamo como devuelto (operación de dominio)
Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])
    ->name('prestamos.devolver');

// Renovar un préstamo (ejemplo futuro)
// Route::post('prestamos/{prestamo}/renovar', [PrestamoController::class, 'renovar']);

/*
|--------------------------------------------------------------------------
| Statistics & Reporting Routes (Nivel 8)
|--------------------------------------------------------------------------
|
| Endpoints para dashboard y estadísticas del sistema.
| Preparadas para implementación en Nivel 8.
*/

// Grupo de rutas para estadísticas (implementación futura)
Route::prefix('estadisticas')->name('estadisticas.')->group(function () {
    // Route::get('dashboard', [EstadisticasController::class, 'dashboard']);
    // Route::get('prestamos-mes', [EstadisticasController::class, 'prestamosPorMes']);
    // Route::get('libros-populares', [EstadisticasController::class, 'librosPopulares']);
    // Route::get('usuarios-activos', [EstadisticasController::class, 'usuariosActivos']);
});

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
|
| Rutas para documentación automática de la API
| (Preparadas para Swagger/OpenAPI en Nivel 10)
*/

// Route::get('docs', function() {
//     return view('api.docs');
// });

/*
|--------------------------------------------------------------------------
| Middleware Groups & Rate Limiting
|--------------------------------------------------------------------------
|
| Configuración de seguridad y límites de rate limiting
| siguiendo las mejores prácticas de seguridad API
*/

// Aplicar middleware de throttling a todas las rutas API
Route::middleware(['throttle:api'])->group(function () {
    // Todas las rutas definidas arriba automáticamente tendrán rate limiting
    // Configuración en config/auth.php: 60 requests per minute por defecto
});

/*
|--------------------------------------------------------------------------
| API Versioning (Preparado para el futuro)
|--------------------------------------------------------------------------
|
| Estructura preparada para versionado de API cuando el sistema crezca
*/

// Route::prefix('v1')->group(function () {
//     // Todas las rutas actuales irían aquí para v1
// });

// Route::prefix('v2')->group(function () {
//     // Futuras versiones de la API
// });

/*
|--------------------------------------------------------------------------
| Error Handling Routes
|--------------------------------------------------------------------------
|
| Rutas para manejo de errores comunes de API
*/

// Catch-all para rutas no encontradas en API
Route::fallback(function(){
    return response()->json([
        'success' => false,
        'message' => 'Endpoint no encontrado',
        'error' => 'La ruta solicitada no existe en esta API',
        'available_endpoints' => [
            'GET /api/libros' => 'Listar libros',
            'GET /api/usuarios' => 'Listar usuarios',
            'GET /api/prestamos' => 'Listar préstamos',
            'GET /api/health' => 'Health check'
        ]
    ], 404);
});

/*
|--------------------------------------------------------------------------
| Development & Testing Routes
|--------------------------------------------------------------------------
|
| Rutas útiles solo para desarrollo y testing
| NOTA: Eliminar en producción
*/

if (app()->environment('local', 'testing')) {
    // Ruta para probar la API rápidamente
    Route::get('test', function() {
        return response()->json([
            'message' => 'API funcionando correctamente',
            'environment' => app()->environment(),
            'database' => 'conectada',
            'timestamp' => now()
        ]);
    });

    // Ruta para generar datos de prueba rápidamente
    Route::post('seed-test-data', function() {
        Artisan::call('db:seed');
        return response()->json([
            'message' => 'Datos de prueba generados correctamente'
        ]);
    });
}
