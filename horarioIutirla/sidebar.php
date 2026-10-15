<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
  error_reporting(E_ALL ^ E_NOTICE);  
?>

<div id="mySidenav" class="sidenav">
  <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
  <a href="index.php">Inicio</a>
  
  <!-- Sección desplegable para Horarios -->
  <button class="dropdown-btn">Horarios <span class="flecha">▼</span></button>
  <div class="dropdown-container">
    <a href="horarios.php">Horarios General</a>
    <a href="horarioprof.php">Horarios Profesores</a>
    <a href="horariosaula.php">Horarios Aulas</a>
  </div>

  <?php
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
      if($_SESSION['usuario']==='admin'){ 
            echo '<a href="registro.php">Registro</a>';  
      }
      echo '<a href="profesores.php">Profesores</a>';  
      echo '<a href="usuarios.php">Usuarios </a>';
      echo '<a href="closelog.php">Cerrar sesión </a>';
    }else{
      echo '  <a href="inicio.php">Ingresar</a>';
    }
  ?>

</div>

<button class="MENU" onclick="openNav()">☰</button>

<style>
.MENU {
    cursor: pointer;
    width: 100px;
    height: 40px;
    border-radius: 8px;
    margin: 20px;
    background-color: #720000;
    color: whitesmoke;
    font-size: 25px; /* Corregido a 25px */
    border: none;
}

/* The side navigation menu */
.sidenav {
  height: 100%; 
  width: 0; 
  position: fixed; 
  z-index: 1;
  top: 0; 
  left: 0;
  background-color: #720000; 
  overflow-x: hidden; 
  padding-top: 60px; 
  transition: 0.5s; 
}

/* The navigation menu links */
.sidenav a {
  padding: 8px 8px 8px 32px;
  text-decoration: none;
  font-size: 25px;
  color: whitesmoke;
  display: block;
  transition: 0.3s;
}

/* Estilos para el botón del desplegable */
.dropdown-btn {
  padding: 8px 8px 8px 32px;
  text-decoration: none;
  font-size: 25px;
  color: whitesmoke;
  display: block;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  outline: none;
  transition: 0.3s;
  font-family: inherit;
}

/* Hover general para los botones y enlaces del sidebar */
.sidenav a:hover, .dropdown-btn:hover, .MENU:hover {
  color: #f1f1f1;
  background-color: #990101;
}

/* Container de los sub-enlaces del dropdown (ocultos por defecto) */
.dropdown-container {
  display: none;
  background-color: #550000; /* Un tono un poco más oscuro para diferenciar el submenú */
  padding-left: 15px; /* Sangría para denotar que es un submenú */
}

/* Sub-enlaces un poco más pequeños para jerarquía */
.dropdown-container a {
  font-size: 20px;
}

.flecha {
  float: right;
  padding-right: 25px;
  font-size: 18px;
}

/* Active class para cuando el menú esté desplegado */
.active-btn {
  background-color: #990101;
}

/* Position and style the close button (top right corner) */
.sidenav .closebtn {
  position: absolute;
  top: 0;
  right: 25px;
  font-size: 36px;
  margin-left: 50px;
  background: none; /* Quitar fondo hover raro de la x */
}
.sidenav .closebtn:hover {
  background: none; 
}

/* Style page content */
#main {
  transition: margin-left .5s;
  padding: 20px;
}
</style>

<script>
/* Set the width of the side navigation to 250px and the left margin of the page content to 250px */
function openNav() {
  document.getElementById("mySidenav").style.width = "270px"; /* Un poco más ancho para dar espacio al menú desplegable */
  document.getElementById("main").style.marginLeft = "270px";
}

/* Set the width of the side navigation to 0 and the left margin of the page content to 0 */
function closeNav() {
  document.getElementById("mySidenav").style.width = "0";
  document.getElementById("main").style.marginLeft = "0";
} 

/* Lógica para manejar el menú desplegable (Dropdown) */
document.addEventListener("DOMContentLoaded", function() {
    var dropdowns = document.getElementsByClassName("dropdown-btn");
    
    for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].addEventListener("click", function() {
            this.classList.toggle("active-btn");
            var dropdownContent = this.nextElementSibling;
            
            // Cambiar la flechita cuando se abre/cierra
            var flecha = this.querySelector('.flecha');
            
            if (dropdownContent.style.display === "block") {
                dropdownContent.style.display = "none";
                flecha.innerHTML = "▼";
            } else {
                dropdownContent.style.display = "block";
                flecha.innerHTML = "▲";
            }
        });
    }
});
</script>