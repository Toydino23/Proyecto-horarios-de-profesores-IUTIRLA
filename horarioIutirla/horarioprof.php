<?php
    include("sidebar.php");
    include("connect.php");
    session_start();

  // --- CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA ---
  $registros_por_pagina = 3; 
  $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
  $offset = ($pagina_actual - 1) * $registros_por_pagina;

  $condicion_busqueda = "";
  $parametros_url = "";

  if(isset($_GET['searchProf']) && $_GET['searchProf'] !== ''){
      $search = mysqli_real_escape_string($conn, $_GET['searchProf']);
      $condicion_busqueda = "WHERE CONCAT_WS(' ', Nombre, Apellido, Cedula, Carrera, Clase) LIKE '%$search%'";
      $parametros_url = "&searchProf=" . urlencode($search) . "&Busqueda=Busqueda";
  }

  // Contar el total de profesores (usando COUNT(DISTINCT) para no contar las clases duplicadas del mismo profesor)
  $sql_total = "SELECT COUNT(DISTINCT Cedula) as total FROM profesor $condicion_busqueda";
  $resultado_total = mysqli_query($conn, $sql_total);
  $fila_total = mysqli_fetch_assoc($resultado_total);
  $total_registros = $fila_total['total'];
  $total_paginas = ceil($total_registros / $registros_por_pagina);

  // Consulta paginada (Agrupamos por Cedula para que el limit traiga 5 profesores exactos)
  $sql = "SELECT Nombre, Apellido, Cedula 
          FROM profesor 
          $condicion_busqueda
          GROUP BY Cedula, Nombre, Apellido
          LIMIT $offset, $registros_por_pagina";
          
  $result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios Profesores</title>
</head>
<body>

  <div id="main">
  
  <div class="login-page">
      <form action="" method="GET" class="form">
      <h2>Busqueda Profesores</h2>
      <br>
      <input type="text" name="searchProf" placeholder="Introduzca datos a buscar" class="inputext" value="<?php echo isset($_GET['searchProf']) ? htmlspecialchars($_GET['searchProf']) : ''; ?>">
      <br>
      <input type="submit" name="Busqueda" value="Busqueda" style="cursor: pointer;">
      <br>
      <input type="button" value="Resetear" onclick="window.location.href='<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>?pagina=1';" style="cursor: pointer;">
    </form>
  </div>

  <div class="login-page" style="width: auto; max-width: 800px;">
    <div class="form" style="max-width: 100%;">
        <h1>Horarios Profesores</h1>
        
        <table style="width: 100%; margin: 0 auto;">
          <tr>
            <th>Profesor</th>
            <th>Horario</th>
          </tr>

          <?php 
          // Como ya agrupamos por Cédula en el SQL, el arreglo y condicional de PHP ya no son obligatorios, 
          // pero los mantenemos por compatibilidad.
          while($row = mysqli_fetch_assoc($result)){ 
          ?>
            <tr>
              <td><?php echo htmlspecialchars($row['Nombre'] . " " . $row['Apellido'] . " - C.I: " . $row['Cedula']); ?></td>
              <td>
                    <div style="display:flex;gap:8px;flex-wrap:wrap; justify-content:center;">
                    <form method="post" action="pdf_profesor.php" style="display:inline-block;margin:0;">
                      <input type="hidden" name="cedula" value="<?php echo htmlspecialchars($row['Cedula']); ?>">
                      <button type="submit" name="export_pdf_prof" style="background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                            Horario
                      </button>
                    </form>
                  </div>
              </td>
            </tr>
          <?php 
          } 
          ?>
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

  </div>

</body>
</html>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
        
body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }

.login-page {
  width: 360px;
  margin: auto;
}
.form {
  position: relative;
  z-index: 1;
  background: #FFFFFF;
  max-width: 360px;
  margin: 0 auto 30px;
  padding: 45px;
  text-align: center;
  box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
}
.form input {
  font-family: "Roboto", sans-serif;
  outline: 0;
  background: #f2f2f2;
  width: 100%;
  border: 0;
  margin: 0 0 15px;
  padding: 15px;
  box-sizing: border-box;
  font-size: 14px;
}
.form button {
  font-family: "Roboto", sans-serif;
  text-transform: uppercase;
  outline: 0;
  background: #720000;
  width: 100%;
  border: 0;
  padding: 15px;
  color: #FFFFFF;
  font-size: 14px;
  transition: all 0.3 ease;
  cursor: pointer;
}
.form button:hover,.form button:active,.form button:focus {
  background: #990101;
}

table{
  border: 4px solid black;
  border-collapse: collapse;
}

th{
  border: 4px solid black;
  text-align: center;
  font-weight: bold;
  background: skyblue;
  border-collapse: collapse;
}

td{
  border: 4px solid black;
  border-collapse: collapse;
  text-align: center;
  padding: 15px
}

/* CSS para Paginación */
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
    font-family: Arial, sans-serif;
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