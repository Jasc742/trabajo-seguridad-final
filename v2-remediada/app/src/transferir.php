<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$usuario_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT id, saldo FROM cuentas WHERE usuario_id = :uid");
$stmt->execute(['uid' => $usuario_id]);
$mi_cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ============================================
    // SEC-FIX #4: Broken Access Control resuelto
    // La cuenta origen NUNCA se toma del formulario,
    // siempre se deriva de la sesion autenticada
    // ============================================
    if (!$mi_cuenta) {
        $mensaje = "Tu usuario no posee una cuenta bancaria activa.";
    } else {
        $cuenta_origen_id = $mi_cuenta['id'];

        $cuenta_destino_id = filter_input(INPUT_POST, 'cuenta_destino_id', FILTER_VALIDATE_INT);
        $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
        $nota = trim($_POST['nota'] ?? '');

        if (!$cuenta_destino_id || !$monto || $monto <= 0) {
            $mensaje = "Datos invalidos";
        } elseif ($cuenta_destino_id === $cuenta_origen_id) {
            $mensaje = "No puedes transferir a tu propia cuenta";
        } elseif (strlen($nota) > 200) {
            $mensaje = "La nota es demasiado larga";
        } else {
            try {
                // SEC-FIX: transaccion atomica real (evita race conditions)
                $conn->beginTransaction();

                $stmtSaldo = $conn->prepare("SELECT saldo FROM cuentas WHERE id = :id FOR UPDATE");
                $stmtSaldo->execute(['id' => $cuenta_origen_id]);
                $saldoActual = $stmtSaldo->fetchColumn();

                $stmtDestino = $conn->prepare("SELECT id FROM cuentas WHERE id = :id");
                $stmtDestino->execute(['id' => $cuenta_destino_id]);

                if (!$stmtDestino->fetch()) {
                    throw new Exception("Cuenta destino no existe");
                } elseif ($saldoActual < $monto) {
                    throw new Exception("Saldo insuficiente");
                }

                $conn->prepare("UPDATE cuentas SET saldo = saldo - :m WHERE id = :id")
                     ->execute(['m' => $monto, 'id' => $cuenta_origen_id]);
                $conn->prepare("UPDATE cuentas SET saldo = saldo + :m WHERE id = :id")
                     ->execute(['m' => $monto, 'id' => $cuenta_destino_id]);

                // SEC-FIX #8: nota se guarda cruda, se escapa al MOSTRAR (ver historial.php)
                $stmtInsert = $conn->prepare(
                    "INSERT INTO transacciones (cuenta_origen_id, cuenta_destino_id, monto, nota, estado)
                     VALUES (:origen, :destino, :monto, :nota, 'completada')"
                );
                $stmtInsert->execute([
                    'origen' => $cuenta_origen_id,
                    'destino' => $cuenta_destino_id,
                    'monto' => $monto,
                    'nota' => $nota
                ]);

                $conn->commit();
                registrar_log($conn, $usuario_id, "transferencia: $monto a cuenta $cuenta_destino_id");
                $mensaje = "Transferencia realizada con exito";

                if ($mi_cuenta) {
                    $mi_cuenta['saldo'] -= $monto;
                }
            } catch (Exception $e) {
                $conn->rollBack();
                registrar_log($conn, $usuario_id, "transferencia_fallida: " . $e->getMessage());
                $mensaje = "No se pudo completar la transferencia";
            }
        }
    }
}

$mi_cuenta_id = $mi_cuenta ? (int)$mi_cuenta['id'] : 0;
$saldo_disponible = $mi_cuenta ? (float)$mi_cuenta['saldo'] : 0.00;

$stmtCuentas = $conn->prepare("SELECT id, numero_cuenta FROM cuentas WHERE id != :mi_id");
$stmtCuentas->execute(['mi_id' => $mi_cuenta_id]);
$cuentas = $stmtCuentas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferir - Billetera Digital V2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <header class="navbar">
        <a href="index.php" class="brand">⚡ Billetera<span>V2</span></a>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="transferir.php" class="active">Transferencias</a>
            <a href="historial.php">Historial</a>
            <a href="perfil.php">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="account-card">
            <div>
                <div class="balance-title">Tu Saldo Disponible</div>
                <div class="balance-amount">$<?= number_format($saldo_disponible, 2) ?> USD</div>
            </div>
            <div>
                <div class="balance-title">ID Cuenta Origen Protegida</div>
                <div class="account-num"><?= $mi_cuenta ? 'ID: ' . $mi_cuenta_id : 'Sin cuenta activa' ?></div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">💸 Transferencia de Fondos Segura</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'exito') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="transferir.php">
                <div class="form-group">
                    <label>Seleccionar Cuenta Destino:</label>
                    <select name="cuenta_destino_id" class="form-control" required>
                        <option value="">-- Selecciona una cuenta --</option>
                        <?php foreach ($cuentas as $c): ?>
                            <option value="<?= (int)$c['id'] ?>">Cuenta No. <?= htmlspecialchars($c['numero_cuenta']) ?> (ID: <?= (int)$c['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Monto a Transferir ($ USD):</label>
                    <input type="number" step="0.01" min="0.01" name="monto" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>Concepto / Nota (Máx 200 caracteres):</label>
                    <input type="text" name="nota" class="form-control" maxlength="200" placeholder="Ej: Transferencia de servicios">
                </div>

                <button type="submit" class="btn">Confirmar Transferencia Segura</button>
            </form>
        </div>
    </main>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
