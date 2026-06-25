<?php
$selectedDatasetId = isset($_GET["dataset"]) ? (int) $_GET["dataset"] : null;
$datasetUploads = ateneaDatasetUploads($conn, 12);
$dataset = ateneaDatasetCurrentUpload($conn, $selectedDatasetId);
$datasetId = $dataset ? (int) $dataset["id"] : 0;
$rawRows = $datasetId > 0 ? ateneaDatasetRows($conn, $datasetId, 5000, true) : [];
$cleanRows = $datasetId > 0 ? ateneaDatasetRows($conn, $datasetId, 20) : [];
$totalRows = (int) ($dataset["total_students"] ?? $dataset["filas_clean"] ?? count($rawRows));
$passFail = $datasetId > 0 ? ateneaDatasetPassFail($conn, $datasetId) : [];
$genderDistribution = $datasetId > 0 ? ateneaDatasetDistribution($conn, $datasetId, "gender") : [];
$levelDistribution = $datasetId > 0 ? ateneaDatasetDistribution($conn, $datasetId, "performance_level") : [];
$parentalDistribution = $datasetId > 0 ? ateneaDatasetDistribution($conn, $datasetId, "parental_education") : [];
$subjectAverages = ateneaDatasetSubjectAverages($dataset);
$lifecycle = ateneaDatasetLifecycle($dataset);

function datasetNumber($value, string $suffix = "", int $decimals = 1): string
{
    if ($value === null || $value === "") {
        return "Sin dato";
    }

    if (is_numeric($value)) {
        $value = number_format((float) $value, $decimals, ".", "");
        $value = rtrim(rtrim($value, "0"), ".");
    }

    return h($value . $suffix);
}

function datasetPercentValue(int $value, int $total): float
{
    if ($total <= 0) {
        return 0;
    }

    return round(($value / $total) * 100, 2);
}

function datasetReadableCategory(string $field, string $value): string
{
    $value = trim($value);
    if ($value === "") {
        return "Sin dato";
    }

    $map = [
        "internet_access" => ["Yes" => "Si", "No" => "No"],
        "extracurricular_activities" => ["Yes" => "Si", "No" => "No"],
        "pass_fail" => ["Yes" => "Aprobado", "No" => "Reprobado", "1" => "Aprobado", "0" => "Reprobado"],
    ];

    if (isset($map[$field][$value])) {
        return $map[$field][$value];
    }

    return $value;
}

function datasetBuildNumericProfile(array $rows, string $field, string $label, string $hint, string $unit = "", int $decimals = 1): array
{
    $values = [];

    foreach ($rows as $row) {
        if (!isset($row[$field]) || $row[$field] === "" || !is_numeric($row[$field])) {
            continue;
        }

        $values[] = (float) $row[$field];
    }

    $count = count($values);
    $min = $count > 0 ? min($values) : null;
    $max = $count > 0 ? max($values) : null;
    $avg = $count > 0 ? array_sum($values) / $count : null;

    $bins = [];
    if ($count > 0 && $min !== null && $max !== null) {
        $bucketCount = 5;
        $range = $max - $min;
        $bucketSize = $range > 0 ? ($range / $bucketCount) : 1;
        $counts = array_fill(0, $bucketCount, 0);

        foreach ($values as $value) {
            $index = $bucketSize > 0 ? (int) floor(($value - $min) / $bucketSize) : 0;
            if ($index < 0) {
                $index = 0;
            }
            if ($index >= $bucketCount) {
                $index = $bucketCount - 1;
            }
            $counts[$index]++;
        }

        $highest = max($counts);
        foreach ($counts as $countValue) {
            $bins[] = [
                "count" => $countValue,
                "height" => $highest > 0 ? round(($countValue / $highest) * 100, 2) : 0,
            ];
        }
    }

    return [
        "field" => $field,
        "label" => $label,
        "hint" => $hint,
        "type" => "numeric",
        "unit" => $unit,
        "decimals" => $decimals,
        "summary" => $avg,
        "summary_label" => $avg !== null ? datasetNumber($avg, $unit, $decimals) : "Sin dato",
        "min" => $min,
        "max" => $max,
        "extra" => [
            "Min " . datasetNumber($min, $unit, $decimals),
            "Max " . datasetNumber($max, $unit, $decimals),
        ],
        "bins" => $bins,
    ];
}

