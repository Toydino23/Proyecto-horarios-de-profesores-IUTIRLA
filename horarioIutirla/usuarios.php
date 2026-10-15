<?php
session_start();

include("connect.php");

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: inicio.php");
    exit;
}

include("sidebar.php");

$mensaje = '';
$tipoMensaje = '';

if (isset($_GET['id'])) {
    $s_id = intval($_GET['id']);
    $del_sql = "DELETE FROM usuarios WHERE id='$s_id'";
    mysqli_query($conn, $del_sql);
    
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_clave'])) {
    $idUsuario = intval($_POST['id_usuario'] ?? 0);
    $claveActual = trim($_POST['clave_actual'] ?? '');
    $claveNueva = trim($_POST['clave_nueva'] ?? '');
    $claveConfirmar = trim($_POST['clave_confirmar'] ?? '');

    $idSesion = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
    $esAdmin = isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'admin';

    if ($idUsuario <= 0 || $claveActual === '' || $claveNueva === '' || $claveConfirmar === '') {
        $mensaje = "Completa todos los campos.";
        $tipoMensaje = "error";
    } elseif ($claveNueva !== $claveConfirmar) {
        $mensaje = "La nueva clave y su confirmación no coinciden.";
        $tipoMensaje = "error";
    } elseif (!$esAdmin && $idSesion !== $idUsuario) {
        $mensaje = "No tienes permiso para cambiar esta clave.";
        $tipoMensaje = "error";
    } else {
        $sqlCheck = "SELECT contraseña FROM usuarios WHERE id = ?";
        $stmtCheck = mysqli_prepare($conn, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "i", $idUsuario);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);
        $usuarioDB = mysqli_fetch_assoc($resCheck);
        mysqli_stmt_close($stmtCheck);

        if ($usuarioDB) {
            $claveBD = $usuarioDB['contraseña'];

            if ($esAdmin || password_verify($claveActual, $claveBD)) {
                $hashed_password = password_hash($claveNueva, PASSWORD_BCRYPT);

                $sqlUpdate = "UPDATE usuarios SET contraseña = ? WHERE id = ?";
                $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
                mysqli_stmt_bind_param($stmtUpdate, "si", $hashed_password, $idUsuario);
                mysqli_stmt_execute($stmtUpdate);
                mysqli_stmt_close($stmtUpdate);

                $mensaje = "Clave actualizada correctamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "La contraseña actual no es correcta.";
                $tipoMensaje = "error";
            }
        } else {
            $mensaje = "Usuario no encontrado.";
            $tipoMensaje = "error";
        }
    }
}

// --- CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA ---
$registros_por_pagina = 3; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$condicion_busqueda = "WHERE usuario != 'admin'";
$parametros_url = "";

if (isset($_GET['searchUser']) && $_GET['searchUser'] !== '') {
    $search = mysqli_real_escape_string($conn, $_GET['searchUser']);
    $condicion_busqueda .= " AND CONCAT_WS(' ', usuario, correo, fecha_CR) LIKE '%$search%'";
    $parametros_url = "&searchUser=" . urlencode($search) . "&Busqueda=Busqueda";
}

// Contar total de registros
$sql_total = "SELECT COUNT(*) as total FROM usuarios $condicion_busqueda";
$resultado_total = mysqli_query($conn, $sql_total);
$fila_total = mysqli_fetch_assoc($resultado_total);
$total_registros = $fila_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta final con límite
$sql = "SELECT * FROM usuarios $condicion_busqueda LIMIT $offset, $registros_por_pagina";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error SQL: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Iutirla</title>
</head>
<body>

<div id="main">
    <div id="ListaUser">
        <br><br>

        <div>
            <form action="" method="GET" class="form">
                <h2>Busqueda Usuarios</h2>
                <br>
                <input type="text" name="searchUser" placeholder="Introduzca datos a buscar" class="inputext" value="<?php echo isset($_GET['searchUser']) ? htmlspecialchars($_GET['searchUser']) : ''; ?>">
                <br>
                <input type="submit" name="Busqueda" value="Busqueda" style="cursor: pointer;">
                <br>
                <input type="button" value="Resetear" onclick="window.location.href='<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>?pagina=1';" style="cursor: pointer;">
            </form>
        </div>

        <br><br>

        <?php if ($mensaje !== ''): ?>
            <div style="max-width:1200px;margin:0 auto 15px auto;padding:12px 15px;border-radius:4px;background:<?php echo $tipoMensaje === 'success' ? '#e8f5e9' : '#fdecea'; ?>;color:<?php echo $tipoMensaje === 'success' ? '#217346' : '#c0392b'; ?>;border-left:4px solid <?php echo $tipoMensaje === 'success' ? '#217346' : '#c0392b'; ?>;">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <table style="max-width: 1200px; margin: 0 auto;">
            <tr>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Fecha de Creacion</th>
                <th>Cambiar clave</th>    
                <th>Eliminar Usuario</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['correo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['fecha_CR'], ENT_QUOTES, 'UTF-8'); ?></td>

                    <td>
                        <?php if ($_SESSION['usuario'] === 'admin' || intval($_SESSION['id']) === intval($row['id'])) { ?>
                            <form method="post" action="" style="margin:0; display:flex; flex-direction:column; gap:8px; align-items:center;">
                                <input type="hidden" name="id_usuario" value="<?php echo intval($row['id']); ?>">
                                <input type="password" name="clave_nueva" placeholder="Nueva clave" style="width:180px; padding:8px;">
                                <input type="password" name="clave_confirmar" placeholder="Confirmar clave" style="width:180px; padding:8px;">
                                <button type="submit" name="cambiar_clave" style="background:#217346;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;">
                                    Cambiar clave
                                </button>
                            </form>
                        <?php } ?>
                    </td>

                    <td>
                    <?php if($_SESSION['usuario'] === 'admin' || intval($_SESSION['id']) === intval($row['id'])){ ?>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <form method="get" action="usuarios.php" style="display:inline-block;margin:0;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="eliminar" style="background:#dc3545;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                                    Eliminar
                                </button>
                            </form>
                        </div>    
                    <?php } ?>
                    </td>                
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
</div>

</body>
</html>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
        
body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }

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
    transition: all 0.3 ease;
    cursor: pointer;
}

.form button:hover,.form button:active,.form button:focus {
  background: #990101;
}

table {
    border: 4px solid black;
    border-collapse: collapse;
}

th {
    border: 4px solid black;
    text-align: center;
    font-weight: bold;
    background: skyblue;
    border-collapse: collapse;
}

td {
    border: 4px solid black;
    border-collapse: collapse;
    text-align: center;
    padding: 15px;
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