<?php

function ateneaRiskScore(array $survey): float
{
    $score = 0;

    $promedio = (float) ($survey["promedio"] ?? 0);
    $reprobadas = (int) ($survey["materias_reprobadas"] ?? 0);
    $asistencia = (int) ($survey["asistencia"] ?? 0);
    $estudio = (float) ($survey["horas_estudio"] ?? 0);
    $sueno = (float) ($survey["horas_sueno"] ?? 0);
    $redes = (float) ($survey["uso_redes"] ?? 0);
    $estres = (int) ($survey["nivel_estres"] ?? 0);
    $desmotivacion = (int) ($survey["desmotivacion"] ?? 0);
    $tiempo = (int) ($survey["administracion_tiempo"] ?? 0);
    $entrega = (int) ($survey["entrega_tareas"] ?? 3);

    if ($promedio < 6) {
        $score += 24;
    } elseif ($promedio < 7.5) {
        $score += 14;
    } elseif ($promedio < 8.5) {
        $score += 6;
    }

    $score += min(18, $reprobadas * 6);

    if ($asistencia < 70) {
        $score += 18;
    } elseif ($asistencia < 85) {
        $score += 10;
    } elseif ($asistencia < 93) {
        $score += 4;
    }

    if ($estudio < 1) {
        $score += 10;
    } elseif ($estudio < 2) {
        $score += 5;
    }

    if ($sueno < 5) {
        $score += 10;
    } elseif ($sueno < 6.5) {
        $score += 5;
    }

    if ($redes > 6) {
        $score += 8;
    } elseif ($redes > 4) {
        $score += 4;
    }

    if ($estres >= 8) {
        $score += 12;
    } elseif ($estres >= 5) {
        $score += 6;
    }

    if ($desmotivacion >= 4) {
        $score += 10;
    } elseif ($desmotivacion >= 3) {
        $score += 5;
    }

    if ($tiempo <= 2) {
        $score += 8;
    } elseif ($tiempo === 3) {
        $score += 3;
    }

    if ($entrega <= 2) {
        $score += 8;
    }

    if ((int) ($survey["acceso_internet"] ?? 1) === 0) {
        $score += 6;
    }

    if ((int) ($survey["espacio_estudio"] ?? 1) === 0) {
        $score += 6;
    }

    if ((int) ($survey["trabaja"] ?? 0) === 1) {
        $score += 4;
    }

    return min(100, round($score, 2));
}

function ateneaRiskLevel(float $score): string
{
    if ($score >= 60) {
        return "Alto";
    }

    if ($score >= 32) {
        return "Medio";
    }

    return "Bajo";
}

function ateneaRecommendations(array $survey, string $level): array
{
    $items = [];

    if ((float) ($survey["promedio"] ?? 10) < 7.5) {
        $items[] = "Agenda dos bloques de estudio enfocado por semana para reforzar las materias con menor desempeño.";
    }

    if ((int) ($survey["materias_reprobadas"] ?? 0) > 0) {
        $items[] = "Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.";
    }

    if ((int) ($survey["asistencia"] ?? 100) < 85) {
        $items[] = "Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.";
    }

    if ((float) ($survey["horas_sueno"] ?? 8) < 6.5) {
        $items[] = "Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.";
    }

    if ((float) ($survey["uso_redes"] ?? 0) > 4) {
        $items[] = "Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.";
    }

    if ((int) ($survey["nivel_estres"] ?? 0) >= 7) {
        $items[] = "Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.";
    }

    if ((int) ($survey["administracion_tiempo"] ?? 5) <= 3) {
        $items[] = "Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.";
    }

    if ((int) ($survey["espacio_estudio"] ?? 1) === 0) {
        $items[] = "Identifica un espacio fijo de estudio, aunque sea compartido, con horario definido y materiales listos.";
    }

    if (count($items) === 0) {
        $items[] = $level === "Bajo"
            ? "Mantén tus hábitos actuales y revisa tus métricas cada semana para sostener el avance."
            : "Revisa tus hábitos principales y elige una acción pequeña para mejorar esta semana.";
    }

    return array_slice($items, 0, 5);
}

function ateneaObservation(array $survey, float $score, string $level): string
{
    $promedio = $survey["promedio"] ?? "sin dato";
    $asistencia = $survey["asistencia"] ?? "sin dato";
    $estres = $survey["nivel_estres"] ?? "sin dato";

    return "Riesgo $level con puntuación $score/100. Promedio: $promedio, asistencia: $asistencia%, estrés: $estres/10.";
}

function ateneaLatestSurvey(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare("
        SELECT *
        FROM encuestas
        WHERE usuario_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $survey = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $survey ?: null;
}

function ateneaEnsureResultForSurvey(mysqli $conn, array $survey): ?array
{
    if (empty($survey["id"])) {
        return null;
    }

    $surveyId = (int) $survey["id"];
    $stmt = $conn->prepare("
        SELECT *
        FROM resultados
        WHERE encuesta_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    $existing = $stmt->get_result();
    $result = $existing ? $existing->fetch_assoc() : null;
    $stmt->close();

    if ($result) {
        return $result;
    }

    $score = ateneaRiskScore($survey);
    $level = ateneaRiskLevel($score);
    $observation = ateneaObservation($survey, $score, $level);

    $stmt = $conn->prepare("
        INSERT INTO resultados
        (
            encuesta_id,
            nivel_riesgo,
            puntuacion_riesgo,
            observaciones
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("isds", $surveyId, $level, $score, $observation);
    $stmt->execute();
    $resultId = $conn->insert_id;
    $stmt->close();

    foreach (ateneaRecommendations($survey, $level) as $message) {
        $stmt = $conn->prepare("
            INSERT INTO recomendaciones
            (
                resultado_id,
                mensaje
            )
            VALUES
            (
                ?,
                ?
            )
        ");

        if ($stmt) {
            $stmt->bind_param("is", $resultId, $message);
            $stmt->execute();
            $stmt->close();
        }
    }

    return [
        "id" => $resultId,
        "encuesta_id" => $surveyId,
        "nivel_riesgo" => $level,
        "puntuacion_riesgo" => $score,
        "observaciones" => $observation,
    ];
}

function ateneaEnsureResultForUser(mysqli $conn, int $userId): ?array
{
    $survey = ateneaLatestSurvey($conn, $userId);

    if (!$survey) {
        return null;
    }

    return ateneaEnsureResultForSurvey($conn, $survey);
}

function ateneaEnsureAllResults(mysqli $conn): void
{
    $result = $conn->query("
        SELECT usuario_id
        FROM encuestas
        WHERE usuario_id IS NOT NULL
        GROUP BY usuario_id
    ");

    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        ateneaEnsureResultForUser($conn, (int) $row["usuario_id"]);
    }
}