function datasetBuildCategoryProfile(array $rows, string $field, string $label, string $hint): array
{
    $counts = [];

    foreach ($rows as $row) {
        $value = trim((string) ($row[$field] ?? ""));
        $value = datasetReadableCategory($field, $value);
        if ($value === "") {
            $value = "Sin dato";
        }

        $counts[$value] = ($counts[$value] ?? 0) + 1;
    }

    arsort($counts);

    $total = array_sum($counts);
    $top = array_slice($counts, 0, 4, true);
    $bars = [];
    $highest = $top ? max($top) : 0;

    foreach ($top as $name => $count) {
        $bars[] = [
            "label" => $name,
            "count" => $count,
            "percent" => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            "height" => $highest > 0 ? round(($count / $highest) * 100, 2) : 0,
        ];
    }

    $leader = $bars[0] ?? null;

    return [
        "field" => $field,
        "label" => $label,
        "hint" => $hint,
        "type" => "category",
        "summary_label" => $leader
            ? ($leader["label"] . " · " . datasetNumber($leader["percent"], "%"))
            : "Sin dato",
        "extra" => array_map(
            fn ($item) => $item["label"] . " · " . datasetNumber($item["percent"], "%"),
            $bars
        ),
        "bars" => $bars,
    ];
}

function datasetBuildIdentityProfile(array $rows, string $field, string $label, string $hint): array
{
    $values = [];
    foreach ($rows as $row) {
        $value = trim((string) ($row[$field] ?? ""));
        if ($value !== "") {
            $values[] = $value;
        }
    }

    $uniqueValues = array_values(array_unique($values));
    $first = $uniqueValues[0] ?? "Sin dato";
    $last = $uniqueValues !== [] ? $uniqueValues[count($uniqueValues) - 1] : "Sin dato";
    $count = count($uniqueValues);
    $total = max(count($values), 1);

    return [
        "field" => $field,
        "label" => $label,
        "hint" => $hint,
        "type" => "identity",
        "summary_label" => datasetNumber($count, "", 0) . " unicos",
        "extra" => [
            "Primero " . $first,
            "Ultimo " . $last,
        ],
        "bars" => [
            [
                "label" => "Completitud",
                "count" => $count,
                "percent" => datasetPercentValue($count, $total),
                "height" => 100,
            ],
        ],
    ];
}

function datasetBuildProfiles(array $rows): array
{
    $configs = [
        ["field" => "student_id", "label" => "ID estudiante", "type" => "identity", "hint" => "Identificador unico del registro"],
        ["field" => "age", "label" => "Edad", "type" => "numeric", "hint" => "Edad del estudiante", "unit" => "", "decimals" => 0],
        ["field" => "gender", "label" => "Genero", "type" => "category", "hint" => "Distribucion por genero"],
        ["field" => "class", "label" => "Clase", "type" => "numeric", "hint" => "Nivel academico registrado", "unit" => "", "decimals" => 0],
        ["field" => "study_hours_per_day", "label" => "Horas de estudio", "type" => "numeric", "hint" => "Tiempo invertido por dia", "unit" => " h", "decimals" => 1],
        ["field" => "attendance_percentage", "label" => "Asistencia", "type" => "numeric", "hint" => "Asistencia acumulada", "unit" => "%", "decimals" => 1],
        ["field" => "parental_education", "label" => "Educacion parental", "type" => "category", "hint" => "Nivel educativo familiar"],
        ["field" => "internet_access", "label" => "Acceso a internet", "type" => "category", "hint" => "Condicion de conectividad"],
        ["field" => "extracurricular_activities", "label" => "Actividades extra", "type" => "category", "hint" => "Participacion fuera de clase"],
        ["field" => "math_score", "label" => "Matematicas", "type" => "numeric", "hint" => "Calificacion en matematicas", "unit" => "", "decimals" => 0],
        ["field" => "science_score", "label" => "Ciencias", "type" => "numeric", "hint" => "Calificacion en ciencias", "unit" => "", "decimals" => 0],
        ["field" => "english_score", "label" => "Ingles", "type" => "numeric", "hint" => "Calificacion en ingles", "unit" => "", "decimals" => 0],
        ["field" => "previous_year_score", "label" => "Ciclo previo", "type" => "numeric", "hint" => "Rendimiento del ciclo anterior", "unit" => "%", "decimals" => 1],
        ["field" => "final_percentage", "label" => "Final", "type" => "numeric", "hint" => "Resultado global del estudiante", "unit" => "%", "decimals" => 1],
        ["field" => "performance_level", "label" => "Nivel", "type" => "category", "hint" => "Clasificacion de rendimiento"],
        ["field" => "pass_fail", "label" => "Aprobacion", "type" => "category", "hint" => "Resultado final"],
    ];

    $profiles = [];

    foreach ($configs as $config) {
        switch ($config["type"]) {
            case "identity":
                $profiles[] = datasetBuildIdentityProfile($rows, $config["field"], $config["label"], $config["hint"]);
                break;
            case "category":
                $profiles[] = datasetBuildCategoryProfile($rows, $config["field"], $config["label"], $config["hint"]);
                break;
            case "numeric":
            default:
                $profiles[] = datasetBuildNumericProfile(
                    $rows,
                    $config["field"],
                    $config["label"],
                    $config["hint"],
                    $config["unit"] ?? "",
                    $config["decimals"] ?? 1
                );
                break;
        }
    }

    return $profiles;
}

