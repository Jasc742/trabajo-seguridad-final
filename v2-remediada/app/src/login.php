<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/logger.php';

$error = "";

if (!isset($_SESSION['intentos_login'])) {
    $_SESSION['intentos_login'] = 0;
    $_SESSION['ultimo_intento'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_SESSION['intentos_login'] >= 5 && (time() - $_SESSION['ultimo_intento']) < 60) {
        $error = "Demasiados intentos. Espera 60 segundos.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Formato de email invalido";
        } else {
            // ============================================
            // SEC-FIX #1: SQL Injection resuelto con
            // prepared statements (PDO)
            // ============================================
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // SEC-FIX #2/#12: verificacion con password_verify (hash bcrypt)
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['intentos_login'] = 0;

                registrar_log($conn, $user['id'], 'login_exitoso');

                header("Location: index.php");
                exit;
            } else {
                $_SESSION['intentos_login']++;
                $_SESSION['ultimo_intento'] = time();
                registrar_log($conn, null, 'login_fallido: ' . $email);
                $error = "Credenciales incorrectas";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Seguro - Billetera Digital V2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        🛡️ VERSION 2 — SISTEMA REMEDIADO & SEGURO (HTTPS / TLS 1.3 / BCRYPT)
    </div>

    <div class="auth-wrapper">
        <div class="card">
            <h2 class="card-title">🔒 Iniciar Sesión Segura</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    🚨 <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Correo Electrónico:</label>
                    <input type="email" name="email" class="form-control" placeholder="usuario@empresa.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn">Ingresar al Portal Seguro</button>
            </form>

            <div class="auth-links">
                <a href="registro.php">Crear nueva cuenta segura</a> &bull; 
                <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>

    <footer>
        Billetera Digital Enterprise &copy; 2026 - Entorno Protegido V2 Remediado
    </footer>
</body>
</html>
