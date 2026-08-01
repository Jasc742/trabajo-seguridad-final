<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// ============================================
// VULN #5: SQL Injection - filtro de busqueda
// concatenado directamente en la query
// ============================================
$filtro = isset($_GET['buscar']) ? $_GET['buscar'] : '';

$query_cuenta = "SELECT id FROM cuentas WHERE usuario_id = $usuario_id";
$result_cuenta = mysqli_query($conn, $query_cuenta);
$cuenta = mysqli_fetch_assoc($result_cuenta);
$cuenta_id = $cuenta ? $cuenta['id'] : 0;

if ($filtro !== '') {
    $query = "SELECT * FROM transacciones 
              WHERE (cuenta_origen_id = $cuenta_id OR cuenta_destino_id = $cuenta_id)
              AND nota LIKE '%$filtro%'
              ORDER BY fecha DESC";
} else {
    $query = "SELECT * FROM transacciones 
              WHERE cuenta_origen_id = $cuenta_id OR cuenta_destino_id = $cuenta_id
              ORDER BY fecha DESC";
}

$result = mysqli_query($conn, $query);

if (!$result) {
    // VULN #11: error SQL expuesto
    die("Error en la consulta: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - Billetera Digital V1</title>
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
            <a href="transferir.php">Transferir</a>
            <a href="historial.php" class="active">Historial</a>
            <a href="perfil.php">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="card">
            <h2 class="card-title">📜 Historial de Transacciones</h2>

            <form method="GET" action="historial.php" style="margin-bottom: 1.5rem; display:flex; gap:0.5rem;">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nota o concepto..." value="<?= $filtro ?>">
                <button type="submit" class="btn" style="width: auto; padding: 0.75rem 1.5rem;">Buscar</button>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Monto</th>
                            <th>Nota / Concepto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $t['fecha'] ?></td>
                                <td>ID: <?= $t['cuenta_origen_id'] ?></td>
                                <td>ID: <?= $t['cuenta_destino_id'] ?></td>
                                <td style="font-weight:700; color:var(--accent-amber);">$<?= number_format((float)$t['monto'], 2) ?></td>
                                <td><?= $t['nota'] ?></td>
                                <td><span style="background:rgba(16, 185, 129, 0.2); color:#6ee7b7; padding:3px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;"><?= strtoupper($t['estado']) ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:2rem;">No se encontraron transacciones registadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
