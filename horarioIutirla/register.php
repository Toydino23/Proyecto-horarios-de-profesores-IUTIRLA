<?php
session_start();
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST['usuario']);
    $contra  = trim($_POST['contraseña']);
    $email   = trim($_POST['correo']);

    if (empty($usuario) || empty($contra) || empty($email)) {
        $_SESSION['mensaje'] = "Por favor complete todos los datos de la tabla";
        header('Location: registro.php');
        exit();
    }

    $check = "SELECT id FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($check);
    if ($stmt === false) {
        die("Error en prepare() 1: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $_SESSION['mensaje'] = "El correo ya existe";
        header('Location: registro.php');
        exit();
    }

    $check = "SELECT id FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($check);
    if ($stmt === false) {
        die("Error en prepare() 2: " . $conn->error);
    }
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $_SESSION['mensaje'] = "El usuario ya existe";
        header('Location: registro.php');
        exit();
    }

    $hashed_password = password_hash($contra, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (usuario, contrasena, correo, fecha_CR) VALUES (?, ?, ?, CURDATE())";    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error en prepare() 3: " . $conn->error);
    }

    $stmt->bind_param("sss", $usuario, $hashed_password, $email);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Cuenta creada con exito";
        header('Location: registro.php');
        exit();
    } else {
        $_SESSION['mensaje'] = "Error: " . $stmt->error;
        header('Location: registro.php');
        exit();
    }
}
?>