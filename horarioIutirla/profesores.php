<?php
    include("sidebar.php");
    include("connect.php");
    session_start();

  if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
      header("Location: inicio.php");     
      exit;
  }

  // --- LÓGICA DE ELIMINACIÓN ---
  if (isset($_GET['id'])){
      $s_id = intval($_GET['id']); 

      $buscar_semana = "SELECT idsemana FROM profesor WHERE id='$s_id'";
      $resultado_semana = mysqli_query($conn, $buscar_semana);

      if ($fila = mysqli_fetch_assoc($resultado_semana)) {
          $idsemana = $fila['idsemana'];

          $del_prof_sql = "DELETE FROM profesor WHERE id='$s_id'";
          mysqli_query($conn, $del_prof_sql);

          $del_sem_sql = "DELETE FROM semana WHERE idsemana='$idsemana'";
          mysqli_query($conn, $del_sem_sql);
      }

      $_SESSION['mensaje'] = "Eliminado con exito el profesor";
  }

  $registros_por_pagina = 5; 
  $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
  $offset = ($pagina_actual - 1) * $registros_por_pagina;

  $condicion_busqueda = "";
  $parametros_url = ""; 

  if(isset($_GET['Busqueda'])){
    $search = mysqli_real_escape_string($conn, $_GET['searchProf']);
    // Usamos CONCAT_WS para evitar problemas con campos nulos
    $condicion_busqueda = "WHERE CONCAT_WS(' ', Nombre, Apellido, Cedula, Carrera, Clase) LIKE '%$search%'";
    $parametros_url = "&searchProf=" . urlencode($search) . "&Busqueda=Busqueda";
  }

  // 1. Contar el total de registros para calcular las páginas
  $sql_total = "SELECT COUNT(*) as total FROM profesor INNER JOIN semana ON profesor.idsemana = semana.idsemana $condicion_busqueda";
  $resultado_total = mysqli_query($conn, $sql_total);
  $fila_total = mysqli_fetch_assoc($resultado_total);
  $total_registros = $fila_total['total'];
  $total_paginas = ceil($total_registros / $registros_por_pagina);

  // 2. Consulta principal con LIMIT para la paginación
  $sql = "SELECT profesor.*, semana.Lunes, semana.Martes, semana.Miercoles, semana.Jueves, semana.Viernes
          FROM profesor
          INNER JOIN semana ON profesor.idsemana = semana.idsemana
          $condicion_busqueda
          LIMIT $offset, $registros_por_pagina";
  
  $result = mysqli_query($conn, $sql);

  // --- LÓGICA DE VISUALIZACIÓN (Para que se mantenga en la lista) ---
  // Si hay una búsqueda activa o una página seleccionada, mostramos la lista por defecto
  $mostrar_lista = (isset($_GET['Busqueda']) || isset($_GET['pagina'])) ? true : false;
  
  $estilo_lista = $mostrar_lista ? "display: block;" : "display: none;";
  $estilo_registro = $mostrar_lista ? "display: none;" : "display: block;";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesores</title>
</head>
<body>

<div id="main">
    
  <button onclick="Mostrarprofesores()" class="clickable2" style="width: 300px;">Lista de Profesores / Registrar Profesor</button>

  <div id="Listaprof" style="<?php echo $estilo_lista; ?>">
    
    <br> <br>

    <div>
    <form action="" method="GET" class="form">
      <h2>Busqueda Profesores</h2>
      <br>
      <input type="text" name="searchProf" placeholder="Introduzca datos a buscar" class="inputext" value="<?php echo isset($_GET['searchProf']) ? htmlspecialchars($_GET['searchProf']) : ''; ?>">
      <br>
      <input type="submit" name="Busqueda" value="Busqueda" style="cursor: pointer;">
      <br>
      <input type="button" value="Resetear" onclick="window.location.href='<?php echo strtok($_SERVER['REQUEST_URI'], '?') . '?pagina=1'; ?>';" style="cursor: pointer;">    </form>
    </div>

    <br> <br>


<table style="max-width: 1200px; margin: 0 auto;">
  <tr>
    <th>Nombre</th>
    <th>Apellido</th>
    <th>Cedula</th>
    <th>Carrera</th>
    <th>Clase</th>
    <th>Dias de la semana admisibles</th>
    <?php if($_SESSION['usuario'] === 'admin'){ ?>
      <th>
        Eliminar profesor
      </th>
    <?php } ?>
  </tr>

  <?php while($row = mysqli_fetch_assoc($result)){ ?>
    <tr>
      <td><?php echo $row['Nombre']; ?></td>
      <td><?php echo $row['Apellido']; ?></td>
      <td><?php echo $row['Cedula']; ?></td>
      <td><?php echo $row['Carrera']; ?></td>
      <td><?php echo $row['Clase']; ?></td>
      <td><?php 

        if ($row['Lunes']==1){ echo "Lunes, "; }
        if ($row['Martes']==1){ echo "Martes, "; }        
        if ($row['Miercoles']==1){ echo "Miercoles, "; }        
        if ($row['Jueves']==1){ echo "Jueves, "; }        
        if ($row['Viernes']==1){ echo "Viernes "; }
      ?></td>

      <?php if($_SESSION['usuario'] === 'admin'){ ?>
      <td>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <form method="get" action="profesores.php" style="display:inline-block;margin:0;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este profesor?');">
              
              <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
              
              <button type="submit" name="eliminar" style="background:#dc3545;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                    Eliminar
              </button>

            </form>
          </div>
      </td>
      <?php } ?>
    </tr>
  <?php } ?>
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

