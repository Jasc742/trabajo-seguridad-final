<?php
// VULN #11: credenciales hardcodeadas, sin uso de variables de entorno
$db_host = getenv('DB_HOST') ?: "db";
$db_name = "billetera_v1";
$db_user = "billetera_user";
$db_pass = "billetera123";

// Conexion mysqli clasica, sin manejo seguro de errores
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // VULN #11: mensaje de error verboso expone detalles internos
    die("Error de conexion: " . mysqli_connect_error());
}

// Iniciar sesion sin configuracion segura de cookies
session_start();
?>
