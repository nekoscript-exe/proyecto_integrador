<?php
$survey = ateneaLatestSurvey($conn, $userId);
$result = $survey ? ateneaEnsureResultForSurvey($conn, $survey) : null;
$score = $result ? (float) $result["puntuacion_riesgo"] : 0;
$level = $result["nivel_riesgo"] ?? "Sin datos";

$factors = [];
if ($survey) {
    $factors = [
        ["label" => "Promedio", "value" => max(0, min(100, ((float) $survey["promedio"]) * 10)), "display" => metricValue($survey["promedio"])],
        ["label" => "Asistencia", "value" => max(0, min(100, (int) $survey["asistencia"])), "display" => metricValue($survey["asistencia"], "%")],
        ["label" => "Estudio diario", "value" => max(0, min(100, ((float) $survey["horas_estudio"]) * 20)), "display" => metricValue($survey["horas_estudio"], " h")],
        ["label" => "Descanso", "value" => max(0, min(100, ((float) $survey["horas_sueno"]) * 12.5)), "display" => metricValue($survey["horas_sueno"], " h")],
        ["label" => "Manejo del tiempo", "value" => max(0, min(100, ((int) $survey["administracion_tiempo"]) * 20)), "display" => metricValue($survey["administracion_tiempo"], "/5")],
    ];
}
?>

<?php if (!$survey): ?>
    <section class="empty-state">
        <h2>Necesitas completar la encuesta.</h2>
        <p>Con tus respuestas Atenea puede estimar riesgo academico y crear recomendaciones personalizadas.</p>
        <a class="primary-action" href="form.php">Completar encuesta</a>
    </section>
<?php else: ?>
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Diagnostico personal</p>
            <h2>Tu riesgo academico actual es <?= h($level) ?>.</h2>
            <p><?= h($result["observaciones"] ?? "Analisis generado con tu encuesta mas reciente.") ?></p>
        </div>
        <div class="hero-stat">
            <span><?= metricValue($score, "/100") ?></span>
            <small>puntuacion de riesgo</small>
        </div>
    </section>

    <section class="metric-grid">
        <article class="metric-card">
            <small>Promedio</small>
            <strong><?= metricValue($survey["promedio"]) ?></strong>
        </article>
        <article class="metric-card">
            <small>Materias reprobadas</small>
            <strong><?= metricValue($survey["materias_reprobadas"]) ?></strong>
        </article>
        <article class="metric-card">
            <small>Estres</small>
            <strong><?= metricValue($survey["nivel_estres"], "/10") ?></strong>
        </article>
    </section>

    <section class="detail-panel">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Radar academico</p>
                <h2>Factores protectores</h2>
            </div>
            <a class="text-link" href="dashboard.php?v=plan">Ver plan</a>
        </div>

        <div class="bar-list">
            <?php foreach ($factors as $factor): ?>
                <div class="bar-item">
                    <div>
                        <span><?= h($factor["label"]) ?></span>
                        <strong><?= $factor["display"] ?></strong>
                    </div>
                    <div class="bar-track">
                        <span style="width: <?= (float) $factor["value"] ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
