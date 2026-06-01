<?php
$distribution = ["Bajo" => 0, "Medio" => 0, "Alto" => 0];
$communityStats = [
    "avg_promedio" => null,
    "avg_asistencia" => null,
    "avg_estres" => null,
    "avg_riesgo" => null,
];
$careerRows = [];

$result = $conn->query("
    SELECT nivel_riesgo, COUNT(*) AS total
    FROM resultados
    GROUP BY nivel_riesgo
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $distribution[$row["nivel_riesgo"]] = (int) $row["total"];
    }
}

$result = $conn->query("
    SELECT
        AVG(e.promedio) AS avg_promedio,
        AVG(e.asistencia) AS avg_asistencia,
        AVG(e.nivel_estres) AS avg_estres,
        AVG(r.puntuacion_riesgo) AS avg_riesgo
    FROM encuestas e
    LEFT JOIN resultados r ON r.encuesta_id = e.id
");

if ($result) {
    $communityStats = array_merge($communityStats, $result->fetch_assoc());
}

$result = $conn->query("
    SELECT
        COALESCE(u.carrera, 'Sin carrera') AS carrera,
        COUNT(DISTINCT u.id) AS estudiantes,
        AVG(e.promedio) AS promedio,
        AVG(r.puntuacion_riesgo) AS riesgo
    FROM usuarios u
    LEFT JOIN encuestas e ON e.usuario_id = u.id
    LEFT JOIN resultados r ON r.encuesta_id = e.id
    GROUP BY u.carrera
    ORDER BY estudiantes DESC, promedio DESC
    LIMIT 8
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $careerRows[] = $row;
    }
}

$totalRisk = max(1, array_sum($distribution));
?>

<section class="hero-panel">
    <div>
        <p class="eyebrow">Estadisticas vivas</p>
        <h2>La comunidad Atenea medida con datos reales.</h2>
        <p>Esta vista usa usuarios, encuestas y resultados para mostrar tendencias academicas del grupo.</p>
    </div>
    <div class="hero-stat">
        <span><?= metricValue($communityStats["avg_riesgo"], "/100") ?></span>
        <small>riesgo promedio</small>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <small>Promedio general</small>
        <strong><?= metricValue($communityStats["avg_promedio"]) ?></strong>
    </article>
    <article class="metric-card">
        <small>Asistencia media</small>
        <strong><?= metricValue($communityStats["avg_asistencia"], "%") ?></strong>
    </article>
    <article class="metric-card">
        <small>Estres medio</small>
        <strong><?= metricValue($communityStats["avg_estres"], "/10") ?></strong>
    </article>
</section>

<section class="content-grid">
    <article class="detail-panel">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Distribucion</p>
                <h2>Riesgo academico</h2>
            </div>
        </div>

        <div class="bar-list">
            <?php foreach ($distribution as $label => $count): ?>
                <div class="bar-item">
                    <div>
                        <span><?= h($label) ?></span>
                        <strong><?= $count ?> estudiantes</strong>
                    </div>
                    <div class="bar-track">
                        <span class="risk-fill-<?= strtolower($label) ?>" style="width: <?= ($count / $totalRisk) * 100 ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="detail-panel">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Carreras</p>
                <h2>Rendimiento por grupo</h2>
            </div>
        </div>

        <div class="detail-list">
            <?php foreach ($careerRows as $row): ?>
                <div>
                    <span><?= h($row["carrera"]) ?> · <?= (int) $row["estudiantes"] ?></span>
                    <strong><?= metricValue($row["promedio"]) ?> / <?= metricValue($row["riesgo"], "/100") ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
