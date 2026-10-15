<?php
$servername = "localhost";
$username = "root";
$password  = "";
$dbname = "horario_iutirla";


$conn = new mysqli($servername,$username,$password,$dbname);

if($conn ->connect_error){
    echo "error de coneccion";
}
?>