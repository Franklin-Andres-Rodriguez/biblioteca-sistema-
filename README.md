# 📚 Sistema de Biblioteca

**Sistema de gestión de biblioteca desarrollado como demostración técnica, implementando CRUD completo de libros, gestión inteligente de préstamos, y dashboard.**
---

## 🏗️ Arquitectura Técnica

**Stack tecnológico seleccionado siguiendo mejores prácticas de la industria:**

### Backend (API REST)
- **Framework**: Laravel 10.x (PHP)
- **Base de datos**: SQLite (desarrollo) / MySQL (producción)
- **ORM**: Eloquent con relaciones complejas
- **Arquitectura**: Clean Architecture + Repository Pattern
- **Testing**: PHPUnit para pruebas unitarias

### Frontend (SPA)
- **Framework**: React 18.x con Hooks
- **Estado**: Context API + useReducer
- **UI Library**: Tailwind CSS + componentes custom
- **HTTP Client**: Axios con interceptors
- **Testing**: Jest + React Testing Library

### Infraestructura
- **Control de versiones**: Git con commits organizados
- **Containerización**: Docker + Docker Compose
- **Documentación**: Markdown + diagramas técnicos

---

## 🎯 Funcionalidades Implementadas

### ✅ Completadas

#### 📖 Gestión de Libros (CRUD Completo)
- [x] **Crear libros**: Título, autor, género, disponibilidad
- [x] **Listar libros**: Con filtros por género y disponibilidad
- [x] **Actualizar libros**: Información y estado de disponibilidad
- [x] **Eliminar libros**: Con validación de préstamos activos

#### 👥 Gestión de Usuarios
- [x] **Registro de usuarios**: Nombre, email, teléfono
- [x] **Perfil de usuario**: Historial de préstamos
- [x] **Validaciones**: Email único, datos requeridos

#### 📋 Sistema de Préstamos Inteligente
- [x] **Crear préstamos**: Con fechas automáticas
- [x] **Devoluciones**: Actualización de disponibilidad
- [x] **Validaciones de negocio**: Libro disponible, límites por usuario
- [x] **Alertas de vencimiento**: Identificación automática de retrasos

#### 📊 Dashboard de Estadísticas
- [x] **Métricas generales**: Total de libros, préstamos activos/vencidos
- [x] **Top rankings**: Libros más prestados, usuarios más activos
- [x] **Análisis temporal**: Tendencias de préstamos por período
- [x] **Indicadores clave**: Tasa de devolución, tiempo promedio de préstamo

### 🔄 En desarrollo
- [ ] Interface React con componentes modulares
- [ ] Dashboard interactivo con gráficos
- [ ] Sistema de notificaciones automáticas
- [ ] API de búsqueda avanzada

---

## 🚀 Instalación y Configuración

### Prerrequisitos técnicos
```bash
- PHP >= 8.1 con extensiones: OpenSSL, PDO, Mbstring, Tokenizer, XML
- Composer >= 2.0
- Node.js >= 18.x y npm >= 9.x
- Git >= 2.x


## Setup del Backend (Laravel)
# Clonar repositorio
- git clone [URL_DEL_REPOSITORIO]
- cd biblioteca-sistema

# Configurar backend
- cd backend
- composer install
- cp .env.example .env
- php artisan key:generate
- php artisan migrate --seed

# Iniciar servidor de desarrollo
- php artisan serve
# API disponible en: http://localhost:8000

## Setup del Frontend (React)
# En nueva terminal, desde raíz del proyecto
- cd frontend
- npm install
- npm start
# Aplicación disponible en: http://localhost:3000

## Verificación de instalación:
# Probar endpoints de API

```
- curl http://localhost:8000/api/libros
- curl http://localhost:8000/api/usuarios
- curl http://localhost:8000/api/prestamos

```

# Verificar base de datos
```
php artisan tinker
>>> App\Models\Libro::count()
>>> App\Models\Usuario::count()
>>> App\Models\Prestamo::count()
```

## 🧪 Testing y Calidad
### Backend Testing (PHPUnit)
```
cd backend
php artisan test                    # Ejecutar todas las pruebas
php artisan test --coverage        # Con reporte de cobertura
php artisan test --filter=LibroTest # Pruebas específicas
```

## Frontend Testing (Jest)
cd frontend
npm test                           # Modo interactivo
npm run test:coverage             # Con cobertura
npm run test:ci                   # Para integración continua
