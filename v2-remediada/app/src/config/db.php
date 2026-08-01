<?php
// SEC-FIX #11: credenciales via variables de entorno, no hardcodeadas
$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'billetera_v2';
$db_user = getenv('DB_USER') ?: 'billetera_user';
$db_pass = getenv('DB_PASS');

if (!$db_pass) {
    error_log("DB_PASS no configurada en variables de entorno");
    http_response_code(500);
    die("Error interno del servidor");
}

// SEC-FIX: uso de PDO con excepciones controladas, sin exponer detalles al cliente
try {
    $conn = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexion BD: " . $e->getMessage());
    http_response_code(500);
    die("Error interno del servidor. Contacte al administrador.");
}

// SEC-FIX #12: cookies de sesion seguras
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
?>
