<?php
session_start();
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST['usuario']);
    $contra  = trim($_POST['contraseña']);

    if (empty($usuario) || empty($contra)) {
        echo "<script>
            alert('Debe completar usuario y contraseña');
            window.location.href = 'inicio.php';
        </script>";
        exit();
    }

    $sql = "SELECT id, usuario, contrasena FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error en prepare(): " . $conn->error);
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $hash = $user['contrasena'];

        if (password_verify($contra, $hash)) {
            $_SESSION["loggedin"] = true;
            $_SESSION["usuario"] = $user["usuario"];
            $_SESSION["id"] = $user["id"];

            echo "<script>
                alert('Bienvenido al sistema IUTIRLA');
                window.location.href = 'index.php';
            </script>";
            exit();
        }
    }

    echo "<script>
        alert('Usuario o contraseña incorrecto');
        window.location.href = 'inicio.php';
    </script>";
    exit();
}
?>