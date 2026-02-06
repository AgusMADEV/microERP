# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.0] - 2026-02-06

### 🎉 Lanzamiento Inicial

Primera versión pública de microERP - Sistema ERP auto-generado.

### ✨ Funcionalidades Añadidas

#### Core
- Sistema CRUD completo auto-generado desde esquema MySQL
- Detección automática de tipos de datos y relaciones
- Renderizado inteligente de controles de formulario
- Manejo de claves foráneas (FK) con selectores dinámicos
- Validación de integridad referencial antes de eliminaciones

#### Interfaz de Usuario
- Diseño moderno con gradientes vibrantes (indigo, naranja, rosa)
- Navegación lateral con lista de tablas
- Vista de tarjetas estilizadas para registros
- Animaciones y transiciones fluidas
- Diseño responsive para móviles

#### Visualización de Datos
- Tablas relacionales con expansión de FK
- Gráficos donut (pie charts) en SVG puro
- Sistema de reportes con leyendas interactivas
- Cálculo automático de totales y porcentajes

#### Seguridad
- Sistema de autenticación con sesiones PHP
- Protección contra inyección SQL
- Escape de datos de salida (XSS prevention)
- Validación de operaciones críticas

#### Personalización
- Sistema de plantillas personalizables por tabla
- Dos modos de añadir: genérico y template
- Estilos CSS personalizables por template
- Variables predefinidas disponibles en templates

#### Plantillas Incluidas
- `templates/clientes.php` - Formulario español para clientes
- `templates/customers.php` - Formulario inglés para clientes
- `templates/orders.php` - Formulario complejo para pedidos
- `templates/pedidos.php` - Formulario español para pedidos

### 📁 Estructura del Proyecto
- Arquitectura monolítica en un solo archivo PHP
- Separación de templates en carpeta dedicada
- Documentación completa en README.md
- Guía de contribución en CONTRIBUTING.md

### 🛠️ Tecnologías
- PHP 7.4+ con extensión MySQLi
- MySQL 5.7+ / MariaDB 10.2+
- HTML5 + CSS3 vanilla (sin frameworks)
- SVG para gráficos vectoriales

### 📝 Documentación
- README.md completo con badges y estructura profesional
- Guía de instalación paso a paso
- Documentación de API para plantillas
- Ejemplos de uso y código
- Screenshots placeholders preparados

### 🔒 Seguridad
- Archivo .gitignore configurado
- config.example.php como template
- Credenciales de ejemplo (no reales) en código
- Guía de buenas prácticas de seguridad

---

## [Unreleased]

### 🚀 Próximas Funcionalidades (Roadmap)

#### En Planificación
- [ ] Internacionalización (i18n) - Soporte multi-idioma
- [ ] Exportación a CSV, Excel, PDF
- [ ] Búsqueda y filtros avanzados
- [ ] Paginación para tablas grandes
- [ ] Ordenamiento de columnas
- [ ] Más tipos de gráficos (barras, líneas, área)
- [ ] Dashboard personalizable con widgets
- [ ] Sistema de permisos y roles
- [ ] Historial de cambios (audit log)
- [ ] API REST para integración externa

#### Mejoras Técnicas Planeadas
- [ ] Refactorización a arquitectura MVC
- [ ] Tests unitarios con PHPUnit
- [ ] CI/CD con GitHub Actions
- [ ] Migración a PDO (en lugar de MySQLi)
- [ ] Cache de consultas frecuentes
- [ ] Soporte para múltiples bases de datos
- [ ] Modo dark/light theme

#### UX/UI Planeadas
- [ ] Drag & drop para ordenar registros
- [ ] Edición inline en tablas
- [ ] Acciones masivas (eliminar múltiples)
- [ ] Atajos de teclado
- [ ] Mejor accesibilidad (WCAG 2.1)
- [ ] PWA (Progressive Web App)

---

## Guía de Versiones

### Formato de Versión: MAJOR.MINOR.PATCH

- **MAJOR**: Cambios incompatibles con versiones anteriores
- **MINOR**: Nueva funcionalidad compatible con versiones anteriores
- **PATCH**: Corrección de bugs compatible con versiones anteriores

### Tipos de Cambios

- `Added` - Nueva funcionalidad
- `Changed` - Cambios en funcionalidad existente
- `Deprecated` - Funcionalidad que será eliminada
- `Removed` - Funcionalidad eliminada
- `Fixed` - Corrección de bugs
- `Security` - Correcciones de seguridad

---

## Comparar Versiones

- [Unreleased](https://github.com/agusmaDEV/microERP/compare/v1.0.0...HEAD)
- [1.0.0](https://github.com/agusmaDEV/microERP/releases/tag/v1.0.0) - 2026-02-06

---

**Nota**: Este changelog se actualiza con cada release. Para cambios en desarrollo, ver la rama `develop` o los [commits recientes](https://github.com/agusmaDEV/microERP/commits/main).
