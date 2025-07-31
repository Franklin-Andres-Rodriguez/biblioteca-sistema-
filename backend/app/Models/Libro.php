<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Libro - Active Record Pattern con Business Logica

 *
 * @property int $id
 * @property string $titulo
 * @property string $autor
 * @property string $genero
 * @property bool $disponible
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder disponibles()
 * @method static \Illuminate\Database\Eloquent\Builder porGenero(string $genero)
 */
class Libro extends Model
{
    use HasFactory;

    // ================================================
    // CONFIGURACIÓN DE TABLA
    // ================================================

    /**
     * Nombre explícito de tabla
     */
    protected $table = 'libros';

    // ================================================
    // MASS ASSIGNMENT PROTECTION (Seguridad crítica)
    // ================================================

    /**
     * Campos que se pueden asignar masivamente
     *
     * @var array<string>
     */
    protected $fillable = [
        'titulo',      // Requerido por requisito R1
        'autor',       // Requerido por requisito R1
        'genero',      // Requerido por requisito R1
        'disponible'   // Requerido por requisito R1 + R2
    ];

    // ================================================
    // ATTRIBUTE CASTING (Type safety)
    // ================================================

    /**
     * Conversiones automáticas de tipos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'disponible' => 'boolean',     // Convierte 0/1 a true/false automáticamente
        'created_at' => 'datetime',    // Objetos Carbon para fechas
        'updated_at' => 'datetime'
    ];

    // ================================================
    // RELACIONES ELOQUENT
    // ================================================

    /**
     * Un libro puede tener muchos préstamos a lo largo del tiempo
     * Relación: 1 libro → N préstamos
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    // ================================================
    // MÉTODOS DE NEGOCIO (Business Logic)
    // ================================================

    /**
     * Verificar si el libro está disponible para préstamo
     *
     * Considera tanto el campo 'disponible' como préstamos activos
     *
     * @return bool
     */
    public function estaDisponible(): bool
    {
        return $this->disponible &&                     // Campo disponible = true
               !$this->prestamosActivos()->exists();    // Y no tiene préstamos activos
    }

    /**
     * Obtener préstamos activos (no devueltos) de este libro
     *
     * Scope de relación para reutilizar en consultas
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prestamosActivos()
    {
        return $this->prestamos()->where('estado', 'activo');
    }

    /**
     * Obtener historial completo de préstamos con información de usuarios
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function historialPrestamos()
    {
        return $this->prestamos()
                   ->with('usuario')                    // Eager loading para performance
                   ->orderBy('fecha_prestamo', 'desc');  // Más recientes primero
    }

    /**
     * Calcular total de veces que este libro ha sido prestado
     *
     * @return int
     */
    public function totalPrestamos(): int
    {
        return $this->prestamos()->count();
    }

    /**
     * Verificar si el libro es popular (más de X préstamos)
     *
     * Business rule para identificar libros demandados
     *
     * @param int $umbral Número mínimo de préstamos para considerar popular
     * @return bool
     */
    public function esPopular(int $umbral = 5): bool
    {
        return $this->totalPrestamos() >= $umbral;
    }

    // ================================================
    // QUERY SCOPES (Consultas reutilizables)
    // ================================================

    /**
     * Scope para filtrar libros disponibles
     *
     * Uso: Libro::disponibles()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true)
                    ->whereDoesntHave('prestamos', function ($q) {
                        $q->where('estado', 'activo');
                    });
    }

    /**
     * Scope para filtrar por género específico
     *
     * Uso: Libro::porGenero('Ficción')->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $genero
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorGenero($query, string $genero)
    {
        return $query->where('genero', $genero);
    }

    /**
     * Scope para libros populares (con muchos préstamos)
     *
     * Uso: Libro::populares()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $umbral
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopulares($query, int $umbral = 5)
    {
        return $query->withCount('prestamos')
                    ->having('prestamos_count', '>=', $umbral);
    }

    // ================================================
    // ACCESSORS & MUTATORS (Data transformation)
    // ================================================

    /**
     * Accessor: Formatear título con primera letra mayúscula
     *
     * Se ejecuta automáticamente al acceder a $libro->titulo
     *
     * @param string $value
     * @return string
     */
    public function getTituloAttribute(string $value): string
    {
        return ucfirst(strtolower($value));
    }

    /**
     * Limpiar y formatear título antes de guardar
     *
     * Se ejecuta automáticamente al asignar $libro->titulo = "algo"
     *
     * @param string $value
     * @return void
     */
    public function setTituloAttribute(string $value): void
    {
        $this->attributes['titulo'] = trim(ucfirst(strtolower($value)));
    }

    /**
     * Accessor: Formatear autor con nombre propio
     *
     * @param string $value
     * @return string
     */
    public function getAutorAttribute(string $value): string
    {
        return ucwords(strtolower($value));
    }

    /**
     * Limpiar y formatear autor antes de guardar
     *
     * @param string $value
     * @return void
     */
    public function setAutorAttribute(string $value): void
    {
        $this->attributes['autor'] = trim(ucwords(strtolower($value)));
    }

    // ================================================
    // MÉTODOS ESTÁTICOS PARA CONSULTAS COMPLEJAS
    // ================================================

    /**
     * Obtener libros más prestados
     *
     * @param int $limite
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function masPrestados(int $limite = 10)
    {
        return static::withCount('prestamos')
                    ->orderByDesc('prestamos_count')
                    ->limit($limite)
                    ->get();
    }

    /**
     * Buscar libros por texto en título o autor
     *
     * @param string $termino
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function buscar(string $termino)
    {
        return static::where('titulo', 'like', "%{$termino}%")
                    ->orWhere('autor', 'like', "%{$termino}%");
    }

    // ================================================
    // MÉTODOS DE VALIDACIÓN DE BUSINESS RULES
    // ================================================

    /**
     * Validar si el libro puede ser eliminado
     *
     * No se puede eliminar si tiene préstamos activos
     *
     * @return bool
     */
    public function puedeEliminarse(): bool
    {
        return !$this->prestamosActivos()->exists();
    }

    /**
     * Obtener razón por la cual el libro no puede eliminarse
     *
     * @return string|null
     */
    public function razonNoEliminacion(): ?string
    {
        if ($this->prestamosActivos()->exists()) {
            $cantidad = $this->prestamosActivos()->count();
            return "El libro tiene {$cantidad} préstamo(s) activo(s)";
        }

        return null;
    }

    // ================================================
    // MÉTODOS PARA STRING REPRESENTATION
    // ================================================

    /**
     * Representación en string del libro
     *
     * Útil para debugging y logs
     *
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->titulo} por {$this->autor} ({$this->genero})";
    }

    /**
     * Representación detallada para APIs'.
     *
     * @return array
     */
    public function toDetailedArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'genero' => $this->genero,
            'disponible' => $this->disponible,
            'esta_disponible' => $this->estaDisponible(),
            'total_prestamos' => $this->totalPrestamos(),
            'es_popular' => $this->esPopular(),
            'puede_eliminarse' => $this->puedeEliminarse(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
