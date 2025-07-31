<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Usuario - Active Record Pattern con Business Logic
 *
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string|null $telefono
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder conPrestamosActivos()
 * @method static \Illuminate\Database\Eloquent\Builder buscar(string $termino)
 * @method static \Illuminate\Database\Eloquent\Builder conRetrasos()
 */
class Usuario extends Model
{
    use HasFactory;

    // ================================================
    // CONFIGURACIÓN DE TABLA
    // ================================================

    /**
     * Nombre explícito de tabla (buena práctica)
     */
    protected $table = 'usuarios';

    // ================================================
    // MASS ASSIGNMENT PROTECTION (Seguridad crítica)
    // ================================================

    /**
     * Campos que se pueden asignar masivamente
     * Aplicando Martin Fowler: "Fail fast on invalid data"
     *
     * @var array<string>
     */
    protected $fillable = [
        'nombre',      // Requerido por requisito R2 - Identificación humana
        'email',       // Requerido por requisito R2 - Identificación técnica + contacto
        'telefono'     // Opcional - Contacto alternativo para notificaciones
    ];

    // ================================================
    // ATTRIBUTE CASTING (Type safety)
    // ================================================

    /**
     * Conversiones automáticas de tipos
     * Aplicando Jonas Schmedtmann: "Let the framework handle complexity"
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',    // Objetos Carbon para fechas
        'updated_at' => 'datetime'
    ];

    // ================================================
    // RELACIONES ELOQUENT (Requisito R2 - Gestión de préstamos)
    // ================================================

    /**
     * Un usuario puede tener muchos préstamos a lo largo del tiempo
     * Relación: 1 usuario → N préstamos
     *
     * Aplicando Martin Fowler: "Domain relationships should be explicit"
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
     * Obtener préstamos activos (no devueltos) del usuario
     *
     * Crítico para reglas de negocio (¿puede pedir más libros?)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prestamosActivos()
    {
        return $this->prestamos()->where('estado', 'activo');
    }

    /**
     * Verificar si el usuario puede solicitar un préstamo
     *
     * Implementa regla de negocio: máximo X libros simultáneos
     *
     * @param int $limite Máximo número de préstamos simultáneos
     * @return bool
     */
    public function puedeTomarPrestamo(int $limite = 3): bool
    {
        return $this->prestamosActivos()->count() < $limite;
    }

    /**
     * Obtener libros actualmente en préstamo con información completa
     *
     * Útil para mostrar en interfaces de usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function librosEnPrestamo()
    {
        return $this->prestamosActivos()->with('libro');
    }

    /**
     * Calcular total de libros prestados históricamente
     *
     * Útil para estadísticas (Requisito R3)
     *
     * @return int
     */
    public function totalLibrosPrestados(): int
    {
        return $this->prestamos()->count();
    }

    /**
     * Verificar si el usuario tiene préstamos vencidos
     *
     * Critical for business rules and notifications
     *
     * @return bool
     */
    public function tieneRetrasos(): bool
    {
        return $this->prestamosActivos()
                   ->where('fecha_devolucion_esperada', '<', now())
                   ->exists();
    }

    /**
     * Obtener préstamos vencidos del usuario
     *
     * Para alertas y gestión de biblioteca
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prestamosVencidos()
    {
        return $this->prestamosActivos()
                   ->where('fecha_devolucion_esperada', '<', now())
                   ->with('libro');
    }

    /**
     * Calcular días promedio de préstamo del usuario
     *
     * Estadística para análisis de comportamiento
     *
     * @return float
     */
    public function diasPromedioPrestamo(): float
    {
        $prestamosDevueltos = $this->prestamos()
                                  ->where('estado', 'devuelto')
                                  ->whereNotNull('fecha_devolucion_real')
                                  ->get();

        if ($prestamosDevueltos->count() === 0) {
            return 0.0;
        }

        $totalDias = $prestamosDevueltos->sum(function ($prestamo) {
            return $prestamo->fecha_prestamo->diffInDays($prestamo->fecha_devolucion_real);
        });

        return round($totalDias / $prestamosDevueltos->count(), 1);
    }

    /**
     * Verificar si el usuario es activo (tiene préstamos recientes)
     *
     * Business rule para identificar usuarios regulares
     *
     * @param int $meses Meses hacia atrás para considerar actividad
     * @return bool
     */
    public function esUsuarioActivo(int $meses = 6): bool
    {
        return $this->prestamos()
                   ->where('fecha_prestamo', '>=', now()->subMonths($meses))
                   ->exists();
    }

    // ================================================
    // QUERY SCOPES (Consultas reutilizables)
    // ================================================

    /**
     * Scope para usuarios con préstamos activos
     *
     * Uso: Usuario::conPrestamosActivos()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConPrestamosActivos($query)
    {
        return $query->whereHas('prestamos', function ($q) {
            $q->where('estado', 'activo');
        });
    }

    /**
     * Scope para buscar usuarios por nombre o email
     *
     * Uso: Usuario::buscar('juan')->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $termino
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%");
    }

    /**
     * Scope para usuarios con préstamos vencidos
     *
     * Uso: Usuario::conRetrasos()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConRetrasos($query)
    {
        return $query->whereHas('prestamos', function ($q) {
            $q->where('estado', 'activo')
              ->where('fecha_devolucion_esperada', '<', now());
        });
    }

    /**
     * Scope para usuarios activos (con préstamos recientes)
     *
     * Uso: Usuario::activos()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $meses
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query, int $meses = 6)
    {
        return $query->whereHas('prestamos', function ($q) use ($meses) {
            $q->where('fecha_prestamo', '>=', now()->subMonths($meses));
        });
    }

    // ================================================
    // ACCESSORS & MUTATORS (Data transformation)
    // ================================================

    /**
     * Accessor: Formatear nombre con formato correcto
     *
     * @param string $value
     * @return string
     */
    public function getNombreAttribute(string $value): string
    {
        return ucwords(strtolower($value));
    }

