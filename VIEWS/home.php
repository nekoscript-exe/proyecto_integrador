<?php
$students = [];
$community = [
    "total" => 0,
    "avg_promedio" => null,
    "avg_asistencia" => null,
    "avg_estres" => null,
];

$summarySql = "
    SELECT
        COUNT(u.id) AS total,
        AVG(e.promedio) AS avg_promedio,
        AVG(e.asistencia) AS avg_asistencia,
        AVG(e.nivel_estres) AS avg_estres
    FROM usuarios u
    LEFT JOIN (
        SELECT e1.*
        FROM encuestas e1
        INNER JOIN (
            SELECT usuario_id, MAX(id) AS latest_id
            FROM encuestas
            WHERE usuario_id IS NOT NULL
            GROUP BY usuario_id
        ) latest ON latest.latest_id = e1.id
    ) e ON e.usuario_id = u.id
";

$summaryResult = $conn->query($summarySql);
if ($summaryResult) {
    $community = array_merge($community, $summaryResult->fetch_assoc());
}

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.carrera,
        u.fecha_registro,
        e.promedio,
        e.asistencia,
        e.horas_estudio,
        e.nivel_estres,
        e.materias_reprobadas,
        e.desmotivacion,
        e.fecha_encuesta
    FROM usuarios u
    LEFT JOIN (
        SELECT e1.*
        FROM encuestas e1
        INNER JOIN (
            SELECT usuario_id, MAX(id) AS latest_id
            FROM encuestas
            WHERE usuario_id IS NOT NULL
            GROUP BY usuario_id
        ) latest ON latest.latest_id = e1.id
    ) e ON e.usuario_id = u.id
    WHERE u.id <> ?
    ORDER BY COALESCE(e.fecha_encuesta, u.fecha_registro) DESC
    LIMIT 24
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>

<section class="hero-panel">
    <div>
        <p class="eyebrow">Comunidad estudiantil</p>
        <h2>Conecta con estudiantes, compara avances y descubre perfiles academicos.</h2>
        <p>
            El feed muestra estudiantes registrados en Atenea con indicadores utiles para detectar
            fortalezas, habitos y posibles factores de riesgo academico.
        </p>
    </div>
    <div class="hero-stat">
        <span><?= metricValue($community["total"], "", "0") ?></span>
        <small>estudiantes registrados</small>
    </div>
</section>

<section class="metric-grid" aria-label="Resumen de la comunidad">
    <article class="metric-card">
        <small>Promedio comunitario</small>
        <strong><?= metricValue($community["avg_promedio"]) ?></strong>
    </article>
    <article class="metric-card">
        <small>Asistencia media</small>
        <strong><?= metricValue($community["avg_asistencia"], "%") ?></strong>
    </article>
    <article class="metric-card">
        <small>Estres promedio</small>
        <strong><?= metricValue($community["avg_estres"], "/10") ?></strong>
    </article>
</section>

<section class="quick-actions">
    <a href="form.php">
        <strong>Completar encuesta</strong>
        <span>Registra tus habitos y actualiza tu analisis</span>
    </a>
    <a href="dashboard.php?v=assistant">
        <strong>Asistente IA</strong>
        <span>Proximamente: consejos academicos personalizados</span>
    </a>
    <a href="dashboard.php?v=profile">
        <strong>Ver mi perfil</strong>
        <span>Consulta tus datos y tu lectura academica</span>
    </a>
    <a href="dashboard.php?v=analysis">
        <strong>Revisar diagnostico</strong>
        <span>Riesgo, factores y lectura academica</span>
    </a>
    <a href="dashboard.php?v=ranking">
        <strong>Consultar ranking</strong>
        <span>Compara tu avance con la comunidad</span>
    </a>
</section>

<section class="section-head">
    <div>
        <p class="eyebrow">Feed social</p>
        <h2>Perfiles recientes</h2>
    </div>
    <a class="text-link" href="dashboard.php?v=ranking">Ver ranking</a>
</section>

<?php if (count($students) > 0): ?>
    <section class="feed-grid">
        <?php foreach ($students as $student): ?>
            <?php $risk = riskLabel($student["nivel_estres"], $student["materias_reprobadas"]); ?>
            <a class="student-card" href="dashboard.php?v=profile&id=<?= (int) $student["id"] ?>">
                <div class="student-card__top">
                    <div class="avatar"><?= h(initials($student["nombre"])) ?></div>
                    <span class="risk-pill risk-<?= strtolower($risk) ?>"><?= h($risk) ?></span>
                </div>

                <div class="student-card__body">
                    <h3><?= h($student["nombre"]) ?></h3>
                    <p><?= h($student["carrera"] ?: "Carrera no registrada") ?></p>
                </div>

                <div class="student-metrics">
                    <span>
                        <small>Promedio</small>
                        <strong><?= metricValue($student["promedio"]) ?></strong>
                    </span>
                    <span>
                        <small>Asistencia</small>
                        <strong><?= metricValue($student["asistencia"], "%") ?></strong>
                    </span>
                    <span>
                        <small>Estudio</small>
                        <strong><?= metricValue($student["horas_estudio"], " h") ?></strong>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="empty-state">
        <h2>Aun no hay otros estudiantes registrados.</h2>
        <p>Cuando mas usuarios completen la encuesta, apareceran aqui como tarjetas de comunidad.</p>
    </section>
<?php endif; ?>
