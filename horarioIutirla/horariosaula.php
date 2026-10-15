<?php
include("sidebar.php");
include("connect.php");
session_start();

// --- CONFIGURACIÓN DE BÚSQUEDA ---
$condicion_busqueda = "WHERE hd.salon != 0";
$parametros_url = "";

if (isset($_GET['buscar_salon']) && $_GET['buscar_salon'] !== '') {
    $salon = (int)$_GET['buscar_salon'];
    $condicion_busqueda .= " AND hd.salon = $salon";
    $parametros_url .= "&buscar_salon=" . urlencode($salon);
}

if (isset($_GET['buscar_periodo']) && $_GET['buscar_periodo'] !== '') {
    $periodo = mysqli_real_escape_string($conn, $_GET['buscar_periodo']);
    $condicion_busqueda .= " AND h.periodo LIKE '%$periodo%'";
    $parametros_url .= "&buscar_periodo=" . urlencode($periodo);
}

if (isset($_GET['buscarnombre']) && $_GET['buscarnombre'] !== '') {
    $nombre = mysqli_real_escape_string($conn, $_GET['buscarnombre']);
    $condicion_busqueda .= " AND h.nombre LIKE '%$nombre%'";
    $parametros_url .= "&buscarnombre=" . urlencode($nombre);
}

// --- CONFIGURACIÓN DE PAGINACIÓN ---
$registros_por_pagina = 5; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros ocupados
$sql_total = "SELECT COUNT(hd.iddetalle) as total 
              FROM horario_detalle hd
              INNER JOIN horario h ON hd.idhorario = h.idhorario
              $condicion_busqueda";
$resultado_total = mysqli_query($conn, $sql_total);
$fila_total = mysqli_fetch_assoc($resultado_total);
$total_registros = $fila_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta paginada uniendo detalles, horario y profesor
$sql = "SELECT 
            hd.salon, 
            h.periodo, 
            h.nombre AS nombre_horario, 
            hd.dia, 
            hd.intervalo, 
            p.Nombre, 
            p.Apellido,
            p.Cedula
        FROM horario_detalle hd
        INNER JOIN horario h ON hd.idhorario = h.idhorario
        LEFT JOIN profesor p ON hd.idprofesor = p.id
        $condicion_busqueda
        ORDER BY h.periodo DESC, hd.salon ASC, hd.dia ASC
        LIMIT $offset, $registros_por_pagina";
        
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Aula</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { text-align: center; color: #720000; margin-bottom: 20px; }

        .form-section {
            background: #FFFFFF;
            padding: 30px;
            margin: 0 auto 30px auto;
            border-radius: 8px;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .search-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-container input[type="text"], 
        .search-container input[type="number"] {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 200px;
        }

        .boton {
            background: #217346; color: white; padding: 10px 20px;
            font-size: 14px; border: none; border-radius: 4px;
            cursor: pointer; font-weight: 600; text-transform: uppercase;
        }
        .boton:hover { background: #1e6b40; }
        .btn-reset { background: #720000; }
        .btn-reset:hover { background: #990101; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th { background: #217346; color: white; padding: 12px; text-align: center; font-weight: 600; border: 1px solid #1e6b40; }
        td { padding: 10px; text-align: center; border: 1px solid #ccc; vertical-align: middle; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #e8f5e9; }

        /* Paginación */
        .paginacion {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .paginacion a {
            padding: 8px 12px;
            background-color: #217346;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .paginacion a.activo {
            background-color: #114227;
            font-weight: bold;
            pointer-events: none;
        }
        .paginacion a:hover {
            background-color: #145230;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="form-section">
        <h2>Horarios Aulas</h2>
        <form action="" method="GET">
            <div class="search-container">
                <input type="number" name="buscar_salon" placeholder="Nro. de Salón (ej. 15)" value="<?php echo isset($_GET['buscar_salon']) ? htmlspecialchars($_GET['buscar_salon']) : ''; ?>">
                <input type="text" name="buscar_periodo" placeholder="Periodo (ej. 2024-1)" value="<?php echo isset($_GET['buscar_periodo']) ? htmlspecialchars($_GET['buscar_periodo']) : ''; ?>">
                <input type="text" name="buscarnombre" placeholder="Nombre del horario" value="<?php echo isset($_GET['buscarnombre']) ? htmlspecialchars($_GET['buscar_periodo']) : ''; ?>">
            </div>
            <div>
                <button type="submit" class="boton">Buscar</button>
                <button type="button" class="boton btn-reset" onclick="window.location.href='<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>';">Resetear</button>
            </div>
        </form>
    </div>

    <div class="form-section" style="padding: 20px;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Aula</th>
                    <th>Periodo</th>
                    <th>Horario (Referencia)</th>
                    <th>Día</th>
                    <th>Intervalo de Tiempo</th>
                    <th>Profesor Asignado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)){ 
                        $nombre_profesor = ($row['Nombre'] || $row['Apellido']) 
                            ? htmlspecialchars($row['Nombre'] . " " . $row['Apellido'] . " (" . $row['Cedula'] . ")") 
                            : "<span style='color:#777;'>Sin profesor</span>";
                        
                        // Reemplazar los espacios múltiples del intervalo por un salto de línea para que se vea mejor
                        $intervaloVisual = preg_replace('/\s{2,}/', '<br>', htmlspecialchars($row['intervalo']));
                ?>
                <tr>
                    <td style="font-weight: bold; font-size: 16px; color: #720000;"><?php echo htmlspecialchars($row['salon']); ?></td>
                    <td><?php echo htmlspecialchars($row['periodo']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_horario']); ?></td>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($row['dia']); ?></td>
                    <td><?php echo $intervaloVisual; ?></td>
                    <td><?php echo $nombre_profesor; ?></td>
                </tr>
                <?php 
                    } 
                } else {
                    echo "<tr><td colspan='6' style='padding: 20px; color: #777;'>No se encontraron salones ocupados con los criterios de búsqueda.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
          <div class="paginacion">
              <?php if ($pagina_actual > 1): ?>
                  <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $parametros_url; ?>">&laquo; Anterior</a>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                  <a href="?pagina=<?php echo $i; ?><?php echo $parametros_url; ?>" class="<?php echo ($i == $pagina_actual) ? 'activo' : ''; ?>">
                      <?php echo $i; ?>
                  </a>
              <?php endfor; ?>

              <?php if ($pagina_actual < $total_paginas): ?>
                  <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $parametros_url; ?>">Siguiente &raquo;</a>
              <?php endif; ?>
          </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>