<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("conexion.php");
require_once("analytics.php");

// Si no hay sesion activa, mandamos al login
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["usuario_rol"] ?? "usuario") === "admin") {
    header("Location: admin_dashboard.php");
    exit();
}

$view = $_GET["v"] ?? "home";
$allowedViews = ["home", "profile", "ranking", "analysis", "plan", "community", "activity", "assistant"];

if (!in_array($view, $allowedViews, true)) {
    $view = "home";
}

$userId = (int) $_SESSION["usuario_id"];
ateneaEnsureAllResults($conn);
$currentUser = [
    "nombre" => $_SESSION["usuario_nombre"] ?? "Estudiante",
    "correo" => $_SESSION["usuario_correo"] ?? "",
    "carrera" => "Atenea",
];

$stmt = $conn->prepare("SELECT nombre, correo, carrera FROM usuarios WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $currentUser = $result->fetch_assoc();
    }
    $stmt->close();
}

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

function initials(?string $name): string
{
    $name = trim((string) $name);
    if ($name === "") {
        return "A";
    }

    $parts = preg_split('/\s+/', $name);
    $first = function_exists("mb_substr") ? mb_substr($parts[0], 0, 1, "UTF-8") : substr($parts[0], 0, 1);
    $lastPart = end($parts);
    $second = count($parts) > 1
        ? (function_exists("mb_substr") ? mb_substr($lastPart, 0, 1, "UTF-8") : substr($lastPart, 0, 1))
        : "";

    return function_exists("mb_strtoupper") ? mb_strtoupper($first . $second, "UTF-8") : strtoupper($first . $second);
}

function metricValue($value, string $suffix = "", string $empty = "Sin dato"): string
{
    if ($value === null || $value === "") {
        return $empty;
    }

    if (is_numeric($value)) {
        $value = rtrim(rtrim(number_format((float) $value, 1, ".", ""), "0"), ".");
    }

    return h($value . $suffix);
}

function riskBadgeClass(?string $level): string
{
    return match ($level) {
        "Alto" => "risk-alto",
        "Medio" => "risk-medio",
        "Bajo" => "risk-bajo",
        default => "risk-neutral",
    };
}

function riskBadgeText(?string $level): string
{
    return in_array($level, ["Alto", "Medio", "Bajo"], true) ? $level : "Sin datos";
}

function yesNoValue($value): string
{
    if ($value === null || $value === "") {
        return "Sin dato";
    }

    return (int) $value === 1 ? "Si" : "No";
}

$labels = [
    "home" => "Inicio",
    "profile" => "Perfil",
    "ranking" => "Ranking",
    "analysis" => "Analisis",
    "plan" => "Plan",
    "community" => "Comunidad",
    "activity" => "Actividad",
    "assistant" => "Asistente IA",
];

// El boton superior cambia segun la seccion actual
$topAction = [
    "label" => "Completar encuesta",
    "href" => "form.php",
];

if ($view === "assistant") {
    $topAction = [
        "label" => "Volver al inicio",
        "href" => "dashboard.php?v=home",
    ];
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
    <title>Atenea | Dashboard</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
</head>

<body>
    <div class="dashboard-shell">
        <aside class="sidebar" id="dashboardSidebar">
            <a class="brand" href="dashboard.php?v=home" aria-label="Ir al inicio">
                <img src="../IMG/favicon.png" alt="Logo Atenea" class="brand-image">
                <small>ODS 4</small>
            </a>

            <nav class="nav" aria-label="Navegacion principal">
                <span class="nav-group">Explorar</span>
                <a class="<?= $view === "home" ? "active" : "" ?>" href="dashboard.php?v=home">
                    <span>Inicio</span>
                </a>
                <a class="<?= $view === "ranking" ? "active" : "" ?>" href="dashboard.php?v=ranking">
                    <span>Ranking</span>
                </a>
                <a class="<?= $view === "analysis" ? "active" : "" ?>" href="dashboard.php?v=analysis">
                    <span>Analisis</span>
                </a>
                <span class="nav-group">Comunidad</span>
                <a class="<?= $view === "community" ? "active" : "" ?>" href="dashboard.php?v=community">
                    <span>Comunidad</span>
                </a>
                <a class="<?= $view === "plan" ? "active" : "" ?>" href="dashboard.php?v=plan">
                    <span>Plan</span>
                </a>
                <a class="<?= $view === "activity" ? "active" : "" ?>" href="dashboard.php?v=activity">
                    <span>Actividad</span>
                </a>
                <a class="<?= $view === "assistant" ? "active" : "" ?>" href="dashboard.php?v=assistant">
                    <span>Asistente IA</span>
                </a>
                <span class="nav-group">Perfil</span>
                <a class="<?= $view === "profile" ? "active" : "" ?>" href="dashboard.php?v=profile">
                    <span>Mi perfil</span>
                </a>
            </nav>

            <div class="sidebar-card">
                <div class="mini-avatar"><?= h(initials($currentUser["nombre"] ?? "")) ?></div>
                <div>
                    <strong><?= h($currentUser["nombre"] ?? "Estudiante") ?></strong>
                    <small><?= h($currentUser["carrera"] ?? "Atenea") ?></small>
                </div>
            </div>

            <a class="logout-link" href="logout.php">Cerrar sesion</a>
        </aside>

        <main class="main">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Atenea Dashboard</p>
                    <h1><?= h($labels[$view]) ?></h1>
                </div>
                <div class="topbar-actions">
                    <button
                        type="button"
                        class="nav-toggle"
                        data-nav-toggle
                        aria-controls="dashboardSidebar"
                        aria-expanded="false"
                        aria-label="Abrir menu"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <button type="button" class="theme-toggle theme-toggle--dashboard" data-theme-toggle>Modo oscuro</button>
                    <?php if ($topAction): ?>
                        <a class="primary-action primary-action--small" href="<?= h($topAction["href"]) ?>"><?= h($topAction["label"]) ?></a>
                    <?php endif; ?>
                </div>
            </header>

            <?php
            switch ($view) {
                case "home":
                    include "../VIEWS/home.php";
                    break;
                case "profile":
                    include "../VIEWS/profile.php";
                    break;
                case "ranking":
                    include "../VIEWS/ranking.php";
                    break;
                case "analysis":
                    include "../VIEWS/analysis.php";
                    break;
                case "plan":
                    include "../VIEWS/plan.php";
                    break;
                case "community":
                    include "../VIEWS/community.php";
                    break;
                case "activity":
                    include "../VIEWS/activity.php";
                    break;
                case "assistant":
                    include "../VIEWS/assistant.php";
                    break;
            }
            ?>
        </main>
    </div>

    <div class="dashboard-overlay" data-nav-overlay></div>

    <script src="../JS/theme.js"></script>
    <script src="../JS/dashboard.js"></script>
</body>

</html>
