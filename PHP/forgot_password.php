<?php
session_start();

require_once("conexion.php");
require_once("security.php");

if (!isset($conn)) {
    die("Error: conexión no encontrada.");
}

ateneaEnsureSecuritySchema($conn);

$message = "";
$debugLink = "";
$showDebugLink = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");

    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $message = "Escribe un correo válido.";
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, correo FROM usuarios WHERE correo = ? LIMIT 1");
        if (!$stmt) {
            $message = "No fue posible procesar la solicitud.";
        } else {
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($user) {
                $reset = ateneaCreatePasswordReset($conn, (int) $user["id"]);
                if ($reset) {
                    $debugLink = "reset_password.php?token=" . urlencode($reset["token"]);
                    $absoluteLink = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http")
                        . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost")
                        . rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? "/PHP/forgot_password.php"), "/")
                        . "/reset_password.php?token=" . urlencode($reset["token"]);

                    $showDebugLink = in_array(($_SERVER["HTTP_HOST"] ?? ""), ["localhost", "127.0.0.1"], true)
                        || str_contains(($_SERVER["HTTP_HOST"] ?? ""), ".local")
                        || str_contains(($_SERVER["SERVER_NAME"] ?? ""), "localhost");

                    @mail(
                        $user["correo"],
                        "Atenea - Restablecer contrasena",
                        "Hola " . $user["nombre"] . ",\n\nUsa este enlace para restablecer tu contrasena:\n" . $absoluteLink . "\n\nEste enlace expira en 1 hora."
                    );
                }
            }

            $message = "Si el correo existe en la plataforma, te mostramos el enlace de recuperacion.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../IMG/favicon.png">
    <title>Atenea | Recuperar contrasena</title>
    <link rel="stylesheet" href="../CSS/login.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="overlay"></div>
            <div class="content">
                <div class="brand-lockup">
                    <img src="../IMG/logo.png" alt="Logo Atenea" class="brand-logo">
                </div>
                <p class="subtitle">Recuperacion de acceso</p>
                <p class="description">Te ayudamos a recuperar tu cuenta con un enlace temporal de un solo uso.</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Restablecer contrasena</h2>
                <p class="login-text">Escribe tu correo registrado y te guiaremos con un enlace seguro.</p>

                <?php if ($message !== ""): ?>
                    <div class="error-box"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label>Correo electrónico</label>
                        <input type="email" name="correo" required>
                    </div>
                    <button type="submit" class="login-btn">Enviar enlace</button>
                </form>

                <?php if ($showDebugLink && $debugLink !== ""): ?>
                    <div class="reset-link-box">
                        <p>Enlace temporal generado para desarrollo local:</p>
                        <a href="<?= htmlspecialchars($debugLink, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($debugLink, ENT_QUOTES, "UTF-8") ?></a>
                    </div>
                <?php endif; ?>

                <div class="extra-links">
                    <a href="login.php">Volver al inicio de sesión</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
