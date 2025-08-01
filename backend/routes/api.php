<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LibroController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PrestamoController;
use App\Http\Controllers\Api\EstadisticasController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Biblioteca
|--------------------------------------------------------------------------
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
*/

// === LIBROS - Operaciones especializadas ===
Route::get('libros/disponibles', [LibroController::class, 'disponibles'])
    ->name('libros.disponibles');

// === USUARIOS - Operaciones de gestión ===
Route::get('usuarios/con-vencidos', [UsuarioController::class, 'conVencidos'])
    ->name('usuarios.con-vencidos');

// === PRÉSTAMOS - Operaciones de negocio críticas ===
Route::get('prestamos/vencidos', [PrestamoController::class, 'vencidos'])
    ->name('prestamos.vencidos');

Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])
    ->name('prestamos.devolver');

/*
|--------------------------------------------------------------------------
| Statistics & Reporting Routes
|--------------------------------------------------------------------------
*/

Route::prefix('estadisticas')->name('estadisticas.')->group(function () {
    Route::get('dashboard', [EstadisticasController::class, 'dashboard']);
    // Route::get('prestamos-mes', [EstadisticasController::class, 'prestamosPorMes']);
    // Route::get('libros-populares', [EstadisticasController::class, 'librosPopulares']);
    // Route::get('usuarios-activos', [EstadisticasController::class, 'usuariosActivos']);
});

// ... resto del archivo igual ...
