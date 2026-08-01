<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// ============================================
// VULN #7: Command Injection
// El nombre de archivo destino se toma del usuario
// y se concatena directamente en un comando de shell
// ============================================
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'csv';
$usuario_id = $_SESSION['usuario_id'];
$nombre_salida = "reporte_" . $usuario_id . "." . $formato;

// Ejemplo deliberadamente inseguro: se usa el parametro sin sanitizar
// dentro de un comando del sistema (simula generacion de reporte via CLI)
$comando = "echo 'Generando reporte para usuario $usuario_id' > /tmp/$nombre_salida";
system($comando);

echo "Reporte generado: $nombre_salida";

// PoC de explotacion (para documentacion de pentest):
// ?formato=csv;cat /etc/passwd > /tmp/pwned.txt
?>
