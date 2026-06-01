<?php
$ranking = [];

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.carrera,
        e.promedio,
        e.asistencia,
        e.nivel_estres,
        e.materias_reprobadas
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
    ORDER BY e.promedio IS NULL, e.promedio DESC, e.asistencia DESC, u.nombre ASC
    LIMIT 20
";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ranking[] = $row;
    }
}
?>

<section class="hero-panel ranking-hero">
    <div>
        <p class="eyebrow">Ranking global</p>
        <h2>Reconoce el desempeno academico y encuentra perfiles destacados.</h2>
        <p>
            El orden considera el promedio mas reciente registrado por cada estudiante. Los perfiles
            sin encuesta aparecen al final para mantener visible a toda la comunidad.
        </p>
    </div>
    <div class="hero-stat">
        <span><?= count($ranking) ?></span>
        <small>perfiles visibles</small>
    </div>
</section>

<?php if (count($ranking) > 0): ?>
    <section class="ranking-list">
        <?php foreach ($ranking as $index => $student): ?>
            <?php $risk = riskLabel($student["nivel_estres"], $student["materias_reprobadas"]); ?>
            <a class="ranking-row <?= (int) $student["id"] === $userId ? "is-current" : "" ?>" href="dashboard.php?v=profile&id=<?= (int) $student["id"] ?>">
                <div class="rank-number"><?= $index + 1 ?></div>
                <div class="avatar small"><?= h(initials($student["nombre"])) ?></div>
                <div class="rank-person">
                    <h3><?= h($student["nombre"]) ?></h3>
                    <p><?= h($student["carrera"] ?: "Carrera no registrada") ?></p>
                </div>
                <div class="rank-data">
                    <span>
                        <small>Promedio</small>
                        <strong><?= metricValue($student["promedio"]) ?></strong>
                    </span>
                    <span>
                        <small>Asistencia</small>
                        <strong><?= metricValue($student["asistencia"], "%") ?></strong>
                    </span>
                    <span class="risk-pill risk-<?= strtolower($risk) ?>"><?= h($risk) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="empty-state">
        <h2>No hay estudiantes para rankear.</h2>
        <p>Cuando existan registros de usuarios, Atenea construira este listado automaticamente.</p>
    </section>
<?php endif; ?>
