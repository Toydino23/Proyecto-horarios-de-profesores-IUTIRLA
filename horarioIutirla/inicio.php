<?php
    include("sidebar.php");
    session_start();

    if (isset($_SESSION["usuario"])) {
    header("Location: index.php");     
    exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion</title>
</head>
<body>

  <div id="main">
      
  <div class="login-page">
    <div class="form">
      <h2>Ingreso</h2>
      <br>
      <form class="login-form" action="login.php" method="POST">
        <input type="text" name="usuario" placeholder="usuario"/>
        <input type="password" name="contraseña" placeholder="contraseña"/>
        <button>Ingresar</button>
      </form>
        <br>
        <a class="eliminar" href="recuperar.php">Olvidaste tu contraseña?</a>
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
