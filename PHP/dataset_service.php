<?php

function ateneaDatasetTableExists(mysqli $conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaDatasetColumnExists(mysqli $conn, string $table, string $column): bool
{
    if (!ateneaDatasetTableExists($conn, $table)) {
        return false;
    }

    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaDatasetIndexExists(mysqli $conn, string $table, string $index): bool
{
    if (!ateneaDatasetTableExists($conn, $table)) {
        return false;
    }

    $index = $conn->real_escape_string($index);
    $result = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaDatasetEnsureIndex(mysqli $conn, string $table, string $index, string $column): void
{
    if (!ateneaDatasetIndexExists($conn, $table, $index)) {
        $conn->query("ALTER TABLE `{$table}` ADD KEY `{$index}` (`{$column}`)");
    }
}

function ateneaDatasetEnsureSchema(mysqli $conn): void
{
    // Tabla de control para saber que CSV se cargo, cuando y con que resultado
    if (!ateneaDatasetTableExists($conn, "dataset_uploads")) {
        $conn->query("
            CREATE TABLE dataset_uploads (
                id int(11) NOT NULL AUTO_INCREMENT,
                nombre_original varchar(255) NOT NULL,
                archivo_guardado varchar(255) DEFAULT NULL,
                fuente varchar(100) DEFAULT 'Kaggle',
                filas_raw int(11) DEFAULT 0,
                filas_clean int(11) DEFAULT 0,
                estado enum('procesando','completado','error') NOT NULL DEFAULT 'procesando',
                mensaje text DEFAULT NULL,
                uploaded_by int(11) DEFAULT NULL,
                fecha_carga timestamp NOT NULL DEFAULT current_timestamp(),
                fecha_procesado datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY uploaded_by (uploaded_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    if (!ateneaDatasetTableExists($conn, "dataset_estudiantes_raw")) {
        $conn->query("
            CREATE TABLE dataset_estudiantes_raw (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                student_id varchar(20) DEFAULT NULL,
                age int(11) DEFAULT NULL,
                gender varchar(20) DEFAULT NULL,
                `class` int(11) DEFAULT NULL,
                study_hours_per_day decimal(4,2) DEFAULT NULL,
                attendance_percentage decimal(5,2) DEFAULT NULL,
                parental_education varchar(50) DEFAULT NULL,
                internet_access enum('Yes','No') DEFAULT NULL,
                extracurricular_activities enum('Yes','No') DEFAULT NULL,
                math_score int(11) DEFAULT NULL,
                science_score int(11) DEFAULT NULL,
                english_score int(11) DEFAULT NULL,
                previous_year_score decimal(5,2) DEFAULT NULL,
                final_percentage decimal(5,2) DEFAULT NULL,
                performance_level varchar(20) DEFAULT NULL,
                pass_fail varchar(10) DEFAULT NULL,
                fecha_carga timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    if (!ateneaDatasetTableExists($conn, "dataset_estudiantes_clean")) {
        $conn->query("
            CREATE TABLE dataset_estudiantes_clean (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                student_id varchar(20) DEFAULT NULL,
                age int(11) DEFAULT NULL,
                gender varchar(20) DEFAULT NULL,
                `class` int(11) DEFAULT NULL,
                study_hours_per_day decimal(4,2) DEFAULT NULL,
                attendance_percentage decimal(5,2) DEFAULT NULL,
                parental_education varchar(50) DEFAULT NULL,
                internet_access tinyint(1) DEFAULT NULL,
                extracurricular_activities tinyint(1) DEFAULT NULL,
                math_score int(11) DEFAULT NULL,
                science_score int(11) DEFAULT NULL,
                english_score int(11) DEFAULT NULL,
                previous_year_score decimal(5,2) DEFAULT NULL,
                final_percentage decimal(5,2) DEFAULT NULL,
                performance_level varchar(20) DEFAULT NULL,
                pass_fail tinyint(1) DEFAULT NULL,
                promedio_materias decimal(5,2) DEFAULT NULL,
                fecha_procesado timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    if (!ateneaDatasetTableExists($conn, "dataset_analysis_results")) {
        $conn->query("
            CREATE TABLE dataset_analysis_results (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                total_students int(11) DEFAULT NULL,
                promedio_general decimal(5,2) DEFAULT NULL,
                porcentaje_aprobados decimal(5,2) DEFAULT NULL,
                porcentaje_reprobados decimal(5,2) DEFAULT NULL,
                promedio_matematicas decimal(5,2) DEFAULT NULL,
                promedio_ciencias decimal(5,2) DEFAULT NULL,
                promedio_ingles decimal(5,2) DEFAULT NULL,
                correlacion_estudio_desempeno decimal(6,4) DEFAULT NULL,
                correlacion_asistencia_desempeno decimal(6,4) DEFAULT NULL,
                fecha_analisis timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    foreach (["dataset_estudiantes_raw", "dataset_estudiantes_clean", "dataset_analysis_results"] as $table) {
        if (!ateneaDatasetColumnExists($conn, $table, "dataset_upload_id")) {
            $conn->query("ALTER TABLE `{$table}` ADD COLUMN dataset_upload_id int(11) DEFAULT NULL AFTER id");
        }
        ateneaDatasetEnsureIndex($conn, $table, "dataset_upload_id", "dataset_upload_id");
    }

    if (!ateneaDatasetColumnExists($conn, "dataset_estudiantes_clean", "class")) {
        $conn->query("ALTER TABLE `dataset_estudiantes_clean` ADD COLUMN `class` int(11) DEFAULT NULL AFTER gender");
    }
}

function ateneaDatasetFetchAll(mysqli $conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if ($types !== "" && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    return $rows;
}

function ateneaDatasetFetchOne(mysqli $conn, string $sql, string $types = "", array $params = []): ?array
{
    $rows = ateneaDatasetFetchAll($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function ateneaDatasetUploads(mysqli $conn, int $limit = 12): array
{
    ateneaDatasetEnsureSchema($conn);
    $limit = max(1, min(50, $limit));

    return ateneaDatasetFetchAll($conn, "
        SELECT
            u.*,
            a.promedio_general,
            a.porcentaje_aprobados,
            a.correlacion_estudio_desempeno,
            a.correlacion_asistencia_desempeno
        FROM dataset_uploads u
        LEFT JOIN dataset_analysis_results a ON a.dataset_upload_id = u.id
        ORDER BY u.fecha_carga DESC
        LIMIT {$limit}
    ");
}

function ateneaDatasetCurrentUpload(mysqli $conn, ?int $uploadId = null): ?array
{
    ateneaDatasetEnsureSchema($conn);

    if ($uploadId !== null && $uploadId > 0) {
        return ateneaDatasetFetchOne($conn, "
            SELECT
                u.*,
                a.total_students,
                a.promedio_general,
                a.porcentaje_aprobados,
                a.porcentaje_reprobados,
                a.promedio_matematicas,
                a.promedio_ciencias,
                a.promedio_ingles,
                a.correlacion_estudio_desempeno,
                a.correlacion_asistencia_desempeno,
                a.fecha_analisis
            FROM dataset_uploads u
            LEFT JOIN dataset_analysis_results a ON a.dataset_upload_id = u.id
            WHERE u.id = ?
            LIMIT 1
        ", "i", [$uploadId]);
    }

    return ateneaDatasetFetchOne($conn, "
        SELECT
            u.*,
            a.total_students,
            a.promedio_general,
            a.porcentaje_aprobados,
            a.porcentaje_reprobados,
            a.promedio_matematicas,
            a.promedio_ciencias,
            a.promedio_ingles,
            a.correlacion_estudio_desempeno,
            a.correlacion_asistencia_desempeno,
            a.fecha_analisis
        FROM dataset_uploads u
        LEFT JOIN dataset_analysis_results a ON a.dataset_upload_id = u.id
        WHERE u.estado = 'completado'
        ORDER BY u.fecha_carga DESC
        LIMIT 1
    ");
}

function ateneaDatasetRows(mysqli $conn, int $uploadId, int $limit = 25, bool $raw = false): array
{
    $limit = max(1, min(5000, $limit));
    $table = $raw ? "dataset_estudiantes_raw" : "dataset_estudiantes_clean";

    return ateneaDatasetFetchAll($conn, "
        SELECT *
        FROM {$table}
        WHERE dataset_upload_id = ?
        ORDER BY id ASC
        LIMIT {$limit}
    ", "i", [$uploadId]);
}

function ateneaDatasetDistribution(mysqli $conn, int $uploadId, string $field): array
{
    $allowed = ["gender", "performance_level", "parental_education"];
    if (!in_array($field, $allowed, true)) {
        return [];
    }

    return ateneaDatasetFetchAll($conn, "
        SELECT {$field} AS label, COUNT(*) AS total
        FROM dataset_estudiantes_clean
        WHERE dataset_upload_id = ?
        GROUP BY {$field}
        ORDER BY total DESC
    ", "i", [$uploadId]);
}

function ateneaDatasetPassFail(mysqli $conn, int $uploadId): array
{
    return ateneaDatasetFetchAll($conn, "
        SELECT
            CASE WHEN pass_fail = 1 THEN 'Aprobado' ELSE 'Reprobado' END AS label,
            COUNT(*) AS total
        FROM dataset_estudiantes_clean
        WHERE dataset_upload_id = ?
        GROUP BY pass_fail
        ORDER BY pass_fail DESC
    ", "i", [$uploadId]);
}

function ateneaDatasetSubjectAverages(?array $upload): array
{
    if (!$upload) {
        return [];
    }

    return [
        ["label" => "Matematicas", "value" => (float) ($upload["promedio_matematicas"] ?? 0)],
        ["label" => "Ciencias", "value" => (float) ($upload["promedio_ciencias"] ?? 0)],
        ["label" => "Ingles", "value" => (float) ($upload["promedio_ingles"] ?? 0)],
    ];
}

function ateneaDatasetLifecycle(?array $upload): array
{
    $raw = (int) ($upload["filas_raw"] ?? 0);
    $clean = (int) ($upload["filas_clean"] ?? 0);

    return [
        ["label" => "Ingesta CSV", "value" => $raw],
        ["label" => "Datos raw", "value" => $raw],
        ["label" => "Datos clean", "value" => $clean],
        ["label" => "Metricas", "value" => $clean > 0 ? 1 : 0],
    ];
}

function ateneaDatasetPercent(int $value, int $total): float
{
    if ($total <= 0) {
        return 0;
    }
    return round(($value / $total) * 100, 2);
}

function ateneaDatasetNumericSummary(mysqli $conn, int $uploadId, string $field, string $label, string $unit = "", int $decimals = 1): ?array
{
    $allowed = [
        "age",
        "class",
        "study_hours_per_day",
        "attendance_percentage",
        "math_score",
        "science_score",
        "english_score",
        "previous_year_score",
        "final_percentage",
        "promedio_materias",
    ];

    if (!in_array($field, $allowed, true)) {
        return null;
    }

    $summary = ateneaDatasetFetchOne($conn, "
        SELECT
            MIN(`{$field}`) AS min_value,
            MAX(`{$field}`) AS max_value,
            AVG(`{$field}`) AS avg_value
        FROM dataset_estudiantes_clean
        WHERE dataset_upload_id = ?
    ", "i", [$uploadId]);

    if (!$summary) {
        return null;
    }

    return [
        "field" => $field,
        "label" => $label,
        "unit" => $unit,
        "decimals" => $decimals,
        "min" => $summary["min_value"] !== null ? round((float) $summary["min_value"], $decimals) : null,
        "max" => $summary["max_value"] !== null ? round((float) $summary["max_value"], $decimals) : null,
        "avg" => $summary["avg_value"] !== null ? round((float) $summary["avg_value"], $decimals) : null,
    ];
}
