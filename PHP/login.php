<?php

session_start();

/* CONEXION */
require_once("conexion.php");
require_once("security.php");

/* VALIDAR CONEXION */

if(!isset($conn)){

    die("Error: conexión no encontrada.");

}

ateneaEnsureSecuritySchema($conn);

$error = "";
$success = "";

function usuariosTieneEstado(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'estado'");
    return $result && $result->num_rows > 0;
}

/* LOGIN */

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $correo = trim($_POST["correo"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if(
        empty($correo) ||
        empty($password)
    ){

        $error = "Completa todos los campos.";

    }else{

        $tieneEstado = usuariosTieneEstado($conn);
        $sqlLogin = $tieneEstado
            ? "SELECT id, nombre, correo, password, rol, estado FROM usuarios WHERE correo = ? LIMIT 1"
            : "SELECT id, nombre, correo, password, rol, 'activo' AS estado FROM usuarios WHERE correo = ? LIMIT 1";
        $stmt = $conn->prepare($sqlLogin);

        if(!$stmt){
            $error = "No fue posible validar el acceso.";
        }else{
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if($resultado && $resultado->num_rows == 1){

            $usuario = $resultado->fetch_assoc();

            if(
                password_verify(
                    $password,
                    $usuario["password"]
                )
            ){

                /* SESSION */

                $_SESSION["usuario_id"] = $usuario["id"];

                $_SESSION["usuario_nombre"] = $usuario["nombre"];

                $_SESSION["usuario_correo"] = $usuario["correo"];
                $_SESSION["usuario_rol"] = $usuario["rol"] ?? "usuario";

                if (($usuario["estado"] ?? "activo") === "bloqueado") {
                    session_unset();
                    session_destroy();
                    $error = "Tu cuenta está bloqueada. Contacta a un administrador.";
                } else {
                    /* GUARDAR SESION */

                    $ipUsuario = $_SERVER["REMOTE_ADDR"] ?? "Desconocida";

                    $dispositivo = $_SERVER["HTTP_USER_AGENT"] ?? "Desconocido";

                    $stmtSesion = $conn->prepare("
                        INSERT INTO sesiones
                        (
                            usuario_id,
                            ip_usuario,
                            dispositivo
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )
                    ");

                    if($stmtSesion){
                        $stmtSesion->bind_param(
                            "iss",
                            $usuario["id"],
                            $ipUsuario,
                            $dispositivo
                        );
                        $stmtSesion->execute();
                        $stmtSesion->close();
                    }

                    if (($_SESSION["usuario_rol"] ?? "usuario") === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                }

            }else{

                $error = "Contraseña incorrecta.";

            }

            }else{

                $error = "Correo no registrado.";

            }

            $stmt->close();

        }

    }

}

if (($_GET["reset"] ?? "") === "ok") {
    $success = "Tu contrasena fue actualizada. Ya puedes iniciar sesion.";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link rel="icon" type="image/png" href="../IMG/favicon.png">

    <title>
        Atenea | Iniciar Sesión
    </title>

    <link
        rel="stylesheet"
        href="../CSS/login.css"
    >

</head>

<body>

    <div class="container">

        <!-- LEFT -->
        <div class="left-panel">

            <div class="overlay"></div>

            <div class="content">

                <div class="brand-lockup">
                    <img src="../IMG/logo.png" alt="Logo Atenea" class="brand-logo">
                </div>

                <p class="description">

                    Plataforma enfocada en el análisis
                    de datos académicos y hábitos
                    estudiantiles para predicción de Riesgo Académico

                </p>

            </div>

        </div>

        <!-- RIGHT -->
        <div class="right-panel">

            <div class="login-card">

                <h2>
                    Bienvenido
                </h2>

                <p class="login-text">

                    Inicia sesión para acceder
                    a tu panel académico.

                </p>

                <?php if(!empty($error)): ?>

                    <div class="error-box">

                        <?php echo $error; ?>

                    </div>

                <?php endif; ?>

                <?php if(!empty($success)): ?>

                    <div class="success-box">

                        <?php echo $success; ?>

                    </div>

                <?php endif; ?>

                <form method="POST">

                    <div class="input-group">

                        <label>
                            Correo Electrónico
                        </label>

                        <input
                            type="email"
                            name="correo"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            Contraseña
                        </label>

                        <input
                            type="password"
                            name="password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="login-btn"
                    >

                        Iniciar Sesión

                    </button>

                </form>

                <div class="extra-links">

                    <a href="LandingPage.php">
                        Inicio
                    </a>

                    <a href="../PHP/form.php">
                        Crear Cuenta
                    </a>

                    <a href="../PHP/forgot_password.php">
                        Restablecer contrasena
                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>
