<?php
include "connect.php";
session_start();

if($_SERVER["REQUEST_METHOD"] !== "POST"){
exit();
}

$lunes = (int)($_POST['lunes'] ?? 0);
$martes = (int)($_POST['martes'] ?? 0);
$miercoles = (int)($_POST['miercoles'] ?? 0);
$jueves = (int)($_POST['jueves'] ?? 0);
$viernes = (int)($_POST['viernes'] ?? 0);
//verifica si hay un dia valido
if($lunes===0 && $martes===0 && $miercoles===0 && $jueves===0 && $viernes===0){
    $_SESSION['mensaje']= "No hay un dia seleccionado";
    header('location:profesores.php');
    exit();
}

$nombre = mysqli_real_escape_string($conn, trim($_POST['nombre'] ?? ''));
$apellido = mysqli_real_escape_string($conn, trim($_POST['apellido'] ?? ''));
$cedula = mysqli_real_escape_string($conn, trim($_POST['cedula'] ?? ''));
$carrera = mysqli_real_escape_string($conn, trim($_POST['carrera'] ?? ''));
$clase = mysqli_real_escape_string($conn, trim($_POST['clase'] ?? ''));
//verifica si hay una semana valida
if ($nombre === '' || $apellido === '' || $cedula === '' || $carrera === '' || $clase === '') {
    $_SESSION['mensaje']= "Hay un espacio sin rellenar";
    header('location:profesores.php');
    exit();
}

$conn->begin_transaction();

try {
// verifica si existe el profesor
$check = $conn->prepare("SELECT id FROM profesor WHERE Nombre = ? AND Apellido = ? AND Cedula = ? AND Carrera = ? AND Clase = ?");
$check->bind_param("sssss", $nombre, $apellido, $cedula, $carrera, $clase);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
$check->close();
$conn->rollback();
    $_SESSION['mensaje']= "Este profesor ya esta registrado";
    header('location:profesores.php');
    exit();
}
$check->close();

// Agrega la semana primero
$stmt1 = $conn->prepare("INSERT INTO semana (Lunes, Martes, Miercoles, Jueves, Viernes) VALUES (?, ?, ?, ?, ?)");
$stmt1->bind_param("iiiii", $lunes, $martes, $miercoles, $jueves, $viernes);
if (!$stmt1->execute()) {
throw new Exception("Error insertando semana " . $stmt1->error);
}
$semana_id = $conn->insert_id;
$stmt1->close();

// Agrega al profesor
$stmt2 = $conn->prepare("INSERT INTO profesor (Nombre, Apellido, Cedula, Carrera, Clase, idsemana) VALUES (?, ?, ?, ?, ?, ?)");
$stmt2->bind_param("sssssi", $nombre, $apellido, $cedula, $carrera, $clase, $semana_id);
if (!$stmt2->execute()) {
throw new Exception("Error insertando profesor " . $stmt2->error);
}
$stmt2->close();

$conn->commit();
    $_SESSION['mensaje']= "Este profesor fue registrado exitosamente";
    header('location:profesores.php');
    exit();} catch (Exception $e) {
$conn->rollback();
echo "Error: " . $e->getMessage();
}
?>