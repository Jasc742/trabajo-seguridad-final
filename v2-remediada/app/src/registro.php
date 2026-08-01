<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Email invalido";
    } elseif (strlen($password) < 8) {
        $mensaje = "La contrasena debe tener al menos 8 caracteres";
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmtCheck->execute(['email' => $email]);

        if ($stmtCheck->fetch()) {
            $mensaje = "El email ya esta registrado";
        } else {
            // SEC-FIX #2: hash seguro de contrasena con bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol) VALUES (:n, :e, :p, 'user')"
            );
            $stmt->execute(['n' => $nombre, 'e' => $email, 'p' => $password_hash]);

            $usuario_id = $conn->lastInsertId();
            $numero_cuenta = str_pad($usuario_id, 10, '0', STR_PAD_LEFT);

            $stmtC = $conn->prepare(
                "INSERT INTO cuentas (usuario_id, saldo, numero_cuenta) VALUES (:uid, 0.00, :nc)"
            );
            $stmtC->execute(['uid' => $usuario_id, 'nc' => $numero_cuenta]);

            registrar_log($conn, $usuario_id, 'registro_usuario');
            $mensaje = "Registro exitoso. Ya puedes iniciar sesion.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Seguro - Billetera Digital V2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <div class="auth-wrapper">
        <div class="card">
            <h2 class="card-title">📝 Crear Cuenta Segura</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'exitoso') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="registro.php">
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico:</label>
                    <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña (Mínimo 8 Caracteres):</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="8" required>
                </div>
                <button type="submit" class="btn">Crear mi Cuenta Segura</button>
            </form>

            <div class="auth-links">
                <a href="login.php">¿Ya tienes cuenta? Inicia Sesión</a>
            </div>
        </div>
    </div>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
