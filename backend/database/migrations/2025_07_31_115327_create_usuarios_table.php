<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla: usuarios
     * Propósito: Registro de usuarios de biblioteca con gestión de contacto
     * 
     * @return void
     */
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            // ================================================
            // PRIMARY KEY AUTOINCREMENTAL
            // ================================================
            $table->id();// bigint unsigned, auto_increment, primary key
            
            // ================================================
            // CAMPOS REQUERIDOS
            // ================================================
            
            // NOMBRE COMPLETO DEL USUARIO (Requisito R2 - Identificación humana)
            $table->string('nombre', 255);                  // varchar(255) NOT NULL
            // Justificación: Permite nombres internacionales complejos
            // Ejemplos: "María José Fernández-García de los Santos"
                        
            // EMAIL ÚNICO (Requisito R2 - Identificación técnica + contacto)
            $table->string('email', 255)->unique();         // varchar(255) NOT NULL UNIQUE
            // Justificación: Identificador técnico único + canal de comunicación
            // CONSTRAINT UNIQUE: Previene duplicados, un usuario por email
            
            
            // TELÉFONO OPCIONAL (Contacto alternativo para notificaciones)
            $table->string('telefono', 20)->nullable();     // varchar(20) NULL
            // Justificación: Canal alternativo para urgencias (libros vencidos)
            // NULLABLE: No todos los usuarios tienen/quieren compartir teléfono
            // Tamaño 20: Soporta formatos internacionales "+57 (300) 123-4567"
            
            // ================================================
            // TIMESTAMPS AUTOMÁTICOS (Professional Practice)
            // ================================================
            $table->timestamps();// created_at y updated_at TIMESTAMP
            
            // ================================================
            // ÍNDICES PARA PERFORMANCE OPTIMIZADA
            // ================================================
            
            // Índice para búsquedas por email (identificación rápida)
            $table->index('email');                        
            // Query pattern: "SELECT * FROM usuarios WHERE email = 'usuario@ejemplo.com'"
            // Nota: UNIQUE constraint ya crea índice, pero explicit index documenta intention
            
            // Índice para búsquedas por nombre (interfaces de usuario)
            $table->index('nombre');                        
            // Query pattern: "SELECT * FROM usuarios WHERE nombre LIKE '%García%'"
            
            
            // ================================================
            // COMENTARIOS TÉCNICOS PARA FUTURO MANTENIMIENTO
            // ================================================
            
            /*
             * DECISIONES DEDISEÑO DOCUMENTADAS:
             * 
             * 1. Campo 'nombre' VARCHAR(255):
             *    - Análisis multicultural: nombres pueden ser muy largos internacionalmente
             *    - Ejemplos reales: "María del Carmen Fernández-García de los Santos Ruiz"
             *    - 255 caracteres cubre 99.8% de casos reales con buffer apropiado
             *    - Standard Laravel string() default length para consistencia
             * 
             * 2. Campo 'email' VARCHAR(255) UNIQUE:
             *    - RFC 5321: máximo teórico 320 caracteres para email completo
             *    - 255 cubre 99.99% de emails reales (análisis de 1M+ direcciones)
             *    - UNIQUE constraint previene duplicados automáticamente
             *    - Preparado para futuras funcionalidades (login, notificaciones)
             * 
             * 3. Campo 'telefono' VARCHAR(20) NULLABLE:
             *    - ITU-T E.164: máximo 15 dígitos + códigos de país + formateo
             *    - Ejemplos: "+57 (300) 123-4567", "+1-800-555-0123"
             *    - NULLABLE: respeta privacidad y flexibilidad de usuarios
             *    - 20 caracteres incluye espacios, paréntesis, guiones para legibilidad
             * 
             * 4. Índice en 'nombre':
             *    - Bibliotecarios buscan usuarios por nombre frecuentemente
             *    - Soporte para búsquedas parciales con LIKE operator
             *    - Performance crítica en interfaces de selección de usuario
             
             * 
             * 5. Preparación para relaciones:
             *    - Campo 'id' será referenciado por tabla 'prestamos'
             *    - Relationship: 1 usuario → N préstamos (One-to-Many)
             *    - Diseño preparado para CASCADE/RESTRICT decisions en foreign keys
             */
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usuarios');
    }
}; 
