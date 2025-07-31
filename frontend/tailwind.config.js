/** @type {import('tailwindcss').Config} */

/*
|--------------------------------------------------------------------------
| Tailwind CSS Configuration - Sistema Biblioteca
|--------------------------------------------------------------------------
|
| Configuración optimizada siguiendo las mejores prácticas de:
| - Sarah Drasner: Visual excellence y design systems
| - Tailwind Team: Performance-first configuration
| - Adam Wathan: Utility-first CSS methodology
| - Steve Schoger: UI design principles
|
| Features implementadas:
| - Design system consistente con biblioteca temática
| - Dark mode preparado para futura implementación
| - Responsive design mobile-first
| - Performance optimizations
| - Custom components para la biblioteca
|
*/

module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
    "./public/index.html"
  ],
  
  theme: {
    extend: {
      // Paleta de colores temática de biblioteca
      colors: {
        // Colores primarios (inspirados en bibliotecas clásicas)
        biblioteca: {
          50: '#fef7ee',   // Pergamino muy claro
          100: '#fdedd3',  // Pergamino claro
          200: '#fbd9a5',  // Pergamino medio
          300: '#f8c06d',  // Dorado suave
          400: '#f59e33',  // Dorado
          500: '#f37a0b',  // Dorado intenso (primario)
          600: '#e45e07',  // Cuero oscuro
          700: '#bd4409',  // Cuero muy oscuro
          800: '#973610',  // Madera oscura
          900: '#7c2d12',  // Caoba
          950: '#431407'   // Casi negro
        },
        
        // Colores secundarios (verdes para disponibilidad)
        disponible: {
          50: '#f0fdf4',   // Verde muy claro
          100: '#dcfce7',  // Verde claro
          200: '#bbf7d0',  // Verde suave
          300: '#86efac',  // Verde medio
          400: '#4ade80',  // Verde brillante
          500: '#22c55e',  // Verde (disponible)
          600: '#16a34a',  // Verde oscuro
          700: '#15803d',  // Verde profundo
          800: '#166534',  // Verde muy oscuro
          900: '#14532d',  // Verde casi negro
          950: '#052e16'   // Negro verdoso
        },
        
        // Colores de estado (rojos para vencidos)
        vencido: {
          50: '#fef2f2',   // Rojo muy claro
          100: '#fee2e2',  // Rojo claro
          200: '#fecaca',  // Rojo suave
          300: '#fca5a5',  // Rojo medio
          400: '#f87171',  // Rojo brillante
          500: '#ef4444',  // Rojo (vencido)
          600: '#dc2626',  // Rojo oscuro
          700: '#b91c1c',  // Rojo profundo
          800: '#991b1b',  // Rojo muy oscuro
          900: '#7f1d1d',  // Rojo casi negro
          950: '#450a0a'   // Negro rojizo
        }
      },
      
      // Tipografía optimizada para lectura (biblioteca theme)
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif'],
        'serif': ['Crimson Text', 'serif'], // Para títulos elegantes
        'mono': ['JetBrains Mono', 'monospace']
      },
      
      // Spacing personalizado para componentes de biblioteca
      spacing: {
        '18': '4.5rem',   // 72px
        '72': '18rem',    // 288px
        '84': '21rem',    // 336px
        '96': '24rem'     // 384px
      },
      
      // Shadows temáticas (como libros apilados)
      boxShadow: {
        'libro': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.1)',
        'libro-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
        'card-hover': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
      },
      
      // Animaciones personalizadas
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'bounce-soft': 'bounceSoft 0.6s ease-in-out',
        'pulse-slow': 'pulse 3s infinite'
      },
      
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        },
        bounceSoft: {
          '0%, 100%': { transform: 'translateY(-5%)' },
          '50%': { transform: 'translateY(0)' }
        }
      },
      
      // Breakpoints personalizados
      screens: {
        'xs': '475px',    // Móviles grandes
        '3xl': '1600px'   // Pantallas ultra wide
      }
    },
  },
  
  plugins: [
    // Plugin para forms (formularios elegantes)
    require('@tailwindcss/forms')({
      strategy: 'class' // Solo aplicar cuando se use la clase 'form-input'
    }),
    
    // Plugin para typography (contenido de texto rico)
    require('@tailwindcss/typography'),
    
    // Plugin para aspect ratio (imágenes responsivas)
    require('@tailwindcss/aspect-ratio'),
  ],
  
  // Dark mode preparado para futura implementación
  darkMode: 'class', // Usar clase 'dark' para activar
  
  // Optimizaciones de performance
  corePlugins: {
    // Deshabilitar plugins no utilizados para reducir bundle size
    backdropOpacity: false,
    backgroundOpacity: false,
    borderOpacity: false,
    divideOpacity: false,
    ringOpacity: false,
    textOpacity: false
  }
}