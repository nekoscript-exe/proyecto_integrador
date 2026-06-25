<?php
require_once("conexion.php");
require_once("analytics.php");
require_once("dataset_service.php");

ateneaEnsureAllResults($conn);
ateneaDatasetEnsureSchema($conn);

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

$latestDataset = ateneaDatasetCurrentUpload($conn);
$latestDatasetRows = $latestDataset ? ateneaDatasetRows($conn, (int) $latestDataset["id"], 4) : [];
$datasetSummaries = $latestDataset
    ? [
        ateneaDatasetNumericSummary($conn, (int) $latestDataset["id"], "age", "Edad", "", 0),
        ateneaDatasetNumericSummary($conn, (int) $latestDataset["id"], "study_hours_per_day", "Horas de estudio", " h", 1),
        ateneaDatasetNumericSummary($conn, (int) $latestDataset["id"], "attendance_percentage", "Asistencia", "%", 1),
        ateneaDatasetNumericSummary($conn, (int) $latestDataset["id"], "final_percentage", "Final", "%", 1),
    ]
    : [];

function landingNumber($value, string $suffix = "", string $empty = "0", int $decimals = 1): string
{
    if ($value === null || $value === "") {
        return $empty;
    }

    if (is_numeric($value)) {
        $value = rtrim(rtrim(number_format((float) $value, $decimals, ".", ""), "0"), ".");
    }

    return htmlspecialchars((string) $value . $suffix, ENT_QUOTES, "UTF-8");
}

function landingH($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            var theme = localStorage.getItem("atenea-theme");
            if (theme === "light" || theme === "dark") {
                document.documentElement.dataset.theme = theme;
            }
        })();
    </script>
    <link rel="icon" type="image/png" href="../IMG/favicon.png">

    <title>Atenea | Predicción de Riesgo Académico</title>

    <!-- Cargamos la hoja de estilos -->
    <link rel="stylesheet" href="../CSS/LandingPage.css">
</head>

<body>

    <!-- Encabezado principal -->
    <header>

        <div class="logo">
            <img src="../IMG/logo.png" alt="Logo Atenea">
        </div>

        <nav>
            <ul>
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#datos-reales">Datos reales</a></li>
                <li><a href="#problema">Problemática</a></li>
                <li><a href="#objetivos">Objetivos</a></li>
                <li><a href="#estadisticas">Estadísticas</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <button type="button" class="theme-toggle" data-theme-toggle>Modo oscuro</button>
        </div>

    </header>

    <!-- Seccion principal -->
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

    <?php if ($latestDataset): ?>
        <section class="dataset-preview" id="datos-reales">
            <div class="dataset-preview__copy">
                <span>Datos reales</span>
                <h2>
                    Un vistazo al dataset estudiantil ya procesado dentro de ATENEA
                </h2>
                <p>
                    La plataforma ya integra un CSV real de rendimiento estudiantil, con datos crudos,
                    limpieza, analisis y resultados listos para explorar desde el dashboard.
                </p>

                <div class="dataset-preview__stats">
                    <article>
                        <strong><?= landingNumber($latestDataset["filas_clean"] ?? 0) ?></strong>
                        <small>registros limpios</small>
                    </article>
                    <article>
                        <strong><?= landingNumber($latestDataset["porcentaje_aprobados"] ?? 0, "%") ?></strong>
                        <small>aprobacion</small>
                    </article>
                    <article>
                        <strong><?= landingNumber($latestDataset["promedio_general"] ?? 0, "%") ?></strong>
                        <small>promedio general</small>
                    </article>
                </div>

                <div class="dataset-preview__actions">
                    <a href="../PHP/login.php" class="btn-primary">Abrir dashboard</a>
                    <a href="../PHP/form.php" class="btn-secondary">Crear cuenta</a>
                </div>
            </div>

            <div class="dataset-preview__panel">
                <div class="dataset-preview__panel-head">
                    <div>
                        <p>Ultima carga</p>
                        <strong><?= landingH($latestDataset["nombre_original"] ?? "Dataset") ?></strong>
                    </div>
                    <a href="../PHP/login.php">Ver completo</a>
                </div>

                <div class="dataset-preview__bars">
                    <?php foreach ($datasetSummaries as $summary): ?>
                        <?php if (!$summary) { continue; } ?>
                        <?php
                            $maxValue = (float) ($summary["max"] ?? 0);
                            $avgValue = (float) ($summary["avg"] ?? 0);
                            $percent = $maxValue > 0 ? round(($avgValue / $maxValue) * 100, 2) : 0;
                        ?>
                        <article>
                            <div class="dataset-preview__bars-top">
                                <strong><?= landingH($summary["label"]) ?></strong>
                                <span>
                                    <?= landingNumber($summary["min"] ?? 0, "", "0", $summary["decimals"] ?? 1) ?>
                                    -
                                    <?= landingNumber($summary["max"] ?? 0, $summary["unit"], "0", $summary["decimals"] ?? 1) ?>
                                </span>
                            </div>
                            <div class="dataset-preview__track">
                                <span style="width: <?= max(5, min(100, $percent)) ?>%"></span>
                            </div>
                            <small>Promedio: <?= landingNumber($summary["avg"] ?? 0, $summary["unit"], "0", $summary["decimals"] ?? 1) ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (count($latestDatasetRows) > 0): ?>
                    <div class="dataset-preview__table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Edad</th>
                                    <th>Asistencia</th>
                                    <th>Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestDatasetRows as $row): ?>
                                    <tr>
                                        <td><?= landingH($row["student_id"]) ?></td>
                                        <td><?= landingNumber($row["age"], "", 0) ?></td>
                                        <td><?= landingNumber($row["attendance_percentage"], "%") ?></td>
                                        <td><?= landingNumber($row["final_percentage"], "%") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Problematica -->
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

    <!-- Objetivos del proyecto -->
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

    <!-- Estadisticas destacadas -->
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

   <!-- Como funciona Atenea -->
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

<!-- Bloque sobre la plataforma -->
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

<!-- Llamado a la accion -->
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

    <!-- Pie de pagina -->
    <footer>

        <p>
            Atenea © 2026 · Proyecto Integrador · por I.A?
        </p>

    </footer>

    <script src="../JS/theme.js"></script>

</body>

</html>
