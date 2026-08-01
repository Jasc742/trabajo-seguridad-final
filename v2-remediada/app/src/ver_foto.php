<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit("No autorizado");
}

$user_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

define('UPLOAD_DIR', '/var/www/uploads_privados/');

function servir_avatar_defecto() {
    header("Content-Type: image/svg+xml");
    header("Cache-Control: public, max-age=86400");
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
    exit;
}

if (!$usuario || empty($usuario['foto_perfil'])) {
    servir_avatar_defecto();
}

// Prevencion de Path Traversal
$nombre_seguro = basename($usuario['foto_perfil']);
$ruta_completa = UPLOAD_DIR . $nombre_seguro;

if (!file_exists($ruta_completa)) {
    servir_avatar_defecto();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $ruta_completa);
finfo_close($finfo);

$permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mime, $permitidos)) {
    servir_avatar_defecto();
}

header("Content-Type: " . $mime);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Length: " . filesize($ruta_completa));
readfile($ruta_completa);
exit;
