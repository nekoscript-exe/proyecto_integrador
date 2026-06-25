<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("conexion.php");
require_once("analytics.php");
require_once("security.php");
require_once("dataset_service.php");

// Solo admins pueden entrar aqui
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["usuario_rol"] ?? "usuario") !== "admin") {
    header("Location: dashboard.php");
    exit();
}

ateneaEnsureAllResults($conn);
ateneaEnsureSecuritySchema($conn);
ateneaDatasetEnsureSchema($conn);

$adminId = (int) $_SESSION["usuario_id"];
$feedback = "";
$sqlOutput = "";
$sqlError = "";
$sqlPreview = [];

function adminH($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

function usuariosTieneEstadoColumna(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'estado'");
    return $result && $result->num_rows > 0;
}

function reloadAdmin(): void
{
    header("Location: admin_dashboard.php");
    exit();
}

// Helper para acortar textos largos y no romper las tarjetas
function adminShort(string $text, int $limit = 80): string
{
    $text = trim($text);
    if (function_exists("mb_strlen") && mb_strlen($text, "UTF-8") > $limit) {
        return mb_substr($text, 0, $limit - 1, "UTF-8") . "…";
    }

    if (!function_exists("mb_strlen") && strlen($text) > $limit) {
        return substr($text, 0, $limit - 1) . "…";
    }

    return $text;
}

function adminProcessDatasetFile(mysqli $conn, int $adminId, string $filePath, string $originalName, string $storedPath): array
{
    global $host, $user, $pass, $db;

    $projectRoot = dirname(__DIR__);
    $pythonPath = $projectRoot . "/venv/bin/python";
    $python = is_executable($pythonPath) ? $pythonPath : "python3";
    $script = $projectRoot . "/PYTHON/process_student_dataset.py";

    if (!function_exists("exec")) {
        return [
            "ok" => false,
            "message" => "PHP no permite ejecutar Python desde el panel admin. Procesa el CSV desde terminal.",
            "raw" => "",
        ];
    }

    $command = implode(" ", [
        escapeshellarg($python),
        escapeshellarg($script),
        escapeshellarg($filePath),
        "--host",
        escapeshellarg($host),
        "--user",
        escapeshellarg($user),
        "--password",
        escapeshellarg($pass),
        "--database",
        escapeshellarg($db),
        "--uploaded-by",
        escapeshellarg((string) $adminId),
        "--source-name",
        escapeshellarg($originalName),
        "--stored-path",
        escapeshellarg($storedPath),
    ]);

    $output = [];
    $exitCode = 0;
    exec($command . " 2>&1", $output, $exitCode);
    $rawOutput = implode("\n", $output);
    $payload = json_decode($rawOutput, true);

    if ($exitCode !== 0 || !is_array($payload) || empty($payload["ok"])) {
        return [
            "ok" => false,
            "message" => is_array($payload) && isset($payload["error"])
                ? (string) $payload["error"]
                : "No fue posible procesar el dataset.",
            "raw" => $rawOutput,
        ];
    }

    ateneaLogAdminAction(
        $conn,
        $adminId,
        "Importar dataset",
        null,
        "Dataset {$originalName} procesado con " . (int) $payload["clean_rows"] . " filas limpias"
    );

    return [
        "ok" => true,
        "message" => "Dataset procesado: " . (int) $payload["raw_rows"] . " filas raw, " . (int) $payload["clean_rows"] . " filas limpias.",
        "payload" => $payload,
    ];
}

$tieneEstado = usuariosTieneEstadoColumna($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "toggle_block") {
        $targetId = (int) ($_POST["target_id"] ?? 0);

        if (!$tieneEstado) {
            $feedback = "No existe la columna estado en usuarios. Ejecuta la migración admin.";
        } elseif ($targetId <= 0 || $targetId === $adminId) {
            $feedback = "No puedes bloquear/desbloquear esta cuenta.";
        } else {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET estado = CASE WHEN estado = 'bloqueado' THEN 'activo' ELSE 'bloqueado' END
                WHERE id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $targetId);
                $stmt->execute();
                $stmt->close();
                ateneaLogAdminAction($conn, $adminId, "Bloquear/desbloquear usuario", $targetId, "Estado alternado");
                reloadAdmin();
            }
        }
    }

    if ($action === "set_role") {
        $targetId = (int) ($_POST["target_id"] ?? 0);
        $newRole = $_POST["new_role"] ?? "usuario";
        $allowed = ["usuario", "admin"];

        if (!in_array($newRole, $allowed, true)) {
            $feedback = "Rol inválido.";
        } elseif ($targetId <= 0 || $targetId === $adminId) {
            $feedback = "No puedes cambiar tu propio rol.";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("si", $newRole, $targetId);
                $stmt->execute();
                $stmt->close();
                ateneaLogAdminAction($conn, $adminId, "Cambiar rol", $targetId, "Nuevo rol: {$newRole}");
                reloadAdmin();
            }
        }
    }

    if ($action === "update_user") {
        $targetId = (int) ($_POST["target_id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $edad = (int) ($_POST["edad"] ?? 0);
        $carrera = trim($_POST["carrera"] ?? "");

        if ($targetId <= 0 || $nombre === "" || $edad <= 0 || $carrera === "") {
            $feedback = "Datos inválidos para actualizar usuario.";
        } else {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET nombre = ?, edad = ?, carrera = ?
                WHERE id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("sisi", $nombre, $edad, $carrera, $targetId);
                $stmt->execute();
                $stmt->close();
                ateneaLogAdminAction($conn, $adminId, "Actualizar usuario", $targetId, "Nombre, edad o carrera modificados");
                reloadAdmin();
            }
        }
    }

    if ($action === "delete_user") {
        $targetId = (int) ($_POST["target_id"] ?? 0);

        if ($targetId <= 0 || $targetId === $adminId) {
            $feedback = "No puedes eliminar esta cuenta.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    DELETE r, rec
                    FROM resultados r
                    LEFT JOIN recomendaciones rec ON rec.resultado_id = r.id
                    INNER JOIN encuestas e ON e.id = r.encuesta_id
                    WHERE e.usuario_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param("i", $targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                ateneaLogAdminAction($conn, $adminId, "Eliminar usuario", $targetId, "Se eliminaron datos asociados");

                $stmt = $conn->prepare("DELETE FROM encuestas WHERE usuario_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM sesiones WHERE usuario_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();
                reloadAdmin();
            } catch (Throwable $e) {
                $conn->rollback();
                $feedback = "No fue posible eliminar el usuario.";
            }
        }
    }

    if ($action === "dataset_import") {
        $file = $_FILES["dataset_csv"] ?? null;

        if (!$file || ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $feedback = "Selecciona un archivo CSV valido.";
        } else {
            $extension = strtolower(pathinfo((string) $file["name"], PATHINFO_EXTENSION));
            if ($extension !== "csv") {
                $feedback = "El archivo debe ser CSV.";
            } else {
                $projectRoot = dirname(__DIR__);
                $uploadDir = $projectRoot . "/DATASET/uploads";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $safeBase = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo((string) $file["name"], PATHINFO_FILENAME));
                $storedFileName = date("Ymd_His") . "_" . $safeBase . ".csv";
                $targetPath = $uploadDir . "/" . $storedFileName;
                $storedPath = "DATASET/uploads/" . $storedFileName;

                if (!move_uploaded_file((string) $file["tmp_name"], $targetPath)) {
                    $feedback = "No fue posible guardar el CSV.";
                } else {
                    $result = adminProcessDatasetFile($conn, $adminId, $targetPath, (string) $file["name"], $storedPath);
                    $feedback = $result["message"];
                }
            }
        }
    }

    if ($action === "dataset_import_sample") {
        $projectRoot = dirname(__DIR__);
        $samplePath = $projectRoot . "/DATASET/Student_Performance_Dataset.csv";

        if (!is_file($samplePath)) {
            $feedback = "No se encontro el dataset de ejemplo en DATASET.";
        } else {
            $result = adminProcessDatasetFile(
                $conn,
                $adminId,
                $samplePath,
                "Student_Performance_Dataset.csv",
                "DATASET/Student_Performance_Dataset.csv"
            );
            $feedback = $result["message"];
        }
    }

    if ($action === "sql_exec") {
        $sql = trim($_POST["sql_text"] ?? "");

        if ($sql === "") {
            $sqlError = "Escribe una consulta SQL.";
        } elseif (preg_match('/;\s*.+/s', $sql)) {
            $sqlError = "Solo se permite una sentencia SQL por ejecución.";
        } else {
            $queryResult = $conn->query($sql);
            if ($queryResult === false) {
                $sqlError = "Error SQL: " . $conn->error;
            } else {
                ateneaLogAdminAction($conn, $adminId, "Ejecutar SQL", null, adminShort($sql, 180));
                if ($queryResult instanceof mysqli_result) {
                    $sqlOutput = "Consulta ejecutada: " . $queryResult->num_rows . " filas retornadas.";
                    while ($row = $queryResult->fetch_assoc()) {
                        $sqlPreview[] = $row;
                    }
                } else {
                    $sqlOutput = "Consulta ejecutada correctamente. Filas afectadas: " . $conn->affected_rows;
                }
            }
        }
    }
}

