<?php
    include("sidebar.php");
    include("connect.php");
    session_start();

    if (isset($_SESSION["usuario"])) {
        header("Location: index.php");     
        exit;
    }

    $error = "";
    $ok = "";

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuario"]) && isset($_POST["correo"])) {
        $usuario = trim($_POST["usuario"]);
        $correo = trim($_POST["correo"]);
        
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND correo = ?");
        $stmt->bind_param("ss", $usuario, $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $_SESSION["reset_usuario"] = $usuario;
            $_SESSION["reset_correo"] = $correo;
            $ok = "Usuario verificado. Ahora puedes cambiar la contraseña.";
        } else {
            $error = "El usuario y el correo no coinciden.";
        }

        $stmt->close();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nueva_password"]) && isset($_SESSION["reset_usuario"])) {
        $nueva_password = $_POST["nueva_password"];
        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE usuario = ? AND correo = ?");
        $stmt->bind_param("sss", $hash, $_SESSION["reset_usuario"], $_SESSION["reset_correo"]);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $error = null;
            $ok = "Contraseña actualizada con exito"; 
            unset($_SESSION["reset_usuario"], $_SESSION["reset_correo"]);
        } else {
            $error = "No se pudo actualizar la contraseña.";
        }

        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña</title>
</head>
<body>

  <div id="main">

  <div class="login-page">
    <div class="form">

    <?php
    if (!empty($error)) echo "<p style='color:red;'>$error</p>";
    if (!empty($ok)) echo "<p style='color:green;'>$ok</p>";
    ?>

    <?php if (!isset($_SESSION["reset_usuario"])): ?>
      <h2>Ingrese los Datos solicitados</h2>
      <br>
      <form action="recuperar.php" method="POST">
        <input type="text" name="usuario" placeholder="usuario"/>
        <input type="email" name="correo" placeholder="Correo"/>
        <button>Ingresar</button>
      </form>
    <?php else: ?>
        <form method="POST" action="recuperar.php">
        <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
        <button type="submit">Cambiar contraseña</button>
    </form>
<?php endif; ?>
        <br>
        <a class="eliminar" href="inicio.php">Iniciar sesion</a>
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
  padding: 8% 0 0;
  margin: auto;
}
.form {
  position: relative;
  z-index: 1;
  background: #FFFFFF;
  max-width: 360px;
  margin: 0 auto 100px;
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
.form button:hover,.form button:active,.form button:focus {
  background: #990101;
}
.form .message {
  margin: 15px 0 0;
  color: #b3b3b3;
  font-size: 12px;
}
.form .message a {
  color: #4CAF50;
  text-decoration: none;
}
.form .register-form {
  display: none;
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

.eliminar:link {
  text-decoration: none;
}

.eliminar:visited {
  text-decoration: none;
}

.eliminar:hover {
  text-decoration: underline;
}

.eliminar:active {
  text-decoration: underline;
}
</style>
