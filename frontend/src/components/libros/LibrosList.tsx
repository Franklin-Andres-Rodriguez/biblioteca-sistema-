import React, { useState, useEffect } from 'react';
import { 
  BookOpenIcon, 
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  PlusIcon
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/solid';
import CrearLibroModal from './CrearLibroModal';
import { Libro, LibrosFilters } from '../../types';
import apiClient from '../../services/api';

interface LibrosListProps {
  showSearch?: boolean;
  maxItems?: number;
}

interface LibrosState {
  libros: Libro[];
  loading: boolean;
  error: string | null;
  pagination?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

const LibrosList: React.FC<LibrosListProps> = ({ 
  showSearch = true, 
  maxItems 
}) => {
  const [state, setState] = useState<LibrosState>({
    libros: [],
    loading: true,
    error: null
  });

  const [filters, setFilters] = useState<LibrosFilters>({
    per_page: maxItems || 12
  });

  const [searchTerm, setSearchTerm] = useState('');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);

  useEffect(() => {
    fetchLibros();
  }, [filters]);

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      if (searchTerm !== filters.buscar) {
        setFilters(prev => ({
          ...prev,
          buscar: searchTerm || undefined,
          page: 1
        }));
      }
    }, 500);

    return () => clearTimeout(timeoutId);
  }, [searchTerm, filters.buscar]);

  const fetchLibros = async (showRefreshFeedback = false) => {
    try {
      if (showRefreshFeedback) {
        setIsRefreshing(true);
      }
      
      setState(prev => ({ ...prev, loading: true, error: null }));
      
      const queryParams: Record<string, any> = {};
      if (filters.buscar) queryParams.buscar = filters.buscar;
      if (filters.disponible !== undefined) queryParams.disponible = filters.disponible;
      if (filters.genero) queryParams.genero = filters.genero;
      if (filters.per_page) queryParams.per_page = filters.per_page;
      if (filters.page) queryParams.page = filters.page;
      if (filters.sort) queryParams.sort = filters.sort;
      if (filters.direction) queryParams.direction = filters.direction;

      const response = await apiClient.get<Libro[]>('/libros', { params: queryParams });
      
      let paginationData: { current_page: number; last_page: number; total: number; } | undefined;
      
      if (response.meta && 
          typeof response.meta.current_page === 'number' && 
          typeof response.meta.last_page === 'number' && 
          typeof response.meta.total === 'number') {
        paginationData = {
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
          total: response.meta.total
        };
      }

      setState(prev => ({
        ...prev,
        libros: response.data,
        loading: false,
        pagination: paginationData
      }));

    } catch (error: any) {
      setState(prev => ({
        ...prev,
        loading: false,
        error: error.message || 'Error al cargar libros'
      }));
    } finally {
      if (showRefreshFeedback) {
        setIsRefreshing(false);
      }
    }
  };

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
  };

  const handleFilterChange = (key: keyof LibrosFilters, value: any) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
      page: 1
    }));
  };

  const handleRetry = () => {
    fetchLibros();
  };

  const handleOpenCreateModal = () => {
    setIsCreateModalOpen(true);
  };

  const handleCloseCreateModal = () => {
    setIsCreateModalOpen(false);
  };

  const handleCreateSuccess = () => {
    fetchLibros(true);
  };

  const handleRefreshClick = () => {
    fetchLibros(true);
  };

  const renderHeader = () => (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div className="flex-1">
        <h2 className="text-2xl font-serif font-semibold text-gray-900">
          Biblioteca de Libros
        </h2>
        {state.pagination && (
          <p className="text-sm text-gray-600 mt-1">
            {state.pagination.total} libros registrados
          </p>
        )}
      </div>
      
      <div className="flex items-center space-x-3">
        <div className="flex items-center space-x-2">
          <div className={`w-2 h-2 rounded-full ${
            state.loading || isRefreshing ? 'bg-yellow-500 animate-pulse' : 
            state.error ? 'bg-red-500' : 'bg-green-500'
          }`}></div>
          <span className="text-sm text-gray-600">
            {state.loading ? 'Cargando...' : 
             isRefreshing ? 'Actualizando...' :
             state.error ? 'Error' : 'Conectado'}
          </span>
        </div>
        
        <button
          onClick={handleRefreshClick}
          disabled={state.loading || isRefreshing}
          className="btn btn-sm btn-secondary"
          title="Actualizar lista"
        >
          <ArrowPathIcon className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
        </button>
        
        <button
          onClick={handleOpenCreateModal}
          className="btn btn-primary"
          disabled={state.loading}
        >
          <PlusIcon className="w-4 h-4 mr-2" />
          Crear Libro
        </button>
      </div>
    </div>
  );

  const renderSearchBar = () => {
    if (!showSearch) return null;

    return (
      <div className="mb-6">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            className="form-input pl-10 pr-4"
            placeholder="Buscar libros por título o autor..."
            value={searchTerm}
            onChange={handleSearch}
            disabled={state.loading}
          />
        </div>
        
        <div className="mt-4 flex flex-wrap gap-2">
          <button
            onClick={() => handleFilterChange('disponible', undefined)}
            className={`btn btn-sm ${filters.disponible === undefined ? 'btn-primary' : 'btn-secondary'}`}
            disabled={state.loading}
          >
            Todos
          </button>
          <button
            onClick={() => handleFilterChange('disponible', true)}
            className={`btn btn-sm ${filters.disponible === true ? 'btn-success' : 'btn-secondary'}`}
            disabled={state.loading}
          >
            Disponibles
          </button>
          <button
            onClick={() => handleFilterChange('disponible', false)}
            className={`btn btn-sm ${filters.disponible === false ? 'btn-danger' : 'btn-secondary'}`}
            disabled={state.loading}
          >
            Prestados
          </button>
        </div>
      </div>
    );
  };

  const renderLibroCard = (libro: Libro) => (
    <div key={libro.id} className="card card-libro card-hover p-6">
      <div className="flex justify-between items-start mb-4">
        <div className="flex-1">
          <h3 className="text-lg font-serif font-semibold text-gray-900 truncate-2-lines">
            {libro.titulo}
          </h3>
          <p className="text-sm text-gray-600 mt-1">por {libro.autor}</p>
        </div>
        <div className="ml-3">
          {libro.disponible ? (
            <span className="badge badge-disponible">
              <CheckCircleIcon className="w-3 h-3 mr-1" />
              Disponible
            </span>
          ) : (
            <span className="badge badge-vencido">
              <XCircleIcon className="w-3 h-3 mr-1" />
              Prestado
            </span>
          )}
        </div>
      </div>

      <div className="mb-4">
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-biblioteca-100 text-biblioteca-800">
          {libro.genero}
        </span>
      </div>

      {libro.descripcion && (
        <p className="text-sm text-gray-600 mb-4 truncate-3-lines">
          {libro.descripcion}
        </p>
      )}

      {libro.isbn && (
        <p className="text-xs text-gray-500 mb-4 font-mono">
          ISBN: {libro.isbn}
        </p>
      )}

      <div className="flex space-x-2">
        <button className="btn btn-sm btn-primary flex-1">
          Ver Detalles
        </button>
        {libro.disponible && (
          <button className="btn btn-sm btn-success">
            Prestar
          </button>
        )}
      </div>
    </div>
  );

  const renderLoadingState = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {[...Array(6)].map((_, index) => (
        <div key={index} className="card p-6 animate-pulse">
          <div className="loading-skeleton h-4 w-3/4 mb-2"></div>
          <div className="loading-skeleton h-3 w-1/2 mb-4"></div>
          <div className="loading-skeleton h-3 w-1/4 mb-4"></div>
          <div className="loading-skeleton h-16 mb-4"></div>
          <div className="flex space-x-2">
            <div className="loading-skeleton h-8 flex-1"></div>
            <div className="loading-skeleton h-8 w-20"></div>
          </div>
        </div>
      ))}
    </div>
  );

  const renderErrorState = () => (
    <div className="text-center py-12">
      <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-vencido-500 mb-4" />
      <h3 className="text-lg font-medium text-gray-900 mb-2">
        Error al cargar libros
      </h3>
      <p className="text-gray-600 mb-6">
        {state.error}
      </p>
      <button
        onClick={handleRetry}
        className="btn btn-primary"
      >
        <ArrowPathIcon className="w-4 h-4 mr-2" />
        Reintentar
      </button>
    </div>
  );

  const renderEmptyState = () => (
    <div className="text-center py-12">
      <BookOpenIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
      <h3 className="text-lg font-medium text-gray-900 mb-2">
        No se encontraron libros
      </h3>
      <p className="text-gray-600 mb-6">
        {searchTerm || filters.disponible !== undefined
          ? 'Intenta ajustar los filtros de búsqueda'
          : 'No hay libros registrados en el sistema'
        }
      </p>
      
      {!searchTerm && filters.disponible === undefined && (
        <button
          onClick={handleOpenCreateModal}
          className="btn btn-primary"
        >
          <PlusIcon className="w-4 h-4 mr-2" />
          Crear Primer Libro
        </button>
      )}
    </div>
  );

  return (
    <div className="space-y-6">
      {renderHeader()}
      {renderSearchBar()}

      {state.loading ? (
        renderLoadingState()
      ) : state.error ? (
        renderErrorState()
      ) : state.libros.length === 0 ? (
        renderEmptyState()
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {state.libros.map(renderLibroCard)}
        </div>
      )}

      {state.pagination && state.pagination.total > 0 && (
        <div className="text-center text-sm text-gray-600">
          Mostrando {state.libros.length} de {state.pagination.total} libros
        </div>
      )}

      <CrearLibroModal
        isOpen={isCreateModalOpen}
        onClose={handleCloseCreateModal}
        onSuccess={handleCreateSuccess}
      />
    </div>
  );
};

export default LibrosList;