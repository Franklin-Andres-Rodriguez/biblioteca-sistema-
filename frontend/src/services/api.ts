/**
 * API Service Client - Sistema Biblioteca
 * 
 * 
 * Features implementadas:
 * - Request/Response interceptors
 * - Error handling centralizado
 * - Loading states management
 * - Type-safe responses
 * - Retry logic para network errors
 * - Development logging
 * 
 * @author Sistema Biblioteca - API Integration Layer
 * @version 1.0.0
 */

import axios, { 
  AxiosInstance, 
  AxiosResponse, 
  AxiosError, 
  AxiosRequestConfig 
} from 'axios';

// ===== TYPES & INTERFACES =====

/**
 * Estructura estándar de respuestas de la API Laravel
 * Basada en JSON API specification
 */
export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
    from?: number;
    to?: number;
    [key: string]: any;
  };
  links?: {
    first?: string;
    last?: string;
    prev?: string;
    next?: string;
  };
}

/**
 * Estructura de errores de la API
 */
export interface ApiError {
  success: false;
  message: string;
  error: string;
  errors?: Record<string, string[]>; // Validation errors
  timestamp?: string;
}

/**
 * Configuración del cliente API
 */
interface ApiClientConfig {
  baseURL: string;
  timeout: number;
  retries: number;
  retryDelay: number;
}

/**
 * Estado de loading para requests
 */
export interface LoadingState {
  [key: string]: boolean;
}

// ===== CONSTANTS =====

const DEFAULT_CONFIG: ApiClientConfig = {
  baseURL: 'http://localhost:8000/api',
  timeout: 10000, // 10 segundos
  retries: 3,
  retryDelay: 1000 // 1 segundo
};

/**
 * HTTP Status codes relevantes para la aplicación
 */
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  CONFLICT: 409,
  UNPROCESSABLE_ENTITY: 422,
  INTERNAL_SERVER_ERROR: 500,
  SERVICE_UNAVAILABLE: 503
} as const;

// ===== API CLIENT CLASS =====

/**
 * Cliente API principal
 * 
 * Implementa el patrón Singleton para mantener una instancia única
 * del cliente HTTP a través de toda la aplicación
 */
class ApiClient {
  private axiosInstance: AxiosInstance;
  private config: ApiClientConfig;
  private loadingStates: LoadingState = {};

