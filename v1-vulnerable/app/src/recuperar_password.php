<?php
require_once __DIR__ . '/config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // ============================================
    // VULN #9: Broken Authentication
    // Token predecible: MD5 del email + timestamp truncado a la hora
    // ============================================
    $token = md5($email . date('Y-m-d-H'));

    mysqli_query($conn, "UPDATE usuarios SET token_recuperacion = '$token' WHERE email = '$email'");

    // VULN: el token se muestra directamente en pantalla en vez de enviarse por email
    $mensaje = "Token generado: $token (simulacion: normalmente se envia por email)";
}

if (isset($_GET['token']) && isset($_GET['nueva_password'])) {
    $token = $_GET['token'];
    $nueva = $_GET['nueva_password'];

    $query = "UPDATE usuarios SET password = '$nueva' WHERE token_recuperacion = '$token'";
    mysqli_query($conn, $query);
    $mensaje = "Contraseña actualizada exitosamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Billetera Digital V1</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        ⚠️ VERSION 1 — ENTORNO VULNERABLE DE PRUEBAS (LABORATORIO PENTEST)
    </div>

    <div class="auth-wrapper">
        <div class="card">
            <h2 class="card-title">🔑 Recuperar Contraseña</h2>

            <?php if ($mensaje): ?>
                <div class="alert alert-warning">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="recuperar_password.php">
                <div class="form-group">
                    <label>Correo Electrónico:</label>
                    <input type="text" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                </div>
                <button type="submit" class="btn">Solicitar Token</button>
            </form>

            <div class="auth-links">
                <a href="login.php">Volver al Login</a>
            </div>
        </div>
    </div>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
