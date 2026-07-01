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

$officialDataPath = dirname(__DIR__) . "/DATASETS/processed/landing_metrics.json";
$officialData = [];

if (is_file($officialDataPath)) {
    $officialJson = file_get_contents($officialDataPath);
    $officialData = json_decode($officialJson ?: "[]", true) ?: [];
}

$officialKpis = $officialData["kpis"] ?? [];
$officialCharts = $officialData["charts"] ?? [];
$periodSummary = $officialData["period_summary"] ?? [];
$topMunicipalities = $officialData["top_municipalities"] ?? [];

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

function landingAsset($value): string
{
    return "../" . ltrim((string) $value, "/");
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

        <button
            type="button"
            class="landing-menu-toggle"
            data-landing-menu-toggle
            aria-controls="landingNav"
            aria-expanded="false"
            aria-label="Abrir menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav id="landingNav">
            <ul>
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#datos-oficiales">Datos oficiales</a></li>
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
                Analisis de datos reales para entender el riesgo academico estudiantil
            </h2>

            <p>
                Atenea procesa reportes oficiales de eficiencia educativa, limpia
                los datos con Python y convierte indicadores como reprobacion,
                desercion y eficiencia terminal en lecturas faciles de entender.
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

            <h3>Ciclo de vida de datos</h3>

            <div class="grid">

                <div class="box">Ingesta XLSX</div>
                <div class="box">Limpieza con pandas</div>
                <div class="box">Datos faltantes como NULL</div>
                <div class="box">Indicadores ponderados</div>
                <div class="box">Graficas de barras</div>
                <div class="box">Grafica de pastel</div>
                <div class="box">Mapa municipal</div>
                <div class="box">Lectura ODS 4</div>

            </div>

        </div>

    </section>

    <section class="official-data" id="datos-oficiales">
        <div class="official-data__intro">
            <span>Datos oficiales</span>
            <h2>
                Reportes XLSX procesados para medir riesgo academico con enfoque ODS 4
            </h2>
            <p>
                Se analizaron ciclos escolares de 2020-2021 a 2024-2025. El proceso
                limpia encabezados combinados, normaliza municipios y transforma
                datos faltantes en valores NULL para no inventar resultados.
            </p>
            <div class="official-data__actions">
                <a href="../DATASETS/processed/official_education_clean.csv" class="btn-primary" download>Descargar CSV limpio</a>
                <a href="#estadisticas" class="btn-secondary">Ver impacto</a>
            </div>
        </div>

        <?php if (!empty($officialKpis)): ?>
            <div class="official-kpis">
                <article>
                    <strong><?= landingNumber($officialKpis["archivos"] ?? 0, "", "0", 0) ?></strong>
                    <small>archivos XLSX</small>
                </article>
                <article>
                    <strong><?= landingNumber($officialKpis["registros"] ?? 0, "", "0", 0) ?></strong>
                    <small>registros limpios</small>
                </article>
                <article>
                    <strong><?= landingNumber($officialKpis["municipios"] ?? 0, "", "0", 0) ?></strong>
                    <small>municipios</small>
                </article>
                <article>
                    <strong><?= landingNumber($officialKpis["matricula_ultimo_periodo"] ?? 0, "", "0", 0) ?></strong>
                    <small>matricula <?= landingH($officialKpis["periodo_final"] ?? "") ?></small>
                </article>
                <article>
                    <strong><?= landingNumber($officialKpis["riesgo_ultimo_periodo"] ?? null, "", "N/A") ?></strong>
                    <small>riesgo ponderado</small>
                </article>
                <article>
                    <strong><?= landingNumber($officialKpis["desercion_ultimo_periodo"] ?? null, "%", "N/A") ?></strong>
                    <small>desercion ponderada</small>
                </article>
            </div>

            <div class="official-chart-grid">
                <?php if (!empty($officialCharts["trend"])): ?>
                    <article class="official-chart-card official-chart-card--wide">
                        <div>
                            <span>Grafica de barras</span>
                            <h3>Riesgo academico por ciclo escolar</h3>
                        </div>
                        <img src="<?= landingH(landingAsset($officialCharts["trend"])) ?>" alt="Grafica de barras del riesgo por periodo">
                    </article>
                <?php endif; ?>

                <?php if (!empty($officialCharts["factors"])): ?>
                    <article class="official-chart-card">
                        <div>
                            <span>Factores</span>
                            <h3>Ultimo ciclo escolar</h3>
                        </div>
                        <img src="<?= landingH(landingAsset($officialCharts["factors"])) ?>" alt="Grafica de factores de riesgo">
                    </article>
                <?php endif; ?>

                <?php if (!empty($officialCharts["margin"])): ?>
                    <article class="official-chart-card">
                        <div>
                            <span>Pastel</span>
                            <h3>Marginacion escolar</h3>
                        </div>
                        <img src="<?= landingH(landingAsset($officialCharts["margin"])) ?>" alt="Grafica de pastel por marginacion">
                    </article>
                <?php endif; ?>

                <?php if (!empty($officialCharts["map"])): ?>
                    <article class="official-chart-card official-chart-card--wide">
                        <div>
                            <span>Mapa</span>
                            <h3>Riesgo academico municipal en Queretaro</h3>
                        </div>
                        <img src="<?= landingH(landingAsset($officialCharts["map"])) ?>" alt="Mapa municipal de riesgo academico">
                    </article>
                <?php endif; ?>
            </div>

            <div class="official-tables">
                <article>
                    <div class="official-table-head">
                        <span>Prioridad municipal</span>
                        <h3>Municipios con mayor riesgo ponderado</h3>
                    </div>
                    <div class="official-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Municipio</th>
                                    <th>Matricula</th>
                                    <th>Reprobacion</th>
                                    <th>Desercion</th>
                                    <th>Riesgo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topMunicipalities as $municipality): ?>
                                    <tr>
                                        <td><?= landingH($municipality["municipio"] ?? "") ?></td>
                                        <td><?= landingNumber($municipality["matricula"] ?? null, "", "N/A", 0) ?></td>
                                        <td><?= landingNumber($municipality["reprobacion_pct"] ?? null, "%", "N/A") ?></td>
                                        <td><?= landingNumber($municipality["desercion_pct"] ?? null, "%", "N/A") ?></td>
                                        <td><?= landingNumber($municipality["riesgo_score"] ?? null, "", "N/A") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article>
                    <div class="official-table-head">
                        <span>Resumen historico</span>
                        <h3>Ciclos escolares procesados</h3>
                    </div>
                    <div class="official-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Nivel</th>
                                    <th>Escuelas</th>
                                    <th>Matricula</th>
                                    <th>Riesgo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periodSummary as $period): ?>
                                    <tr>
                                        <td><?= landingH($period["periodo"] ?? "") ?></td>
                                        <td><?= landingH($period["nivel_reporte"] ?? "") ?></td>
                                        <td><?= landingNumber($period["escuelas"] ?? null, "", "N/A", 0) ?></td>
                                        <td><?= landingNumber($period["matricula"] ?? null, "", "N/A", 0) ?></td>
                                        <td><?= landingNumber($period["riesgo_score"] ?? null, "", "N/A") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        <?php else: ?>
            <div class="official-empty">
                <h3>Los datos oficiales aun no estan procesados.</h3>
                <p>Ejecuta <strong>python3 PYTHON/process_official_datasets.py</strong> para generar el CSV limpio, JSON y graficas.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Problematica -->
    <section class="problem" id="problema">

        <h2 class="section-title">
            Problemática
        </h2>

        <p>
            Los reportes educativos suelen existir como hojas de calculo
            dificiles de leer. Atenea convierte esos archivos en informacion
            limpia, comparable y visual para detectar zonas donde la calidad
            educativa necesita seguimiento.
        </p>

    </section>

    <!-- Objetivos del proyecto -->
    <section class="about" id="objetivos">

        <h2 class="section-title">
            Objetivos del Proyecto
        </h2>

        <div class="cards">

            <div class="card">
                <h4>Ingesta de Datos</h4>

                <p>
                    Leer archivos XLSX oficiales por periodo escolar y nivel educativo.
                </p>
            </div>

            <div class="card">
                <h4>Limpieza y Transformacion</h4>

                <p>
                    Normalizar encabezados, municipios, columnas y valores faltantes.
                </p>
            </div>

            <div class="card">
                <h4>Analisis Visual</h4>

                <p>
                    Mostrar tendencias, factores de riesgo y mapas para tomar decisiones.
                </p>
            </div>

        </div>

    </section>

    <!-- Estadisticas destacadas -->
    <section class="stats" id="estadisticas">

        <h2 class="section-title">
            Impacto del Proyecto
        </h2>

        <div class="stats-container">

            <div class="stat-box">
                <h3><?= landingNumber($officialKpis["registros"] ?? 0, "", "0", 0) ?></h3>
                <p>Registros oficiales procesados</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($officialKpis["escuelas_unicas"] ?? 0, "", "0", 0) ?></h3>
                <p>Escuelas identificadas</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($officialKpis["municipios"] ?? 0, "", "0", 0) ?></h3>
                <p>Municipios analizados</p>
            </div>

            <div class="stat-box">
                <h3><?= landingNumber($officialKpis["riesgo_ultimo_periodo"] ?? null, "", "N/A") ?></h3>
                <p>Riesgo ponderado mas reciente</p>
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
                Se toman los archivos XLSX oficiales agrupados por ciclo escolar.
            </p>
        </div>

        <div class="workflow-box">
            <h3>2</h3>

            <h4>Procesamiento</h4>

            <p>
                Python limpia encabezados combinados, datos faltantes y columnas clave.
            </p>
        </div>

        <div class="workflow-box">
            <h3>3</h3>

            <h4>Análisis</h4>

            <p>
                Se calculan reprobacion, desercion, eficiencia terminal y riesgo.
            </p>
        </div>

        <div class="workflow-box">
            <h3>4</h3>

            <h4>Visualización</h4>

            <p>
                La pagina presenta KPIs, graficas, tablas y un mapa municipal.
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
            Atenea combina Python, pandas, visualizacion de datos, PHP y
            MySQL para convertir datos educativos reales en informacion clara.
        </p>

        <p>
            El proyecto se alinea con el Objetivo de Desarrollo Sostenible
            numero 4 al facilitar el seguimiento de indicadores educativos.
        </p>

    </div>

    <div class="about-project-card">

        <div class="mini-card">
            <h3>Base de Datos</h3>
            <p>Datos del sistema y resultados listos para futuras consultas.</p>
        </div>

        <div class="mini-card">
            <h3>Python Analytics</h3>
            <p><?= landingNumber($officialKpis["archivos"] ?? 0, "", "0", 0) ?> archivos oficiales convertidos en un dataset limpio.</p>
        </div>

        <div class="mini-card">
            <h3>Visualizacion</h3>
            <p>Graficas de barras, pastel y mapa para explicar los hallazgos.</p>
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
    <script src="../JS/landing.js"></script>

</body>

</html>
