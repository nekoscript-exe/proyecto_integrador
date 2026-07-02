<?php
session_start();

require_once("conexion.php");
require_once("security.php");
require_once("mailer.php");

if (!isset($conn)) {
    die("Error: conexión no encontrada.");
}

ateneaEnsureSecuritySchema($conn);

$message = "";
$messageType = "error";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");

    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $message = "Escribe un correo válido.";
        $messageType = "error";
    } else {
        // Buscamos la cuenta y luego generamos el enlace de recuperacion
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
                    $absoluteLink = rtrim(APP_URL, "/") . "/PHP/reset_password.php?token=" . urlencode($reset["token"]);
                    $safeName = htmlspecialchars((string) $user["nombre"], ENT_QUOTES, "UTF-8");
                    $safeLink = htmlspecialchars($absoluteLink, ENT_QUOTES, "UTF-8");
                    $htmlBody = "
                        <div style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #0d1724;\">
                            <h2>Restablecer contrasena en Atenea</h2>
                            <p>Hola {$safeName},</p>
                            <p>Recibimos una solicitud para cambiar la contrasena de tu cuenta.</p>
                            <p>
                                <a href=\"{$safeLink}\" style=\"display: inline-block; padding: 12px 18px; background: #00d9ff; color: #08111f; border-radius: 10px; text-decoration: none; font-weight: 700;\">
                                    Cambiar contrasena
                                </a>
                            </p>
                            <p>Este enlace expira en 1 hora y solo puede usarse una vez.</p>
                            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                        </div>
                    ";
                    $textBody = "Hola " . $user["nombre"] . ",\n\n"
                        . "Usa este enlace para restablecer tu contrasena:\n"
                        . $absoluteLink . "\n\n"
                        . "Este enlace expira en 1 hora y solo puede usarse una vez.\n\n"
                        . "Si no solicitaste este cambio, puedes ignorar este correo.";

                    ateneaSendMail(
                        $user["correo"],
                        $user["nombre"],
                        "Atenea - Restablecer contrasena",
                        $htmlBody,
                        $textBody
                    );
                }
            }

            $message = "Si el correo existe en la plataforma, enviaremos un enlace de recuperacion.";
            $messageType = "success";
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
    <script src="../JS/theme.js" defer></script>
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
            <div class="theme-row">
                <button type="button" class="theme-toggle" data-theme-toggle>Modo oscuro</button>
            </div>
            <div class="login-card">
                <h2>Restablecer contrasena</h2>
                <p class="login-text">Escribe tu correo registrado y te guiaremos con un enlace seguro.</p>

                <?php if ($message !== ""): ?>
                    <div class="<?= $messageType === "success" ? "success-box" : "error-box" ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label>Correo electrónico</label>
                        <input type="email" name="correo" required>
                    </div>
                    <button type="submit" class="login-btn">Enviar enlace</button>
                </form>

                <div class="extra-links">
                    <a href="login.php">Volver al inicio de sesión</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
