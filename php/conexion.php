<?php
$servidor = "localhost"; 
$usuario = "root";
//$password = "3brutales247_";    

$password = "";       
$base_datos = "fadasportsbd";  

$conn = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

?>