    /**
     * Mutator: Limpiar nombre antes de guardar
     *
     * @param string $value
     * @return void
     */
    public function setNombreAttribute(string $value): void
    {
        $this->attributes['nombre'] = trim(ucwords(strtolower($value)));
    }

    /**
     * Mutator: Convertir email a minúsculas y limpiar
     *
     * @param string $value
     * @return void
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    /**
     * Mutator: Limpiar formato de teléfono
     *
     * @param string|null $value
     * @return void
     */
    public function setTelefonoAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['telefono'] = null;
            return;
        }

        // Remover espacios, guiones, paréntesis - mantener solo dígitos y + inicial
        $telefono = preg_replace('/[^\d+]/', '', $value);
        $this->attributes['telefono'] = $telefono ?: null;
    }

    /**
     * Accessor: Formatear teléfono para display
     *
     * @param string|null $value
     * @return string|null
     */
    public function getTelefonoDisplayAttribute(): ?string
    {
        if (!$this->telefono) {
            return null;
        }

        // Formato colombiano: +57 (300) 123-4567
        if (preg_match('/^\+57(\d{3})(\d{3})(\d{4})$/', $this->telefono, $matches)) {
            return "+57 ({$matches[1]}) {$matches[2]}-{$matches[3]}";
        }

        // Formato genérico
        return $this->telefono;
    }

    // ================================================
    // MÉTODOS ESTÁTICOS PARA CONSULTAS COMPLEJAS
    // ================================================

    /**
     * Obtener usuarios más activos (estadística para Requisito R3)
     *
     * @param int $limite
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function masActivos(int $limite = 10)
    {
        return static::withCount('prestamos')
                    ->orderByDesc('prestamos_count')
                    ->limit($limite)
                    ->get();
    }

    /**
     * Obtener estadísticas de usuarios para dashboard
     *
     *
     * @return array
     */
    public static function estadisticasGenerales(): array
    {
        return [
            'total_usuarios' => static::count(),
            'usuarios_activos' => static::conPrestamosActivos()->count(),
            'usuarios_con_retrasos' => static::conRetrasos()->count(),
            'usuarios_registrados_mes' => static::where('created_at', '>=', now()->startOfMonth())->count(),
            'promedio_prestamos_por_usuario' => round(static::withCount('prestamos')->avg('prestamos_count'), 1)
        ];
    }

    // ================================================
    // MÉTODOS DE VALIDACIÓN DE BUSINESS RULES
    // ================================================

    /**
     * Validar si el usuario puede ser eliminado
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
     * Obtener razón por la cual el usuario no puede eliminarse
     *
     * @return string|null
     */
    public function razonNoEliminacion(): ?string
    {
        if ($this->prestamosActivos()->exists()) {
            $cantidad = $this->prestamosActivos()->count();
            return "El usuario tiene {$cantidad} préstamo(s) activo(s)";
        }

        return null;
    }

    /**
     * Validar límites de préstamo antes de asignar nuevo libro
     *
     *
     * @param int $limite
     * @return array
     */
    public function validarLimitePrestamo(int $limite = 3): array
    {
        $prestamosActivos = $this->prestamosActivos()->count();

        return [
            'puede_tomar' => $prestamosActivos < $limite,
            'prestamos_actuales' => $prestamosActivos,
            'limite_maximo' => $limite,
            'prestamos_disponibles' => max(0, $limite - $prestamosActivos),
            'mensaje' => $prestamosActivos >= $limite
                ? "Usuario ha alcanzado el límite de {$limite} préstamos simultáneos"
                : "Usuario puede tomar " . ($limite - $prestamosActivos) . " préstamo(s) adicional(es)"
        ];
    }

    // ================================================
    // MÉTODOS PARA STRING REPRESENTATION
    // ================================================

    /**
     * Representación en string del usuario
     *
     * Útil para debugging y logs
     *
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->nombre} ({$this->email})";
    }

    /**
     * Representación detallada para APIs
     *
     * Aplicando Jonas Schmedtmann: "APIs should provide rich information"
     *
     * @return array
     */
    public function toDetailedArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'telefono_display' => $this->telefono_display,
            'total_prestamos' => $this->totalLibrosPrestados(),
            'prestamos_activos' => $this->prestamosActivos()->count(),
            'tiene_retrasos' => $this->tieneRetrasos(),
            'puede_tomar_prestamo' => $this->puedeTomarPrestamo(),
            'es_usuario_activo' => $this->esUsuarioActivo(),
            'dias_promedio_prestamo' => $this->diasPromedioPrestamo(),
            'puede_eliminarse' => $this->puedeEliminarse(),
            'validacion_limite' => $this->validarLimitePrestamo(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
