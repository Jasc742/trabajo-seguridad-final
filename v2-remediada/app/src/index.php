<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// ============================================
// SEC-FIX #3: IDOR resuelto - se ignora cualquier
// user_id de la URL, siempre se usa el de la sesion
// ============================================
$user_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM cuentas WHERE usuario_id = :id");
$stmt2->execute(['id' => $user_id]);
$cuenta = $stmt2->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Billetera Digital V2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <header class="navbar">
        <a href="index.php" class="brand">
            ⚡ Billetera<span>V2</span>
        </a>
        <nav>
            <a href="index.php" class="active">Dashboard</a>
            <a href="transferir.php">Transferencias</a>
            <a href="historial.php">Historial</a>
            <a href="perfil.php">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="profile-card">
            <div class="profile-avatar-container">
                <img src="ver_foto.php?t=<?= time() ?>" alt="Foto de perfil" class="profile-avatar">
            </div>
            <div class="profile-details">
                <div class="profile-name">👋 Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></div>
                <div class="profile-email">📧 <?= htmlspecialchars($usuario['email']) ?></div>
                <div>
                    <span class="profile-badge">Rol: <?= htmlspecialchars(strtoupper($usuario['rol'])) ?></span>
                </div>
            </div>
        </div>

        <div class="account-card">
            <div>
                <div class="balance-title">Saldo Total Disponible</div>
                <div class="balance-amount">$<?= number_format((float)$cuenta['saldo'], 2) ?> USD</div>
                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #a7f3d0; font-weight:600;">
                    ✓ Cuenta Verificada & Protegida contra Reentrancia
                </div>
            </div>
            <div>
                <div class="balance-title">Número de Cuenta</div>
                <div class="account-num"><?= htmlspecialchars($cuenta['numero_cuenta']) ?></div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">⚡ Acciones Rápidas</h2>
            <p style="color:var(--text-muted); margin-bottom: 1.5rem;">
                Tu sesión está protegida con cookies <code>HttpOnly</code>, <code>SameSite=Strict</code> y canal cifrado <code>TLS 1.3</code>.
            </p>

            <div style="display:flex; gap:1.25rem; flex-wrap:wrap;">
                <a href="transferir.php" class="btn" style="flex:1; min-width:220px;">💸 Transferir Fondos</a>
                <a href="historial.php" class="btn" style="flex:1; min-width:220px; background:linear-gradient(135deg, #1e293b 0%, #334155 100%); color:#f8fafc; border:1px solid rgba(255,255,255,0.1); box-shadow:none;">📜 Historial de Movimientos</a>
                <a href="perfil.php" class="btn" style="flex:1; min-width:220px; background:rgba(16, 185, 129, 0.15); color:#10b981; border:1px solid rgba(16, 185, 129, 0.3); box-shadow:none;">👤 Mi Perfil</a>
                <a href="exportar.php?formato=csv" class="btn" style="flex:1; min-width:220px; background:rgba(56, 189, 248, 0.15); color:#38bdf8; border:1px solid rgba(56, 189, 248, 0.3); box-shadow:none;">📥 Exportar Reporte CSV</a>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
