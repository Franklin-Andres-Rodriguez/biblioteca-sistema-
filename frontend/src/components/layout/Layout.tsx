/**
 * Layout Component - Sistema Biblioteca

 * Features implementadas:
 * - Responsive sidebar navigation
 * - Header con breadcrumbs y user actions
 * - Main content area con scroll independiente
 * - Mobile-first responsive design
 * - Accessibility features (ARIA, keyboard navigation)
 * - Loading states y error boundaries preparadas
 * 
 * @author Sistema Biblioteca - Layout Foundation
 * @version 1.0.0
 */

import React, { useState, useEffect } from 'react';
import { 
  BookOpenIcon, 
  UsersIcon, 
  ClipboardDocumentListIcon,
  ChartBarIcon,
  Bars3Icon,
  XMarkIcon,
  HomeIcon,
  CogIcon
} from '@heroicons/react/24/outline';

// ===== TYPES & INTERFACES =====

interface LayoutProps {
  children: React.ReactNode;
  title?: string;
  breadcrumbs?: BreadcrumbItem[];
}

interface BreadcrumbItem {
  label: string;
  href?: string;
  current?: boolean;
}

interface NavigationItem {
  name: string;
  href: string;
  icon: React.ComponentType<React.SVGProps<SVGSVGElement>>;
  count?: number;
  current?: boolean;
}

// ===== NAVIGATION CONFIGURATION =====

const navigation: NavigationItem[] = [
  { 
    name: 'Dashboard', 
    href: '/', 
    icon: HomeIcon,
    current: true 
  },
  { 
    name: 'Libros', 
    href: '/libros', 
    icon: BookOpenIcon,
    count: 0 // Se actualizará dinámicamente
  },
  { 
    name: 'Usuarios', 
    href: '/usuarios', 
    icon: UsersIcon,
    count: 0 
  },
  { 
    name: 'Préstamos', 
    href: '/prestamos', 
    icon: ClipboardDocumentListIcon,
    count: 0 
  },
  { 
    name: 'Estadísticas', 
    href: '/estadisticas', 
    icon: ChartBarIcon 
  },
  { 
    name: 'Configuración', 
    href: '/configuracion', 
    icon: CogIcon 
  }
];

// ===== LAYOUT COMPONENT =====

