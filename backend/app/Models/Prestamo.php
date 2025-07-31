<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Prestamo Model - Core business entity
 *
 * Representa un préstamo de libro a usuario, implementando
 * todas las reglas de negocio del dominio biblioteca.
 *
 * Business Rules:
 * - Un préstamo conecta un usuario con un libro
 * - Tiene fechas de inicio y fin
 * - Puede estar activo (devuelto = false) o completado (devuelto = true)
 * - Los préstamos vencidos son aquellos no devueltos después de fecha_devolucion
 *
 * @property int $id
 * @property int $usuario_id
 * @property int $libro_id
 * @property Carbon $fecha_prestamo
 * @property Carbon $fecha_devolucion
 * @property bool $devuelto
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Usuario $usuario
 * @property-read Libro $libro
 */
class Prestamo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * CRITICAL: Estos campos pueden ser asignados masivamente usando create() o fill()
     * Siguiendo las mejores prácticas de seguridad de Laravel
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'libro_id',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'estado'
    ];

    /**
     * The attributes that should be cast.
     *
     * Automatic casting para tipos de datos específicos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_devolucion_esperada' => 'date',
        'fecha_devolucion_real' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Relationship: Prestamo belongs to Usuario
     *
     * @return BelongsTo
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Relationship: Prestamo belongs to Libro
     *
     * @return BelongsTo
     */
    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes - Business Logic Queries
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Solo préstamos activos (no devueltos)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query)
    {
        return $query->where('devuelto', false);
    }

    /**
     * Scope: Solo préstamos completados (devueltos)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompletados($query)
    {
        return $query->where('devuelto', true);
    }

    /**
     * Scope: Préstamos vencidos (activos + fecha_devolucion pasada)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVencidos($query)
    {
        return $query->where('devuelto', false)
                    ->where('fecha_devolucion', '<', now());
    }

    /**
     * Scope: Préstamos por vencer en X días
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $dias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorVencer($query, $dias = 3)
    {
        return $query->where('devuelto', false)
                    ->whereBetween('fecha_devolucion', [
                        now(),
                        now()->addDays($dias)
                    ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Verificar si el préstamo está vencido
     *
     * @return bool
     */
    public function estaVencido(): bool
    {
        return !$this->devuelto && $this->fecha_devolucion < now();
    }

    /**
     * Obtener días de retraso (si está vencido)
     *
     * @return int
     */
    public function diasRetraso(): int
    {
        if (!$this->estaVencido()) {
            return 0;
        }

        return now()->diffInDays($this->fecha_devolucion);
    }

    /**
     * Obtener días restantes (si está activo)
     *
     * @return int|null
     */
    public function diasRestantes(): ?int
    {
        if ($this->devuelto) {
            return null;
        }

        return now()->diffInDays($this->fecha_devolucion, false);
    }

    /**
     * Marcar préstamo como devuelto
     *
     * Business operation que actualiza tanto el préstamo como el libro
     *
     * @return bool
     */
    public function marcarComoDevuelto(): bool
    {
        $this->devuelto = true;
        $resultado = $this->save();

        if ($resultado) {
            // Side effect: liberar el libro
            $this->libro->update(['disponible' => true]);
        }

        return $resultado;
    }

    /**
     * Renovar préstamo por X días adicionales
     *
     * @param int $diasAdicionales
     * @return bool
     */
    public function renovar(int $diasAdicionales = 14): bool
    {
        if ($this->devuelto) {
            return false; // No se puede renovar un préstamo ya devuelto
        }

        $this->fecha_devolucion = $this->fecha_devolucion->addDays($diasAdicionales);
        return $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Computed Properties (Accessors)
    |--------------------------------------------------------------------------
    */

    /**
     * Accessor: Estado del préstamo calculado
     *
     * @return string
     */
    public function getEstadoAttribute(): string
    {
        if ($this->devuelto) {
            return 'completado';
        }

        if ($this->estaVencido()) {
            $diasRetraso = $this->diasRetraso();
            if ($diasRetraso > 7) {
                return 'vencido_critico';
            }
            return 'vencido';
        }

        $diasRestantes = $this->diasRestantes();
        if ($diasRestantes <= 2) {
            return 'por_vencer';
        }

        return 'activo';
    }

    /**
     * Accessor: Duración total del préstamo en días
     *
     * @return int
     */
    public function getDuracionAttribute(): int
    {
        return $this->fecha_prestamo->diffInDays($this->fecha_devolucion);
    }
}
