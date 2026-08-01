<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $nombre_archivo = $_FILES['foto']['name'];
    $tmp_path = $_FILES['foto']['tmp_name'];

    // ============================================
    // VULN #6: Unrestricted File Upload -> RCE (V1)
    // - Sin validacion de extension ni MIME
    // - Sin renombrar archivo
    // ============================================
    $destino = __DIR__ . "/uploads/" . $nombre_archivo;

    if (move_uploaded_file($tmp_path, $destino)) {
        // Eliminar la foto anterior si existe en disco
        $res_old = mysqli_query($conn, "SELECT foto_perfil FROM usuarios WHERE id = $usuario_id");
        $usr_old = mysqli_fetch_assoc($res_old);
        if (!empty($usr_old['foto_perfil'])) {
            $foto_antigua_path = __DIR__ . '/' . $usr_old['foto_perfil'];
            if (file_exists($foto_antigua_path) && is_file($foto_antigua_path)) {
                @unlink($foto_antigua_path);
            }
        }

        $ruta_bd = "uploads/" . $nombre_archivo;
        mysqli_query($conn, "UPDATE usuarios SET foto_perfil = '$ruta_bd' WHERE id = $usuario_id");
        $mensaje = "Foto de perfil actualizada correctamente: " . $nombre_archivo;
    } else {
        $mensaje = "Error al subir la imagen";
    }
}

// Consultar datos actualizados del usuario y cuenta
$query_usr = "SELECT u.*, c.numero_cuenta, c.saldo 
              FROM usuarios u 
              LEFT JOIN cuentas c ON u.id = c.usuario_id 
              WHERE u.id = $usuario_id";
$res_usr = mysqli_query($conn, $query_usr);
$usuario = mysqli_fetch_assoc($res_usr);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Billetera Digital V1</title>
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
            <a href="historial.php">Historial</a>
            <a href="perfil.php" class="active">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <!-- Tarjeta de Encabezado de Perfil -->
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
                <div class="profile-name"><?= $usuario['nombre'] ?></div>
                <div class="profile-email">📧 <?= $usuario['email'] ?></div>
                <div style="display:flex; gap:0.5rem; margin-top:0.5rem; flex-wrap:wrap;">
                    <span class="profile-badge">Rol: <?= strtoupper($usuario['rol']) ?></span>
                    <span class="profile-badge" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:rgba(59,130,246,0.3);">
                        ID: #<?= $usuario['id'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Formulario para Cambiar Foto de Perfil -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 class="card-title">🖼️ Cambiar Foto de Perfil</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'correctamente') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="perfil.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Seleccionar Nueva Imagen de Perfil:</label>
                    <input type="file" name="foto" class="form-control" style="padding: 0.5rem;" required>
                </div>
                <button type="submit" class="btn">Actualizar Foto de Perfil</button>
            </form>
        </div>

        <!-- Información de Cuenta -->
        <div class="card">
            <h2 class="card-title">💳 Información de la Cuenta Bancaria</h2>
            <div class="account-widget">
                <div class="stat-box">
                    <div class="title">Número de Cuenta</div>
                    <div class="value" style="font-size:1.4rem; color:var(--text-main); font-family:monospace;">
                        <?= $usuario['numero_cuenta'] ?? 'N/A' ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="title">Saldo Disponible</div>
                    <div class="value">$<?= number_format((float)($usuario['saldo'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
