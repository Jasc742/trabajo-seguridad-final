<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT id FROM cuentas WHERE usuario_id = :uid");
$stmt->execute(['uid' => $usuario_id]);
$cuenta_id = $stmt->fetchColumn() ?: 0;

// SEC-FIX #5: SQL Injection resuelto con prepared statement
$filtro = trim($_GET['buscar'] ?? '');

if ($filtro !== '') {
    $stmtT = $conn->prepare(
        "SELECT * FROM transacciones 
         WHERE (cuenta_origen_id = :cid OR cuenta_destino_id = :cid2)
         AND nota LIKE :filtro
         ORDER BY fecha DESC"
    );
    $stmtT->execute([
        'cid' => $cuenta_id,
        'cid2' => $cuenta_id,
        'filtro' => '%' . $filtro . '%'
    ]);
} else {
    $stmtT = $conn->prepare(
        "SELECT * FROM transacciones 
         WHERE cuenta_origen_id = :cid OR cuenta_destino_id = :cid2
         ORDER BY fecha DESC"
    );
    $stmtT->execute(['cid' => $cuenta_id, 'cid2' => $cuenta_id]);
}

$transacciones = $stmtT->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - Billetera Digital V2</title>
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
            <a href="transferir.php">Transferencias</a>
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
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
                <h2 class="card-title" style="margin-bottom:0;">📜 Historial de Transacciones</h2>
                <a href="exportar.php?formato=csv" class="btn" style="width:auto; padding:0.6rem 1.25rem; font-size:0.85rem; background:rgba(16, 185, 129, 0.2); color:#a7f3d0; border:1px solid rgba(16, 185, 129, 0.4); box-shadow:none;">
                    📥 Exportar a CSV
                </a>
            </div>

            <form method="GET" action="historial.php" style="margin-bottom: 1.5rem; display:flex; gap:0.75rem;">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar concepto o nota..." value="<?= htmlspecialchars($filtro) ?>">
                <button type="submit" class="btn" style="width: auto; padding: 0.75rem 1.75rem;">Buscar</button>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Monto</th>
                            <th>Nota / Concepto (Escapado XSS)</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transacciones) > 0): ?>
                            <?php foreach ($transacciones as $t): ?>
                            <tr>
                                <td style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($t['fecha']) ?></td>
                                <td>ID: <?= (int)$t['cuenta_origen_id'] ?></td>
                                <td>ID: <?= (int)$t['cuenta_destino_id'] ?></td>
                                <td style="font-weight:800; color:#38bdf8;">$<?= number_format((float)$t['monto'], 2) ?></td>
                                <td><?= htmlspecialchars($t['nota']) ?></td>
                                <td><span style="background:rgba(16, 185, 129, 0.2); color:#6ee7b7; border:1px solid rgba(16, 185, 129, 0.3); padding:3px 10px; border-radius:6px; font-size:0.75rem; font-weight:700;"><?= htmlspecialchars(strtoupper($t['estado'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:2rem;">No se encontraron registros de transacciones.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
