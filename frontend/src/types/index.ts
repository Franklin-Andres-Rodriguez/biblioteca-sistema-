/**
 * TypeScript Interfaces - Sistema Biblioteca
 * 
 * Estructura:
 * 1. Domain entities (Libro, Usuario, Prestamo)
 * 2. API response types
 * 3. Form data types
 * 4. UI state types
 * 5. Utility types
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
 * Representa un libro en el sistema de biblioteca
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
 * Representa un usuario del sistema de biblioteca
 */
export interface Usuario extends BaseEntity {
  nombre: string;
  email: string;
  telefono?: string;
}

/**
 * Interface Prestamo
 * Representa un préstamo de libro a usuario
 */
export interface Prestamo extends BaseEntity {
  usuario_id: number;
  libro_id: number;
  fecha_prestamo: string;
  fecha_devolucion: string;
  devuelto: boolean;
  
  // Relaciones (cuando se incluyen via eager loading)
  usuario?: Usuario;
  libro?: Libro;
}

// ===== EXTENDED DOMAIN TYPES =====

/**
 * Libro con información adicional calculada
 * Para uso en componentes que muestran métricas
 */
export interface LibroDetallado extends Libro {
  total_prestamos?: number;
  prestamos_activos?: number;
  ultimo_prestamo?: string;
  popularidad?: 'alta' | 'media' | 'baja';
}

/**
 * Usuario con información de préstamos
 * Para perfiles y dashboards
 */
export interface UsuarioDetallado extends Usuario {
  estadisticas?: {
    total_prestamos: number;
    prestamos_activos: number;
    prestamos_vencidos: number;
    prestamos_completados: number;
  };
  historial_prestamos?: PrestamoDetallado[];
}

/**
 * Préstamo con información de negocio calculada
 * Para listas y dashboards con métricas
 */
export interface PrestamoDetallado extends Omit<Prestamo, 'libro'> {
  estado_negocio?: 'activo' | 'vencido' | 'vencido_critico' | 'por_vencer' | 'completado';
  dias_vencido?: number;
  dias_restantes?: number;
  duracion_prestamo?: number;
  usuario?: Usuario;
  libro?: Pick<Libro, 'id' | 'titulo' | 'autor' | 'genero'>;
}

// ===== API RESPONSE TYPES =====

/**
 * Respuesta paginada de la API
 * Compatible con Laravel pagination
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
    metricas_negocio?: Record<string, any>;
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
  business_info?: Record<string, any>;
  operacion_info?: Record<string, any>;
}

/**
 * Respuesta de error de la API
 */
