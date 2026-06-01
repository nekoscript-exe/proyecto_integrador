<?php
session_start();

require_once("conexion.php");
require_once("security.php");

if (!isset($conn)) {
    die("Error: conexión no encontrada.");
}

ateneaEnsureSecuritySchema($conn);

$token = trim($_GET["token"] ?? $_POST["token"] ?? "");
$message = "";
$tokenData = $token !== "" ? ateneaGetPasswordReset($conn, $token) : null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = trim($_POST["password"] ?? "");
    $confirm = trim($_POST["confirm_password"] ?? "");

    if (!$tokenData) {
        $message = "El enlace no es válido o ya expiró.";
    } elseif ($password === "" || strlen($password) < 8) {
        $message = "La nueva contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirm) {
        $message = "Las contraseñas no coinciden.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $message = "No fue posible actualizar la contraseña.";
        } else {
            $userId = (int) $tokenData["usuario_id"];
            $stmt->bind_param("si", $passwordHash, $userId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ? LIMIT 1");
            if ($stmt) {
                $resetId = (int) $tokenData["id"];
                $stmt->bind_param("i", $resetId);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: login.php?reset=ok");
            exit();
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
    <title>Atenea | Nueva contraseña</title>
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
                <p class="subtitle">Nueva contraseña</p>
                <p class="description">Este enlace solo puede usarse una vez y expira al poco tiempo.</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Crear nueva contraseña</h2>
                <p class="login-text">Elige una contraseña segura para volver a entrar a tu cuenta.</p>

                <?php if ($message !== ""): ?>
                    <div class="error-box"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>

                <?php if (!$tokenData && $message === ""): ?>
                    <div class="error-box">El enlace no es válido o ya expiró.</div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, "UTF-8") ?>">
                        <div class="input-group">
                            <label>Nueva contraseña</label>
                            <input type="password" name="password" minlength="8" required>
                        </div>
                        <div class="input-group">
                            <label>Confirmar contraseña</label>
                            <input type="password" name="confirm_password" minlength="8" required>
                        </div>
                        <button type="submit" class="login-btn">Actualizar contraseña</button>
                    </form>
                <?php endif; ?>

                <div class="extra-links">
                    <a href="login.php">Volver al inicio de sesión</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
