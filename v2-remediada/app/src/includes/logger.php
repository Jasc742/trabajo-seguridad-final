<?php
// SEC-FIX: registro centralizado de eventos de seguridad (resuelve Logging & Monitoring Failures)
function registrar_log($conn, $usuario_id, $accion) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id, accion, ip) VALUES (:uid, :accion, :ip)");
    $stmt->execute([
        'uid' => $usuario_id,
        'accion' => $accion,
        'ip' => $ip
    ]);
}
?>
