<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";

define('UPLOAD_DIR', '/var/www/uploads_privados/');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $archivo = $_FILES['foto'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje = "Error al subir la imagen";
    } elseif ($archivo['size'] > 2 * 1024 * 1024) {
        $mensaje = "El archivo excede el tamaño máximo permitido (2MB)";
    } else {
        // SEC-FIX: Validacion estricta por contenido MIME (Magic Bytes)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if (!isset($permitidos[$mime])) {
            $mensaje = "Tipo de archivo no permitido. Solo se aceptan imágenes JPG o PNG.";
            registrar_log($conn, $usuario_id, "intento_upload_invalido: $mime");
        } else {
            // SEC-FIX: Nombre aleatorio seguro y guardado fuera del WebRoot publico
            $extension = $permitidos[$mime];
            $nombre_seguro = bin2hex(random_bytes(16)) . '.' . $extension;
            $destino = UPLOAD_DIR . $nombre_seguro;

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            if (move_uploaded_file($archivo['tmp_name'], $destino)) {
                // SEC-FIX: Eliminar la foto anterior de la carpeta privada si existe
                $stmt_old = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :uid");
                $stmt_old->execute(['uid' => $usuario_id]);
                $usr_old = $stmt_old->fetch(PDO::FETCH_ASSOC);

                if (!empty($usr_old['foto_perfil'])) {
                    $foto_antigua_path = UPLOAD_DIR . basename($usr_old['foto_perfil']);
                    if (file_exists($foto_antigua_path) && is_file($foto_antigua_path)) {
                        @unlink($foto_antigua_path);
                    }
                }

                $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = :ruta WHERE id = :uid");
                $stmt->execute(['ruta' => $nombre_seguro, 'uid' => $usuario_id]);

                registrar_log($conn, $usuario_id, "foto_actualizada");
                $mensaje = "Foto de perfil actualizada correctamente (Guardada de forma segura fuera del WebRoot)";
            } else {
                $mensaje = "Error al guardar la imagen en el servidor";
            }
        }
    }
}

// Consultar datos actualizados del usuario y cuenta con PDO
$stmt_usr = $conn->prepare(
    "SELECT u.*, c.numero_cuenta, c.saldo 
     FROM usuarios u 
     LEFT JOIN cuentas c ON u.id = c.usuario_id 
     WHERE u.id = :id"
);
$stmt_usr->execute(['id' => $usuario_id]);
$usuario = $stmt_usr->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Billetera Digital V2</title>
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
            <a href="historial.php">Historial</a>
            <a href="perfil.php" class="active">👤 Mi Perfil</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin/panel.php">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <!-- Tarjeta de Encabezado de Perfil V2 -->
        <div class="profile-card">
            <div class="profile-avatar-container">
                <img src="ver_foto.php?t=<?= time() ?>" alt="Foto de perfil" class="profile-avatar">
            </div>
            <div class="profile-details">
                <div class="profile-name"><?= htmlspecialchars($usuario['nombre']) ?></div>
                <div class="profile-email">📧 <?= htmlspecialchars($usuario['email']) ?></div>
                <div style="display:flex; gap:0.5rem; margin-top:0.5rem; flex-wrap:wrap;">
                    <span class="profile-badge">Rol: <?= htmlspecialchars(strtoupper($usuario['rol'])) ?></span>
                    <span class="profile-badge" style="background:rgba(56,189,248,0.15); color:#38bdf8; border-color:rgba(56,189,248,0.3);">
                        ID: #<?= htmlspecialchars($usuario['id']) ?>
                    </span>
                    <span class="profile-badge" style="background:rgba(16,185,129,0.15); color:#10b981; border-color:rgba(16,185,129,0.3);">
                        ✓ Almacenamiento Seguro
                    </span>
                </div>
            </div>
        </div>

        <!-- Formulario para Cambiar Foto de Perfil -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 class="card-title">🖼️ Cambiar Foto de Perfil Segura</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
                🛡️ Las imágenes se procesan con validación de tipo mágico MIME por servidor y se almacenan fuera del WebRoot accesible desde el navegador en <code>/var/www/uploads_privados/</code>.
            </p>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'correctamente') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="perfil.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Seleccionar Imagen (JPG o PNG, máx 2MB):</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png" style="padding: 0.6rem;" required>
                </div>
                <button type="submit" class="btn">Actualizar Foto de Perfil</button>
            </form>
        </div>

        <!-- Resumen de Cuenta Protegida -->
        <div class="account-card">
            <div>
                <div class="balance-title">Saldo Total Disponible</div>
                <div class="balance-amount">$<?= number_format((float)($usuario['saldo'] ?? 0), 2) ?> USD</div>
                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #a7f3d0; font-weight:600;">
                    ✓ Cuenta Verificada & Protegida contra Reentrancia
                </div>
            </div>
            <div>
                <div class="balance-title">Número de Cuenta</div>
                <div class="account-num"><?= htmlspecialchars($usuario['numero_cuenta'] ?? 'N/A') ?></div>
            </div>
        </div>
    </main>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
