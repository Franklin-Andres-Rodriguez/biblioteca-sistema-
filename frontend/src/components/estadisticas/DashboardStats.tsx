import React, { useState, useEffect } from 'react';
import { 
  BookOpen, 
  CheckCircle, 
  Users, 
  Clock, 
  AlertTriangle,
  TrendingUp
} from 'lucide-react';
import  api  from '../../services/api';

// 🎓 Applying Dan Abramov's interface-first design
interface EstadisticasData {
  total_libros: number;
  libros_disponibles: number;
  total_usuarios: number;
  prestamos_activos: number;
  prestamos_vencidos: number;
}

interface StatCardProps {
  title: string;
  value: number;
  icon: React.ReactNode;
  color: string;
  bgColor: string;
  loading?: boolean;
}

// 🎨 Sarah Drasner's micro-animation principles
const StatCard: React.FC<StatCardProps> = ({ 
  title, 
  value, 
  icon, 
  color, 
  bgColor, 
  loading = false 
}) => {
  if (loading) {
    return (
      <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div className="flex items-center justify-between">
          <div className="flex-1">
            <div className="h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
            <div className="h-8 bg-gray-200 rounded animate-pulse w-16"></div>
          </div>
          <div className={`p-3 rounded-full ${bgColor} animate-pulse`}>
            <div className="w-6 h-6 bg-gray-300 rounded"></div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300 hover:scale-105 transform">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <h3 className="text-sm font-medium text-gray-600 mb-1">
            {title}
          </h3>
          <p className={`text-3xl font-bold ${color} transition-colors duration-200`}>
            {value.toLocaleString()}
          </p>
        </div>
        <div className={`p-3 rounded-full ${bgColor} transition-transform duration-200 hover:rotate-12`}>
          <div className={`w-6 h-6 ${color}`}>
            {icon}
          </div>
        </div>
      </div>
    </div>
  );
};

// 🏗️ Main Dashboard Component - Kent C. Dodds' robust state management
const DashboardStats: React.FC = () => {
  const [estadisticas, setEstadisticas] = useState<EstadisticasData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // 🎓 Jonas Schmedtmann's theory-practice integration: useEffect for data fetching
  useEffect(() => {
    const fetchEstadisticas = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const response = await api.get('/estadisticas/dashboard');
        
        // Applying Martin Fowler's defensive programming
        if (response.data?.success && response.data?.data) {
          setEstadisticas(response.data.data);
        } else {
          throw new Error('Formato de respuesta inesperado');
        }
      } catch (err) {
        console.error('Error fetching estadisticas:', err);
        setError('Error al cargar las estadísticas. Intente nuevamente.');
      } finally {
        setLoading(false);
      }
    };

    fetchEstadisticas();
  }, []);

  // 🎨 Robert C. Martin's Clean Code: Early returns for error states
  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
        <h3 className="text-lg font-semibold text-red-800 mb-2">
          Error al cargar estadísticas
        </h3>
        <p className="text-red-600 mb-4">{error}</p>
        <button 
          onClick={() => window.location.reload()}
          className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-200"
        >
          Reintentar
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header Section - Sarah Drasner's visual hierarchy */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TrendingUp className="w-7 h-7 text-blue-600" />
            Dashboard - Estadísticas
          </h2>
          <p className="text-gray-600 mt-1">
            Resumen general del sistema de biblioteca
          </p>
        </div>
        <div className="text-sm text-gray-500">
          Actualizado: {new Date().toLocaleString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: '2-digit'
          })}
        </div>
      </div>

      {/* Stats Grid - Responsive design following mobile-first principles */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <StatCard
          title="Total de Libros"
          value={estadisticas?.total_libros || 0}
          icon={<BookOpen />}
          color="text-blue-600"
          bgColor="bg-blue-100"
          loading={loading}
        />
        
        <StatCard
          title="Libros Disponibles"
          value={estadisticas?.libros_disponibles || 0}
          icon={<CheckCircle />}
          color="text-green-600"
          bgColor="bg-green-100"
          loading={loading}
        />
        
        <StatCard
          title="Total Usuarios"
          value={estadisticas?.total_usuarios || 0}
          icon={<Users />}
          color="text-purple-600"
          bgColor="bg-purple-100"
          loading={loading}
        />
        
        <StatCard
          title="Préstamos Activos"
          value={estadisticas?.prestamos_activos || 0}
          icon={<Clock />}
          color="text-amber-600"
          bgColor="bg-amber-100"
          loading={loading}
        />
        
        <StatCard
          title="Préstamos Vencidos"
          value={estadisticas?.prestamos_vencidos || 0}
          icon={<AlertTriangle />}
          color="text-red-600"
          bgColor="bg-red-100"
          loading={loading}
        />
      </div>

      {/* Quick Insights - Brad Traversy's practical value addition */}
      {estadisticas && !loading && (
        <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
          <h3 className="text-lg font-semibold text-gray-900 mb-3">
            📊 Insights Rápidos
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-green-500 rounded-full"></div>
              <span>
                <strong>{Math.round((estadisticas.libros_disponibles / estadisticas.total_libros) * 100)}%</strong> de libros disponibles
              </span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-amber-500 rounded-full"></div>
              <span>
                <strong>{(estadisticas.prestamos_activos / estadisticas.total_usuarios).toFixed(1)}</strong> préstamos promedio por usuario
              </span>
            </div>
            {estadisticas.prestamos_vencidos > 0 && (
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-red-500 rounded-full"></div>
                <span className="text-red-700">
                  <strong>{estadisticas.prestamos_vencidos}</strong> préstamos requieren atención
                </span>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default DashboardStats;