<?php
require_once __DIR__ . '/../config/db.php';

// ============================================
// VULN #10: Broken Access Control (V1)
// Solo verifica que exista sesion, NO que el rol sea 'admin'
// ============================================
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";

// Procesar asignacion/cambio de rol en V1
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $target_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $nuevo_rol = ($_POST['nuevo_rol'] === 'admin') ? 'admin' : 'user';

    if ($target_id > 0) {
        if ($target_id === (int)$_SESSION['usuario_id'] && $nuevo_rol === 'user') {
            $mensaje = "No puedes revocar tu propio rol de Administrador";
        } else {
            $query_update = "UPDATE usuarios SET rol = '$nuevo_rol' WHERE id = $target_id";
            if (mysqli_query($conn, $query_update)) {
                $mensaje = "Rol del usuario #$target_id actualizado exitosamente a " . strtoupper($nuevo_rol);
            } else {
                $mensaje = "Error al actualizar rol: " . mysqli_error($conn);
            }
        }
    }
}

// Consultar lista de usuarios (sin la columna de contraseña)
$usuarios = mysqli_query($conn, "SELECT usuarios.id, nombre, email, rol, saldo 
    FROM usuarios JOIN cuentas ON usuarios.id = cuentas.usuario_id ORDER BY usuarios.id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Billetera Digital V1</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="version-banner">
        ⚠️ VERSION 1 — ENTORNO VULNERABLE DE PRUEBAS (LABORATORIO PENTEST)
    </div>

    <header class="navbar">
        <a href="../index.php" class="brand">💰 Billetera<span>V1</span></a>
        <nav>
            <a href="../index.php">Inicio</a>
            <a href="../transferir.php">Transferir</a>
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
                    <?= $mensaje ?>
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
                        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td style="font-weight:600;"><?= $u['nombre'] ?></td>
                            <td><?= $u['email'] ?></td>
                            <td>
                                <span style="background:rgba(245, 158, 11, 0.2); color:#fde047; padding:3px 10px; border-radius:4px; font-weight:700; font-size:0.8rem;">
                                    <?= strtoupper($u['rol']) ?>
                                </span>
                            </td>
                            <td style="color:var(--accent-amber); font-weight:700;">$<?= number_format((float)$u['saldo'], 2) ?></td>
                            <td>
                                <form method="POST" action="panel.php" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="cambiar_rol" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['id'] == $_SESSION['usuario_id']): ?>
                                        <span style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">(Tu cuenta activa)</span>
                                    <?php elseif ($u['rol'] === 'user'): ?>
                                        <button type="submit" name="nuevo_rol" value="admin" class="btn" style="padding:4px 12px; font-size:0.8rem; background:linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color:white; width:auto; margin:0;">
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
                        <?php endwhile; ?>
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
