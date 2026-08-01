<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logger.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// ============================================
// SEC-FIX #10: Broken Access Control resuelto
// Verificacion explicita de rol de administrador (RBAC)
// ============================================
if ($_SESSION['rol'] !== 'admin') {
    registrar_log($conn, $_SESSION['usuario_id'], 'intento_acceso_admin_denegado');
    http_response_code(403);
    die("Acceso denegado");
}

$mensaje = "";

// Procesar asignacion/cambio de rol seguro en V2
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $target_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $nuevo_rol = (isset($_POST['nuevo_rol']) && $_POST['nuevo_rol'] === 'admin') ? 'admin' : 'user';

    if (!$target_id) {
        $mensaje = "ID de usuario inválido";
    } elseif ($target_id === (int)$_SESSION['usuario_id'] && $nuevo_rol === 'user') {
        // Prevención de auto-despojo de permisos administrativos
        $mensaje = "No puedes revocar tu propio rol de Administrador";
    } else {
        $stmt_upd = $conn->prepare("UPDATE usuarios SET rol = :rol WHERE id = :id");
        if ($stmt_upd->execute(['rol' => $nuevo_rol, 'id' => $target_id])) {
            registrar_log($conn, $_SESSION['usuario_id'], "admin_cambio_rol: usuario #$target_id a $nuevo_rol");
            $mensaje = "Rol del usuario #$target_id actualizado exitosamente a " . strtoupper($nuevo_rol);
        } else {
            $mensaje = "Error al actualizar el rol del usuario";
        }
    }
}

// SEC-FIX: Consulta de usuarios con Prepared Statement (Sanitizado, sin passwords)
$stmt = $conn->query(
    "SELECT u.id, u.nombre, u.email, u.rol, c.saldo 
     FROM usuarios u JOIN cuentas c ON u.id = c.usuario_id 
     ORDER BY u.id DESC"
);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

registrar_log($conn, $_SESSION['usuario_id'], 'acceso_panel_admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Billetera Digital V2</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <header class="navbar">
        <a href="../index.php" class="brand">⚡ Billetera<span>V2</span></a>
        <nav>
            <a href="../index.php">Dashboard</a>
            <a href="../transferir.php">Transferencias</a>
            <a href="../historial.php">Historial</a>
            <a href="../perfil.php">👤 Mi Perfil</a>
            <a href="panel.php" class="active">Panel Admin</a>
            <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="card">
            <h2 class="card-title">👑 Panel de Administración de Usuarios</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'exitosamente') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol Actual</th>
                            <th>Saldo</th>
                            <th>Acciones de Rol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>#<?= (int)$u['id'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span style="background:rgba(16, 185, 129, 0.2); color:#a7f3d0; padding:3px 10px; border-radius:4px; font-weight:700; font-size:0.8rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    <?= htmlspecialchars(strtoupper($u['rol'])) ?>
                                </span>
                            </td>
                            <td style="color:#10b981; font-weight:700;">$<?= number_format((float)$u['saldo'], 2) ?></td>
                            <td>
                                <form method="POST" action="panel.php" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="cambiar_rol" value="1">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <?php if ($u['id'] == $_SESSION['usuario_id']): ?>
                                        <span style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">(Tu cuenta activa)</span>
                                    <?php elseif ($u['rol'] === 'user'): ?>
                                        <button type="submit" name="nuevo_rol" value="admin" class="btn" style="padding:4px 12px; font-size:0.8rem; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color:white; width:auto; margin:0;">
                                            👑 Promover a Admin
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="nuevo_rol" value="user" class="btn" style="padding:4px 12px; font-size:0.8rem; background:linear-gradient(135deg, #475569 0%, #334155 100%); color:white; width:auto; margin:0;">
                                            👤 Cambiar a User
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
