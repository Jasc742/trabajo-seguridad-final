<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// ============================================
// VULN #3: IDOR - toma el user_id de la URL sin validar
// que corresponda al usuario logueado
// ============================================
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['usuario_id'];

$query = "SELECT * FROM usuarios WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$usuario = mysqli_fetch_assoc($result);

$query_cuenta = "SELECT * FROM cuentas WHERE usuario_id = $user_id";
$result_cuenta = mysqli_query($conn, $query_cuenta);
$cuenta = mysqli_fetch_assoc($result_cuenta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Billetera - V1 Vulnerable</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        ⚠️ VERSION 1 — ENTORNO VULNERABLE DE PRUEBAS (LABORATORIO PENTEST)
    </div>

    <header class="navbar">
        <a href="index.php" class="brand">
            💰 Billetera<span>V1</span>
        </a>
        <nav>
            <a href="index.php" class="active">Inicio</a>
            <a href="transferir.php">Transferir</a>
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
                <?php if (!empty($usuario['foto_perfil'])): ?>
                    <img src="<?= $usuario['foto_perfil'] ?>?t=<?= time() ?>" alt="Foto de perfil" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <div class="profile-name">👋 Bienvenido, <?= $usuario['nombre'] ?></div>
                <div class="profile-email">📧 <?= $usuario['email'] ?></div>
                <div>
                    <span class="profile-badge">Rol: <?= strtoupper($usuario['rol']) ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">💳 Resumen de Cuenta</h2>
            <div class="account-widget">
                <div class="stat-box">
                    <div class="title">Número de Cuenta</div>
                    <div class="value" style="font-size:1.4rem; color:var(--text-main); font-family:monospace;">
                        <?= $cuenta['numero_cuenta'] ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="title">Saldo Disponible</div>
                    <div class="value">$<?= number_format((float)$cuenta['saldo'], 2) ?></div>
                </div>
            </div>

            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                <a href="transferir.php" class="btn" style="flex:1; min-width:200px;">💸 Realizar Transferencia</a>
                <a href="historial.php" class="btn" style="flex:1; min-width:200px; background:linear-gradient(135deg, #475569 0%, #334155 100%); color:#fff;">📜 Ver Historial</a>
                <a href="perfil.php" class="btn" style="flex:1; min-width:200px; background:linear-gradient(135deg, #d97706 0%, #b45309 100%); color:#fff;">👤 Mi Perfil</a>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
