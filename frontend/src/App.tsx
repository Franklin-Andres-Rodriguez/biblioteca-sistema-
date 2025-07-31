/**
 * App Component - Sistema Biblioteca
 * 
 * Componente principal de la aplicación con Layout test
 * Aplicando principios de Dan Abramov (composition) y Sarah Drasner (visual excellence)
 */

import React from 'react';
import Layout from './components/layout/Layout';

function App() {
  // Breadcrumbs de ejemplo para testing
  const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Libros', current: true }
  ];

  return (
    <Layout 
      title="Dashboard Principal" 
      breadcrumbs={breadcrumbs}
    >
      {/* CONTENIDO DE PRUEBA */}
      <div className="space-y-6">
        {/* Welcome Card */}
        <div className="card card-hover p-6">
          <h2 className="text-2xl font-serif font-semibold text-gray-900 mb-4">
            🎉 ¡Sistema de Biblioteca Funcionando!
          </h2>
          <p className="text-gray-600 mb-4">
            Layout creado aplicando las mejores prácticas de Sarah Drasner (visual excellence) 
            y Dan Abramov (component composition).
          </p>
          <div className="flex space-x-4">
            <button className="btn btn-primary">
              Ver Libros
            </button>
            <button className="btn btn-secondary">
              Gestionar Usuarios
            </button>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="card p-6 text-center">
            <div className="text-3xl font-bold text-biblioteca-600 mb-2">150</div>
            <div className="text-sm text-gray-600">Total Libros</div>
          </div>
          <div className="card p-6 text-center">
            <div className="text-3xl font-bold text-disponible-600 mb-2">45</div>
            <div className="text-sm text-gray-600">Disponibles</div>
          </div>
          <div className="card p-6 text-center">
            <div className="text-3xl font-bold text-vencido-600 mb-2">12</div>
            <div className="text-sm text-gray-600">Vencidos</div>
          </div>
        </div>

        {/* API Connection Test */}
        <div className="card p-6">
          <h3 className="text-lg font-semibold mb-4">🔧 Test de Conexión API</h3>
          <div className="space-y-3">
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-disponible-500 rounded-full"></div>
              <span>Backend Laravel corriendo en puerto 8000</span>
            </div>
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-disponible-500 rounded-full"></div>
              <span>CORS configurado correctamente</span>
            </div>
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-disponible-500 rounded-full"></div>
              <span>Datos de prueba listos</span>
            </div>
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-biblioteca-500 rounded-full animate-pulse"></div>
              <span>Frontend React + TypeScript + Tailwind</span>
            </div>
          </div>
        </div>

        {/* Next Steps */}
        <div className="card p-6 bg-gradient-biblioteca text-white">
          <h3 className="text-lg font-semibold mb-4">🚀 Próximos Pasos</h3>
          <ul className="space-y-2 text-biblioteca-100">
            <li>✅ Layout profesional implementado</li>
            <li>⏳ Conectar con API Laravel</li>
            <li>⏳ Implementar CRUD de libros</li>
            <li>⏳ Dashboard con estadísticas</li>
          </ul>
        </div>
      </div>
    </Layout>
  );
}

export default App;