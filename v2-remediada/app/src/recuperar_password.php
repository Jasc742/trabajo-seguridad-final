<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $mensaje = "Email invalido";
    } else {
        // SEC-FIX #9: token criptograficamente seguro (bin2hex + random_bytes)
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + 3600); // 1 hora de validez

        $stmt = $conn->prepare(
            "UPDATE usuarios SET token_recuperacion = :t, token_expira = :exp WHERE email = :e"
        );
        $stmt->execute(['t' => $token, 'exp' => $expira, 'e' => $email]);

        // SEC-FIX: respuesta generica que evita enumeracion de usuarios
        $mensaje = "Si el email existe en nuestro sistema, recibiras instrucciones para restablecer tu contrasena.";
        registrar_log($conn, null, 'solicitud_recuperacion: ' . $email);
    }
}

if (isset($_GET['token']) && isset($_POST['nueva_password'])) {
    $token = $_GET['token'];
    $nueva = $_POST['nueva_password'];

    if (strlen($nueva) < 8) {
        $mensaje = "La contrasena debe tener al menos 8 caracteres";
    } else {
        $stmt = $conn->prepare(
            "SELECT id FROM usuarios WHERE token_recuperacion = :t AND token_expira > NOW()"
        );
        $stmt->execute(['t' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            $stmtU = $conn->prepare(
                "UPDATE usuarios SET password = :p, token_recuperacion = NULL, token_expira = NULL WHERE id = :id"
            );
            $stmtU->execute(['p' => $hash, 'id' => $user['id']]);

            registrar_log($conn, $user['id'], 'password_restablecida');
            $mensaje = "Contrasena actualizada exitosamente";
        } else {
            $mensaje = "Token invalido o expirado";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Billetera Digital V2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <div class="auth-wrapper">
        <div class="card">
            <h2 class="card-title">🔑 Recuperación de Contraseña</h2>

            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="recuperar_password.php">
                <div class="form-group">
                    <label>Correo Electrónico Registrado:</label>
                    <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                </div>
                <button type="submit" class="btn">Solicitar Restablecimiento</button>
            </form>

            <div class="auth-links">
                <a href="login.php">Volver al Inicio de Sesión</a>
            </div>
        </div>
    </div>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
