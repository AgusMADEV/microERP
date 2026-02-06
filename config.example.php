<?php
/**
 * Archivo de configuración de ejemplo para microERP
 * 
 * Instrucciones:
 * 1. Copia este archivo como 'config.php'
 * 2. Rellena los valores con tus credenciales reales
 * 3. Asegúrate de que 'config.php' esté en .gitignore
 */

// =====================================
// CONFIGURACIÓN DE BASE DE DATOS
// =====================================

// Host del servidor de base de datos (generalmente 'localhost')
$db_host = "localhost";

// Nombre de tu base de datos
$db_name = "nombre_de_tu_base_de_datos";

// Usuario de MySQL
$db_user = "tu_usuario_mysql";

// Contraseña de MySQL
$db_pass = "tu_contraseña_mysql";


// =====================================
// CREDENCIALES DE ACCESO AL ERP
// =====================================

// Usuario para acceder al sistema microERP
$usuario_valido = "admin";

// Contraseña para acceder al sistema microERP
$contrasena_valida = "password_seguro_aqui";


// =====================================
// CONFIGURACIÓN ADICIONAL (OPCIONAL)
// =====================================

// Zona horaria (opcional)
date_default_timezone_set('Europe/Madrid');

// Nivel de reporte de errores (para desarrollo)
// Comentar o cambiar en producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de sesión (opcional)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

?>