$totals = [
    "usuarios" => 0,
    "admins" => 0,
    "bloqueados" => 0,
    "encuestas" => 0,
    "resultados" => 0,
    "recomendaciones" => 0,
    "datasets" => 0,
    "dataset_rows" => 0
];

$statsSql = $tieneEstado
    ? "SELECT
        (SELECT COUNT(*) FROM usuarios) AS usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE rol='admin') AS admins,
        (SELECT COUNT(*) FROM usuarios WHERE estado='bloqueado') AS bloqueados,
        (SELECT COUNT(*) FROM encuestas) AS encuestas,
        (SELECT COUNT(*) FROM resultados) AS resultados,
        (SELECT COUNT(*) FROM recomendaciones) AS recomendaciones,
        (SELECT COUNT(*) FROM dataset_uploads) AS datasets,
        (SELECT COUNT(*) FROM dataset_estudiantes_clean) AS dataset_rows"
    : "SELECT
        (SELECT COUNT(*) FROM usuarios) AS usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE rol='admin') AS admins,
        0 AS bloqueados,
        (SELECT COUNT(*) FROM encuestas) AS encuestas,
        (SELECT COUNT(*) FROM resultados) AS resultados,
        (SELECT COUNT(*) FROM recomendaciones) AS recomendaciones,
        (SELECT COUNT(*) FROM dataset_uploads) AS datasets,
        (SELECT COUNT(*) FROM dataset_estudiantes_clean) AS dataset_rows";