function datasetRenderHistogram(array $bins): void
{
    if (count($bins) === 0) {
        ?>
        <div class="dataset-chart dataset-chart--empty">
            <span>Sin datos</span>
        </div>
        <?php
        return;
    }
    ?>
    <div class="dataset-chart dataset-chart--histogram">
        <?php foreach ($bins as $bin): ?>
            <span style="height: <?= max(8, min(100, (float) ($bin["height"] ?? 0))) ?>%"></span>
        <?php endforeach; ?>
    </div>
    <?php
}

function datasetRenderCategoryBars(array $bars): void
{
    if (count($bars) === 0) {
        ?>
        <div class="dataset-chart dataset-chart--empty">
            <span>Sin datos</span>
        </div>
        <?php
        return;
    }
    ?>
    <div class="dataset-chart dataset-chart--category">
        <?php foreach ($bars as $bar): ?>
            <div class="dataset-category-row">
                <div>
                    <span><?= h($bar["label"]) ?></span>
                    <strong><?= datasetNumber($bar["percent"], "%") ?></strong>
                </div>
                <div class="dataset-chart-track">
                    <span style="width: <?= max(6, min(100, (float) ($bar["height"] ?? 0))) ?>%"></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

$columnProfiles = $datasetId > 0 ? datasetBuildProfiles($rawRows) : [];
?>

<?php if (!$dataset): ?>
    <section class="empty-state">
        <h2>Aun no hay datasets procesados.</h2>
        <p>Cuando un administrador importe un CSV, aqui apareceran las tablas, metricas y graficas del analisis.</p>
    </section>
<?php else: ?>
    <section class="hero-panel dataset-hero">
        <div>
            <p class="eyebrow">Explorador de datos</p>
            <h2><?= h($dataset["nombre_original"]) ?></h2>
            <p>
                Aqui se muestran los 5000 registros del dataset original, con perfiles por columna,
                distribuciones y acceso rapido para revisar el archivo sin perderse entre tantas filas.
            </p>
            <div class="dataset-actions">
                <form method="GET" class="dataset-selector">
                    <input type="hidden" name="v" value="data">
                    <select name="dataset" onchange="this.form.submit()">
                        <?php foreach ($datasetUploads as $upload): ?>
                            <option value="<?= (int) $upload["id"] ?>" <?= (int) $upload["id"] === $datasetId ? "selected" : "" ?>>
                                <?= h($upload["nombre_original"]) ?> · <?= h(date("d/m/Y", strtotime($upload["fecha_carga"]))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a class="text-link" href="download_dataset.php?dataset=<?= $datasetId ?>">Descargar CSV</a>
            </div>
        </div>
        <div class="hero-stat">
            <span><?= datasetNumber($totalRows, "", 0) ?></span>
            <small>registros visibles</small>
        </div>
    </section>

    <section class="dataset-toolbar">
        <div>
            <p class="eyebrow">Busqueda rapida</p>
            <h2>Filtra sin perder contexto</h2>
        </div>
        <div class="dataset-toolbar__actions">
            <div class="dataset-search">
                <label for="dataset-search">Buscar en la tabla</label>
                <input id="dataset-search" type="search" data-dataset-search placeholder="Escribe un nombre, valor o ID">
            </div>
            <div class="dataset-counter">
                <small>Filas visibles</small>
                <strong data-visible-count><?= datasetNumber(count($rawRows), "", 0) ?></strong>
            </div>
        </div>
    </section>

    <section class="dataset-explorer">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Perfil por columna</p>
                <h2>Grafica y detalle de cada campo</h2>
            </div>
            <span><?= count($columnProfiles) ?> columnas analizadas</span>
        </div>
        <div class="dataset-column-grid dataset-column-grid--full">
            <?php foreach ($columnProfiles as $profile): ?>
                <article class="dataset-column-card" data-column-card="<?= h($profile["field"]) ?>">
                    <div class="dataset-column-card__head">
                        <div>
                            <p class="eyebrow"><?= h($profile["label"]) ?></p>
                            <h3><?= h($profile["hint"]) ?></h3>
                        </div>
                        <strong><?= h($profile["summary_label"] ?? "Sin dato") ?></strong>
                    </div>
                    <?php if ($profile["type"] === "numeric"): ?>
                        <?php datasetRenderHistogram($profile["bins"] ?? []); ?>
                    <?php else: ?>
                        <?php datasetRenderCategoryBars($profile["bars"] ?? []); ?>
                    <?php endif; ?>
                    <div class="dataset-column-card__foot">
                        <?php foreach (($profile["extra"] ?? []) as $detail): ?>
                            <span><?= h($detail) ?></span>
                        <?php endforeach; ?>
                        <button type="button" class="column-focus-button" data-column-focus="<?= h($profile["field"]) ?>">
                            Fijar en tabla
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="detail-panel dataset-table-panel">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Datos originales</p>
                <h2>Tabla completa de 5000 registros</h2>
            </div>
            <span><?= datasetNumber(count($rawRows), "", 0) ?> filas cargadas</span>
        </div>
        <div class="dataset-table-wrap dataset-table-wrap--large" data-dataset-table-wrap>
            <table class="dataset-table dataset-table--wide" data-dataset-table>
                <thead>
                    <tr>
                        <th data-column="student_id">Student_ID</th>
                        <th data-column="age">Age</th>
                        <th data-column="gender">Gender</th>
                        <th data-column="class">Class</th>
                        <th data-column="study_hours_per_day">Study_Hours</th>
                        <th data-column="attendance_percentage">Attendance</th>
                        <th data-column="parental_education">Parental</th>
                        <th data-column="internet_access">Internet</th>
                        <th data-column="extracurricular_activities">Extra</th>
                        <th data-column="math_score">Math</th>
                        <th data-column="science_score">Science</th>
                        <th data-column="english_score">English</th>
                        <th data-column="previous_year_score">Previous</th>
                        <th data-column="final_percentage">Final</th>
                        <th data-column="performance_level">Level</th>
                        <th data-column="pass_fail">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rawRows as $row): ?>
                        <tr>
                            <td data-column="student_id"><?= h($row["student_id"]) ?></td>
                            <td data-column="age"><?= datasetNumber($row["age"], "", 0) ?></td>
                            <td data-column="gender"><?= h($row["gender"]) ?></td>
                            <td data-column="class"><?= datasetNumber($row["class"], "", 0) ?></td>
                            <td data-column="study_hours_per_day"><?= datasetNumber($row["study_hours_per_day"], " h") ?></td>
                            <td data-column="attendance_percentage"><?= datasetNumber($row["attendance_percentage"], "%") ?></td>
                            <td data-column="parental_education"><?= h($row["parental_education"]) ?></td>
                            <td data-column="internet_access"><?= h($row["internet_access"]) ?></td>
                            <td data-column="extracurricular_activities"><?= h($row["extracurricular_activities"]) ?></td>
                            <td data-column="math_score"><?= datasetNumber($row["math_score"], "", 0) ?></td>
                            <td data-column="science_score"><?= datasetNumber($row["science_score"], "", 0) ?></td>
                            <td data-column="english_score"><?= datasetNumber($row["english_score"], "", 0) ?></td>
                            <td data-column="previous_year_score"><?= datasetNumber($row["previous_year_score"], "%") ?></td>
                            <td data-column="final_percentage"><?= datasetNumber($row["final_percentage"], "%") ?></td>
                            <td data-column="performance_level"><?= h($row["performance_level"]) ?></td>
                            <td data-column="pass_fail"><?= h($row["pass_fail"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dataset-support-grid">
        <article class="detail-panel">
            <div class="section-head compact">
                <div>
                    <p class="eyebrow">Lectura sintetica</p>
                    <h2>Promedios clave</h2>
                </div>
            </div>
            <div class="dataset-bars">
                <?php foreach ($subjectAverages as $subject): ?>
                    <div class="dataset-bar-row">
                        <div>
                            <span><?= h($subject["label"]) ?></span>
                            <strong><?= datasetNumber($subject["value"], "%") ?></strong>
                        </div>
                        <div class="dataset-bar-track">
                            <span style="width: <?= max(2, min(100, (float) $subject["value"])) ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="detail-panel">
            <div class="section-head compact">
                <div>
                    <p class="eyebrow">Correlaciones</p>
                    <h2>Relacion entre habitos y desempeno</h2>
                </div>
            </div>
            <div class="dataset-correlation-grid">
                <article>
                    <small>Estudio vs desempeno</small>
                    <strong><?= datasetNumber($dataset["correlacion_estudio_desempeno"] ?? null, "", 4) ?></strong>
                </article>
                <article>
                    <small>Asistencia vs desempeno</small>
                    <strong><?= datasetNumber($dataset["correlacion_asistencia_desempeno"] ?? null, "", 4) ?></strong>
                </article>
            </div>
            <p class="dataset-support-note">
                Cuando la lectura se acerca a 1 o -1, la relacion es mas clara. Cerca de 0, la relacion lineal es debil.
            </p>
        </article>
    </section>

    <?php if (count($cleanRows) > 0): ?>
        <section class="detail-panel dataset-clean-preview">
            <div class="section-head compact">
                <div>
                    <p class="eyebrow">Control de calidad</p>
                    <h2>Muestra de los datos limpios</h2>
                </div>
                <span><?= count($cleanRows) ?> filas de verificacion</span>
            </div>
            <div class="dataset-table-wrap">
                <table class="dataset-table dataset-table--compact">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Edad</th>
                            <th>Genero</th>
                            <th>Clase</th>
                            <th>Estudio</th>
                            <th>Asistencia</th>
                            <th>Mat</th>
                            <th>Ciencias</th>
                            <th>Ingles</th>
                            <th>Final</th>
                            <th>Nivel</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cleanRows as $row): ?>
                            <tr>
                                <td><?= h($row["student_id"]) ?></td>
                                <td><?= datasetNumber($row["age"], "", 0) ?></td>
                                <td><?= h($row["gender"]) ?></td>
                                <td><?= datasetNumber($row["class"], "", 0) ?></td>
                                <td><?= datasetNumber($row["study_hours_per_day"], " h") ?></td>
                                <td><?= datasetNumber($row["attendance_percentage"], "%") ?></td>
                                <td><?= datasetNumber($row["math_score"], "", 0) ?></td>
                                <td><?= datasetNumber($row["science_score"], "", 0) ?></td>
                                <td><?= datasetNumber($row["english_score"], "", 0) ?></td>
                                <td><?= datasetNumber($row["final_percentage"], "%") ?></td>
                                <td><?= h($row["performance_level"]) ?></td>
                                <td><?= (int) $row["pass_fail"] === 1 ? "Aprobado" : "Reprobado" ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
