<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla: prestamos
     * Propósito: Núcleo del sistema - gestión completa de préstamos con business logic
     * 
     * @return void
     */
    public function up()
    {
        Schema::create('prestamos', function (Blueprint $table) {
            // ================================================
            // PRIMARY KEY AUTOINCREMENTAL
            // ================================================
            $table->id();// bigint unsigned, auto_increment, primary key
            
            // ================================================
            // FOREIGN KEYS CON BUSINESS RULES CRÍTICAS
            // ================================================
            
            // RELACIÓN CON LIBRO (Qué libro se prestó)
            $table->foreignId('libro_id')                   // bigint unsigned NOT NULL
                  ->constrained('libros')                   // FOREIGN KEY CONSTRAINT automático
                  ->onDelete('cascade');                    // Si eliminas libro → eliminar préstamos
            // DECISIÓN ARQUITECTÓNICA: CASCADE permite cleanup automático
            // ALTERNATIVA: ->onDelete('restrict') previene eliminación de libros con préstamos
                        
            // RELACIÓN CON USUARIO (A quién se prestó)  
            $table->foreignId('usuario_id')                 // bigint unsigned NOT NULL
                  ->constrained('usuarios')                 // FOREIGN KEY CONSTRAINT automático
                  ->onDelete('cascade');                    // Si eliminas usuario → eliminar préstamos
            // BUSINESS RULE: Un préstamo SIEMPRE pertenece a un usuario específico
                      
            // ================================================
            // TEMPORAL BUSINESS LOGIC (Estado temporal del préstamo)
            // ================================================
            
            // FECHA DE PRÉSTAMO (Cuándo se prestó - INMUTABLE)
            $table->date('fecha_prestamo');                 // DATE NOT NULL
            // BUSINESS RULE: Se establece al crear préstamo, nunca cambia
            // Default: Laravel puede usar Carbon::now() en Model creation
            
            
            // FECHA DEVOLUCIÓN ESPERADA (SLA del préstamo)
            $table->date('fecha_devolucion_esperada');      // DATE NOT NULL  
            // BUSINESS RULE: Calculada automáticamente (fecha_prestamo + 15 días)
            // Base para detección de préstamos vencidos
            
            
            // FECHA DEVOLUCIÓN REAL (Cuándo se devolvió - NULLABLE)
            $table->date('fecha_devolucion_real')->nullable(); // DATE NULL
            // BUSINESS RULE: NULL = no devuelto, NOT NULL = devuelto
            // Critical for state machine: activo vs devuelto
            
            
            // ================================================
            // STATE MACHINE (Estados del préstamo)
            // ================================================
            
            // ESTADO DEL PRÉSTAMO (State machine crítico)
            $table->enum('estado', ['activo', 'devuelto'])  // ENUM con valores específicos
                  ->default('activo');                      // DEFAULT: préstamos inician activos
            
            /*
             * STATE MACHINE DOCUMENTATION:
             * 
             * ACTIVO: 
             *   - fecha_devolucion_real IS NULL
             *   - libro.disponible = false  
             *   - usuario puede tener este libro
             * 
             * DEVUELTO:
             *   - fecha_devolucion_real IS NOT NULL
             *   - libro.disponible = true
             *   - préstamo completado exitosamente
             * 
             * TRANSICIONES VÁLIDAS:
             *   - activo → devuelto (cuando se devuelve libro)
             *   - NO HAY REVERSA (devuelto → activo requiere nuevo préstamo)
                        
             */
            
            // ================================================
            // TIMESTAMPS AUTOMÁTICOS (Professional Practice)
            // "Audit trails enable system forensics"
            // ===============================================
            $table->timestamps(); // created_at y updated_at TIMESTAMP
            
            // ================================================
            // ÍNDICES PARA PERFORMANCE CRÍTICA
            // "Index for query patterns, not just uniqueness"
            // ================================================
            
            // ÍNDICE COMPUESTO: Préstamos activos de un libro específico
            $table->index(['libro_id', 'estado']);          
            // Query pattern: "¿Este libro tiene préstamos activos?"
            // Critical for: Validar disponibilidad antes de nuevo préstamo
            
            // ÍNDICE COMPUESTO: Préstamos activos de un usuario específico  
            $table->index(['usuario_id', 'estado']);        
            // Query pattern: "¿Cuántos libros tiene prestados este usuario?"
            // Critical for: Validar límite de préstamos por usuario (ej: máximo 3)
            
            // ÍNDICE SIMPLE: Ordenamiento cronológico de préstamos
            $table->index('fecha_prestamo');                
            // Query pattern: "Préstamos más recientes primero"
            // Critical for: Interfaces de usuario, reportes históricos
            
            // ÍNDICE SIMPLE: Detección de préstamos vencidos
            $table->index('fecha_devolucion_esperada');     
            // Query pattern: "Préstamos que vencen hoy/mañana/esta semana"
            // Critical for: Sistema de notificaciones automáticas
            
            // ÍNDICE COMPUESTO: Préstamos vencidos (query más complejo)
            $table->index(['estado', 'fecha_devolucion_esperada']);
            // Query pattern: "Préstamos activos vencidos" 
            // SQL: WHERE estado='activo' AND fecha_devolucion_esperada < CURDATE()
            // Critical for: Dashboard de administración, alertas automáticas
            
            // ================================================
            // COMENTARIOS TÉCNICOS PARA FUTURO MANTENIMIENTO
            // ================================================
            
            /*
             * DECISIONES ARQUITECTÓNICAS CRÍTICAS DOCUMENTADAS:
             * 
             * 1. FOREIGN KEY CASCADE vs RESTRICT:
             *    - ELEGIDO: CASCADE DELETE
             *    - JUSTIFICACIÓN: Cleanup automático previene datos huérfanos
             *    - TRADE-OFF: Pérdida de historial vs. integridad referencial
             *    - ALTERNATIVA: RESTRICT + soft deletes (futuro enhancement)
             *     
             * 2. ENUM para estado vs VARCHAR vs INT:
             *    - ELEGIDO: ENUM('activo', 'devuelto')
             *    - JUSTIFICACIÓN: Type safety + performance + explicit values
             *    - BENEFITS: Imposible insertar estados inválidos
             *    - TRADE-OFF: Schema changes para nuevos estados
             *    
             * 
             * 3. DATE vs DATETIME para fechas:
             *    - ELEGIDO: DATE (sin hora)
             *    - JUSTIFICACIÓN: Biblioteca opera por días, no horas específicas
             *    - BENEFITS: Simplifica business logic, reduce storage
             *    - TRADE-OFF: Menos granularidad temporal
             *     
             * 4. NULLABLE fecha_devolucion_real:
             *    - ELEGIDO: NULL = no devuelto, NOT NULL = devuelto
             *    - JUSTIFICACIÓN: State machine natural, no valores mágicos
             *    - BENEFITS: Query simplicity (WHERE fecha_devolucion_real IS NULL)
             *    - ALTERNATIVE: Boolean 'devuelto' field (más storage, menos expresivo)
             *    
             * 
             * 5. ÍNDICES COMPUESTOS - Orden de columnas:
             *    - [libro_id, estado]: libro_id primero (alta cardinalidad)
             *    - [usuario_id, estado]: usuario_id primero (alta cardinalidad)  
             *    - [estado, fecha_devolucion_esperada]: estado primero (filtro común)
             *    - JUSTIFICACIÓN: Leftmost prefix rule optimization
             *    
             * 
             * 6. BUSINESS RULES IMPLEMENTADAS A NIVEL DE SCHEMA:
             *    - Foreign keys: Referential integrity
             *    - NOT NULL constraints: Required business data
             *    - ENUM constraints: Valid state values only
             *    - DEFAULT values: Sensible initial states
             *    - JUSTIFICACIÓN: Database enforces business rules consistently
             *    
             * 
             * 7. PREPARACIÓN PARA ESTADÍSTICAS:
             *    - Índice en fecha_prestamo: Tendencias temporales
             *    - Índices en libro_id/usuario_id: Top rankings
             *    - Estado tracking: Métricas de devolución
             *    - QUERIES PREPARADAS:
             *      * Libros más prestados: GROUP BY libro_id ORDER BY COUNT(*)
             *      * Usuarios más activos: GROUP BY usuario_id ORDER BY COUNT(*)
             *      * Tasa de devolución: COUNT(devuelto) / COUNT(total)
             *      * Préstamos vencidos: WHERE estado='activo' AND fecha < NOW()
             */
        });
    }

    /**
     * Reverse the migrations.
     * 
     * 
     * NOTA CRÍTICA: Esta operación eliminará TODOS los préstamos
     * En producción, considerar migración de datos antes de rollback
     * 
     * @return void
     */
    public function down()
    {
        // Laravel automáticamente maneja foreign key constraints durante drop
        Schema::dropIfExists('prestamos');
    }
};