$stats = $conn->query($statsSql);
if ($stats) {
    $totals = array_merge($totals, $stats->fetch_assoc());
}

$datasetUploads = ateneaDatasetUploads($conn, 8);
$latestDataset = ateneaDatasetCurrentUpload($conn);
$latestDatasetRows = $latestDataset ? ateneaDatasetRows($conn, (int) $latestDataset["id"], 8) : [];

$users = [];
$usersSql = $tieneEstado
    ? "SELECT id, nombre, correo, edad, carrera, rol, estado, fecha_registro FROM usuarios WHERE estado <> 'bloqueado' ORDER BY id DESC"
    : "SELECT id, nombre, correo, edad, carrera, rol, 'activo' AS estado, fecha_registro FROM usuarios ORDER BY id DESC";
$result = $conn->query($usersSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$blockedUsers = [];
if ($tieneEstado) {
    $result = $conn->query("SELECT id, nombre, correo, edad, carrera, rol, estado, fecha_registro FROM usuarios WHERE estado = 'bloqueado' ORDER BY fecha_registro DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $blockedUsers[] = $row;
        }
    }
}

$historyRows = [];
if (ateneaTableExists($conn, 'admin_historial')) {
    $result = $conn->query("
        SELECT
            h.id,
            h.accion,
            h.detalles,
            h.fecha,
            h.ip_admin,
            a.nombre AS admin_nombre,
            t.nombre AS target_nombre
        FROM admin_historial h
        INNER JOIN usuarios a ON a.id = h.admin_id
        LEFT JOIN usuarios t ON t.id = h.target_user_id
        ORDER BY h.fecha DESC
        LIMIT 30
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $historyRows[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../IMG/favicon.png">
    <title>Atenea | Admin</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <script src="../JS/theme.js" defer></script>
    <script src="../JS/admin.js" defer></script>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../IMG/favicon.png" alt="Logo Atenea" class="admin-brand__logo">
                <div>
                    <p class="admin-brand__title">Panel de Administración</p>
                    <p>Control total del sistema</p>
                </div>
            </div>
            <div class="admin-user">
                <strong><?= adminH($_SESSION["usuario_nombre"] ?? "Administrador") ?></strong>
                <small><?= adminH($_SESSION["usuario_correo"] ?? "") ?></small>
            </div>
            <button type="button" class="theme-toggle admin-theme-toggle" data-theme-toggle>Modo oscuro</button>
            <a href="#datasets">Datasets</a>
            <a href="logout.php">Cerrar sesion</a>
        </aside>

        <main class="admin-main">
            <header>
                <h2>Panel de Administracion</h2>
                <p>Gestiona usuarios, datasets, roles, bloqueos y consultas SQL de base de datos.</p>
            </header>

            <?php if ($feedback !== ""): ?>
                <div class="alert"><?= adminH($feedback) ?></div>
            <?php endif; ?>

            <section class="metrics">
                <article><small>Usuarios</small><strong><?= (int) $totals["usuarios"] ?></strong></article>
                <article><small>Admins</small><strong><?= (int) $totals["admins"] ?></strong></article>
                <article><small>Bloqueados</small><strong><?= (int) $totals["bloqueados"] ?></strong></article>
                <article><small>Encuestas</small><strong><?= (int) $totals["encuestas"] ?></strong></article>
                <article><small>Resultados</small><strong><?= (int) $totals["resultados"] ?></strong></article>
                <article><small>Recomendaciones</small><strong><?= (int) $totals["recomendaciones"] ?></strong></article>
                <article><small>Datasets</small><strong><?= (int) $totals["datasets"] ?></strong></article>
                <article><small>Filas clean</small><strong><?= (int) $totals["dataset_rows"] ?></strong></article>
            </section>

            <section class="panel dataset-admin-panel" id="datasets">
                <div class="panel-head">
                    <div>
                        <h3>Datasets y ciclo de vida de datos</h3>
                        <p class="meta">Importa CSV, guarda raw, limpia datos y genera metricas para graficas.</p>
                    </div>
                    <span><?= count($datasetUploads) ?> cargas recientes</span>
                </div>

                <div class="dataset-admin-grid">
                    <form method="POST" enctype="multipart/form-data" class="dataset-upload-form">
                        <input type="hidden" name="action" value="dataset_import">
                        <label>Importar CSV nuevo</label>
                        <input type="file" name="dataset_csv" accept=".csv,text/csv" required>
                        <button type="submit" data-confirm="Procesar este dataset CSV?">Procesar dataset</button>
                    </form>

                    <form method="POST" class="dataset-upload-form">
                        <input type="hidden" name="action" value="dataset_import_sample">
                        <label>Dataset incluido</label>
                        <p class="meta">Usa Student_Performance_Dataset.csv desde la carpeta DATASET.</p>
                        <button type="submit" data-confirm="Procesar el dataset incluido?">Procesar ejemplo</button>
                    </form>
                </div>

                <?php if ($latestDataset): ?>
                    <div class="dataset-admin-summary">
                        <article>
                            <small>Ultima carga</small>
                            <strong><?= adminH($latestDataset["nombre_original"]) ?></strong>
                        </article>
                        <article>
                            <small>Raw</small>
                            <strong><?= (int) $latestDataset["filas_raw"] ?></strong>
                        </article>
                        <article>
                            <small>Clean</small>
                            <strong><?= (int) $latestDataset["filas_clean"] ?></strong>
                        </article>
                        <article>
                            <small>Promedio general</small>
                            <strong><?= adminH($latestDataset["promedio_general"] ?? "Sin dato") ?>%</strong>
                        </article>
                    </div>
                <?php endif; ?>

                <?php if (count($datasetUploads) > 0): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Dataset</th>
                                    <th>Estado</th>
                                    <th>Raw</th>
                                    <th>Clean</th>
                                    <th>Aprobados</th>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datasetUploads as $upload): ?>
                                    <tr>
                                        <td><?= adminH($upload["nombre_original"]) ?></td>
                                        <td><?= adminH($upload["estado"]) ?></td>
                                        <td><?= (int) $upload["filas_raw"] ?></td>
                                        <td><?= (int) $upload["filas_clean"] ?></td>
                                        <td><?= adminH($upload["porcentaje_aprobados"] ?? "Sin dato") ?>%</td>
                                        <td><?= adminH(date("d/m/Y H:i", strtotime($upload["fecha_carga"]))) ?></td>
                                        <td>
                                            <?php if (($upload["estado"] ?? "") === "completado"): ?>
                                                <a class="admin-table-link" href="download_dataset.php?dataset=<?= (int) $upload["id"] ?>">Descargar</a>
                                            <?php else: ?>
                                                <span class="meta">No disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="meta">Aun no hay datasets cargados. Procesa el ejemplo para iniciar la parte de datos.</p>
                <?php endif; ?>

                <?php if (count($latestDatasetRows) > 0): ?>
                    <div class="panel-head dataset-preview-head">
                        <h3>Muestra del dataset limpio</h3>
                        <span><?= count($latestDatasetRows) ?> filas</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Edad</th>
                                    <th>Genero</th>
                                    <th>Estudio</th>
                                    <th>Asistencia</th>
                                    <th>Final</th>
                                    <th>Nivel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestDatasetRows as $row): ?>
                                    <tr>
                                        <td><?= adminH($row["student_id"]) ?></td>
                                        <td><?= (int) $row["age"] ?></td>
                                        <td><?= adminH($row["gender"]) ?></td>
                                        <td><?= adminH($row["study_hours_per_day"]) ?> h</td>
                                        <td><?= adminH($row["attendance_percentage"]) ?>%</td>
                                        <td><?= adminH($row["final_percentage"]) ?>%</td>
                                        <td><?= adminH($row["performance_level"]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h3>Usuarios activos</h3>
                    <span><?= count($users) ?> visibles</span>
                </div>
                <div class="users-grid">
                    <?php foreach ($users as $u): ?>
                        <?php if (($u["estado"] ?? "activo") === "bloqueado") { continue; } ?>
                        <article class="user-card">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="target_id" value="<?= (int) $u["id"] ?>">
                                <label>Nombre</label>
                                <input type="text" name="nombre" value="<?= adminH($u["nombre"]) ?>" required>
                                <label>Edad</label>
                                <input type="number" name="edad" min="1" value="<?= (int) $u["edad"] ?>" required>
                                <label>Carrera</label>
                                <input type="text" name="carrera" value="<?= adminH($u["carrera"]) ?>" required>
                                <p class="meta"><?= adminH($u["correo"]) ?></p>
                                <p class="meta">Rol: <strong><?= adminH($u["rol"]) ?></strong> · Estado: <strong><?= adminH($u["estado"]) ?></strong></p>
                                <div class="actions compact-actions">
                                    <button type="submit" data-confirm="Guardar cambios en este usuario?">Guardar</button>
                                </div>
                            </form>

                            <?php if ((int) $u["id"] !== $adminId): ?>
                                <div class="inline-actions">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="set_role">
                                        <input type="hidden" name="target_id" value="<?= (int) $u["id"] ?>">
                                        <select name="new_role">
                                            <option value="usuario" <?= $u["rol"] === "usuario" ? "selected" : "" ?>>Usuario</option>
                                            <option value="admin" <?= $u["rol"] === "admin" ? "selected" : "" ?>>Admin</option>
                                        </select>
                                        <button type="submit" data-confirm="Cambiar el rol de este usuario?">Cambiar rol</button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle_block">
                                        <input type="hidden" name="target_id" value="<?= (int) $u["id"] ?>">
                                        <button type="submit" data-confirm="<?= $u["estado"] === "bloqueado" ? "Desbloquear este usuario?" : "Bloquear este usuario?" ?>"><?= $u["estado"] === "bloqueado" ? "Desbloquear" : "Bloquear" ?></button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="target_id" value="<?= (int) $u["id"] ?>">
                                        <button type="submit" class="danger" data-confirm="Se eliminará el usuario y sus datos. ¿Continuar?">Eliminar</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h3>Usuarios bloqueados</h3>
                    <span><?= count($blockedUsers) ?> en pausa</span>
                </div>
                <div class="users-grid users-grid--blocked">
                    <?php if (count($blockedUsers) > 0): ?>
                        <?php foreach ($blockedUsers as $u): ?>
                            <article class="user-card user-card--blocked">
                                <strong><?= adminH($u["nombre"]) ?></strong>
                                <p class="meta"><?= adminH($u["correo"]) ?></p>
                                <p class="meta"><?= adminH($u["carrera"]) ?></p>
                                <form method="POST" class="compact-actions">
                                    <input type="hidden" name="action" value="toggle_block">
                                    <input type="hidden" name="target_id" value="<?= (int) $u["id"] ?>">
                                    <button type="submit" data-confirm="Desbloquear este usuario?">Desbloquear</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="user-card user-card--blocked">
                            <strong>No hay usuarios bloqueados</strong>
                            <p class="meta">Cuando bloquees usuarios aparecerán aquí.</p>
                        </article>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h3>Historial de modificaciones</h3>
                    <span>Ultimos <?= count($historyRows) ?> eventos</span>
                </div>
                <div class="history-list">
                    <?php if (count($historyRows) > 0): ?>
                        <?php foreach ($historyRows as $row): ?>
                            <article class="history-item">
                                <div>
                                    <strong><?= adminH($row["accion"]) ?></strong>
                                    <p><?= adminH($row["admin_nombre"]) ?> <?php if ($row["target_nombre"]): ?>sobre <?= adminH($row["target_nombre"]) ?><?php endif; ?></p>
                                    <?php if (!empty($row["detalles"])): ?>
                                        <p><?= adminH($row["detalles"]) ?></p>
                                    <?php endif; ?>
                                </div>
                                <small><?= adminH(date("d/m/Y H:i", strtotime($row["fecha"]))) ?></small>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="history-item">
                            <div>
                                <strong>Sin actividad aún</strong>
                                <p>Los cambios de admin aparecerán aquí.</p>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel">
                <h3>Consola SQL (Admin)</h3>
                <form method="POST" class="sql-form">
                    <input type="hidden" name="action" value="sql_exec">
                    <textarea name="sql_text" rows="4" placeholder="Ejemplo: SELECT id, nombre, rol, estado FROM usuarios LIMIT 10;"></textarea>
                    <button type="submit" data-confirm="Ejecutar esta consulta SQL?">Ejecutar SQL</button>
                </form>

                <?php if ($sqlError !== ""): ?>
                    <p class="sql-error"><?= adminH($sqlError) ?></p>
                <?php endif; ?>
                <?php if ($sqlOutput !== ""): ?>
                    <p class="sql-ok"><?= adminH($sqlOutput) ?></p>
                <?php endif; ?>

                <?php if (count($sqlPreview) > 0): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($sqlPreview[0]) as $col): ?>
                                        <th><?= adminH($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sqlPreview as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= adminH($value) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
