<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// SEC-FIX #7: Command Injection resuelto
// Whitelist estricta de formatos permitidos, sin invocar shell/system()
$formatos_permitidos = ['csv', 'txt'];
$formato = $_GET['formato'] ?? 'csv';

if (!in_array($formato, $formatos_permitidos, true)) {
    http_response_code(400);
    die("Formato no soportado");
}

$stmt = $conn->prepare("SELECT id FROM cuentas WHERE usuario_id = :uid");
$stmt->execute(['uid' => $usuario_id]);
$cuenta_id = $stmt->fetchColumn() ?: 0;

$stmtT = $conn->prepare(
    "SELECT * FROM transacciones WHERE cuenta_origen_id = :cid OR cuenta_destino_id = :cid2 ORDER BY fecha DESC"
);
$stmtT->execute(['cid' => $cuenta_id, 'cid2' => $cuenta_id]);
$transacciones = $stmtT->fetchAll(PDO::FETCH_ASSOC);

// SEC-FIX: generacion del archivo usando funciones nativas de PHP, nunca system()/exec()
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reporte_' . (int)$usuario_id . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Fecha', 'Origen', 'Destino', 'Monto', 'Nota', 'Estado']);
foreach ($transacciones as $t) {
    fputcsv($out, [$t['fecha'], $t['cuenta_origen_id'], $t['cuenta_destino_id'], $t['monto'], $t['nota'], $t['estado']]);
}
fclose($out);

registrar_log($conn, $usuario_id, "exportacion_historial: $formato");
exit;
?>