const Layout: React.FC<LayoutProps> = ({ 
  children, 
  title = 'Sistema de Biblioteca',
  breadcrumbs = []
}) => {
  // ===== STATE MANAGEMENT =====
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [currentPath, setCurrentPath] = useState('/');

  // ===== EFFECTS =====
  useEffect(() => {
    // Simular detección de ruta actual (en app real sería React Router)
    setCurrentPath(window.location.pathname);
  }, []);

  // ===== EVENT HANDLERS =====
  const toggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  const closeSidebar = () => {
    setSidebarOpen(false);
  };

  // Manejar navegación (placeholder - en app real sería React Router)
  const handleNavigation = (href: string) => {
    console.log(`Navegando a: ${href}`);
    setCurrentPath(href);
    closeSidebar();
  };

  // ===== RENDER HELPERS =====

  /**
   * Renderizar breadcrumbs con navegación
   */
  const renderBreadcrumbs = () => {
    if (breadcrumbs.length === 0) return null;

    return (
      <nav className="flex mb-4" aria-label="Breadcrumb">
        <ol className="flex items-center space-x-4">
          <li>
            <div>
              <button
                onClick={() => handleNavigation('/')}
                className="text-gray-400 hover:text-gray-500 transition-colors"
              >
                <HomeIcon className="flex-shrink-0 h-5 w-5" aria-hidden="true" />
                <span className="sr-only">Inicio</span>
              </button>
            </div>
          </li>
          {breadcrumbs.map((item, index) => (
            <li key={index}>
              <div className="flex items-center">
                <svg
                  className="flex-shrink-0 h-5 w-5 text-gray-300"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  aria-hidden="true"
                >
                  <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                </svg>
                {item.href && !item.current ? (
                  <button
                    onClick={() => handleNavigation(item.href!)}
                    className="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
                  >
                    {item.label}
                  </button>
                ) : (
                  <span className="ml-4 text-sm font-medium text-gray-900" aria-current="page">
                    {item.label}
                  </span>
                )}
              </div>
            </li>
          ))}
        </ol>
      </nav>
    );
  };

  /**
   * Renderizar elemento de navegación
   */
  const renderNavItem = (item: NavigationItem) => {
    const isCurrent = currentPath === item.href;
    
    return (
      <button
        key={item.name}
        onClick={() => handleNavigation(item.href)}
        className={`
          group flex items-center px-2 py-2 text-sm font-medium rounded-md w-full text-left
          transition-all duration-200
          ${isCurrent
            ? 'bg-biblioteca-100 text-biblioteca-900 border-r-2 border-biblioteca-500'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          }
        `}
        aria-current={isCurrent ? 'page' : undefined}
      >
        <item.icon
          className={`
            mr-3 flex-shrink-0 h-6 w-6 transition-colors
            ${isCurrent ? 'text-biblioteca-500' : 'text-gray-400 group-hover:text-gray-500'}
          `}
          aria-hidden="true"
        />
        <span className="flex-1">{item.name}</span>
        {item.count !== undefined && (
          <span className={`
            ml-3 inline-block py-0.5 px-3 text-xs font-medium rounded-full
            ${isCurrent 
              ? 'bg-biblioteca-200 text-biblioteca-800' 
              : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200'
            }
          `}>
            {item.count}
          </span>
        )}
      </button>
    );
  };

  // ===== MAIN RENDER =====
  return (
    <div className="h-screen flex overflow-hidden bg-gray-50">
      {/* MOBILE SIDEBAR OVERLAY */}
      {sidebarOpen && (
        <div 
          className="fixed inset-0 flex z-40 md:hidden"
          role="dialog"
          aria-modal="true"
        >
          <div 
            className="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity"
            onClick={closeSidebar}
            aria-hidden="true"
          />
          
          {/* MOBILE SIDEBAR */}
          <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white shadow-xl">
            <div className="absolute top-0 right-0 -mr-12 pt-2">
              <button
                type="button"
                className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                onClick={closeSidebar}
              >
                <span className="sr-only">Cerrar sidebar</span>
                <XMarkIcon className="h-6 w-6 text-white" aria-hidden="true" />
              </button>
            </div>
            
            {/* MOBILE SIDEBAR CONTENT */}
            <div className="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
              <div className="flex-shrink-0 flex items-center px-4">
                <BookOpenIcon className="h-8 w-8 text-biblioteca-500" />
                <h1 className="ml-2 text-xl font-serif font-semibold text-gray-900">
                  Biblioteca
                </h1>
              </div>
              <nav className="mt-5 px-2 space-y-1">
                {navigation.map(renderNavItem)}
              </nav>
            </div>
          </div>
        </div>
      )}

      {/* DESKTOP SIDEBAR */}
      <div className="hidden md:flex md:flex-shrink-0">
        <div className="flex flex-col w-64">
          <div className="flex flex-col h-0 flex-1 border-r border-gray-200 bg-white shadow-sm">
            {/* LOGO */}
            <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
              <div className="flex items-center flex-shrink-0 px-4">
                <BookOpenIcon className="h-8 w-8 text-biblioteca-500" />
                <h1 className="ml-2 text-xl font-serif font-semibold text-gray-900">
                  Biblioteca
                </h1>
              </div>
              
              {/* NAVIGATION */}
              <nav className="mt-5 flex-1 px-2 space-y-1">
                {navigation.map(renderNavItem)}
              </nav>
            </div>
            
            {/* SIDEBAR FOOTER */}
            <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <div className="h-8 w-8 rounded-full bg-biblioteca-500 flex items-center justify-center">
                    <span className="text-sm font-medium text-white">AD</span>
                  </div>
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-gray-700">Admin</p>
                  <p className="text-xs text-gray-500">admin@biblioteca.com</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* MAIN CONTENT */}
      <div className="flex flex-col w-0 flex-1 overflow-hidden">
        {/* HEADER */}
        <div className="relative z-10 flex-shrink-0 flex h-16 bg-white shadow border-b border-gray-200">
          <button
            type="button"
            className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-biblioteca-500 md:hidden"
            onClick={toggleSidebar}
          >
            <span className="sr-only">Abrir sidebar</span>
            <Bars3Icon className="h-6 w-6" aria-hidden="true" />
          </button>
          
          <div className="flex-1 px-4 flex justify-between items-center">
            <div className="flex-1">
              <h1 className="text-2xl font-serif font-semibold text-gray-900">
                {title}
              </h1>
              {renderBreadcrumbs()}
            </div>
            
            {/* HEADER ACTIONS */}
            <div className="ml-4 flex items-center md:ml-6 space-x-3">
              {/* Notifications placeholder */}
              <button
                type="button"
                className="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-biblioteca-500"
              >
                <span className="sr-only">Ver notificaciones</span>
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-5-5-5 5h5z" />
                </svg>
              </button>
              
              {/* User menu placeholder */}
              <div className="relative">
                <button
                  type="button"
                  className="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-biblioteca-500"
                >
                  <span className="sr-only">Abrir menú de usuario</span>
                  <div className="h-8 w-8 rounded-full bg-biblioteca-500 flex items-center justify-center">
                    <span className="text-sm font-medium text-white">AD</span>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* MAIN CONTENT AREA */}
        <main className="flex-1 relative overflow-y-auto focus:outline-none">
          <div className="py-6">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
              {children}
            </div>
          </div>
        </main>
      </div>
    </div>
  );
};

export default Layout;