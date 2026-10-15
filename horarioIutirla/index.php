<?php
    include("sidebar.php");
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Iutirla</title>
</head>

<body>

<div id="main">
    <div class="login">
        <?php
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
            echo "<h3> Bienvenido " . ($_SESSION['usuario']) . "! </h3>";
        }
        ?>
    </div>
    <div class="texto">
        <h1>Sistema de horario de profesores IUTIRLA, sede Puerto ordaz</h1>
    </div>
  <br>
    <img src="..\horarioIutirla\includes\images\logoiutirla.png" alt="Logo Iutirla" class="logo">

</body>
</div>

</html>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }

.texto{
    color: #720000;
    text-align: center;
}

.login{
    text-align: right;
    color: #720000;
}

.logo{
    display: block;
    margin-left: auto;
    margin-right: auto;
    max-width: 100%; /* Prevents image from overflowing small screens */
    height: auto;
  }
</style>