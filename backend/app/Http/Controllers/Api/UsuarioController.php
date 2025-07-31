<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * UsuarioController - API REST para gestión de usuarios
 *
 * @package App\Http\Controllers\Api
 * @author Sistema Biblioteca
 * @version 1.0.0
 */
class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * GET /api/usuarios
     *
     * Principios aplicados:
     * - Query Performance: Eager loading de relaciones
     * - Security: Paginación obligatoria para prevenir data dumps
     * - Search Flexibility: Múltiples criterios de búsqueda
     * - Privacy Considerations: No exponer datos sensibles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. QUERY BASE CON EAGER LOADING (N+1 Prevention)
            // Cargamos relaciones frecuentemente accedidas para optimizar performance
            $query = Usuario::with(['prestamos' => function($q) {
                // Solo préstamos activos para el conteo
                $q->where('devuelto', false)
                  ->with('libro:id,titulo'); // Solo campos necesarios
            }]);

            // 2. FILTROS DE BÚSQUEDA AVANZADA

            // Búsqueda por nombre (case-insensitive, fuzzy matching)
            if ($request->filled('nombre')) {
                $nombre = $request->nombre;
                $query->where('nombre', 'LIKE', "%{$nombre}%");
            }

            // Búsqueda por email (exact match por seguridad)
            if ($request->filled('email')) {
                $email = strtolower(trim($request->email));
                $query->where('email', $email);
            }

            // Filtro por usuarios con préstamos activos
            if ($request->has('con_prestamos')) {
                $conPrestamos = filter_var($request->con_prestamos, FILTER_VALIDATE_BOOLEAN);
                if ($conPrestamos) {
                    $query->whereHas('prestamos', function($q) {
                        $q->where('devuelto', false);
                    });
                } else {
                    $query->whereDoesntHave('prestamos', function($q) {
                        $q->where('devuelto', false);
                    });
                }
            }

            // Filtro por usuarios con préstamos vencidos
            if ($request->has('con_vencidos')) {
                $conVencidos = filter_var($request->con_vencidos, FILTER_VALIDATE_BOOLEAN);
                if ($conVencidos) {
                    $query->whereHas('prestamos', function($q) {
                        $q->where('devuelto', false)
                          ->where('fecha_devolucion', '<', now());
                    });
                }
            }

            // 3. ORDENAMIENTO INTELIGENTE
            $sortBy = $request->get('sort', 'nombre');
            $sortDirection = $request->get('direction', 'asc');

            // Campos permitidos para ordenamiento (Security)
            $allowedSorts = ['nombre', 'email', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // 4. PAGINACIÓN CON LÍMITES DE SEGURIDAD
            $perPage = min($request->get('per_page', 20), 50); // Máximo 50 usuarios por página
            $usuarios = $query->paginate($perPage);

            // 5. TRANSFORMACIÓN DE DATOS (Privacy + Performance)
            $usuariosTransformados = $usuarios->getCollection()->map(function($usuario) {
                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'telefono' => $usuario->telefono,
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at,
                    // Métricas calculadas
                    'prestamos_activos' => $usuario->prestamos->count(),
                    'tiene_vencidos' => $usuario->prestamos->some(function($prestamo) {
                        return $prestamo->fecha_devolucion < now();
                    }),
                    // Información de préstamos (solo títulos para privacy)
                    'libros_prestados' => $usuario->prestamos->map(function($prestamo) {
                        return [
                            'id' => $prestamo->id,
                            'titulo_libro' => $prestamo->libro->titulo,
                            'fecha_prestamo' => $prestamo->fecha_prestamo,
                            'fecha_devolucion' => $prestamo->fecha_devolucion,
                            'vencido' => $prestamo->fecha_devolucion < now()
                        ];
                    })
                ];
            });

            // 6. RESPUESTA ESTRUCTURADA CON MÉTRICAS
            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente',
                'data' => $usuariosTransformados,
                'meta' => [
                    'current_page' => $usuarios->currentPage(),
                    'last_page' => $usuarios->lastPage(),
                    'per_page' => $usuarios->perPage(),
                    'total' => $usuarios->total(),
                    'from' => $usuarios->firstItem(),
                    'to' => $usuarios->lastItem(),
                    // Métricas adicionales del dominio
                    'usuarios_con_prestamos' => $usuarios->getCollection()->where('prestamos_activos', '>', 0)->count(),
                    'usuarios_con_vencidos' => $usuarios->getCollection()->where('tiene_vencidos', true)->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener usuarios',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * POST /api/usuarios
     *
     * Principios aplicados:
     * - Data Integrity: Validación exhaustiva antes de persistir
     * - Security: Sanitización de inputs y prevención de inyecciones
     * - Business Rules: Aplicar reglas específicas del dominio usuario
     * - Atomic Operations: Transacciones para consistencia
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 1. VALIDACIÓN EXHAUSTIVA CON REGLAS DE NEGOCIO
            $validatedData = $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/' // Solo letras y espacios
                ],
                'email' => [
                    'required',
                    'email:rfc,dns', // Validación RFC + DNS lookup
                    'unique:usuarios,email',
                    'max:255'
                ],
                'telefono' => [
                    'nullable',
                    'string',
                    'regex:/^[\+]?[0-9\-\(\)\s]+$/', // Formato internacional flexible
                    'min:7',
                    'max:20'
                ]
            ], [
                // Mensajes de error específicos (UX)
                'nombre.required' => 'El nombre es obligatorio',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios',
                'email.required' => 'El email es obligatorio',
                'email.email' => 'El formato del email es inválido',
                'email.unique' => 'Ya existe un usuario con este email',
                'telefono.regex' => 'El formato del teléfono es inválido'
            ]);

            // 2. SANITIZACIÓN Y NORMALIZACIÓN
            $validatedData['nombre'] = trim($validatedData['nombre']);
            $validatedData['nombre'] = ucwords(strtolower($validatedData['nombre'])); // Capitalización estándar
            $validatedData['email'] = strtolower(trim($validatedData['email']));

            if (isset($validatedData['telefono'])) {
                // Limpiar formato de teléfono (solo números y +)
                $validatedData['telefono'] = preg_replace('/[^0-9\+]/', '', $validatedData['telefono']);
            }

            // 3. TRANSACCIÓN ATÓMICA PARA CONSISTENCIA
            $usuario = DB::transaction(function() use ($validatedData) {
                return Usuario::create($validatedData);
            });

            // 4. RESPUESTA CON DATOS COMPLETOS
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario->fresh()
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear usuario',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * GET /api/usuarios/{id}
     *
     * Principios aplicados:
     * - Rich Information: Incluir datos relacionados relevantes
     * - Performance: Eager loading selectivo
     * - Privacy: Exponer solo información necesaria
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            // 1. CARGA OPTIMIZADA CON RELACIONES
            $usuario = Usuario::with([
                'prestamos' => function($query) {
                    $query->with('libro:id,titulo,autor')
                          ->orderBy('created_at', 'desc')
                          ->limit(10); // Últimos 10 préstamos
                }
            ])->findOrFail($id);

            // 2. TRANSFORMACIÓN CON MÉTRICAS DE NEGOCIO
            $usuarioDetallado = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'created_at' => $usuario->created_at,
                'updated_at' => $usuario->updated_at,

                // Métricas calculadas
                'estadisticas' => [
                    'total_prestamos' => $usuario->prestamos()->count(),
                    'prestamos_activos' => $usuario->prestamos()->where('devuelto', false)->count(),
                    'prestamos_vencidos' => $usuario->prestamos()
                        ->where('devuelto', false)
                        ->where('fecha_devolucion', '<', now())
                        ->count(),
                    'prestamos_completados' => $usuario->prestamos()->where('devuelto', true)->count(),
                ],

                // Historial de préstamos (últimos 10)
                'historial_prestamos' => $usuario->prestamos->map(function($prestamo) {
                    return [
                        'id' => $prestamo->id,
                        'libro' => [
                            'id' => $prestamo->libro->id,
                            'titulo' => $prestamo->libro->titulo,
                            'autor' => $prestamo->libro->autor
                        ],
                        'fecha_prestamo' => $prestamo->fecha_prestamo,
                        'fecha_devolucion' => $prestamo->fecha_devolucion,
                        'devuelto' => $prestamo->devuelto,
                        'vencido' => !$prestamo->devuelto && $prestamo->fecha_devolucion < now(),
                        'dias_restantes' => !$prestamo->devuelto ?
                            now()->diffInDays($prestamo->fecha_devolucion, false) : null
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Usuario encontrado',
                'data' => $usuarioDetallado
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'error' => "No existe un usuario con ID: {$id}"
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * PUT/PATCH /api/usuarios/{id}
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            // Validación con regla de unicidad excluyendo el usuario actual
            $validatedData = $request->validate([
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'
                ],
                'email' => [
                    'sometimes',
                    'required',
                    'email:rfc,dns',
                    'unique:usuarios,email,' . $id,
                    'max:255'
                ],
                'telefono' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'regex:/^[\+]?[0-9\-\(\)\s]+$/',
                    'min:7',
                    'max:20'
                ]
            ]);

            // Sanitización
            if (isset($validatedData['nombre'])) {
                $validatedData['nombre'] = ucwords(strtolower(trim($validatedData['nombre'])));
            }

            if (isset($validatedData['email'])) {
                $validatedData['email'] = strtolower(trim($validatedData['email']));
            }

            if (isset($validatedData['telefono'])) {
                $validatedData['telefono'] = preg_replace('/[^0-9\+]/', '', $validatedData['telefono']);
            }

            // Actualización en transacción
            DB::transaction(function() use ($usuario, $validatedData) {
                $usuario->update($validatedData);
            });

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuario->fresh()
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * DELETE /api/usuarios/{id}
     *
     * Business Rule: No se puede eliminar usuarios con préstamos activos
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            // Business Rule: Verificar préstamos activos
            $prestamosActivos = $usuario->prestamos()->where('devuelto', false)->count();

            if ($prestamosActivos > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el usuario',
                    'error' => "El usuario tiene {$prestamosActivos} préstamo(s) activo(s)"
                ], 409);
            }

            // Soft delete o hard delete según configuración
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ], 204);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    }

    /**
     * Get users with overdue loans.
     *
     * GET /api/usuarios/con-vencidos
     *
     * @return JsonResponse
     */
    public function conVencidos(): JsonResponse
    {
        try {
            $usuariosConVencidos = Usuario::whereHas('prestamos', function($query) {
                $query->where('devuelto', false)
                      ->where('fecha_devolucion', '<', now());
            })
            ->with(['prestamos' => function($query) {
                $query->where('devuelto', false)
                      ->where('fecha_devolucion', '<', now())
                      ->with('libro:id,titulo');
            }])
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Usuarios con préstamos vencidos',
                'data' => $usuariosConVencidos,
                'meta' => [
                    'total_usuarios_morosos' => $usuariosConVencidos->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener usuarios con vencidos',
                $e->getMessage(),
                500
            );
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
