# Guía de Contribución a microERP

¡Gracias por tu interés en contribuir a microERP! 🎉

## 📋 Tabla de Contenidos

- [Código de Conducta](#código-de-conducta)
- [¿Cómo puedo contribuir?](#cómo-puedo-contribuir)
- [Proceso de Desarrollo](#proceso-de-desarrollo)
- [Estándares de Código](#estándares-de-código)
- [Proceso de Pull Request](#proceso-de-pull-request)
- [Reporte de Bugs](#reporte-de-bugs)
- [Sugerencias de Funcionalidades](#sugerencias-de-funcionalidades)

## 📜 Código de Conducta

Este proyecto sigue un código de conducta para garantizar un ambiente acogedor y respetuoso para todos. Al participar, te comprometes a mantener este estándar.

### Nuestros Estándares

✅ **Comportamientos esperados:**
- Usar lenguaje acogedor e inclusivo
- Respetar diferentes puntos de vista y experiencias
- Aceptar críticas constructivas con gracia
- Enfocarse en lo mejor para la comunidad
- Mostrar empatía hacia otros miembros

❌ **Comportamientos inaceptables:**
- Uso de lenguaje o imágenes sexualizadas
- Comentarios insultantes o despectivos
- Acoso público o privado
- Publicar información privada de otros sin permiso
- Conducta que pueda considerarse inapropiada en un entorno profesional

## 🤝 ¿Cómo puedo contribuir?

Hay muchas formas de contribuir a microERP:

### 1. Reportar Bugs
¿Encontraste un error? [Abre un issue](https://github.com/agusmadev/microERP/issues/new?template=bug_report.md)

### 2. Sugerir Mejoras
¿Tienes una idea? [Compártela aquí](https://github.com/agusmadev/microERP/issues/new?template=feature_request.md)

### 3. Mejorar la Documentación
- Corregir errores tipográficos
- Añadir ejemplos
- Traducir a otros idiomas
- Mejorar explicaciones

### 4. Contribuir con Código
- Corregir bugs
- Implementar nuevas funcionalidades
- Optimizar rendimiento
- Mejorar seguridad

### 5. Crear Plantillas
- Diseñar templates para casos de uso comunes
- Compartir ejemplos de formularios personalizados

## 🔧 Proceso de Desarrollo

### Requisitos Previos

- Git instalado
- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.2
- Conocimientos básicos de PHP, HTML, CSS

### Configurar el Entorno de Desarrollo

1. **Fork el repositorio** en GitHub

2. **Clonar tu fork:**
   ```bash
   git clone https://github.com/agusmadev/microERP.git
   cd microERP
   ```

3. **Configurar el remote upstream:**
   ```bash
   git remote add upstream https://github.com/agusmaDEV/microERP.git
   ```

4. **Configurar la base de datos:**
   - Crea una base de datos de prueba
   - Copia `config.example.php` a `config.php`
   - Edita `config.php` con tus credenciales

5. **Crear una rama para tu feature:**
   ```bash
   git checkout -b feature/nombre-descriptivo
   ```

### Workflow de Desarrollo

1. **Mantén tu fork actualizado:**
   ```bash
   git fetch upstream
   git merge upstream/main
   ```

2. **Realiza tus cambios:**
   - Escribe código limpio y comentado
   - Sigue los estándares de código (ver abajo)
   - Prueba exhaustivamente

3. **Commit frecuentes con mensajes claros:**
   ```bash
   git add .
   git commit -m "feat: añadir exportación a CSV"
   ```

4. **Push a tu fork:**
   ```bash
   git push origin feature/nombre-descriptivo
   ```

5. **Abre un Pull Request**

## 📏 Estándares de Código

### PHP

```php
// ✅ BUENO: Nombres descriptivos, indentación clara
function obtener_claves_foraneas($conexion, $tabla, $bd) {
    $fk = [];
    $sql = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?";
    // ...
    return $fk;
}

// ❌ MALO: Nombres crípticos, sin documentación
function get_fk($c,$t,$b){
    $f=[];
    $s="SELECT * FROM X";
    return $f;
}
```

### Convenciones de Nombres

- **Variables**: `$snake_case`
- **Funciones**: `snake_case()`
- **Constantes**: `MAYUSCULAS_CON_GUION_BAJO`
- **Archivos**: `snake_case.php`

### Comentarios

```php
/**
 * Descripción breve de la función
 *
 * Descripción más detallada si es necesario.
 *
 * @param mysqli $conexion Conexión activa a la base de datos
 * @param string $tabla Nombre de la tabla
 * @return array Array asociativo con las claves foráneas
 */
function obtener_claves_foraneas($conexion, $tabla, $bd) {
    // Implementación...
}
```

### HTML/CSS

- Indentación: 2 espacios
- Nombres de clases: `kebab-case`
- Usar comillas dobles para atributos HTML
- CSS: preferir clases sobre IDs para estilos

### Seguridad

✅ **Siempre:**
- Escapar output: `htmlspecialchars($valor, ENT_QUOTES)`
- Escapar SQL: `mysqli_real_escape_string($conexion, $valor)`
- Validar inputs del usuario
- Usar prepared statements cuando sea posible

❌ **Nunca:**
- Concatenar directamente SQL con input del usuario
- Exponer información sensible en errores
- Hardcodear credenciales en el código

## 🔄 Proceso de Pull Request

### Antes de Abrir el PR

- [ ] He probado todos mis cambios localmente
- [ ] El código sigue los estándares del proyecto
- [ ] He añadido comentarios donde sea necesario
- [ ] He actualizado la documentación si es necesario
- [ ] Mi código no genera nuevos warnings/errores

### Template de Pull Request

```markdown
## Descripción
Breve descripción de los cambios realizados.

## Tipo de cambio
- [ ] Bug fix (cambio que corrige un issue)
- [ ] Nueva funcionalidad (cambio que añade funcionalidad)
- [ ] Breaking change (cambio que afecta funcionalidad existente)
- [ ] Mejora de documentación

## ¿Cómo se ha probado?
Describe las pruebas realizadas.

## Checklist
- [ ] Mi código sigue los estándares del proyecto
- [ ] He realizado una auto-revisión de mi código
- [ ] He comentado mi código en áreas complejas
- [ ] He actualizado la documentación
- [ ] Mis cambios no generan nuevos warnings
- [ ] He probado en diferentes navegadores (si aplica)

## Screenshots (si aplica)
```

### Proceso de Revisión

1. Un mantenedor revisará tu PR
2. Puede solicitar cambios o aclaraciones
3. Realiza los cambios solicitados
4. Una vez aprobado, se fusionará con la rama principal

## 🐛 Reporte de Bugs

### Antes de Reportar

1. **Busca en issues existentes** - Puede que ya esté reportado
2. **Verifica que no sea un problema local** - Prueba en un entorno limpio
3. **Actualiza a la última versión** - El bug puede estar ya corregido

### Template de Bug Report

```markdown
**Descripción del bug**
Descripción clara y concisa del problema.

**Pasos para reproducir**
1. Ve a '...'
2. Haz clic en '...'
3. Scroll hasta '...'
4. Ver error

**Comportamiento esperado**
Qué esperabas que sucediera.

**Comportamiento actual**
Qué sucedió en realidad.

**Screenshots**
Si aplica, añade capturas de pantalla.

**Entorno:**
- OS: [ej. Windows 10]
- Navegador: [ej. Chrome 96]
- PHP Version: [ej. 7.4]
- MySQL Version: [ej. 5.7]

**Información adicional**
Cualquier contexto adicional sobre el problema.
```

## 💡 Sugerencias de Funcionalidades

### Template de Feature Request

```markdown
**¿Tu sugerencia está relacionada con un problema?**
Descripción clara del problema. Ej: "Me frustra que..."

**Solución propuesta**
Descripción clara de lo que quieres que suceda.

**Alternativas consideradas**
Descripción de soluciones alternativas que hayas considerado.

**Contexto adicional**
Cualquier otro contexto, capturas, mockups, etc.
```

## 📞 ¿Necesitas Ayuda?

- 💬 [Discussions](https://github.com/agusmaDEV/microERP/discussions) - Para preguntas y conversaciones
- 🐛 [Issues](https://github.com/agusmaDEV/microERP/issues) - Para bugs y features
- 📧 Email: agusmadev@email.com

---

## 🎯 Áreas Prioritarias

Actualmente, estamos especialmente interesados en contribuciones en:

1. **Internacionalización (i18n)** - Soporte multi-idioma
2. **Exportación de datos** - CSV, Excel, PDF
3. **Tests automatizados** - PHPUnit, tests de integración
4. **Mejoras de accesibilidad** - ARIA labels, keyboard navigation
5. **Optimización de rendimiento** - Cache, queries más eficientes
6. **Documentación** - Más ejemplos, tutoriales en video

---

¡Gracias por hacer de microERP un mejor proyecto! 🙌

**Happy Coding! 🚀**
