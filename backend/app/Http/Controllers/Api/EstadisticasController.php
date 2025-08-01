<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Usuario;
use App\Models\Prestamo;
use Illuminate\Http\JsonResponse;

/**
 * EstadisticasController
 *
 * Aplicando Robert C. Martin's Clean Code principles:
 * - Single Responsibility: Solo maneja estadísticas del sistema
 * - Readable code: Métodos autoexplicativos
 * - No side effects: Solo consulta, no modifica datos
 *
 * Martin Fowler's API design:
 * - RESTful endpoint design
 * - Consistent JSON response structure
 * - Efficient database queries
 */
class EstadisticasController extends Controller
{
    /**
     * Dashboard general statistics
     *
     * Endpoint: GET /api/estadisticas/dashboard
     *
     * Siguiendo Ian Sommerville's systematic approach:
     * Métricas fundamentales que todo sistema biblioteca necesita
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Aplicando Martin Kleppmann's efficient data querying
            // Una sola query por métrica para optimizar performance
            $estadisticas = [
                'total_libros' => $this->getTotalLibros(),
                'libros_disponibles' => $this->getLibrosDisponibles(),
                'total_usuarios' => $this->getTotalUsuarios(),
                'prestamos_activos' => $this->getPrestamosActivos(),
                'prestamos_vencidos' => $this->getPrestamosVencidos()
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas del dashboard obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            // Error handling siguiendo las mejores prácticas
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del dashboard',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene el total de libros en el sistema
     *
     * Clean Code: Método autoexplicativo con responsabilidad única
     */
    private function getTotalLibros(): int
    {
        return Libro::count();
    }

    /**
     * Obtiene el total de libros disponibles
     *
     * Business Logic: Un libro está disponible si no tiene préstamos activos
     */
    private function getLibrosDisponibles(): int
    {
        return Libro::whereDoesntHave('prestamos', function ($query) {
            $query->where('estado', 'activo');
        })->count();
    }

    /**
     * Obtiene el total de usuarios registrados
     */
    private function getTotalUsuarios(): int
    {
        return Usuario::count();
    }

    /**
     * Obtiene el total de préstamos activos
     *
     * Business Logic: Préstamos que no han sido devueltos
     */
    private function getPrestamosActivos(): int
    {
        return Prestamo::where('estado', 'activo')->count();
    }

    /**
     * Obtiene el total de préstamos vencidos
     *
     * Business Logic: Préstamos activos que pasaron su fecha de devolución
     */
    private function getPrestamosVencidos(): int
    {
        return Prestamo::where('estado', 'activo')
            ->where('fecha_devolucion', '<', now())
            ->count();
    }
}