export interface ApiError {
  success: false;
  message: string;
  error: string;
  errors?: Record<string, string[]>; // Validation errors
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

// ===== FILTER & SEARCH TYPES =====

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

/**
 * Filtros para usuarios
 */
export interface UsuariosFilters {
  nombre?: string;
  email?: string;
  con_prestamos?: boolean;
  con_vencidos?: boolean;
  sort?: 'nombre' | 'email' | 'created_at' | 'updated_at';
  direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

/**
 * Filtros para préstamos
 */
export interface PrestamosFilters {
  devuelto?: boolean;
  vencidos?: boolean;
  usuario_id?: number;
  libro_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
  sort?: 'created_at' | 'fecha_prestamo' | 'fecha_devolucion' | 'devuelto';
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

/**
 * Estado de formulario genérico
 */
export interface FormState<T> {
  data: T;
  loading: boolean;
  error?: ErrorState;
  success?: boolean;
}

/**
 * Estado de lista/tabla genérico
 */
export interface ListState<T> {
  items: T[];
  loading: boolean;
  error?: ErrorState;
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: Record<string, any>;
}

// ===== COMPONENT PROPS TYPES =====

/**
 * Props base para componentes de tabla
 */
export interface TableProps<T> {
  data: T[];
  loading?: boolean;
  error?: ErrorState;
  onEdit?: (item: T) => void;
  onDelete?: (item: T) => void;
  onView?: (item: T) => void;
}

/**
 * Props para componentes de formulario
 */
export interface FormProps<T> {
  initialData?: Partial<T>;
  onSubmit: (data: T) => void;
  onCancel?: () => void;
  loading?: boolean;
  error?: ErrorState;
  mode?: 'create' | 'edit';
}

/**
 * Props para componentes de modal
 */
export interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  children: React.ReactNode;
}

// ===== STATISTICS TYPES =====

/**
 * Estadísticas generales del sistema
 */
export interface EstadisticasGenerales {
  total_libros: number;
  libros_disponibles: number;
  total_usuarios: number;
  total_prestamos: number;
  prestamos_activos: number;
  prestamos_vencidos: number;
  prestamos_hoy: number;
  usuarios_activos_mes: number;
}

/**
 * Datos para gráficos de tendencias
 */
export interface TendenciaPrestamos {
  fecha: string;
  prestamos: number;
  devoluciones: number;
  nuevos_usuarios: number;
}

/**
 * Top libros más prestados
 */
export interface LibrosPopulares {
  libro: Pick<Libro, 'id' | 'titulo' | 'autor'>;
  total_prestamos: number;
  prestamos_mes_actual: number;
}

/**
 * Usuarios más activos
 */
export interface UsuariosActivos {
  usuario: Pick<Usuario, 'id' | 'nombre' | 'email'>;
  total_prestamos: number;
  prestamos_mes_actual: number;
  ultimo_prestamo: string;
}

// ===== UTILITY TYPES =====

/**
 * Tipo para opciones de select/dropdown
 */
export interface SelectOption<T = any> {
  value: T;
  label: string;
  disabled?: boolean;
}

/**
 * Tipo para breadcrumbs de navegación
 */
export interface BreadcrumbItem {
  label: string;
  href?: string;
  current?: boolean;
}

/**
 * Tipo para notificaciones toast
 */
export interface ToastNotification {
  id: string;
  type: 'success' | 'error' | 'info' | 'warning';
  title: string;
  message?: string;
  duration?: number;
  action?: {
    label: string;
    onClick: () => void;
  };
}

/**
 * Tipo para configuración de columnas de tabla
 */
export interface TableColumn<T> {
  key: keyof T | string;
  label: string;
  sortable?: boolean;
  render?: (value: any, item: T) => React.ReactNode;
  className?: string;
}

// ===== THEME & STYLING TYPES =====

/**
 * Variantes de colores del tema
 */
export type ColorVariant = 
  | 'biblioteca' 
  | 'disponible' 
  | 'vencido' 
  | 'gray' 
  | 'blue' 
  | 'red' 
  | 'green' 
  | 'yellow';

/**
 * Tamaños estándar para componentes
 */
export type ComponentSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

/**
 * Estados visuales de componentes
 */
export type ComponentState = 'default' | 'loading' | 'error' | 'success' | 'disabled';

// ===== TYPE GUARDS =====

/**
 * Type guard para verificar si un objeto es un Libro
 */
export const isLibro = (obj: any): obj is Libro => {
  return obj && typeof obj === 'object' && 
         typeof obj.titulo === 'string' && 
         typeof obj.autor === 'string' &&
         typeof obj.disponible === 'boolean';
};

/**
 * Type guard para verificar si un objeto es un Usuario
 */
export const isUsuario = (obj: any): obj is Usuario => {
  return obj && typeof obj === 'object' && 
         typeof obj.nombre === 'string' && 
         typeof obj.email === 'string';
};

/**
 * Type guard para verificar si un objeto es un Prestamo
 */
export const isPrestamo = (obj: any): obj is Prestamo => {
  return obj && typeof obj === 'object' && 
         typeof obj.usuario_id === 'number' && 
         typeof obj.libro_id === 'number' &&
         typeof obj.devuelto === 'boolean';
};