  constructor(config: Partial<ApiClientConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };
    this.axiosInstance = this.createAxiosInstance();
    this.setupInterceptors();
  }

  /**
   * Crear instancia de Axios con configuración base
   */
  private createAxiosInstance(): AxiosInstance {
    return axios.create({
      baseURL: this.config.baseURL,
      timeout: this.config.timeout,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest' // Laravel CSRF protection
      },
      // Importante para CORS con cookies/sessions
      withCredentials: false
    });
  }

  /**
   * Configurar interceptors para requests y responses
   * Siguiendo principios de Aspect-Oriented Programming (AOP)
   */
  private setupInterceptors(): void {
    // REQUEST INTERCEPTOR
    this.axiosInstance.interceptors.request.use(
      (config) => {
        // Logging en desarrollo (siguiendo principios de Dan Abramov sobre transparencia)
        if (process.env.NODE_ENV === 'development') {
          console.log(`🚀 API Request: ${config.method?.toUpperCase()} ${config.url}`);
          if (config.data) {
            console.log('📤 Request Data:', config.data);
          }
        }

        // Agregar loading state
        const requestKey = this.getRequestKey(config);
        this.setLoading(requestKey, true);

        return config;
      },
      (error) => {
        console.error('❌ Request Error:', error);
        return Promise.reject(error);
      }
    );

    // RESPONSE INTERCEPTOR
    this.axiosInstance.interceptors.response.use(
      (response: AxiosResponse<ApiResponse>) => {
        // Remover loading state
        const requestKey = this.getRequestKey(response.config);
        this.setLoading(requestKey, false);

        // Logging en desarrollo
        if (process.env.NODE_ENV === 'development') {
          console.log(`✅ API Response: ${response.config.method?.toUpperCase()} ${response.config.url}`);
          console.log('📥 Response Data:', response.data);
        }

        return response;
      },
      async (error: AxiosError<ApiError>) => {
        // Remover loading state
        if (error.config) {
          const requestKey = this.getRequestKey(error.config);
          this.setLoading(requestKey, false);
        }

        // Retry logic para network errors
        if (this.shouldRetry(error)) {
          return this.retryRequest(error);
        }

        // Error handling centralizado
        return this.handleError(error);
      }
    );
  }

  /**
   * Generar key única para identificar requests (loading states)
   */
  private getRequestKey(config: AxiosRequestConfig): string {
    return `${config.method || 'get'}-${config.url || ''}`;
  }

  /**
   * Manejar estados de loading
   */
  private setLoading(key: string, loading: boolean): void {
    this.loadingStates[key] = loading;
  }

  /**
   * Obtener estado de loading para un request específico
   */
  public isLoading(method: string, url: string): boolean {
    const key = `${method.toLowerCase()}-${url}`;
    return this.loadingStates[key] || false;
  }

  /**
   * Determinar si un error debe ser reintentado
   */
  private shouldRetry(error: AxiosError): boolean {
    if (!error.config || (error.config as any).__retryCount >= this.config.retries) {
      return false;
    }

    // Solo reintentar errores de red, no errores de aplicación
    return (
      !error.response || // Network error
      error.response.status >= 500 || // Server errors
      error.response.status === HTTP_STATUS.SERVICE_UNAVAILABLE
    );
  }

  /**
   * Reintentar request con backoff exponencial
   */
  private async retryRequest(error: AxiosError): Promise<AxiosResponse> {
    const config = error.config as any;
    config.__retryCount = (config.__retryCount || 0) + 1;

    // Exponential backoff
    const delay = this.config.retryDelay * Math.pow(2, config.__retryCount - 1);
    
    console.log(`🔄 Retrying request (${config.__retryCount}/${this.config.retries}) in ${delay}ms`);
    
    await new Promise(resolve => setTimeout(resolve, delay));
    
    return this.axiosInstance.request(config);
  }

  /**
   * Manejo centralizado de errores
   */
  private handleError(error: AxiosError<ApiError>): Promise<never> {
    let errorMessage = 'Error de conexión';
    let errorDetails: any = error.message;

    if (error.response) {
      // Error de la API (4xx, 5xx)
      const { status, data } = error.response;
      
      switch (status) {
        case HTTP_STATUS.BAD_REQUEST:
          errorMessage = 'Solicitud inválida';
          break;
        case HTTP_STATUS.UNAUTHORIZED:
          errorMessage = 'Acceso no autorizado';
          break;
        case HTTP_STATUS.FORBIDDEN:
          errorMessage = 'Acceso prohibido';
          break;
        case HTTP_STATUS.NOT_FOUND:
          errorMessage = 'Recurso no encontrado';
          break;
        case HTTP_STATUS.CONFLICT:
          errorMessage = 'Conflicto en la operación';
          break;
        case HTTP_STATUS.UNPROCESSABLE_ENTITY:
          errorMessage = 'Datos de entrada inválidos';
          break;
        case HTTP_STATUS.INTERNAL_SERVER_ERROR:
          errorMessage = 'Error interno del servidor';
          break;
        default:
          errorMessage = `Error del servidor (${status})`;
      }

      errorDetails = data || error.message;
    } else if (error.request) {
      // Error de red
      errorMessage = 'Error de conexión con el servidor';
      errorDetails = 'Verifique su conexión a internet';
    }

    // Logging detallado en desarrollo
    if (process.env.NODE_ENV === 'development') {
      console.error('❌ API Error Details:', {
        message: errorMessage,
        status: error.response?.status,
        data: error.response?.data,
        config: error.config
      });
    }

    // Crear error estructurado
    const structuredError = {
      message: errorMessage,
      status: error.response?.status,
      details: errorDetails,
      originalError: error
    };

    return Promise.reject(structuredError);
  }

  // ===== PUBLIC API METHODS =====

  /**
   * GET request genérico
   */
  async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  /**
   * POST request genérico
   */
  async post<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  /**
   * PUT request genérico
   */
  async put<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  /**
   * PATCH request genérico
   */
  async patch<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.patch<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  /**
   * DELETE request genérico
   */
  async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.delete<ApiResponse<T>>(url, config);
    return response.data;
  }

  /**
   * Health check del servidor
   */
  async healthCheck(): Promise<boolean> {
    try {
      await this.get('/health');
      return true;
    } catch (error) {
      console.error('❌ Health check failed:', error);
      return false;
    }
  }

  /**
   * Obtener todos los estados de loading actuales
   */
  public getLoadingStates(): LoadingState {
    return { ...this.loadingStates };
  }

  /**
   * Limpiar todos los estados de loading
   */
  public clearLoadingStates(): void {
    this.loadingStates = {};
  }
}

// ===== SINGLETON INSTANCE =====

/**
 * Instancia singleton del cliente API
 * Siguiendo el patrón Singleton para mantener consistencia
 */
const apiClient = new ApiClient();

export default apiClient;

// ===== UTILITY FUNCTIONS =====

/**
 * Helper para crear URLs con query parameters
 */
export const buildUrl = (base: string, params?: Record<string, any>): string => {
  if (!params) return base;
  
  const searchParams = new URLSearchParams();
  
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      searchParams.append(key, String(value));
    }
  });
  
  const queryString = searchParams.toString();
  return queryString ? `${base}?${queryString}` : base;
};

/**
 * Helper para formatear errores de validación
 */
export const formatValidationErrors = (errors: Record<string, string[]>): string => {
  return Object.entries(errors)
    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
    .join('; ');
};

/**
 * Type guard para verificar si una respuesta es exitosa
 */
export const isSuccessResponse = <T>(response: any): response is ApiResponse<T> => {
  return response && typeof response === 'object' && response.success === true;
};

/**
 * Type guard para verificar si una respuesta es error
 */
export const isErrorResponse = (response: any): response is ApiError => {
  return response && typeof response === 'object' && response.success === false;
};