/**
 * TypeScript Interfaces - Sistema Biblioteca
 * 
 * Definiciones de tipos siguiendo las mejores prácticas de:
 * - Microsoft TypeScript Team: Type safety y developer experience
 * - Dan Abramov: Contract-based architecture y interface segregation
 * - Martin Fowler: Domain modeling y ubiquitous language
 * - Robert C. Martin: Interface Segregation Principle (ISP)
 * 
 * @author Sistema Biblioteca - Type Definitions
 * @version 1.0.0
 */

// ===== BASE ENTITY TYPES =====

/**
 * Base interface para todas las entidades del dominio
 */
export interface BaseEntity {
  id: number;
  created_at: string;
  updated_at: string;
}

// ===== DOMAIN ENTITIES =====

/**
 * Interface Libro
 */
export interface Libro extends BaseEntity {
  titulo: string;
  autor: string;
  genero: string;
  isbn?: string;
  descripcion?: string;
  disponible: boolean;
}

/**
 * Interface Usuario  
 */
export interface Usuario extends BaseEntity {
  nombre: string;
  email: string;
  telefono?: string;
}

/**
 * Interface Prestamo
 */
export interface Prestamo extends BaseEntity {
  usuario_id: number;
  libro_id: number;
  fecha_prestamo: string;
  fecha_devolucion: string;
  devuelto: boolean;
  
  // Relaciones opcionales
  usuario?: Usuario;
  libro?: Libro;
}

// ===== API RESPONSE TYPES =====

/**
 * Respuesta paginada de la API
 */
export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  links: {
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
}

/**
 * Respuesta simple de la API
 */
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

/**
 * Respuesta de error de la API
 */
export interface ApiError {
  success: false;
  message: string;
  error: string;
  errors?: Record<string, string[]>;
  timestamp?: string;
}

// ===== FORM DATA TYPES =====

/**
 * Datos para crear/editar libro
 */
export interface LibroFormData {
  titulo: string;
  autor: string;
  genero: string;
  isbn?: string;
  descripcion?: string;
  disponible?: boolean;
}

/**
 * Datos para crear/editar usuario
 */
export interface UsuarioFormData {
  nombre: string;
  email: string;
  telefono?: string;
}

/**
 * Datos para crear préstamo
 */
export interface PrestamoFormData {
  usuario_id: number;
  libro_id: number;
  fecha_prestamo?: string;
  dias_prestamo?: number;
}

// ===== FILTER TYPES =====

/**
 * Filtros para libros
 */
export interface LibrosFilters {
  disponible?: boolean;
  genero?: string;
  buscar?: string;
  sort?: 'titulo' | 'autor' | 'genero' | 'created_at' | 'updated_at';
  direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

// ===== UI STATE TYPES =====

/**
 * Estado de loading para componentes
 */
export interface LoadingState {
  [key: string]: boolean;
}

/**
 * Estado de error para componentes
 */
export interface ErrorState {
  message: string;
  details?: any;
  timestamp: string;
}