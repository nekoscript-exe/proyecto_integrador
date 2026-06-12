<?php
$profileId = isset($_GET["id"]) ? (int) $_GET["id"] : $userId;
$isOwnProfile = $profileId === $userId;

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.correo,
        u.edad,
        u.carrera,
        u.fecha_registro,
        e.promedio,
        e.materias_reprobadas,
        e.asistencia,
        e.horas_estudio,
        e.horas_sueno,
        e.uso_redes,
        e.actividad_fisica,
        e.entrega_tareas,
        e.tiempo_transporte,
        e.trabaja,
        e.acceso_internet,
        e.espacio_estudio,
        e.nivel_estres,
        e.desmotivacion,
        e.herramientas_digitales,
        e.administracion_tiempo,
        e.fecha_encuesta,
        r.nivel_riesgo,
        r.puntuacion_riesgo
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
    LEFT JOIN resultados r ON r.encuesta_id = e.id
    WHERE u.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$profile = null;

if ($stmt) {
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<?php if (!$profile): ?>
    <section class="empty-state">
        <h2>Usuario no encontrado</h2>
        <p>El perfil que intentas abrir no existe o ya no esta disponible.</p>
        <a class="primary-action" href="dashboard.php?v=home">Volver al inicio</a>
    </section>
<?php else: ?>
    <?php $riskLevel = $profile["nivel_riesgo"] ?? null; ?>

    <section class="profile-hero">
        <div class="profile-avatar"><?= h(initials($profile["nombre"])) ?></div>
        <div class="profile-main">
            <div class="profile-title-row">
                <div>
                    <p class="eyebrow"><?= $isOwnProfile ? "Mi perfil academico" : "Perfil de estudiante" ?></p>
                    <h2><?= h($profile["nombre"]) ?></h2>
                    <p><?= h($profile["carrera"] ?: "Carrera no registrada") ?></p>
                </div>
                <span class="risk-pill <?= h(riskBadgeClass($riskLevel)) ?>"><?= h(riskBadgeText($riskLevel)) ?></span>
            </div>

            <div class="profile-tags">
                <span><?= metricValue($profile["edad"], " anos", "Edad sin dato") ?></span>
                <span><?= $isOwnProfile ? h($profile["correo"]) : "Correo privado" ?></span>
                <span>Desde <?= h(date("M Y", strtotime($profile["fecha_registro"]))) ?></span>
            </div>
        </div>
    </section>

    <section class="metric-grid">
        <article class="metric-card">
            <small>Promedio</small>
            <strong><?= metricValue($profile["promedio"]) ?></strong>
        </article>
        <article class="metric-card">
            <small>Asistencia</small>
            <strong><?= metricValue($profile["asistencia"], "%") ?></strong>
        </article>
        <article class="metric-card">
            <small>Nivel de estres</small>
            <strong><?= metricValue($profile["nivel_estres"], "/10") ?></strong>
        </article>
        <article class="metric-card">
            <small>Materias reprobadas</small>
            <strong><?= metricValue($profile["materias_reprobadas"]) ?></strong>
        </article>
    </section>

    <section class="content-grid">
        <article class="detail-panel">
            <div class="section-head compact">
                <div>
                    <p class="eyebrow">Habitos</p>
                    <h2>Rutina de estudio</h2>
                </div>
            </div>

            <div class="detail-list">
                <div>
                    <span>Horas de estudio</span>
                    <strong><?= metricValue($profile["horas_estudio"], " h/dia") ?></strong>
                </div>
                <div>
                    <span>Horas de sueno</span>
                    <strong><?= metricValue($profile["horas_sueno"], " h/dia") ?></strong>
                </div>
                <div>
                    <span>Uso de redes</span>
                    <strong><?= metricValue($profile["uso_redes"], " h/dia") ?></strong>
                </div>
                <div>
                    <span>Administracion del tiempo</span>
                    <strong><?= metricValue($profile["administracion_tiempo"], "/5") ?></strong>
                </div>
            </div>
        </article>

        <article class="detail-panel">
            <div class="section-head compact">
                <div>
                    <p class="eyebrow">Contexto</p>
                    <h2>Condiciones de aprendizaje</h2>
                </div>
            </div>

            <div class="detail-list">
                <div>
                    <span>Acceso a internet</span>
                    <strong><?= yesNoValue($profile["acceso_internet"]) ?></strong>
                </div>
                <div>
                    <span>Espacio de estudio</span>
                    <strong><?= yesNoValue($profile["espacio_estudio"]) ?></strong>
                </div>
                <div>
                    <span>Trabaja</span>
                    <strong><?= yesNoValue($profile["trabaja"]) ?></strong>
                </div>
                <div>
                    <span>Transporte</span>
                    <strong><?= metricValue($profile["tiempo_transporte"], " min") ?></strong>
                </div>
            </div>
        </article>
    </section>

    <section class="insight-panel">
        <div>
            <p class="eyebrow">Lectura Atenea</p>
            <h2><?= $riskLevel === "Alto" ? "Prioridad de acompanamiento" : ($riskLevel === "Medio" ? "Seguimiento recomendado" : "Avance saludable") ?></h2>
            <p>
                <?php if ($riskLevel === "Alto"): ?>
                    Conviene revisar carga academica, estres y materias pendientes para proponer apoyo temprano.
                <?php elseif ($riskLevel === "Medio"): ?>
                    Hay senales que pueden mejorar con habitos mas constantes y seguimiento academico.
                <?php else: ?>
                    El perfil muestra indicadores estables; mantener rutinas ayudara a sostener el desempeno.
                <?php endif; ?>
            </p>
        </div>
        <a class="text-link" href="dashboard.php?v=home">Explorar comunidad</a>
    </section>
<?php endif; ?>
