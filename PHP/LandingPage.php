<?php
require_once("conexion.php");
require_once("analytics.php");

ateneaEnsureAllResults($conn);

$stats = [
    "usuarios" => 0,
    "encuestas" => 0,
    "promedio" => null,
    "riesgo_alto" => 0,
    "riesgo_medio" => 0,
    "riesgo_bajo" => 0,
];

$result = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios) AS usuarios,
        (SELECT COUNT(*) FROM encuestas) AS encuestas,
        (SELECT AVG(promedio) FROM encuestas) AS promedio,
        (SELECT COUNT(*) FROM resultados WHERE nivel_riesgo = 'Alto') AS riesgo_alto,
        (SELECT COUNT(*) FROM resultados WHERE nivel_riesgo = 'Medio') AS riesgo_medio,
        (SELECT COUNT(*) FROM resultados WHERE nivel_riesgo = 'Bajo') AS riesgo_bajo
");

if ($result) {
    $stats = array_merge($stats, $result->fetch_assoc());
}

function landingNumber($value, string $suffix = "", string $empty = "0"): string
{
    if ($value === null || $value === "") {
        return $empty;
    }

    if (is_numeric($value)) {
        $value = rtrim(rtrim(number_format((float) $value, 1, ".", ""), "0"), ".");
    }

    return htmlspecialchars((string) $value . $suffix, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../IMG/favicon.png">

    <title>Atenea | Predicción de Riesgo Académico</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../CSS/LandingPage.css">
</head>

<body>

    <!-- HEADER -->
    <header>

        <div class="logo">
            <img src="../IMG/logo.png" alt="Logo Atenea">
        </div>

        <nav>
            <ul>
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#problema">Problemática</a></li>
                <li><a href="#objetivos">Objetivos</a></li>
                <li><a href="#estadisticas">Estadísticas</a></li>
            </ul>
        </nav>

    </header>

    <!-- HERO -->
    <section class="hero" id="inicio">

        <div class="hero-text">

            <span>ODS 4 · EDUCACIÓN DE CALIDAD</span>

            <h2>
                Plataforma inteligente para el análisis del riesgo académico estudiantil
            </h2>

            <p>
                Atenea es una plataforma enfocada en la recopilación y análisis
                de datos académicos y hábitos estudiantiles, permitiendo
                identificar patrones relacionados con posibles riesgos académicos.
            </p>

            <div class="buttons">

                <a href="#objetivos" class="btn-primary">
                    Conocer Proyecto
                </a>

                <a href="../PHP/login.php" class="btn-secondary">
                    Iniciar Sesión
                </a>

                <a href="../PHP/form.php" class="btn-secondary">
                    Registrarse
                </a>
            </div>

        </div>

        <div class="hero-card">

            <h3>Factores Analizados</h3>

            <div class="grid">

                <div class="box">Horas de sueño</div>
                <div class="box">Estrés académico</div>
                <div class="box">Horas de estudio</div>
                <div class="box">Uso de redes</div>
                <div class="box">Asistencia</div>
                <div class="box">Promedio escolar</div>
                <div class="box">Tiempo transporte</div>
                <div class="box">Materias reprobadas</div>

            </div>

        </div>

    </section>

    <!-- PROBLEMÁTICA -->
    <section class="problem" id="problema">

        <h2 class="section-title">
            Problemática
        </h2>

        <p>
            Muchos estudiantes presentan problemas de rendimiento académico
            debido a factores personales, sociales y académicos.
            Actualmente, gran parte de esta información no es recopilada
            ni analizada de forma estructurada.
        </p>

    </section>

    <!-- OBJETIVOS -->
    <section class="about" id="objetivos">

        <h2 class="section-title">
            Objetivos del Proyecto
        </h2>

        <div class="cards">

            <div class="card">
                <h4>Recolección de Datos</h4>

                <p>
                    Obtener información estudiantil mediante formularios digitales.
                </p>
            </div>

            <div class="card">
                <h4>Análisis de Información</h4>

                <p>
                    Identificar patrones relacionados con riesgo académico.
                </p>
            </div>

            <div class="card">
                <h4>Visualización</h4>

                <p>
                    Mostrar estadísticas mediante una plataforma web.
                </p>
            </div>

        </div>

    </section>

    <!-- ESTADÍSTICAS -->
    <section class="stats" id="estadisticas">

        <h2 class="section-title">
            Datos Analizados
        </h2>

        <div class="stats-container">

            <div class="stat-box">
                <h3><?= landingNumber($stats["usuarios"]) ?></h3>
                <p>Estudiantes registrados</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($stats["encuestas"]) ?></h3>
                <p>Encuestas respondidas</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($stats["promedio"], "", "N/A") ?></h3>
                <p>Promedio académico global</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($stats["riesgo_alto"]) ?></h3>
                <p>Perfiles con riesgo alto detectado</p>
            </div>

        </div>

    </section>

   <!-- CÓMO FUNCIONA -->
<section class="workflow">

    <h2 class="section-title">
        ¿Cómo funciona Atenea?
    </h2>

    <div class="workflow-container">

        <div class="workflow-box">
            <h3>1</h3>

            <h4>Recolección</h4>

            <p>
                Los estudiantes responden formularios relacionados con
                hábitos, rendimiento y entorno académico.
            </p>
        </div>

        <div class="workflow-box">
            <h3>2</h3>

            <h4>Procesamiento</h4>

            <p>
                La información es organizada y almacenada en una base
                de datos para su análisis.
            </p>
        </div>

        <div class="workflow-box">
            <h3>3</h3>

            <h4>Análisis</h4>

            <p>
                Atenea identifica patrones asociados a posibles riesgos
                académicos estudiantiles.
            </p>
        </div>

        <div class="workflow-box">
            <h3>4</h3>

            <h4>Visualización</h4>

            <p>
                Los resultados se muestran mediante estadísticas y paneles
                interactivos.
            </p>
        </div>

    </div>

</section>

<!-- SOBRE ATENEA -->
<section class="about-project">

    <div class="about-project-text">

        <span>PLATAFORMA EDUCATIVA</span>

        <h2>
            Tecnología aplicada al análisis académico
        </h2>

        <p>
            Atenea busca combinar análisis de datos, bases de datos y
            tecnologías web para desarrollar una herramienta capaz de
            apoyar el monitoreo académico estudiantil.
        </p>

        <p>
            El proyecto está alineado con el Objetivo de Desarrollo
            Sostenible número 4 de la Agenda 2030:
            Educación de Calidad.
        </p>

    </div>

    <div class="about-project-card">

        <div class="mini-card">
            <h3>Base de Datos</h3>
            <p>Almacenamiento estructurado de información estudiantil.</p>
        </div>

        <div class="mini-card">
            <h3>Análisis</h3>
            <p><?= landingNumber($stats["riesgo_bajo"]) ?> perfiles en riesgo bajo y <?= landingNumber($stats["riesgo_medio"]) ?> en seguimiento medio.</p>
        </div>

        <div class="mini-card">
            <h3>Web Platform</h3>
            <p>Acceso rápido y visualización desde cualquier lugar.</p>
        </div>

    </div>

</section>

<!-- CALL TO ACTION -->
<section class="cta">

    <h2>
        El análisis de datos también puede transformar la educación
    </h2>

    <p>
        Atenea busca convertirse en una herramienta tecnológica capaz
        de apoyar la detección temprana de riesgos académicos.
    </p>

    <a href="../PHP/form.php" class="btn-primary">
        Comenzar
    </a>

</section>

    <!-- FOOTER -->
    <footer>

        <p>
            Atenea © 2026 · Proyecto Integrador · por I.A?
        </p>

    </footer>

</body>

</html>
