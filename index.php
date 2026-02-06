<?php
session_start();

// Parámetros de conexión
$db_host = "localhost";
$db_name = "tu_base_de_datos";
$db_user = "tu_usuario";
$db_pass = "tu_contraseña";

// Credenciales iniciales
$usuario_valido     = "tu_usuario";
$contrasena_valida  = "tu_contraseña";

$login_error = "";

// Logout
if (isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header("Location: ?");
  exit;
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {
  $user = $_POST['usuario']     ?? '';
  $pass = $_POST['contrasena']  ?? '';

  if ($user === $usuario_valido && $pass === $contrasena_valida) {
    $_SESSION['usuario'] = $user;
    header("Location: ?");
    exit;
  } else {
    $login_error = "Usuario o contraseña incorrectos";
  }
}

$logged_in = isset($_SESSION['usuario']);

// Solo conectamos a la base de datos si hay sesión iniciada
if ($logged_in) {
  $conexion = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
  if(!$conexion){
    die("Error de conexión: ".mysqli_connect_error());
  }
}

/**
 * FKs salientes desde una tabla
 */
function obtener_claves_foraneas($conexion, $tabla, $bd){
  $fk = [];
  $sql = "
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '".mysqli_real_escape_string($conexion,$bd)."'
      AND TABLE_NAME = '".mysqli_real_escape_string($conexion,$tabla)."'
      AND REFERENCED_TABLE_NAME IS NOT NULL
  ";
  $resultado = mysqli_query($conexion, $sql);
  if($resultado){
    while($fila = mysqli_fetch_assoc($resultado)){
      $fk[$fila['COLUMN_NAME']] = [
        'tabla'   => $fila['REFERENCED_TABLE_NAME'],
        'columna' => $fila['REFERENCED_COLUMN_NAME']
      ];
    }
  }
  return $fk;
}

/**
 * Metadatos de columnas de una tabla.
 */
function obtener_meta_columnas($conexion, $tabla, $bd){
  $meta = [];
  $sql = "
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
           COLUMN_KEY, EXTRA, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '".mysqli_real_escape_string($conexion,$bd)."'
      AND TABLE_NAME = '".mysqli_real_escape_string($conexion,$tabla)."'
  ";
  $resultado = mysqli_query($conexion, $sql);
  if($resultado){
    while($fila = mysqli_fetch_assoc($resultado)){
      $meta[$fila['COLUMN_NAME']] = $fila;
    }
  }
  return $meta;
}

/**
 * Obtener nombre de la columna PK (primer PRIMARY KEY definido).
 */
function obtener_pk_columna($conexion, $tabla, $bd){
  $sql = "
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '".mysqli_real_escape_string($conexion,$bd)."'
      AND TABLE_NAME = '".mysqli_real_escape_string($conexion,$tabla)."'
      AND COLUMN_KEY = 'PRI'
    ORDER BY ORDINAL_POSITION
    LIMIT 1
  ";
  $resultado = mysqli_query($conexion, $sql);
  if($resultado && $fila = mysqli_fetch_assoc($resultado)){
    return $fila['COLUMN_NAME'];
  }
  return null;
}

/**
 * Tablas que referencian a una tabla dada (FK entrantes)
 */
function obtener_tablas_que_referencian($conexion, $tabla_referenciada, $bd){
  $refs = [];
  $sql = "
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '".mysqli_real_escape_string($conexion,$bd)."'
      AND REFERENCED_TABLE_NAME = '".mysqli_real_escape_string($conexion,$tabla_referenciada)."'
  ";
  $resultado = mysqli_query($conexion, $sql);
  if($resultado){
    while($fila = mysqli_fetch_assoc($resultado)){
      $refs[] = [
        'tabla'                => $fila['TABLE_NAME'],
        'columna'              => $fila['COLUMN_NAME'],
        'columna_referenciada' => $fila['REFERENCED_COLUMN_NAME']
      ];
    }
  }
  return $refs;
}

/**
 * Control de formulario adecuado para una columna NO FK.
 */
function render_input_para_columna($nombre_columna, $meta_columna, $valor_actual = ""){
  $data_type   = strtolower($meta_columna['DATA_TYPE']);
  $column_type = strtolower($meta_columna['COLUMN_TYPE']);
  $html = "";

  // Etiqueta
  $html .= "<label>".$nombre_columna."</label>";

  // tinyint(1) -> checkbox
  if($data_type === 'tinyint' && strpos($column_type, '(1)') !== false){
    $checked = ($valor_actual == 1 || $valor_actual === "1") ? "checked" : "";
    $html .= "<input type='checkbox' name='".$nombre_columna."' value='1' ".$checked.">";
    return $html;
  }

  switch($data_type){
    // Textos
    case 'varchar':
    case 'char':
    case 'tinytext':
    case 'text':
    case 'mediumtext':
    case 'longtext':
      $html .= "<input type='text' name='".$nombre_columna."' placeholder='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."'>";
      break;

    // Números enteros
    case 'int':
    case 'integer':
    case 'tinyint':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'year':
      $html .= "<input type='number' name='".$nombre_columna."' placeholder='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."' step='1'>";
      break;

    // Números decimales
    case 'decimal':
    case 'numeric':
    case 'float':
    case 'double':
    case 'real':
      $html .= "<input type='number' name='".$nombre_columna."' placeholder='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."' step='any'>";
      break;

    // Fechas
    case 'date':
      $html .= "<input type='date' name='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."'>";
      break;

    case 'datetime':
    case 'timestamp':
      $valor = str_replace(" ", "T", $valor_actual);
      $html .= "<input type='datetime-local' name='".$nombre_columna."' value='".htmlspecialchars($valor,ENT_QUOTES)."'>";
      break;

    case 'time':
      $html .= "<input type='time' name='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."'>";
      break;

    // ENUM y SET -> select
    case 'enum':
    case 'set':
      $html .= "<select name='".$nombre_columna."'>";
      $html .= "<option value=''>-- seleccionar --</option>";
      if(preg_match_all("/'([^']*)'/", $meta_columna['COLUMN_TYPE'], $matches)){
        foreach($matches[1] as $opcion){
          $selected = ($valor_actual == $opcion) ? "selected" : "";
          $html .= "<option value='".htmlspecialchars($opcion,ENT_QUOTES)."' ".$selected.">".$opcion."</option>";
        }
      }
      $html .= "</select>";
      break;

    // Otros tipos -> text genérico
    default:
      $html .= "<input type='text' name='".$nombre_columna."' placeholder='".$nombre_columna."' value='".htmlspecialchars($valor_actual,ENT_QUOTES)."'>";
      break;
  }

  return $html;
}

/**
 * Renderizar tabla HTML de resultados (para informes).
 */
function render_tabla_html_con_links($conexion, $tabla, $rows, $bd){
  if(!$rows || count($rows) === 0){
    echo "<p class='no-data'>Sin datos.</p>";
    return;
  }
  $foreignKeysLocal = obtener_claves_foraneas($conexion, $tabla, $bd);

  echo "<table class='report-table'>";
  $first = $rows[0];
  echo "<tr>";
  foreach($first as $k => $_){
    echo "<th>".$k."</th>";
  }
  echo "</tr>";

  foreach($rows as $fila){
    echo "<tr>";
    foreach($fila as $clave=>$valor){
      if(isset($foreignKeysLocal[$clave]) && $valor !== null && $valor !== ''){
        $fk = $foreignKeysLocal[$clave];
        $tabla_fk   = $fk['tabla'];
        $columna_fk = $fk['columna'];

        $sql_fk = "
          SELECT *
          FROM ".$tabla_fk."
          WHERE ".$columna_fk." = '".mysqli_real_escape_string($conexion,$valor)."'
          LIMIT 1
        ";
        $res_fk = mysqli_query($conexion, $sql_fk);
        if($res_fk && $fila_fk = mysqli_fetch_assoc($res_fk)){
          $partes = [];
          foreach($fila_fk as $k2=>$v2){
            $partes[] = $v2;
          }
          $texto_celda = implode(" | ", $partes);
          echo "<td>".htmlspecialchars($texto_celda,ENT_QUOTES)."</td>";
        }else{
          echo "<td>".htmlspecialchars($valor,ENT_QUOTES)."</td>";
        }
      }else{
        echo "<td>".htmlspecialchars($valor,ENT_QUOTES)."</td>";
      }
    }
    echo "</tr>";
  }

  echo "</table>";
}

/**
 * Renderiza un gráfico de tipo donut (pie chart) como SVG.
 */
function render_pie_chart($segmentos, $chart_id){
  $total = 0;
  foreach($segmentos as $s){
    $total += $s['total'];
  }
  if($total <= 0){
    echo "<p class='no-data'>Sin datos.</p>";
    return;
  }

  $acumulado = 0.0;

  echo "<div class='chart-pie-wrapper'>";
  echo "<div class='chart-pie'>";
  echo "<svg viewBox='0 0 42 42' class='donut'>";

  echo "<circle class='donut-ring' cx='21' cy='21' r='15.915'></circle>";

  $index = 0;
  foreach($segmentos as $s){
    $valor = $s['total'];
    $percent = ($valor / $total) * 100.0;
    $dasharray = $percent." ".(100 - $percent);
    $dashoffset = 25 - $acumulado;

    echo "<circle class='donut-segment segment-".$index."' cx='21' cy='21' r='15.915' ";
    echo "stroke-dasharray='".$dasharray."' stroke-dashoffset='".$dashoffset."'></circle>";

    $acumulado += $percent;
    $index++;
  }

  echo "</svg>";
  echo "</div>";

  echo "<ul class='chart-legend'>";
  $index = 0;
  foreach($segmentos as $s){
    $label = htmlspecialchars($s['label'],ENT_QUOTES);
    $valor = (int)$s['total'];
    echo "<li>";
    echo "<span class='legend-color segment-".$index."'></span>";
    echo "<span class='legend-label'>".$label."</span>";
    echo "<span class='legend-value'>(".$valor.")</span>";
    echo "</li>";
    $index++;
  }
  echo "</ul>";
  echo "</div>";
}
?>
<!doctype html>
<html lang="es">
  <head>
    <title>microERP</title>
    <meta charset="utf-8">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap'); 
      :root{
        --margen: 20px;
        --color_primario: #665191;
        --color_secundario: #f0f4ff;
        --color_nav_fondo: #003f5c;
        --color_main_fondo: #f5f7ff;
        --color_tabla_header: #665191;
        --color_tabla_borde: #a05195;
        --color_tabla_fila_par: #fafbff;
        --color_tabla_fila_impar: #ffffff;
        --radio: 8px;

        --chart-color-0: #003f5c;
        --chart-color-1: #2f4b7c;
        --chart-color-2: #665191;
        --chart-color-3: #a05195;
        --chart-color-4: #d45087;
        --chart-color-5: #f95d6a;
        --chart-color-6: #ff7c43;
        --chart-color-7: #ffa600;
      }
      *{
        box-sizing:border-box;
      }
      html,body{
        width:100%;
        height:100%;
        padding:0;
        margin:0;
        font-family:ubuntu,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      }
      body{
        display:flex;
        background: linear-gradient(135deg, #f5f7ff 0%, #fff5f8 50%, #fffaf0 100%);
        color:#222;
        position:relative;
      }
      body::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        height:300px;
        background: linear-gradient(180deg, rgba(102,81,145,0.05) 0%, transparent 100%);
        pointer-events:none;
        z-index:0;
      }

      /* NAV LATERAL */
      nav{
        flex:1;
        min-width:240px;
        max-width:320px;
        padding:var(--margen);
        display:flex;
        flex-direction:column;
        gap:var(--margen);
        background: linear-gradient(180deg, #003f5c 0%, #2f4b7c 50%, #665191 100%);
        box-shadow:8px 0 40px rgba(0,63,92,0.4), inset -1px 0 0 rgba(255,255,255,0.1);
        color:white;
        position:relative;
        z-index:2;
      }
      nav::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        bottom:0;
        background: radial-gradient(circle at 20% 20%, rgba(255,166,0,0.1), transparent 50%);
        pointer-events:none;
      }
      #corporativo{
        display:flex;
        color:white;
        gap:calc(var(--margen)/2);
        align-items:center;
        justify-content:space-between;
        padding-bottom:var(--margen);
        border-bottom:1px solid rgba(255,255,255,0.15);
      }
      #corporativo .brand-mark {
          width: 40px;
          height: 40px;
          border-radius: 12px;
          background: radial-gradient(circle at 30% 25%, #ffa600 0%, transparent 50%), 
                      radial-gradient(circle at 70% 75%, #f95d6a 0%, transparent 50%), 
                      linear-gradient(135deg, #665191 0%, #d45087 100%);
          border: 2px solid rgba(255,166,0,.6);
          box-shadow: 0 8px 30px rgba(255,166,0,0.4), 0 0 20px rgba(249,93,106,0.3), inset 0 1px 0 rgba(255,255,255,0.3);
          animation: pulse 3s ease-in-out infinite;
          position:relative;
      }
      #corporativo .brand-mark::before{
          content:'';
          position:absolute;
          inset:-2px;
          border-radius:12px;
          background:linear-gradient(45deg, #ffa600, #f95d6a, #d45087, #665191);
          opacity:0;
          filter:blur(10px);
          transition:opacity 0.3s;
          z-index:-1;
      }
      #corporativo a:hover .brand-mark::before{
          opacity:0.6;
      }
      @keyframes pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
      }
      #corporativo p{
        font-size:26px;
        margin:0;
        font-weight:700;
        letter-spacing:1px;
        text-shadow:0 0 6px rgba(0,0,0,0.3);
      }
      .logout{
        background: linear-gradient(135deg, #f95d6a 0%, #ff7c43 100%);
        color:white;
        padding:8px 16px;
        border-radius:999px;
        text-decoration:none;
        font-size:12px;
        font-weight:700;
        border:2px solid rgba(255,255,255,0.3);
        transition:all 0.3s ease;
        box-shadow:0 4px 15px rgba(249,93,106,0.4);
        text-shadow:0 1px 2px rgba(0,0,0,0.2);
      }
      .logout:hover{
        background: linear-gradient(135deg, #ff7c43 0%, #ffa600 100%);
        transform:translateY(-2px);
        box-shadow:0 6px 25px rgba(255,166,0,0.5);
      }
      .login-user-label{
        font-size:11px;
        opacity:0.85;
        margin-right:6px;
      }

      nav>button{
        background:rgba(255,255,255,0.05);
        color:var(--color_secundario);
        padding:12px 16px;
        border-radius:12px;
        border:1px solid rgba(255,255,255,0.1);
        display:flex;
        justify-content: space-between;
        align-items:center;
        cursor:pointer;
        transition:all 0.3s ease;
        position:relative;
        overflow:hidden;
      }
      nav>button::before{
        content:'';
        position:absolute;
        left:0;
        top:0;
        height:100%;
        width:3px;
        background:linear-gradient(180deg, #ffa600, #f95d6a);
        opacity:0;
        transition:opacity 0.3s;
      }
      nav>button:hover{
        background:rgba(255,255,255,0.12);
        border-color:rgba(255,166,0,0.5);
        transform:translateX(4px);
        box-shadow:0 4px 20px rgba(255,166,0,0.2);
      }
      nav>button:hover::before{
        opacity:1;
      }
      .activo{
        background: linear-gradient(90deg, rgba(255,166,0,0.2) 0%, rgba(249,93,106,0.15) 100%);
        border:2px solid rgba(255,166,0,0.6);
        transform: translateX(6px);
        color: #ffa600;
        width: 115%;
        box-shadow:0 4px 25px rgba(255,166,0,0.3), inset 0 1px 0 rgba(255,255,255,0.2);
        font-weight:700;
      }
      .activo::before{
        opacity:1;
        width:4px;
      }
      .activo:hover{
        background: linear-gradient(90deg, rgba(255,166,0,0.3) 0%, rgba(249,93,106,0.2) 100%);
        box-shadow:0 6px 30px rgba(255,166,0,0.4);
      }
      nav button a{
        text-decoration:none;
        color:inherit;
        font-size:13px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:0.5px;
      }
      .anadir{
        width: 26px;
        height: 26px;
        background: linear-gradient(135deg, #ffa600 0%, #ff7c43 100%);
        border-radius: 50%;
        line-height: 26px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 3px 15px rgba(255,166,0,0.5), 0 0 0 3px rgba(255,166,0,0.2);
        color: white;
        margin-left:8px;
        transition:all 0.3s ease;
        border:2px solid rgba(255,255,255,0.3);
      }
      .anadir:hover{
        transform:scale(1.15) rotate(90deg);
        box-shadow: 0 5px 25px rgba(255,166,0,0.7), 0 0 0 5px rgba(255,166,0,0.3);
      }
      .anadir-template{
        background: linear-gradient(135deg, #f95d6a 0%, #d45087 100%);
        box-shadow: 0 3px 15px rgba(249,93,106,0.5), 0 0 0 3px rgba(249,93,106,0.2);
      }
      .anadir-template:hover{
        box-shadow: 0 5px 25px rgba(249,93,106,0.7), 0 0 0 5px rgba(249,93,106,0.3);
      }

      /* MAIN / DASHBOARD */
      main{
        flex:6;
        padding:var(--margen);
        overflow:auto;
        background-color:var(--color_main_fondo);
        position:relative;
      }
      main::before{
        content:"";
        position:absolute;
        inset:0;
        background-color:transparent;
        pointer-events:none;
        z-index:-1;
      }
      h2{
        margin-top:0;
        margin-bottom:10px;
        background: linear-gradient(135deg, #665191 0%, #d45087 50%, #ffa600 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight:800;
        filter: drop-shadow(0 2px 4px rgba(102,81,145,0.2));
      }

      /* HEADER PRINCIPAL EN MAIN */
      .main-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:var(--margen);
      }
      .main-title{
        font-size:24px;
        font-weight:800;
        background: linear-gradient(135deg, #003f5c 0%, #665191 50%, #d45087 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        filter: drop-shadow(0 2px 8px rgba(102,81,145,0.3));
        letter-spacing:0.5px;
      }
      .main-actions{
        display:flex;
        align-items:center;
        gap:8px;
      }
      .view-toggle{
        border:2px solid rgba(102,81,145,0.3);
        background:transparent;
        color:#665191;
        padding:8px 16px;
        border-radius:999px;
        font-size:11px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.8px;
        cursor:pointer;
        transition:all 0.3s ease;
        position:relative;
        overflow:hidden;
      }
      .view-toggle::before{
        content:'';
        position:absolute;
        inset:0;
        background:linear-gradient(135deg, #665191, #d45087);
        opacity:0;
        transition:opacity 0.3s;
      }
      .view-toggle:hover{
        border-color:#665191;
        transform:translateY(-2px);
        box-shadow:0 4px 15px rgba(102,81,145,0.3);
      }
      .view-toggle.active{
        background:linear-gradient(135deg, #665191 0%, #a05195 100%);
        color:white;
        border-color:transparent;
        box-shadow:0 4px 20px rgba(102,81,145,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
      }
      .view-toggle.active:hover{
        box-shadow:0 6px 25px rgba(102,81,145,0.5);
      }

      /* TABLAS */
      main table{
        width:100%;
        border:1px solid var(--color_tabla_borde);
        border-collapse:collapse;
        border-radius:var(--radio);
        overflow:hidden;
        background-color:var(--color_tabla_fila_impar);
        box-shadow:0 4px 14px rgba(0,0,0,0.06);
      }
      main table tr:nth-child(even){
        background-color:var(--color_tabla_fila_par);
      }
      main table tr:hover{
        background-color:#e4e6ff;
      }
      main table td{
        padding:10px 12px;
        vertical-align:top;
        font-size:13px;
      }
      main table th{
        background:linear-gradient(180deg, #665191 0%, #a05195 100%);
        padding:12px 14px;
        color:white;
        text-align:left;
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:1px;
        font-weight:700;
        box-shadow:inset 0 -2px 0 rgba(255,166,0,0.3);
        position:relative;
      }
      main table th::after{
        content:'';
        position:absolute;
        bottom:0;
        left:0;
        right:0;
        height:2px;
        background:linear-gradient(90deg, transparent, #ffa600, transparent);
      }

      .report-table{
        margin-bottom:var(--margen);
      }
      .no-data{
        font-size:13px;
        color:#666;
      }

      /* ACCIONES */
      .eliminar,
      .editar,
      .reportar{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:24px;
        height:24px;
        border-radius:999px;
        text-decoration:none;
        color:white;
        margin-left:4px;
        font-size:12px;
        box-shadow:0 0 6px rgba(0,0,0,0.25);
        transition:transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
      }
      .eliminar{
        background:linear-gradient(135deg, #f95d6a 0%, #d45087 100%);
        box-shadow:0 3px 12px rgba(249,93,106,0.4);
      }
      .editar{
        background:linear-gradient(135deg, #665191 0%, #a05195 100%);
        box-shadow:0 3px 12px rgba(102,81,145,0.4);
      }
      .editar-template{
        background:linear-gradient(135deg, #ff7c43 0%, #ffa600 100%);
        box-shadow:0 3px 12px rgba(255,166,0,0.4);
      }
      .reportar{
        background:linear-gradient(135deg, #2f4b7c 0%, #003f5c 100%);
        box-shadow:0 3px 12px rgba(47,75,124,0.4);
      }
      .eliminar:hover,
      .editar:hover,
      .reportar:hover{
        transform:translateY(-2px) scale(1.1);
        box-shadow:0 6px 20px rgba(0,0,0,0.4);
      }
      .eliminar:hover{
        box-shadow:0 6px 20px rgba(249,93,106,0.6);
      }
      .editar:hover{
        box-shadow:0 6px 20px rgba(102,81,145,0.6);
      }
      .editar-template:hover{
        box-shadow:0 6px 20px rgba(255,166,0,0.6);
      }
      .reportar:hover{
        box-shadow:0 6px 20px rgba(47,75,124,0.6);
      }

      @keyframes aparece{
        0%{opacity:0;transform:translateX(-30px);}
        100%{opacity:1;transform:translateX(0px);}
      }

      /* FORMULARIOS */
      form{
        columns:2;
        gap:var(--margen);
        margin-top:10px;
      }
      form label{
        display:block;
        font-weight:600;
        margin-bottom:4px;
        font-size:12px;
        color:#444;
      }
      form input,
      form select{
        width:100%;
        padding:10px 12px;
        box-sizing:border-box;
        margin-bottom:var(--margen);
        border:1px solid rgba(75,0,130,0.3);
        border-radius:var(--radio);
        background-color:#ffffff;
        font-size:13px;
        transition:border 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
      }
      form input:focus,
      form select:focus{
        outline:none;
        border-color:var(--color_primario);
        box-shadow:0 0 0 2px rgba(75,0,130,0.15);
        background-color:#ffffff;
      }
      form input[type=checkbox]{
        width:auto;
        padding:0;
        margin-top:5px;
        box-shadow:none;
      }
      form input[type=submit]{
        background-color:var(--color_primario);
        color:white;
        cursor:pointer;
        border:none;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:0.8px;
        box-shadow:0 4px 10px rgba(75,0,130,0.4);
      }
      form input[type=submit]:hover{
        box-shadow:0 5px 14px rgba(75,0,130,0.6);
        transform:translateY(-1px);
      }

      /* LOGIN */
      .login-box{
        max-width:420px;
        margin:80px auto;
        background-color:#ffffff;
        padding:var(--margen);
        border-radius:16px;
        border:1px solid rgba(75,0,130,0.2);
        box-shadow:0 14px 40px rgba(0,0,0,0.18);
        column-count:1;
        position:relative;
        overflow:hidden;
      }
      .login-box h2{
        margin-top:0;
        margin-bottom:var(--margen);
        color:var(--color_primario);
      }
      .login-box form{
        columns:1;
      }
      .login-error{
        background-color:#ffdddd;
        border:1px solid #cc0000;
        color:#660000;
        padding:10px;
        border-radius:var(--radio);
        margin-bottom:var(--margen);
        font-size:14px;
      }

      /* DASHBOARD / PIE CHARTS */
      .dashboard-intro{
        margin-bottom:var(--margen);
        font-size:14px;
        color:#555;
        max-width:700px;
      }
      .charts-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(360px,1fr));
        gap:var(--margen);
      }
      .chart{
        background-color:#ffffff;
        border-radius:var(--radio);
        border:1px solid rgba(75,0,130,0.18);
        padding:var(--margen);
        box-shadow:0 6px 18px rgba(0,0,0,0.08);
        position:relative;
        overflow:hidden;
      }
      .chart h3{
        margin-top:0;
        margin-bottom:8px;
        font-size:15px;
        color:var(--color_primario);
      }
      .chart-subtitle{
        font-size:11px;
        color:#777;
        margin-bottom:8px;
      }

      .chart-pie-wrapper{
        display:flex;
        align-items:center;
        gap:12px;
      }
      .chart-pie{
        width:120px;
        height:120px;
        flex:0 0 auto;
      }
      .chart-pie svg{
        width:100%;
        height:100%;
        transform:rotate(-90deg);
      }
      .donut-ring{
        fill:none;
        stroke:#e0e2ff;
        stroke-width:10;
      }
      .donut-segment{
        fill:none;
        stroke-width:10;
      }
      .segment-0{ stroke:var(--chart-color-0); }
      .segment-1{ stroke:var(--chart-color-1); }
      .segment-2{ stroke:var(--chart-color-2); }
      .segment-3{ stroke:var(--chart-color-3); }
      .segment-4{ stroke:var(--chart-color-4); }
      .segment-5{ stroke:var(--chart-color-5); }
      .segment-6{ stroke:var(--chart-color-6); }
      .segment-7{ stroke:var(--chart-color-7); }

      .chart-legend{
        list-style:none;
        padding:0;
        margin:0;
        font-size:11px;
        color:#555;
        flex:1 1 auto;
      }
      .chart-legend li{
        display:flex;
        align-items:center;
        margin-bottom:4px;
      }
      .legend-color{
        width:10px;
        height:10px;
        border-radius:50%;
        margin-right:6px;
        flex:0 0 auto;
      }
      .legend-color.segment-0{ background-color:var(--chart-color-0); }
      .legend-color.segment-1{ background-color:var(--chart-color-1); }
      .legend-color.segment-2{ background-color:var(--chart-color-2); }
      .legend-color.segment-3{ background-color:var(--chart-color-3); }
      .legend-color.segment-4{ background-color:var(--chart-color-4); }
      .legend-color.segment-5{ background-color:var(--chart-color-5); }
      .legend-color.segment-6{ background-color:var(--chart-color-6); }
      .legend-color.segment-7{ background-color:var(--chart-color-7); }
      .legend-label{
        flex:1 1 auto;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
      }
      .legend-value{
        margin-left:4px;
        color:#333;
      }

      .back-link{
        margin-bottom:var(--margen);
        display:inline-block;
        text-decoration:none;
        color:var(--color_primario);
        font-size:13px;
        padding:4px 8px;
        border-radius:999px;
        background-color:#dadfff;
      }
      .back-link::before{
        content:"← ";
      }
      .subsection{
        margin-top:var(--margen);
      }
      .subsection h3{
        margin-bottom:6px;
        color:#333;
      }
      .subsection h4{
        margin:8px 0 4px;
        font-size:13px;
        color:#555;
      }

      /* VISTAS TABLA / TARJETAS */
      .vista-activa{display:block;}
      .vista-oculta{display:none;}

      .cards-container{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:var(--margen);
      }
      .card-registro{
        background-color:#ffffff;
        border-radius:var(--radio);
        border:1px solid rgba(75,0,130,0.18);
        padding:var(--margen);
        box-shadow:0 6px 18px rgba(0,0,0,0.08);
        display:flex;
        flex-direction:column;
        justify-content:space-between;
      }
      .card-fields{
        margin-bottom:10px;
      }
      .card-field{
        margin-bottom:6px;
        font-size:12px;
      }
      .card-field-label{
        font-weight:600;
        color:#555;
        margin-bottom:2px;
      }
      .card-field-value{
        color:#222;
        word-break:break-word;
      }
      .card-actions{
        align-self:flex-end;
        margin-top:8px;
      }
      .card-actions .eliminar,
      .card-actions .editar,
      .card-actions .reportar{
        margin-left:0;
        margin-right:4px;
      }

      @media (max-width:900px){
        body{flex-direction:column;}
        nav{
          flex:none;
          max-width:none;
          box-shadow:0 4px 12px rgba(0,0,0,0.2);
        }
        .chart-pie-wrapper{
          flex-direction:column;
          align-items:flex-start;
        }
      }
      #corporativo a{color:inherit;text-decoration:none;}
    </style>
  </head>
  <body>
  
    <nav>
      <div id="corporativo">
        <a href="?">
          <div style="display:flex;align-items:center;gap:calc(var(--margen)/2);">
            <div class="brand-mark" aria-hidden="true"></div>
            <p>agusmaDEV</p>
          </div>
        </a>
      </div>

      <?php
        // Listado de tablas SOLO si está logueado
        if ($logged_in && isset($conexion) && $conexion){
          $resultado = mysqli_query($conexion, "SHOW TABLES;");
          $tabla_actual_nav = isset($_GET['tabla']) ? $_GET['tabla'] : "";
          while($fila = mysqli_fetch_assoc($resultado)){
            $nombre_tabla = array_values($fila)[0];
            $clase = ($nombre_tabla == $tabla_actual_nav) ? "activo" : "";
            $template_file_nav = __DIR__ . "/templates/".$nombre_tabla.".php";
            $tiene_template_nav = file_exists($template_file_nav);

            echo "<button class='".$clase."'>";
              echo "<a href='?tabla=".$nombre_tabla."'>".$nombre_tabla."</a>";
              if($nombre_tabla == $tabla_actual_nav){
                if($tiene_template_nav){
                  echo "<div class='dos_botones'><a href='?operacion=añadir&tabla=".$tabla_actual_nav."' class='anadir anadir-normal'>+</a>";
                  echo "<a href='?operacion=añadir&tabla=".$tabla_actual_nav."&template=1' class='anadir anadir-template'>+</a></div>";
                }else{
                  echo "<a href='?operacion=añadir&tabla=".$tabla_actual_nav."' class='anadir anadir-normal'>+</a>";
                }
              }
            echo "</button>";
          }
        }
      ?>
    </nav>
    <main>
      <?php
        // Si NO está logueado -> login
        if(!$logged_in){
          echo "<div class='login-box'>";
          echo "<h2>Acceso a microERP</h2>";
          if($login_error){
            echo "<div class='login-error'>".$login_error."</div>";
          }
          echo "<form method='POST' action=''>";
          echo "<input type='hidden' name='accion' value='login'>";
          echo "<input type='text' name='usuario' placeholder='Usuario' autofocus>";
          echo "<input type='password' name='contrasena' placeholder='Contraseña'>";
          echo "<input type='submit' value='Entrar'>";
          echo "</form>";
          echo "<p style='font-size:12px;color:#555;margin-top:10px;'>Usuario inicial: <b>agusmadev</b> · Contraseña: <b>agusmadev</b></p>";
          echo "</div>";
        } else {

          // HEADER PRINCIPAL DINÁMICO
          $tabla_header     = $_GET['tabla']     ?? null;
          $operacion_header = $_GET['operacion'] ?? '';
          $page_title = "Dashboard de microERP";

          if($tabla_header){
            switch($operacion_header){
              case 'añadir':
                $page_title = "Añadir registro en ".$tabla_header;
                break;
              case 'editar':
                $page_title = "Editar registro en ".$tabla_header;
                break;
              case 'report':
                $page_title = "Informe de ".$tabla_header;
                break;
              default:
                $page_title = "Listado de ".$tabla_header;
            }
          }

          echo "<header class='main-header'>";
          echo "<div class='main-title'>".$page_title."</div>";
          echo "<div class='main-actions'>";

          if($tabla_header && $operacion_header === ''){
            echo "<button type='button' id='btn-vista-tabla' class='view-toggle active'>Tabla</button>";
            echo "<button type='button' id='btn-vista-tarjetas' class='view-toggle'>Tarjetas</button>";
          }

          echo "<span class='login-user-label'>".htmlspecialchars($_SESSION['usuario'])."</span>";
          echo "<a href='?logout=1' class='logout'>Salir</a>";
          echo "</div>";
          echo "</header>";

          // Lógica del microERP
          if(isset($_GET['tabla'])){
            $tabla      = $_GET['tabla'];
            $operacion  = $_GET['operacion'] ?? '';
            $id         = isset($_GET['id']) ? $_GET['id'] : null;

            $foreignKeys  = obtener_claves_foraneas($conexion, $tabla, $db_name);
            $columnMeta   = obtener_meta_columnas($conexion, $tabla, $db_name);
            $primaryKey   = obtener_pk_columna($conexion, $tabla, $db_name);

            // Gestión de plantillas
            $templates_dir        = __DIR__ . "/templates";
            $template_file        = $templates_dir . "/".$tabla.".php";
            $hay_template_tabla   = file_exists($template_file);
            $usar_template        = (isset($_GET['template']) && $_GET['template'] === '1' && $hay_template_tabla);

            // Eliminación
            if($operacion === "eliminar" && $id !== null && $primaryKey){
              $id_sql = "'".mysqli_real_escape_string($conexion,$id)."'";
              mysqli_query(
                $conexion,
                "DELETE FROM ".$tabla." WHERE `".$primaryKey."` = ".$id_sql.";"
              );
              $operacion = '';
            }

            // AÑADIR
            if($operacion === "añadir"){
              // Procesar inserción
              if($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['accion']) || $_POST['accion'] !== 'login')){
                $columnas = [];
                $valores  = [];

                foreach($columnMeta as $nombre_columna => $meta_columna){
                  if($primaryKey && $nombre_columna === $primaryKey){ continue; }

                  $data_type   = strtolower($meta_columna['DATA_TYPE']);
                  $column_type = strtolower($meta_columna['COLUMN_TYPE']);

                  if($data_type === 'tinyint' && strpos($column_type,'(1)') !== false){
                    $valor = isset($_POST[$nombre_columna]) ? 1 : 0;
                    $columnas[] = "`".$nombre_columna."`";
                    $valores[]  = intval($valor);
                    continue;
                  }

                  if(isset($_POST[$nombre_columna]) && $_POST[$nombre_columna] !== ''){
                    $columnas[] = "`".$nombre_columna."`";
                    $valores[]  = "'".mysqli_real_escape_string($conexion,$_POST[$nombre_columna])."'";
                  }
                }

                if(count($columnas) > 0){
                  $sql_insert = "INSERT INTO ".$tabla." (".implode(",",$columnas).") VALUES (".implode(",",$valores).")";
                  mysqli_query($conexion, $sql_insert);
                }
              }

              echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";

              $form_action   = "?operacion=añadir&tabla=".$tabla;
              if($usar_template){ $form_action .= "&template=1"; }

              if($usar_template){
                $template_mode = 'insert';
                $fila_actual   = null;
                include $template_file;
              }else{
                echo "<form action='".$form_action."' method='POST'>";

                foreach($columnMeta as $nombre_columna => $meta_columna){
                  if($primaryKey && $nombre_columna === $primaryKey){ continue; }

                  if(isset($foreignKeys[$nombre_columna])){
                    $fk = $foreignKeys[$nombre_columna];
                    $tabla_fk   = $fk['tabla'];
                    $columna_fk = $fk['columna'];

                    echo "<label>".$nombre_columna."</label>";
                    echo "<select name='".$nombre_columna."'>";
                    echo "<option value=''>-- seleccionar --</option>";

                    $res_fk = mysqli_query($conexion, "SELECT * FROM ".$tabla_fk.";");
                    if($res_fk){
                      while($fila_fk = mysqli_fetch_assoc($res_fk)){
                        $partes = [];
                        foreach($fila_fk as $k2=>$v2){
                          $partes[] = $v2;
                        }
                        $texto_opcion = implode(" | ", $partes);
                        $value_opcion = $fila_fk[$columna_fk];
                        echo "<option value='".$value_opcion."'>".$texto_opcion."</option>";
                      }
                    }

                    echo "</select>";
                  }else{
                    echo render_input_para_columna($nombre_columna, $meta_columna);
                  }
                }
                echo "<input type='submit' value='Guardar'>";
                echo "</form>";
              }

            // EDITAR
            } elseif($operacion === "editar" && $id !== null){

              if(!$primaryKey){
                echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";
                echo "<p>La tabla <b>".$tabla."</b> no tiene clave primaria definida en el esquema, por lo que no se puede editar un registro concreto.</p>";
              } else {
                $id_sql = "'".mysqli_real_escape_string($conexion,$id)."'";
                $res_actual = mysqli_query($conexion, "SELECT * FROM ".$tabla." WHERE `".$primaryKey."` = ".$id_sql." LIMIT 1;");
                $fila_actual = $res_actual ? mysqli_fetch_assoc($res_actual) : null;

                if(!$fila_actual){
                  echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";
                  echo "<p>No se ha encontrado el registro a editar.</p>";
                } else {
                  // Procesar actualización
                  if($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['accion']) || $_POST['accion'] !== 'login')){
                    $sets = [];

                    foreach($columnMeta as $nombre_columna => $meta_columna){
                      if($nombre_columna === $primaryKey){ continue; }

                      $data_type   = strtolower($meta_columna['DATA_TYPE']);
                      $column_type = strtolower($meta_columna['COLUMN_TYPE']);

                      if($data_type === 'tinyint' && strpos($column_type,'(1)') !== false){
                        $valor = isset($_POST[$nombre_columna]) ? 1 : 0;
                        $sets[] = "`".$nombre_columna."`=".intval($valor);
                        continue;
                      }

                      if(isset($_POST[$nombre_columna])){
                        $valor = $_POST[$nombre_columna];
                        $sets[] = "`".$nombre_columna."` = '".mysqli_real_escape_string($conexion,$valor)."'";
                      }
                    }

                    if(count($sets) > 0){
                      $sql_update = "UPDATE ".$tabla." SET ".implode(", ",$sets)." WHERE `".$primaryKey."` = ".$id_sql;
                      mysqli_query($conexion, $sql_update);
                    }

                    $res_actual = mysqli_query($conexion, "SELECT * FROM ".$tabla." WHERE `".$primaryKey."` = ".$id_sql." LIMIT 1;");
                    $fila_actual = $res_actual ? mysqli_fetch_assoc($res_actual) : $fila_actual;
                  }

                  echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";

                  $form_action = "?operacion=editar&tabla=".$tabla."&id=".urlencode($id);
                  if($usar_template){ $form_action .= "&template=1"; }

                  if($usar_template){
                    $template_mode = 'update';
                    include $template_file;
                  }else{
                    echo "<form action='".$form_action."' method='POST'>";

                    if($primaryKey && isset($fila_actual[$primaryKey])){
                      echo "<label>".$primaryKey."</label>";
                      echo "<input type='text' value='".htmlspecialchars($fila_actual[$primaryKey],ENT_QUOTES)."' disabled>";
                    }

                    foreach($columnMeta as $nombre_columna => $meta_columna){
                      if($nombre_columna === $primaryKey){ continue; }
                      $valor_actual = $fila_actual[$nombre_columna] ?? "";

                      if(isset($foreignKeys[$nombre_columna])){
                        $fk = $foreignKeys[$nombre_columna];
                        $tabla_fk   = $fk['tabla'];
                        $columna_fk = $fk['columna'];

                        echo "<label>".$nombre_columna."</label>";
                        echo "<select name='".$nombre_columna."'>";
                        echo "<option value=''>-- seleccionar --</option>";

                        $res_fk = mysqli_query($conexion, "SELECT * FROM ".$tabla_fk.";");
                        if($res_fk){
                          while($fila_fk = mysqli_fetch_assoc($res_fk)){
                            $partes = [];
                            foreach($fila_fk as $k2=>$v2){
                              $partes[] = $v2;
                            }
                            $texto_opcion = implode(" | ", $partes);
                            $value_opcion = $fila_fk[$columna_fk];
                            $selected = ($valor_actual == $value_opcion) ? "selected" : "";
                            echo "<option value='".$value_opcion."' ".$selected.">".$texto_opcion."</option>";
                          }
                        }

                        echo "</select>";
                      }else{
                        echo render_input_para_columna($nombre_columna, $meta_columna, $valor_actual);
                      }
                    }

                    echo "<input type='submit' value='Guardar cambios'>";
                    echo "</form>";
                  }
                }
              }

            // INFORME
            } elseif($operacion === "report" && $id !== null){

              if(!$primaryKey){
                echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";
                echo "<p>La tabla <b>".$tabla."</b> no tiene clave primaria definida en el esquema, por lo que no se puede generar un informe por registro.</p>";
              } else {
                echo "<a href='?tabla=".$tabla."' class='back-link'>Volver al listado</a>";

                $id_sql = "'".mysqli_real_escape_string($conexion,$id)."'";
                $res_actual = mysqli_query($conexion, "SELECT * FROM ".$tabla." WHERE `".$primaryKey."` = ".$id_sql." LIMIT 1;");
                $fila_actual = $res_actual ? mysqli_fetch_assoc($res_actual) : null;

                if(!$fila_actual){
                  echo "<p>No se ha encontrado el registro solicitado.</p>";
                } else {
                  echo "<div class='subsection'>";
                  echo "<h3>Registro principal</h3>";
                  render_tabla_html_con_links($conexion, $tabla, [$fila_actual], $db_name);
                  echo "</div>";

                  echo "<div class='subsection'>";
                  echo "<h3>Datos referenciados por este registro</h3>";

                  $hay_referenciados = false;
                  foreach($foreignKeys as $columna_fk_local => $info_fk){
                    $valor_fk = $fila_actual[$columna_fk_local] ?? null;
                    if($valor_fk === null || $valor_fk === ''){ continue; }

                    $tabla_fk   = $info_fk['tabla'];
                    $col_fk     = $info_fk['columna'];

                    $sql_fk = "SELECT * FROM ".$tabla_fk." WHERE ".$col_fk." = '".mysqli_real_escape_string($conexion,$valor_fk)."'";
                    $res_fk = mysqli_query($conexion, $sql_fk);
                    if($res_fk && mysqli_num_rows($res_fk) > 0){
                      $hay_referenciados = true;
                      $rows_fk = [];
                      while($row_fk = mysqli_fetch_assoc($res_fk)){
                        $rows_fk[] = $row_fk;
                      }

                      echo "<h4>".$tabla_fk." (por ".$columna_fk_local.")</h4>";
                      render_tabla_html_con_links($conexion, $tabla_fk, $rows_fk, $db_name);
                    }
                  }
                  if(!$hay_referenciados){
                    echo "<p class='no-data'>No hay referencias salientes.</p>";
                  }
                  echo "</div>";

                  echo "<div class='subsection'>";
                  echo "<h3>Datos relacionados que apuntan a este registro</h3>";

                  $refs_entrantes = obtener_tablas_que_referencian($conexion, $tabla, $db_name);
                  if(count($refs_entrantes) === 0){
                    echo "<p class='no-data'>No hay tablas que referencien a esta entidad.</p>";
                  } else {
                    $hay_entrantes = false;
                    foreach($refs_entrantes as $ref){
                      $tabla_hija        = $ref['tabla'];
                      $columna_hija      = $ref['columna'];
                      $col_ref_padre     = $ref['columna_referenciada'];

                      $valor_ref_padre = $fila_actual[$col_ref_padre] ?? null;
                      if($valor_ref_padre === null){ continue; }

                      $sql_hija = "SELECT * FROM ".$tabla_hija." WHERE ".$columna_hija." = '".mysqli_real_escape_string($conexion,$valor_ref_padre)."'";
                      $res_hija = mysqli_query($conexion, $sql_hija);
                      if($res_hija && mysqli_num_rows($res_hija) > 0){
                        $hay_entrantes = true;
                        $rows_hija = [];
                        while($fila_hija = mysqli_fetch_assoc($res_hija)){
                          $rows_hija[] = $fila_hija;
                        }

                        echo "<h4>".$tabla_hija." (columna ".$columna_hija.")</h4>";
                        render_tabla_html_con_links($conexion, $tabla_hija, $rows_hija, $db_name);
                      }
                    }
                    if(!$hay_entrantes){
                      echo "<p class='no-data'>No hay registros entrantes que apunten a este registro.</p>";
                    }
                  }

                  echo "</div>";
                }
              }

            // LISTADO
            } else {

              if(!$primaryKey){
                echo "<p class='no-data'>Aviso: la tabla <b>".$tabla."</b> no tiene clave primaria definida. No se podrán editar ni eliminar registros individualmente.</p>";
              }

              $resultado = mysqli_query($conexion, "SELECT * FROM ".$tabla.";");
              $rows = [];
              if($resultado){
                while($fila = mysqli_fetch_assoc($resultado)){
                  $rows[] = $fila;
                }
              }

              if(count($rows) === 0){
                echo "<p class='no-data'>No hay registros en esta tabla.</p>";
              } else {

                // VISTA TABLA
                echo "<div id='vista-tabla' class='vista-activa'>";
                echo "<table>";
                $contador = 0;
                foreach($rows as $fila){
                  if($contador == 0){
                    echo "<tr>";
                      foreach($fila as $clave=>$valor){
                        echo "<th>".$clave."</th>";
                      }
                      echo "<th>Acciones</th>";
                    echo "</tr>";
                  }

                  echo "<tr>";
                  foreach($fila as $clave=>$valor){
                    if(isset($foreignKeys[$clave]) && $valor !== null && $valor !== ''){
                      $fk = $foreignKeys[$clave];
                      $tabla_fk   = $fk['tabla'];
                      $columna_fk = $fk['columna'];

                      $sql_fk = "
                        SELECT *
                        FROM ".$tabla_fk."
                        WHERE ".$columna_fk." = '".mysqli_real_escape_string($conexion,$valor)."'
                        LIMIT 1
                      ";
                      $res_fk = mysqli_query($conexion, $sql_fk);
                      if($res_fk && $fila_fk = mysqli_fetch_assoc($res_fk)){
                        $partes = [];
                        foreach($fila_fk as $k2=>$v2){
                          $partes[] = $v2;
                        }
                        $texto_celda = implode(" | ", $partes);
                        echo "<td>".htmlspecialchars($texto_celda,ENT_QUOTES)."</td>";
                      }else{
                        echo "<td>".htmlspecialchars($valor,ENT_QUOTES)."</td>";
                      }
                    }else{
                      echo "<td>".htmlspecialchars($valor,ENT_QUOTES)."</td>";
                    }
                  }

                  echo "<td>";
                  if($primaryKey && isset($fila[$primaryKey])){
                    $id_fila = $fila[$primaryKey];
                    $id_url  = urlencode($id_fila);
                    echo "<a href='?operacion=editar&tabla=".$tabla."&id=".$id_url."' class='editar'>✏</a>";
                    if($hay_template_tabla){
                      echo "<a href='?operacion=editar&tabla=".$tabla."&id=".$id_url."&template=1' class='editar editar-template'>✏</a>";
                    }
                    echo "<a href='?operacion=report&tabla=".$tabla."&id=".$id_url."' class='reportar'>i</a>";
                    echo "<a href='?operacion=eliminar&tabla=".$tabla."&id=".$id_url."' class='eliminar'>×</a>";
                  } else {
                    echo "<span style='font-size:11px;color:#777;'>—</span>";
                  }
                  echo "</td>";

                  echo "</tr>";
                  $contador++;
                }
                echo "</table>";
                echo "</div>";

                // VISTA TARJETAS
                echo "<div id='vista-tarjetas' class='vista-oculta'>";
                echo "<div class='cards-container'>";
                foreach($rows as $fila){
                  echo "<article class='card-registro'>";
                  echo "<div class='card-fields'>";
                  foreach($fila as $clave=>$valor){
                    echo "<div class='card-field'>";
                    echo "<div class='card-field-label'>".$clave."</div>";

                    $display_value = $valor;

                    if(isset($foreignKeys[$clave]) && $valor !== null && $valor !== ''){
                      $fk = $foreignKeys[$clave];
                      $tabla_fk   = $fk['tabla'];
                      $columna_fk = $fk['columna'];

                      $sql_fk = "
                        SELECT *
                        FROM ".$tabla_fk."
                        WHERE ".$columna_fk." = '".mysqli_real_escape_string($conexion,$valor)."'
                        LIMIT 1
                      ";
                      $res_fk = mysqli_query($conexion, $sql_fk);
                      if($res_fk && $fila_fk = mysqli_fetch_assoc($res_fk)){
                        $partes = [];
                        foreach($fila_fk as $k2=>$v2){
                          $partes[] = $v2;
                        }
                        $display_value = implode(" | ", $partes);
                      }else{
                        $display_value = $valor;
                      }
                    }

                    echo "<div class='card-field-value'>".htmlspecialchars($display_value,ENT_QUOTES)."</div>";
                    echo "</div>";
                  }
                  echo "</div>";

                  echo "<div class='card-actions'>";
                  if($primaryKey && isset($fila[$primaryKey])){
                    $id_fila = $fila[$primaryKey];
                    $id_url  = urlencode($id_fila);
                    echo "<a href='?operacion=editar&tabla=".$tabla."&id=".$id_url."' class='editar'>✏</a>";
                    if($hay_template_tabla){
                      echo "<a href='?operacion=editar&tabla=".$tabla."&id=".$id_url."&template=1' class='editar editar-template'>✏</a>";
                    }
                    echo "<a href='?operacion=report&tabla=".$tabla."&id=".$id_url."' class='reportar'>i</a>";
                    echo "<a href='?operacion=eliminar&tabla=".$tabla."&id=".$id_url."' class='eliminar'>×</a>";
                  }
                  echo "</div>";

                  echo "</article>";
                }
                echo "</div>";
                echo "</div>";
              }
            }
          } else {
            // DASHBOARD INICIAL
            echo "<p class='dashboard-intro'>
              Resumen gráfico de la información categórica y relacional del sistema:
              campos <b>ENUM/SET</b> y campos de <b>clave foránea</b> en todas las tablas, representados como gráficos de tipo donut.
            </p>";

            $resultado = mysqli_query($conexion, "SHOW TABLES;");
            echo "<div class='charts-grid'>";
            while($fila = mysqli_fetch_assoc($resultado)){
              $tabla = array_values($fila)[0];
              $meta  = obtener_meta_columnas($conexion, $tabla, $db_name);
              $fksTabla = obtener_claves_foraneas($conexion, $tabla, $db_name);

              // ENUM/SET
              foreach($meta as $nombre_columna => $meta_columna){
                $data_type = strtolower($meta_columna['DATA_TYPE']);
                if($data_type !== 'enum' && $data_type !== 'set'){ continue; }

                $sql_dist = "
                  SELECT ".$nombre_columna." AS valor, COUNT(*) AS total
                  FROM ".$tabla."
                  GROUP BY ".$nombre_columna."
                  ORDER BY total DESC
                ";
                $res_dist = mysqli_query($conexion, $sql_dist);
                if(!$res_dist || mysqli_num_rows($res_dist) < 1){ continue; }

                $segmentos = [];
                while($fila_dist = mysqli_fetch_assoc($res_dist)){
                  $v = $fila_dist['valor'];
                  $c = (int)$fila_dist['total'];
                  $segmentos[] = [
                    'label' => ($v === null || $v === '' ? '(vacío)' : $v),
                    'total' => $c
                  ];
                }
                if(empty($segmentos)){ continue; }

                echo "<div class='chart'>";
                echo "<h3>".$tabla." · ".$nombre_columna."</h3>";
                echo "<div class='chart-subtitle'>Distribución de valores ENUM/SET</div>";
                render_pie_chart($segmentos, $tabla."_".$nombre_columna."_enum");
                echo "</div>";
              }

              // FKs
              foreach($fksTabla as $columna_fk => $info_fk){
                $tabla_ref = $info_fk['tabla'];
                $col_ref   = $info_fk['columna'];

                $sql_dist_fk = "
                  SELECT ".$columna_fk." AS valor, COUNT(*) AS total
                  FROM ".$tabla."
                  GROUP BY ".$columna_fk."
                  ORDER BY total DESC
                ";
                $res_dist_fk = mysqli_query($conexion, $sql_dist_fk);
                if(!$res_dist_fk || mysqli_num_rows($res_dist_fk) < 1){ continue; }

                $segmentos_fk = [];
                while($fila_dist = mysqli_fetch_assoc($res_dist_fk)){
                  $valor_fk = $fila_dist['valor'];
                  $c        = (int)$fila_dist['total'];

                  if($valor_fk === null || $valor_fk === ''){
                    $label = '(sin valor)';
                  }else{
                    $sql_ref = "
                      SELECT *
                      FROM ".$tabla_ref."
                      WHERE ".$col_ref." = '".mysqli_real_escape_string($conexion,$valor_fk)."'
                      LIMIT 1
                    ";
                    $res_ref = mysqli_query($conexion, $sql_ref);
                    if($res_ref && $fila_ref = mysqli_fetch_assoc($res_ref)){
                      $partes = [];
                      foreach($fila_ref as $k2=>$v2){
                        $partes[] = $v2;
                      }
                      $label = implode(" | ", $partes);
                    }else{
                      $label = 'ID '.$valor_fk.' (huérfano)';
                    }
                  }

                  $segmentos_fk[] = [
                    'label' => $label,
                    'total' => $c
                  ];
                }
                if(empty($segmentos_fk)){ continue; }

                echo "<div class='chart'>";
                echo "<h3>".$tabla." · ".$columna_fk."</h3>";
                echo "<div class='chart-subtitle'>Distribución por referencia a ".$tabla_ref."</div>";
                render_pie_chart($segmentos_fk, $tabla."_".$columna_fk."_fk");
                echo "</div>";
              }
            }
            echo "</div>";
          }
        }
      ?>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var btnTabla     = document.getElementById('btn-vista-tabla');
        var btnTarjetas  = document.getElementById('btn-vista-tarjetas');
        var vistaTabla   = document.getElementById('vista-tabla');
        var vistaTarjetas= document.getElementById('vista-tarjetas');

        if(btnTabla && btnTarjetas && vistaTabla && vistaTarjetas){
          btnTabla.addEventListener('click', function(){
            vistaTabla.classList.add('vista-activa');
            vistaTabla.classList.remove('vista-oculta');
            vistaTarjetas.classList.add('vista-oculta');
            vistaTarjetas.classList.remove('vista-activa');
            btnTabla.classList.add('active');
            btnTarjetas.classList.remove('active');
          });
          btnTarjetas.addEventListener('click', function(){
            vistaTabla.classList.add('vista-oculta');
            vistaTabla.classList.remove('vista-activa');
            vistaTarjetas.classList.add('vista-activa');
            vistaTarjetas.classList.remove('vista-oculta');
            btnTarjetas.classList.add('active');
            btnTabla.classList.remove('active');
          });
        }
      });
    </script>
  </body>
</html>

