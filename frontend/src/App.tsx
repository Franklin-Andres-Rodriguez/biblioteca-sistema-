/**
 * App Component - Sistema Biblioteca
 * 
 * Componente principal aplicando la sabiduría colectiva de:
 * - Dan Abramov: Component composition y state management
 * - Sarah Drasner: Visual excellence y user experience
 * - Brian Holt: Clear progression y maintainable structure
 */

import React, { useState } from 'react';
import Layout from './components/layout/Layout';
import LibrosList from './components/libros/LibrosList';
import DashboardStats from './components/estadisticas/DashboardStats';

interface BreadcrumbItem {
  label: string;
  href?: string;
  current?: boolean;
}

function App() {
  const [currentView, setCurrentView] = useState<'dashboard' | 'libros'>('dashboard');

  // Breadcrumbs dinámicos basados en la vista actual
  const getBreadcrumbs = (): BreadcrumbItem[] => {
    switch (currentView) {
      case 'libros':
        return [
          { label: 'Dashboard', href: '/' },
          { label: 'Libros', current: true }
        ];
      default:
        return [
          { label: 'Dashboard', current: true }
        ];
    }
  };

  const getTitle = (): string => {
    switch (currentView) {
      case 'libros':
        return 'Gestión de Libros';
      default:
        return 'Dashboard Principal';
    }
  };

  const renderDashboard = () => (
    <div className="space-y-6">
      {/* ✨ NUEVO: DashboardStats con API real */}
      <DashboardStats />
      
      {/* Welcome Card - mantenemos la funcionalidad */}
      <div className="card card-hover p-6">
        <h2 className="text-2xl font-serif font-semibold text-gray-900 mb-4">
          🎉 ¡Sistema de Biblioteca Funcionando!
        </h2>
        <p className="text-gray-600 mb-4">
          Dashboard con estadísticas reales desde la API Laravel. 
          Aplicando principios de Sarah Drasner y Dan Abramov.
        </p>
        <div className="flex space-x-4">
          <button 
            className="btn btn-primary"
            onClick={() => setCurrentView('libros')}
          >
            Ver Libros (API Real)
          </button>
          <button className="btn btn-secondary">
            Gestionar Usuarios
          </button>
        </div>
      </div>

      {/* Next Steps - actualizamos el progreso */}
      <div className="card p-6 bg-gradient-biblioteca text-white">
        <h3 className="text-lg font-semibold mb-4">🚀 Progreso Actual</h3>
        <ul className="space-y-2 text-biblioteca-100">
          <li>✅ Layout profesional implementado</li>
          <li>✅ API Service Layer configurado</li>
          <li>✅ TypeScript interfaces definidas</li>
          <li>✅ Dashboard con estadísticas reales funcionando!</li>
          <li>✅ Modal Crear Libro implementado!</li>
          <li>🔄 CRUD completo en desarrollo</li>
          <li>⏳ Gestión de préstamos completa</li>
        </ul>
      </div>
    </div>
  );

  return (
    <Layout 
      title={getTitle()} 
      breadcrumbs={getBreadcrumbs()}
    >
      {currentView === 'dashboard' ? (
        renderDashboard()
      ) : currentView === 'libros' ? (
        <div className="space-y-4">
          <button 
            className="btn btn-secondary"
            onClick={() => setCurrentView('dashboard')}
          >
            ← Volver al Dashboard
          </button>
          <LibrosList showSearch={true} />
        </div>
      ) : null}
    </Layout>
  );
}

// ✅ CORRECCIÓN CRÍTICA: Export correcto
export default App;