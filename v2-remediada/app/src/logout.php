<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

if (isset($_SESSION['usuario_id'])) {
    registrar_log($conn, $_SESSION['usuario_id'], 'logout');
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
