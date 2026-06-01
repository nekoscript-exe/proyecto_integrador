<?php

require_once("conexion.php");
require_once("analytics.php");

/** @var mysqli $conn */

$error = "";
$carrerasPermitidas = [
    "Ingeniería en Tecnología Automotriz",
    "Ingeniería Mecatrónica",
    "Ingeniería en Manufactura Avanzada",
    "Ingeniería en Tecnologías de la Información e Innovación Digital",
    "Ingeniería en Datos e Inteligencia Artificial",
    "Licenciatura en Administración",
    "Licenciatura en Comercio Internacional y Aduanas"
];

function nombreCompletoValido(string $nombre): bool
{
    $nombre = trim(preg_replace('/\s+/', ' ', $nombre));

    if (!preg_match('/^[\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+(?:[\' -][\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+)*(?:\s+[\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+(?:[\' -][\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+)*)+$/u', $nombre)) {
        return false;
    }

    $partes = preg_split('/\s+/', $nombre);
    if (count($partes) < 3) {
        return false;
    }

    foreach ($partes as $parte) {
        $largo = function_exists("mb_strlen") ? mb_strlen($parte, "UTF-8") : strlen($parte);
        if ($largo < 2) {
            return false;
        }
    }

    return true;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombre = trim(preg_replace('/\s+/', ' ', $_POST["nombre"] ?? ""));
    $correo = trim($_POST["correo"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $edad = (int) ($_POST["edad"] ?? 0);
    $carreraOpcion = trim($_POST["carrera_opcion"] ?? "");
    $carreraOtra = trim(preg_replace('/\s+/', ' ', $_POST["carrera_otra"] ?? ""));
    $carrera = $carreraOpcion === "Otra" ? $carreraOtra : $carreraOpcion;

    $camposEncuesta = [
        "promedio",
        "materias_reprobadas",
        "asistencia",
        "horas_estudio",
        "horas_sueno",
        "uso_redes",
        "actividad_fisica",
        "entrega_tareas",
        "tiempo_transporte",
        "trabaja",
        "acceso_internet",
        "espacio_estudio",
        "nivel_estres",
        "desmotivacion",
        "herramientas_digitales",
        "administracion_tiempo"
    ];

    $faltanDatosEncuesta = false;
    foreach($camposEncuesta as $campo){
        if(!isset($_POST[$campo]) || $_POST[$campo] === ""){
            $faltanDatosEncuesta = true;
            break;
        }
    }

    if(
        empty($nombre) ||
        empty($correo) ||
        empty($password) ||
        empty($edad) ||
        empty($carrera)
    ){

        $error = "Todos los campos son obligatorios.";

    }elseif(!nombreCompletoValido($nombre)){

        $error = "Ingresa nombre(s) y dos apellidos. Ejemplo: Maria Fernanda Rosales Silva.";

    }elseif(!filter_var($correo, FILTER_VALIDATE_EMAIL)){

        $error = "Ingresa un correo válido.";

    }elseif($carreraOpcion !== "Otra" && !in_array($carreraOpcion, $carrerasPermitidas, true)){

        $error = "Selecciona una carrera válida.";

    }elseif($carreraOpcion === "Otra" && (function_exists("mb_strlen") ? mb_strlen($carreraOtra, "UTF-8") : strlen($carreraOtra)) < 4){

        $error = "Escribe el nombre completo de tu carrera.";

    }elseif($faltanDatosEncuesta){

        $error = "Primero completa la encuesta académica.";

    }else{

        $stmtCheck = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE correo = ?
            LIMIT 1
        ");

        if(!$stmtCheck){
            $error = "No fue posible validar el correo.";
        }else{
            $stmtCheck->bind_param("s", $correo);
            $stmtCheck->execute();
            $resultado = $stmtCheck->get_result();

            if($resultado && $resultado->num_rows > 0){

                $error = "El correo ya está registrado.";

            }else{

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $conn->begin_transaction();

                try{
                    $stmtUsuario = $conn->prepare("
                        INSERT INTO usuarios
                        (
                            nombre,
                            correo,
                            password,
                            edad,
                            carrera
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");

                    if(!$stmtUsuario){
                        throw new Exception("No fue posible crear el usuario.");
                    }

                    $stmtUsuario->bind_param(
                        "sssis",
                        $nombre,
                        $correo,
                        $passwordHash,
                        $edad,
                        $carrera
                    );
                    $stmtUsuario->execute();
                    $usuario_id = $conn->insert_id;
                    $stmtUsuario->close();

                    $promedio = (float) $_POST["promedio"];
                    $materias_reprobadas = (int) $_POST["materias_reprobadas"];
                    $asistencia = (int) $_POST["asistencia"];
                    $horas_estudio = (float) $_POST["horas_estudio"];
                    $horas_sueno = (float) $_POST["horas_sueno"];
                    $uso_redes = (float) $_POST["uso_redes"];
                    $actividad_fisica = (int) $_POST["actividad_fisica"];
                    $entrega_tareas = (int) $_POST["entrega_tareas"];
                    $tiempo_transporte = (int) $_POST["tiempo_transporte"];
                    $trabaja = (int) $_POST["trabaja"];
                    $acceso_internet = (int) $_POST["acceso_internet"];
                    $espacio_estudio = (int) $_POST["espacio_estudio"];
                    $nivel_estres = (int) $_POST["nivel_estres"];
                    $desmotivacion = (int) $_POST["desmotivacion"];
                    $herramientas_digitales = (int) $_POST["herramientas_digitales"];
                    $administracion_tiempo = (int) $_POST["administracion_tiempo"];

                    $stmtEncuesta = $conn->prepare("
                        INSERT INTO encuestas
                        (
                            usuario_id,
                            promedio,
                            materias_reprobadas,
                            asistencia,
                            horas_estudio,
                            horas_sueno,
                            uso_redes,
                            actividad_fisica,
                            entrega_tareas,
                            tiempo_transporte,
                            trabaja,
                            acceso_internet,
                            espacio_estudio,
                            nivel_estres,
                            desmotivacion,
                            herramientas_digitales,
                            administracion_tiempo
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");

                    if(!$stmtEncuesta){
                        throw new Exception("No fue posible guardar la encuesta.");
                    }

                    $stmtEncuesta->bind_param(
                        "ididdddiiiiiiiiii",
                        $usuario_id,
                        $promedio,
                        $materias_reprobadas,
                        $asistencia,
                        $horas_estudio,
                        $horas_sueno,
                        $uso_redes,
                        $actividad_fisica,
                        $entrega_tareas,
                        $tiempo_transporte,
                        $trabaja,
                        $acceso_internet,
                        $espacio_estudio,
                        $nivel_estres,
                        $desmotivacion,
                        $herramientas_digitales,
                        $administracion_tiempo
                    );
                    $stmtEncuesta->execute();
                    $encuestaId = $conn->insert_id;
                    $stmtEncuesta->close();

                    $encuesta = [
                        "id" => $encuestaId,
                        "usuario_id" => $usuario_id,
                        "promedio" => $promedio,
                        "materias_reprobadas" => $materias_reprobadas,
                        "asistencia" => $asistencia,
                        "horas_estudio" => $horas_estudio,
                        "horas_sueno" => $horas_sueno,
                        "uso_redes" => $uso_redes,
                        "actividad_fisica" => $actividad_fisica,
                        "entrega_tareas" => $entrega_tareas,
                        "tiempo_transporte" => $tiempo_transporte,
                        "trabaja" => $trabaja,
                        "acceso_internet" => $acceso_internet,
                        "espacio_estudio" => $espacio_estudio,
                        "nivel_estres" => $nivel_estres,
                        "desmotivacion" => $desmotivacion,
                        "herramientas_digitales" => $herramientas_digitales,
                        "administracion_tiempo" => $administracion_tiempo
                    ];

                    ateneaEnsureResultForSurvey($conn, $encuesta);

                    $conn->commit();

                    header("Location: login.php");
                    exit();

                }catch(Throwable $e){
                    $conn->rollback();
                    $error = $e->getMessage();
                }

            }

            $stmtCheck->close();
        }

    }

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
        Atenea | Registro
    </title>

    <link
        rel="stylesheet"
        href="../CSS/register.css"
    >

</head>

<body>

    <div class="container">

        <!-- LEFT -->

        <div class="left-panel">

            <div class="overlay"></div>

            <div class="left-content">

                <div class="brand-lockup">
                    <img src="../IMG/logo.png" alt="Logo Atenea" class="brand-logo">
                </div>

                <span class="badge">
                    REGISTRO
                </span>

                <p class="description">

                    Tu encuesta académica fue completada.
                    Ahora crea tu cuenta para guardar
                    tus datos y acceder al dashboard.

                </p>

            </div>

        </div>

        <!-- RIGHT -->

        <div class="right-panel">

            <form
                class="register-card"
                method="POST"
            >

                <!-- =========================
                     HIDDEN INPUTS ENCUESTA
                ========================== -->

                <?php

                $campos = [

                    "promedio",
                    "materias_reprobadas",
                    "asistencia",
                    "horas_estudio",

                    "horas_sueno",
                    "uso_redes",
                    "actividad_fisica",
                    "entrega_tareas",

                    "tiempo_transporte",
                    "trabaja",
                    "acceso_internet",
                    "espacio_estudio",

                    "nivel_estres",
                    "desmotivacion",
                    "herramientas_digitales",
                    "administracion_tiempo"

                ];

                foreach($campos as $campo){

                    if(isset($_POST[$campo])){

                        ?>

                        <input
                            type="hidden"
                            name="<?php echo $campo; ?>"
                            value="<?php echo htmlspecialchars($_POST[$campo]); ?>"
                        >

                        <?php

                    }

                }

                ?>

                <h2>
                    Crear Cuenta
                </h2>

                <p class="subtitle">

                    Completa la información
                    para continuar.

                </p>

                <!-- ERROR -->

                <?php if(!empty($error)): ?>

                    <div class="error-box">

                        <?php echo $error; ?>

                    </div>

                <?php endif; ?>

                <!-- NOMBRE -->

                <div class="input-group">

                    <label>
                        Nombre Completo
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        autocomplete="name"
                        minlength="5"
                        placeholder="Ejemplo: Ian Hernandez"
                        title="Escribe nombre(s) y dos apellidos. Ejemplo: Maria Fernanda Rosales Silva."
                        value="<?php echo htmlspecialchars($nombre ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>

                <!-- CORREO -->

                <div class="input-group">

                    <label>
                        Correo Electrónico
                    </label>

                    <input
                        type="email"
                        name="correo"
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($correo ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>

                <!-- PASSWORD -->

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

                <!-- EDAD -->

                <div class="input-group">

                    <label>
                        Edad
                    </label>

                    <input
                        type="number"
                        name="edad"
                        min="15"
                        max="99"
                        value="<?php echo htmlspecialchars((string) ($edad ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>

                <!-- CARRERA -->

                <div class="input-group">

                    <label>
                        Carrera
                    </label>

                    <select
                        name="carrera_opcion"
                        id="carreraOpcion"
                        required
                    >
                        <option value="">Selecciona tu carrera</option>

                        <?php foreach($carrerasPermitidas as $carreraItem): ?>
                            <option
                                value="<?php echo htmlspecialchars($carreraItem, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo (($carreraOpcion ?? '') === $carreraItem) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($carreraItem, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>

                        <option
                            value="Otra"
                            <?php echo (($carreraOpcion ?? '') === 'Otra') ? 'selected' : ''; ?>
                        >
                            Otra
                        </option>
                    </select>

                    <input
                        type="text"
                        name="carrera_otra"
                        id="carreraOtra"
                        class="other-career"
                        placeholder="Escribe tu carrera"
                        value="<?php echo htmlspecialchars($carreraOtra ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >

                </div>

                <!-- BOTON -->

                <button
                    type="submit"
                    class="register-btn"
                >
                    Crear Cuenta
                </button>

                <div class="extra-links">
                    <a href="login.php">
                        Ya tengo cuenta
                    </a>
                </div>

            </form>

        </div>

    </div>

<script src="../JS/register.js"></script>

</body>

</html>
