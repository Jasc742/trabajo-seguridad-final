<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ============================================
    // VULN #4: Broken Access Control
    // La cuenta origen se toma del formulario (POST),
    // NO de la sesion del usuario autenticado.
    // ============================================
    $cuenta_origen_id  = isset($_POST['cuenta_origen_id']) ? (int)$_POST['cuenta_origen_id'] : 0;
    $cuenta_destino_id = isset($_POST['cuenta_destino_id']) ? (int)$_POST['cuenta_destino_id'] : 0;
    $monto             = isset($_POST['monto']) ? (float)$_POST['monto'] : 0.0;
    $nota              = isset($_POST['nota']) ? $_POST['nota'] : ''; // VULN #8: sin sanitizar (XSS almacenado)

    $query_saldo = "SELECT saldo FROM cuentas WHERE id = $cuenta_origen_id";
    $result = mysqli_query($conn, $query_saldo);
    $origen = mysqli_fetch_assoc($result);

    if ($origen && $origen['saldo'] >= $monto) {
        mysqli_query($conn, "UPDATE cuentas SET saldo = saldo - $monto WHERE id = $cuenta_origen_id");
        mysqli_query($conn, "UPDATE cuentas SET saldo = saldo + $monto WHERE id = $cuenta_destino_id");

        $query_insert = "INSERT INTO transacciones (cuenta_origen_id, cuenta_destino_id, monto, nota, estado) 
                          VALUES ($cuenta_origen_id, $cuenta_destino_id, $monto, '$nota', 'completada')";
        mysqli_query($conn, $query_insert);

        // Nota: no se registra en tabla logs (Logging Failure)

        $mensaje = "Transferencia realizada con exito";
    } else {
        $mensaje = "Saldo insuficiente o cuenta invalida";
    }
}

$cuentas = mysqli_query($conn, "SELECT id, numero_cuenta FROM cuentas");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferir - Billetera Digital V1</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        ⚠️ VERSION 1 — ENTORNO VULNERABLE DE PRUEBAS (LABORATORIO PENTEST)
    </div>

    <header class="navbar">
        <a href="index.php" class="brand">💰 Billetera<span>V1</span></a>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="transferir.php" class="active">Transferir</a>
            <a href="historial.php">Historial</a>
            <a href="perfil.php">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="card">
            <h2 class="card-title">💸 Realizar Transferencia</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'exito') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="transferir.php">
                <div class="form-group">
                    <label>Cuenta Origen (ID Usuario):</label>
                    <input type="text" name="cuenta_origen_id" class="form-control" value="<?= $_SESSION['usuario_id'] ?>">
                </div>

                <div class="form-group">
                    <label>Cuenta Destino:</label>
                    <select name="cuenta_destino_id" class="form-control">
                        <?php while ($c = mysqli_fetch_assoc($cuentas)): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['numero_cuenta'] ?> (ID: <?= $c['id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Monto a transferir ($):</label>
                    <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>Nota / Concepto:</label>
                    <input type="text" name="nota" class="form-control" placeholder="Ej: Pago de servicios">
                </div>

                <button type="submit" class="btn">Confirmar Transferencia</button>
            </form>
        </div>
    </main>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
