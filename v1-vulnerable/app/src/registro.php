<?php
require_once __DIR__ . '/config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = $_POST['nombre'];
    $email    = $_POST['email'];
    $password = $_POST['password']; // VULN #2: sin politica de complejidad

    // ============================================
    // VULN #2: Broken Authentication
    // - Sin validar longitud/complejidad de password
    // - Password se guarda en texto plano (VULN #12 tambien)
    // ============================================
    $query = "INSERT INTO usuarios (nombre, email, password, rol) 
              VALUES ('$nombre', '$email', '$password', 'user')";

    if (mysqli_query($conn, $query)) {
        $usuario_id = mysqli_insert_id($conn);
        $numero_cuenta = str_pad($usuario_id, 10, '0', STR_PAD_LEFT);
        mysqli_query($conn, "INSERT INTO cuentas (usuario_id, saldo, numero_cuenta) 
                              VALUES ($usuario_id, 0.00, '$numero_cuenta')");
        $mensaje = "Registro exitoso. Ya puedes iniciar sesión.";
    } else {
        $mensaje = "Error: " . mysqli_error($conn); // VULN #11: error expuesto
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Billetera Digital V1</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="version-banner">
        ⚠️ VERSION 1 — ENTORNO VULNERABLE DE PRUEBAS (LABORATORIO PENTEST)
    </div>

    <div class="auth-wrapper">
        <div class="card">
            <h2 class="card-title">📝 Crear Cuenta</h2>
            
            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'exitoso') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="registro.php">
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico:</label>
                    <input type="text" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn">Registrarse</button>
            </form>

            <div class="auth-links">
                <a href="login.php">¿Ya tienes cuenta? Inicia Sesión</a>
            </div>
        </div>
    </div>

    <footer>
        Billetera Digital Pentest &copy; 2026 - Entorno de Laboratorio Vulnerable V1
    </footer>
</body>
</html>
