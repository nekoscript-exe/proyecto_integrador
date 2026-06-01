<?php
$sessions = [];
$sessionStats = [
    "total" => 0,
    "last_login" => null,
];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total, MAX(fecha_inicio) AS last_login
    FROM sesiones
    WHERE usuario_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $sessionStats = array_merge($sessionStats, $row);
    }
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT fecha_inicio, ip_usuario, dispositivo
    FROM sesiones
    WHERE usuario_id = ?
    ORDER BY fecha_inicio DESC
    LIMIT 10
");

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        $sessions[] = $row;
    }
    $stmt->close();
}
?>

<section class="hero-panel">
    <div>
        <p class="eyebrow">Actividad</p>
        <h2>Historial de acceso a tu cuenta.</h2>
        <p>Esta seccion utiliza la tabla de sesiones para mostrar uso reciente de la plataforma.</p>
    </div>
    <div class="hero-stat">
        <span><?= metricValue($sessionStats["total"]) ?></span>
        <small>sesiones registradas</small>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <small>Ultimo acceso</small>
        <strong><?= $sessionStats["last_login"] ? h(date("d/m/Y", strtotime($sessionStats["last_login"]))) : "Sin dato" ?></strong>
    </article>
    <article class="metric-card">
        <small>Dashboard</small>
        <strong>Activo</strong>
    </article>
    <article class="metric-card">
        <small>Cuenta</small>
        <strong>Protegida</strong>
    </article>
</section>

<section class="ranking-list">
    <?php foreach ($sessions as $index => $session): ?>
        <article class="ranking-row">
            <div class="rank-number"><?= $index + 1 ?></div>
            <div class="avatar small">S</div>
            <div class="rank-person">
                <h3><?= h(date("d/m/Y H:i", strtotime($session["fecha_inicio"]))) ?></h3>
                <p><?= h($session["ip_usuario"] ?: "IP no registrada") ?></p>
            </div>
            <div class="rank-data session-device">
                <span>
                    <small>Dispositivo</small>
                    <strong><?= h($session["dispositivo"] ?: "Sin dato") ?></strong>
                </span>
            </div>
        </article>
    <?php endforeach; ?>
</section>