<div class="login-page" id="registroprof" style="<?php echo $estilo_registro; ?>">
  <div class="form">
<form class="login-form" action="profesorRegistrar.php" method="POST">
  <h2>Profesores Iutirla</h2>
  <br>
  <?php
  if (!empty($_SESSION['mensaje'])) {
    echo '<p class="notice" style="color: red; font-weight: bold;">' . htmlspecialchars($_SESSION['mensaje']) . '</p>';
    unset($_SESSION['mensaje']);
  }
  ?>
  <br>
  <input type="text" name="nombre" placeholder="nombre">
  <input type="text" name="apellido" placeholder="apellido">
  <input type="number" name="cedula" placeholder="cedula">
  <input type="text" name="carrera" placeholder="carrera">
  <input type="text" name="clase" placeholder="clase">

  <div class="dias">
  <button type="button" class="dia-btn" data-target="lunes">Lunes</button>
  <input type="hidden" name="lunes" id="lunes" value="0">

  <button type="button" class="dia-btn" data-target="martes">Martes</button>
  <input type="hidden" name="martes" id="martes" value="0">

  <button type="button" class="dia-btn" data-target="miercoles">Miércoles</button>
  <input type="hidden" name="miercoles" id="miercoles" value="0">

  <button type="button" class="dia-btn" data-target="jueves">Jueves</button>
  <input type="hidden" name="jueves" id="jueves" value="0">

  <button type="button" class="dia-btn" data-target="viernes">Viernes</button>
  <input type="hidden" name="viernes" id="viernes" value="0">
  </div>
  <br>
  <button>Registrar</button>
</form>
  </div>
</div>

</div>

</body>
</html>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
        
body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }

h1 { text-align: center; 
  color: #217346; 
  margin-bottom: 20px; 
  font-size: 28px; }

.form {
  position: relative;
  z-index: 1;
  background: #FFFFFF;
  max-width: 360px;
  margin: 0 auto;
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
  -webkit-transition: all 0.3 ease;
  transition: all 0.3 ease;
  cursor: pointer;
}

.form .message {
  margin: 15px 0 0;
  color: #b3b3b3;
  font-size: 12px;
}
.form .message a {
  color: #720000;
  text-decoration: none;
}

.container {
  position: relative;
  z-index: 1;
  max-width: 300px;
  margin: 0 auto;
}
.container:before, .container:after {
  content: "";
  display: block;
  clear: both;
}
.container .info {
  margin: 50px auto;
  text-align: center;
}
.container .info h1 {
  margin: 0 0 15px;
  padding: 0;
  font-size: 36px;
  font-weight: 300;
  color: #1a1a1a;
}
.container .info span {
  color: #4d4d4d;
  font-size: 12px;
}
.container .info span a {
  color: #000000;
  text-decoration: none;
}
.container .info span .fa {
  color: #EF3B3A;
}

.clickable2 {
    cursor: pointer;
    width: 150px;
    height: 30px;
    border-radius: 8px;
    margin: 20px;
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

.dias{
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin: 15px 0;
}

.dia-btn{
  font-family: Roboto, sans-serif;
  border: 0;
  padding: 10px 14px;
  border-radius: 8px;
  cursor: pointer;
  background: #e0e0e0;
  color: #333;
  transition: 0.3s ease;
  box-shadow: 0 0 0 rgba(76,175,80,0);
}

.dia-btn.active{
  background: #990101;
  color: #fff;
  box-shadow: 0 0 12px 3px rgba(76,175,80,0.85);
}

/* ESTILOS DE LA PAGINACIÓN */
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

<script>
function Mostrarprofesores() {
  var x = document.getElementById("registroprof");
  var y = document.getElementById("Listaprof");
  if (x.style.display === "none") {
    x.style.display = "block";
    y.style.display = "none";
  } else {
    x.style.display = "none";
    y.style.display = "block";
  }
}

document.querySelectorAll('.dia-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const target = this.dataset.target;
    const input = document.getElementById(target);

    this.classList.toggle('active');
    input.value = this.classList.contains('active') ? '1' : '0';
  });
});
</script>