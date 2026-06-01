<?php
$survey = ateneaLatestSurvey($conn, $userId);
$result = $survey ? ateneaEnsureResultForSurvey($conn, $survey) : null;
$recommendations = [];

if ($result) {
    $stmt = $conn->prepare("
        SELECT mensaje, fecha
        FROM recomendaciones
        WHERE resultado_id = ?
        ORDER BY id ASC
    ");

    if ($stmt) {
        $resultId = (int) $result["id"];
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $rows = $stmt->get_result();
        while ($row = $rows->fetch_assoc()) {
            $recommendations[] = $row;
        }
        $stmt->close();
    }
}
?>

<?php if (!$survey || !$result): ?>
    <section class="empty-state">
        <h2>Aun no hay plan personalizado.</h2>
        <p>Completa la encuesta para que Atenea cree recomendaciones con base en tus datos.</p>
        <a class="primary-action" href="form.php">Completar encuesta</a>
    </section>
<?php else: ?>
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Plan de accion</p>
            <h2>Acciones sugeridas para bajar tu riesgo academico.</h2>
            <p>Estas recomendaciones nacen de tu encuesta mas reciente y se guardan en la tabla de recomendaciones.</p>
        </div>
        <div class="hero-stat">
            <span><?= count($recommendations) ?></span>
            <small>acciones activas</small>
        </div>
    </section>

    <section class="plan-list">
        <?php foreach ($recommendations as $index => $recommendation): ?>
            <article class="plan-card">
                <span><?= $index + 1 ?></span>
                <div>
                    <h3><?= h($recommendation["mensaje"]) ?></h3>
                    <p>Recomendacion generada el <?= h(date("d/m/Y", strtotime($recommendation["fecha"]))) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="insight-panel">
        <div>
            <p class="eyebrow">Siguiente paso</p>
            <h2>Vuelve a medir tu progreso despues de aplicar el plan.</h2>
            <p>Una segunda encuesta permite comparar cambios en promedio, asistencia, descanso, estres y administracion del tiempo.</p>
        </div>
        <a class="primary-action" href="form.php">Nueva encuesta</a>
    </section>
<?php endif; ?>
