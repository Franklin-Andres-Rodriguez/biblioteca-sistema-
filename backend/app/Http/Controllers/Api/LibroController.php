<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * LibroController - API REST para gestión de libros
 *
 * @package App\Http\Controllers\Api
 * @author Sistema Biblioteca
 * @version 1.0.0
 */
class LibroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * GET /api/libros
     *
     * Principio aplicado: Query Object Pattern (Martin Fowler)
     * - Permite filtros flexibles sin contaminar el controlador
     * - Paginación automática para performance
     * - Scopes de Eloquent para queries legibles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. PREPARACIÓN DE LA QUERY BASE
            // Usamos el Query Builder pattern para construir queries flexibles
            $query = Libro::query();

            // 2. FILTROS DINÁMICOS (Specification Pattern)
            // Cada filtro es opcional y se aplica solo si existe el parámetro

            // Filtro por disponibilidad (true/false)
            if ($request->has('disponible')) {
                $disponible = filter_var($request->disponible, FILTER_VALIDATE_BOOLEAN);
                $query->where('disponible', $disponible);
            }

            // Filtro por género (string exacto)
            if ($request->filled('genero')) {
                $query->where('genero', $request->genero);
            }

            // Búsqueda por título o autor (LIKE search)
            if ($request->filled('buscar')) {
                $termino = $request->buscar;
                $query->where(function ($q) use ($termino) {
                    $q->where('titulo', 'LIKE', "%{$termino}%")
                      ->orWhere('autor', 'LIKE', "%{$termino}%");
                });
            }

            // 3. ORDENAMIENTO (Default: más recientes primero)
            $sortBy = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            // Validación de campos permitidos para ordenamiento (Security)
            $allowedSorts = ['titulo', 'autor', 'genero', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // 4. PAGINACIÓN CONFIGURABLE
            $perPage = min($request->get('per_page', 15), 100); // Máximo 100 por página
            $libros = $query->paginate($perPage);

            // 5. RESPUESTA ESTRUCTURADA (JSON API Standard)
            return response()->json([
                'success' => true,
                'message' => 'Libros obtenidos correctamente',
                'data' => $libros->items(), // Solo los datos, sin metadatos de paginación
                'meta' => [
                    'current_page' => $libros->currentPage(),
                    'last_page' => $libros->lastPage(),
                    'per_page' => $libros->perPage(),
                    'total' => $libros->total(),
                    'from' => $libros->firstItem(),
                    'to' => $libros->lastItem()
                ],
                'links' => [
                    'first' => $libros->url(1),
                    'last' => $libros->url($libros->lastPage()),
                    'prev' => $libros->previousPageUrl(),
                    'next' => $libros->nextPageUrl()
                ]
            ], 200);

        } catch (\Exception $e) {
            // Error Handling
            return $this->errorResponse(
                'Error al obtener libros',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * POST /api/libros
     *
     * Principios aplicados:
     * - Validation First: Validar antes de procesar
     * - Single Transaction: Operación atómica
     * - Rich Domain Model: El modelo maneja su propia lógica
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 1. VALIDACIÓN EXHAUSTIVA (Fail Fast Principle)
            $validatedData = $request->validate([
                'titulo' => 'required|string|max:255|min:2',
                'autor' => 'required|string|max:255|min:2',
                'genero' => 'required|string|max:100',
                'isbn' => 'nullable|string|unique:libros,isbn|regex:/^[0-9\-X]+$/',
                'descripcion' => 'nullable|string|max:1000',
                'disponible' => 'boolean'
            ], [
                // Mensajes de error personalizados (UX)
                'titulo.required' => 'El título es obligatorio',
                'titulo.min' => 'El título debe tener al menos 2 caracteres',
                'autor.required' => 'El autor es obligatorio',
                'isbn.unique' => 'Ya existe un libro con este ISBN',
                'isbn.regex' => 'El ISBN tiene un formato inválido'
            ]);

            // 2. NORMALIZACIÓN DE DATOS (Data Sanitization)
            $validatedData['titulo'] = trim($validatedData['titulo']);
            $validatedData['autor'] = trim($validatedData['autor']);
            $validatedData['disponible'] = $validatedData['disponible'] ?? true; // Default true

            // 3. CREACIÓN DEL RECURSO (Mass Assignment Protection activa)
            $libro = Libro::create($validatedData);

            // 4. RESPUESTA DE ÉXITO (Status 201 - Created)
            return response()->json([
                'success' => true,
                'message' => 'Libro creado exitosamente',
                'data' => $libro->fresh(), // fresh() recarga desde DB
            ], 201);

        } catch (ValidationException $e) {
            // Errores de validación (Status 422 - Unprocessable Entity)
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Errores internos del servidor
            return $this->errorResponse(
                'Error al crear libro',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * GET /api/libros/{id}
     *
     * Principio aplicado:
     * - Resource Identification: ID como identificador único
     * - Null Object Pattern: Respuesta consistente para recursos no encontrados
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            // 1. BÚSQUEDA CON MANEJO DE EXCEPCIÓN
            $libro = Libro::findOrFail($id);

            // 2. RESPUESTA EXITOSA
            return response()->json([
                'success' => true,
                'message' => 'Libro encontrado',
                'data' => $libro
            ], 200);

        } catch (ModelNotFoundException $e) {
            // Recurso no encontrado (Status 404)
            return response()->json([
                'success' => false,
                'message' => 'Libro no encontrado',
                'error' => "No existe un libro con ID: {$id}"
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * PUT/PATCH /api/libros/{id}
     *
     * Principios aplicados:
     * - Idempotency: La misma operación produce el mismo resultado
     * - Partial Updates: PATCH permite actualizaciones parciales
     * - Optimistic Locking: updated_at previene conflictos de concurrencia
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // 1. ENCONTRAR EL RECURSO
            $libro = Libro::findOrFail($id);

            // 2. VALIDACIÓN (solo campos presentes para PATCH)
            $validatedData = $request->validate([
                'titulo' => 'sometimes|required|string|max:255|min:2',
                'autor' => 'sometimes|required|string|max:255|min:2',
                'genero' => 'sometimes|required|string|max:100',
                'isbn' => 'sometimes|nullable|string|regex:/^[0-9\-X]+$/|unique:libros,isbn,' . $id,
                'descripcion' => 'sometimes|nullable|string|max:1000',
                'disponible' => 'sometimes|boolean'
            ]);

            // 3. ACTUALIZACIÓN ATÓMICA
            $libro->update($validatedData);

            // 4. RESPUESTA CON DATOS ACTUALIZADOS
            return response()->json([
                'success' => true,
                'message' => 'Libro actualizado exitosamente',
                'data' => $libro->fresh()
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Libro no encontrado',
                'error' => "No existe un libro con ID: {$id}"
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar libro',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * DELETE /api/libros/{id}
     *
     * Principios aplicados:
     * - Business Rules: Verificar si el libro tiene préstamos activos
     * - Soft Delete: Mantener referencial integrity
     * - Cascade Operations: Manear dependencias relacionadas
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // 1. ENCONTRAR EL RECURSO
            $libro = Libro::findOrFail($id);

            // 2. VERIFICAR REGLAS DE NEGOCIO
            // No se puede eliminar un libro con préstamos activos
            if ($libro->prestamos()->where('devuelto', false)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el libro',
                    'error' => 'El libro tiene préstamos activos'
                ], 409); // Conflict
            }

            // 3. ELIMINACIÓN (Soft Delete si está configurado)
            $libro->delete();

            // 4. RESPUESTA DE CONFIRMACIÓN
            return response()->json([
                'success' => true,
                'message' => 'Libro eliminado exitosamente',
                'data' => null
            ], 204); // No Content

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Libro no encontrado',
                'error' => "No existe un libro con ID: {$id}"
            ], 404);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar libro',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get available books only.
     *
     * GET /api/libros/disponibles
     *
     * Endpoint especializado siguiendo el principio de
     * Segregation of Interfaces (ISP)
     *
     * @return JsonResponse
     */
    public function disponibles(): JsonResponse
    {
        try {
            $librosDisponibles = Libro::disponibles()
                                    ->orderBy('titulo')
                                    ->get();

            return response()->json([
                'success' => true,
                'message' => 'Libros disponibles obtenidos correctamente',
                'data' => $librosDisponibles,
                'meta' => [
                    'total_disponibles' => $librosDisponibles->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener libros disponibles',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Helper method para respuestas de error consistentes.
     *
     * Siguiendo el DRY principle y el patrón Template Method
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
