<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Libro;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PrestamoController - API REST para gestión de préstamos
 *
 * Reglas de Negocio Implementadas:
 * 1. Un usuario no puede tener más de 3 préstamos activos
 * 2. Un libro debe estar disponible para ser prestado
 * 3. Los préstamos tienen 14 días por defecto
 * 4. No se pueden crear préstamos con fechas pasadas
 * 5. Solo se pueden devolver préstamos activos
 *
 * @package App\Http\Controllers\Api
 * @author Sistema Biblioteca - Business Logic Core
 * @version 1.0.0
 */
class PrestamoController extends Controller
{
    // Constantes de negocio (Configuration over Convention)
    const MAX_PRESTAMOS_POR_USUARIO = 3;
    const DIAS_PRESTAMO_DEFAULT = 14;
    const DIAS_GRACIA_VENCIMIENTO = 3;

    /**
     * Display a listing of the resource.
     *
     * GET /api/prestamos
     *
     * Query Complex con múltiples joins y filtros de negocio
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. QUERY BASE CON EAGER LOADING OPTIMIZADO
            $query = Prestamo::with([
                'usuario:id,nombre,email', // Solo campos necesarios
                'libro:id,titulo,autor,genero'
            ]);

            // 2. FILTROS DE ESTADO DE NEGOCIO

            // Filtro por estado de devolución
            if ($request->has('devuelto')) {
                $devuelto = filter_var($request->devuelto, FILTER_VALIDATE_BOOLEAN);
                $query->where('devuelto', $devuelto);
            }

            // Filtro por préstamos vencidos (Business Logic)
            if ($request->has('vencidos')) {
                $vencidos = filter_var($request->vencidos, FILTER_VALIDATE_BOOLEAN);
                if ($vencidos) {
                    $query->where('devuelto', false)
                          ->where('fecha_devolucion', '<', now());
                } else {
                    $query->where(function($q) {
                        $q->where('devuelto', true)
                          ->orWhere('fecha_devolucion', '>=', now());
                    });
                }
            }

            // Filtro por usuario específico
            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            // Filtro por libro específico
            if ($request->filled('libro_id')) {
                $query->where('libro_id', $request->libro_id);
            }

            // Filtro por rango de fechas (ISO 8601)
            if ($request->filled('fecha_desde')) {
                $fechaDesde = Carbon::parse($request->fecha_desde)->startOfDay();
                $query->where('created_at', '>=', $fechaDesde);
            }

            if ($request->filled('fecha_hasta')) {
                $fechaHasta = Carbon::parse($request->fecha_hasta)->endOfDay();
                $query->where('created_at', '<=', $fechaHasta);
            }

            // 3. ORDENAMIENTO INTELIGENTE CON BUSINESS LOGIC
            $sortBy = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            // Campos permitidos (Security by Design)
            $allowedSorts = [
                'created_at', 'fecha_prestamo', 'fecha_devolucion',
                'devuelto', 'usuario_id', 'libro_id'
            ];

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // 4. PAGINACIÓN CON LÍMITES DE PERFORMANCE
            $perPage = min($request->get('per_page', 25), 100);
            $prestamos = $query->paginate($perPage);

            // 5. TRANSFORMACIÓN CON MÉTRICAS DE NEGOCIO
            $prestamosEnriquecidos = $prestamos->getCollection()->map(function($prestamo) {
                $ahora = now();
                $diasVencido = 0;
                $estadoNegocio = 'activo';

                if ($prestamo->devuelto) {
                    $estadoNegocio = 'completado';
                } elseif ($prestamo->fecha_devolucion < $ahora) {
                    $diasVencido = $ahora->diffInDays($prestamo->fecha_devolucion);
                    $estadoNegocio = $diasVencido > self::DIAS_GRACIA_VENCIMIENTO ? 'vencido_critico' : 'vencido';
                } elseif ($prestamo->fecha_devolucion->diffInDays($ahora) <= 2) {
                    $estadoNegocio = 'por_vencer';
                }

                return [
                    'id' => $prestamo->id,
                    'usuario' => [
                        'id' => $prestamo->usuario->id,
                        'nombre' => $prestamo->usuario->nombre,
                        'email' => $prestamo->usuario->email
                    ],
                    'libro' => [
                        'id' => $prestamo->libro->id,
                        'titulo' => $prestamo->libro->titulo,
                        'autor' => $prestamo->libro->autor,
                        'genero' => $prestamo->libro->genero
                    ],
                    'fecha_prestamo' => $prestamo->fecha_prestamo,
                    'fecha_devolucion' => $prestamo->fecha_devolucion,
                    'devuelto' => $prestamo->devuelto,
                    'created_at' => $prestamo->created_at,

                    // Métricas de negocio calculadas
                    'estado_negocio' => $estadoNegocio,
                    'dias_vencido' => $diasVencido,
                    'dias_restantes' => !$prestamo->devuelto ?
                        $ahora->diffInDays($prestamo->fecha_devolucion, false) : null,
                    'duracion_prestamo' => $prestamo->fecha_prestamo->diffInDays($prestamo->fecha_devolucion),
                ];
            });

            // 6. MÉTRICAS AGREGADAS DEL DOMINIO
            $metricas = [
                'total_prestamos' => $prestamos->total(),
                'prestamos_activos' => $prestamos->getCollection()->where('devuelto', false)->count(),
                'prestamos_vencidos' => $prestamos->getCollection()->where('estado_negocio', 'vencido')->count(),
                'prestamos_criticos' => $prestamos->getCollection()->where('estado_negocio', 'vencido_critico')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Préstamos obtenidos correctamente',
                'data' => $prestamosEnriquecidos,
                'meta' => array_merge($prestamos->toArray(), ['metricas_negocio' => $metricas])
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener préstamos',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * POST /api/prestamos
     *
     * CORE BUSINESS LOGIC - Validaciones complejas de préstamos
     * Implementa todas las reglas de negocio del dominio
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 1. VALIDACIÓN BÁSICA DE ENTRADA
            $validatedData = $request->validate([
                'usuario_id' => 'required|integer|exists:usuarios,id',
                'libro_id' => 'required|integer|exists:libros,id',
                'fecha_prestamo' => 'nullable|date|after_or_equal:today',
                'dias_prestamo' => 'nullable|integer|min:1|max:30'
            ], [
                'usuario_id.required' => 'El usuario es obligatorio',
                'usuario_id.exists' => 'El usuario no existe',
                'libro_id.required' => 'El libro es obligatorio',
                'libro_id.exists' => 'El libro no existe',
                'fecha_prestamo.after_or_equal' => 'No se pueden crear préstamos con fechas pasadas'
            ]);

            // 2. TRANSACCIÓN ATÓMICA PARA BUSINESS RULES
            $prestamo = DB::transaction(function() use ($validatedData) {

                // BUSINESS RULE 1: Verificar disponibilidad del libro
                $libro = Libro::lockForUpdate()->findOrFail($validatedData['libro_id']);

                if (!$libro->disponible) {
                    throw new \Exception('El libro no está disponible para préstamo');
                }

                // BUSINESS RULE 2: Verificar límite de préstamos por usuario
                $usuario = Usuario::findOrFail($validatedData['usuario_id']);
                $prestamosActivos = $usuario->prestamos()
                    ->where('devuelto', false)
                    ->count();

                if ($prestamosActivos >= self::MAX_PRESTAMOS_POR_USUARIO) {
                    throw new \Exception(
                        "El usuario ya tiene {$prestamosActivos} préstamos activos. Límite máximo: " .
                        self::MAX_PRESTAMOS_POR_USUARIO
                    );
                }

                // BUSINESS RULE 3: Verificar que el usuario no tenga préstamos vencidos
                $prestamosVencidos = $usuario->prestamos()
                    ->where('devuelto', false)
                    ->where('fecha_devolucion', '<', now())
                    ->count();

                if ($prestamosVencidos > 0) {
                    throw new \Exception(
                        'El usuario tiene préstamos vencidos. Debe devolverlos antes de solicitar nuevos préstamos'
                    );
                }

                // 3. CÁLCULO DE FECHAS (Business Logic)
                $fechaPrestamo = $validatedData['fecha_prestamo'] ?
                    Carbon::parse($validatedData['fecha_prestamo']) :
                    now();

                $diasPrestamo = $validatedData['dias_prestamo'] ?? self::DIAS_PRESTAMO_DEFAULT;
                $fechaDevolucion = $fechaPrestamo->copy()->addDays($diasPrestamo);

                // 4. CREACIÓN DEL PRÉSTAMO
                $nuevoPrestamo = Prestamo::create([
                    'usuario_id' => $validatedData['usuario_id'],
                    'libro_id' => $validatedData['libro_id'],
                    'fecha_prestamo' => $fechaPrestamo,
                    'fecha_devolucion' => $fechaDevolucion,
                    'devuelto' => false
                ]);

                // 5. ACTUALIZAR ESTADO DEL LIBRO (Side Effect)
                $libro->update(['disponible' => false]);

                return $nuevoPrestamo;
            });

            // 6. CARGAR RELACIONES PARA RESPUESTA COMPLETA
            $prestamo->load(['usuario:id,nombre,email', 'libro:id,titulo,autor']);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo creado exitosamente',
                'data' => $prestamo,
                'business_info' => [
                    'fecha_devolucion_calculada' => $prestamo->fecha_devolucion,
                    'dias_prestamo' => $prestamo->fecha_prestamo->diffInDays($prestamo->fecha_devolucion),
                    'usuario_prestamos_activos' => $prestamo->usuario->prestamos()->where('devuelto', false)->count()
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear préstamo',
                $e->getMessage(),
                400
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * GET /api/prestamos/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $prestamo = Prestamo::with([
                'usuario:id,nombre,email,telefono',
                'libro:id,titulo,autor,genero,isbn'
            ])->findOrFail($id);

            // Enriquecimiento con métricas de negocio
            $ahora = now();
            $prestamoDetallado = [
                'id' => $prestamo->id,
                'usuario' => $prestamo->usuario,
                'libro' => $prestamo->libro,
                'fecha_prestamo' => $prestamo->fecha_prestamo,
                'fecha_devolucion' => $prestamo->fecha_devolucion,
                'devuelto' => $prestamo->devuelto,
                'created_at' => $prestamo->created_at,
                'updated_at' => $prestamo->updated_at,

                'metricas_negocio' => [
                    'dias_transcurridos' => $prestamo->fecha_prestamo->diffInDays($ahora),
                    'dias_restantes' => !$prestamo->devuelto ?
                        $ahora->diffInDays($prestamo->fecha_devolucion, false) : null,
                    'esta_vencido' => !$prestamo->devuelto && $prestamo->fecha_devolucion < $ahora,
                    'dias_vencido' => !$prestamo->devuelto && $prestamo->fecha_devolucion < $ahora ?
                        $ahora->diffInDays($prestamo->fecha_devolucion) : 0,
                    'duracion_total' => $prestamo->fecha_prestamo->diffInDays($prestamo->fecha_devolucion)
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Préstamo encontrado',
                'data' => $prestamoDetallado
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Préstamo no encontrado'
            ], 404);
        }
    }

    /**
     * Marcar un préstamo como devuelto.
     *
     * POST /api/prestamos/{id}/devolver
     *
     * Business Operation - No es un UPDATE tradicional, es una operación de dominio
     *
     * @param string $id
     * @return JsonResponse
     */
    public function devolver(string $id): JsonResponse
    {
        try {
            $prestamo = DB::transaction(function() use ($id) {
                // 1. BUSCAR PRÉSTAMO CON LOCK
                $prestamo = Prestamo::with(['libro', 'usuario'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                // 2. BUSINESS RULE: Solo se pueden devolver préstamos activos
                if ($prestamo->devuelto) {
                    throw new \Exception('Este préstamo ya fue devuelto anteriormente');
                }

                // 3. MARCAR COMO DEVUELTO (Domain Operation)
                $prestamo->update(['devuelto' => true]);

                // 4. LIBERAR EL LIBRO (Side Effect)
                $prestamo->libro->update(['disponible' => true]);

                return $prestamo;
            });

            // Métricas de la operación
            $ahora = now();
            $diasRetraso = $prestamo->fecha_devolucion < $ahora ?
                $ahora->diffInDays($prestamo->fecha_devolucion) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Libro devuelto exitosamente',
                'data' => $prestamo,
                'operacion_info' => [
                    'fecha_devolucion_real' => $ahora,
                    'fecha_devolucion_esperada' => $prestamo->fecha_devolucion,
                    'devuelto_a_tiempo' => $diasRetraso === 0,
                    'dias_retraso' => $diasRetraso,
                    'libro_disponible' => true
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Préstamo no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al devolver libro',
                $e->getMessage(),
                400
            );
        }
    }

    /**
     * Get all overdue loans.
     *
     * GET /api/prestamos/vencidos
     *
     * @return JsonResponse
     */
    public function vencidos(): JsonResponse
    {
        try {
            $prestamosVencidos = Prestamo::with([
                'usuario:id,nombre,email,telefono',
                'libro:id,titulo,autor'
            ])
            ->where('devuelto', false)
            ->where('fecha_devolucion', '<', now())
            ->orderBy('fecha_devolucion', 'asc') // Más antiguos primero
            ->get();

            // Enriquecer con métricas de urgencia
            $prestamosConUrgencia = $prestamosVencidos->map(function($prestamo) {
                $diasVencido = now()->diffInDays($prestamo->fecha_devolucion);
                $nivelUrgencia = 'baja';

                if ($diasVencido > 14) {
                    $nivelUrgencia = 'critica';
                } elseif ($diasVencido > 7) {
                    $nivelUrgencia = 'alta';
                } elseif ($diasVencido > 3) {
                    $nivelUrgencia = 'media';
                }

                return array_merge($prestamo->toArray(), [
                    'dias_vencido' => $diasVencido,
                    'nivel_urgencia' => $nivelUrgencia
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Préstamos vencidos obtenidos',
                'data' => $prestamosConUrgencia,
                'resumen' => [
                    'total_vencidos' => $prestamosVencidos->count(),
                    'urgencia_critica' => $prestamosConUrgencia->where('nivel_urgencia', 'critica')->count(),
                    'urgencia_alta' => $prestamosConUrgencia->where('nivel_urgencia', 'alta')->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener préstamos vencidos',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * DELETE /api/prestamos/{id}
     *
     * BUSINESS RULE: Solo administradores pueden eliminar préstamos
     * y solo si no afectan la integridad del negocio
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $prestamo = DB::transaction(function() use ($id) {
                $prestamo = Prestamo::with('libro')->findOrFail($id);

                // Si el préstamo no fue devuelto, liberar el libro
                if (!$prestamo->devuelto) {
                    $prestamo->libro->update(['disponible' => true]);
                }

                $prestamo->delete();
                return $prestamo;
            });

            return response()->json([
                'success' => true,
                'message' => 'Préstamo eliminado exitosamente'
            ], 204);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Préstamo no encontrado'
            ], 404);
        }
    }

    /**
     * Helper method para respuestas de error consistentes
     *
     * @param string $message
     * @param string $error
     * @param int $statusCode
     * @return JsonResponse
     */
    private function errorResponse(string $message, string $error, int $statusCode): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ], $statusCode);
    }
}
