<?php

// El puntaje se arma con tres bloques: academia, habitos y contexto
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

    // Bloque academico: promedio y materias reprobadas pesan mas
    if ($promedio < 6) {
        $score += 30;
    } elseif ($promedio < 7) {
        $score += 22;
    } elseif ($promedio < 7.5) {
        $score += 14;
    } elseif ($promedio < 8) {
        $score += 8;
    } elseif ($promedio < 8.5) {
        $score += 4;
    }

    if ($reprobadas === 1) {
        $score += 5;
    } elseif ($reprobadas === 2) {
        $score += 10;
    } elseif ($reprobadas === 3) {
        $score += 15;
    } elseif ($reprobadas >= 4) {
        $score += 20;
    }

    if ($asistencia < 60) {
        $score += 26;
    } elseif ($asistencia < 75) {
        $score += 20;
    } elseif ($asistencia < 85) {
        $score += 13;
    } elseif ($asistencia < 90) {
        $score += 7;
    } elseif ($asistencia < 95) {
        $score += 3;
    }

    // Bloque de habitos: estudio, sueno y distracciones
    if ($estudio < 1) {
        $score += 8;
    } elseif ($estudio < 2) {
        $score += 6;
    } elseif ($estudio < 3) {
        $score += 4;
    } elseif ($estudio < 5) {
        $score += 2;
    }

    if ($sueno < 5) {
        $score += 9;
    } elseif ($sueno < 5.5) {
        $score += 6;
    } elseif ($sueno < 6.5) {
        $score += 3;
    }

    if ($redes > 6) {
        $score += 4;
    } elseif ($redes > 4) {
        $score += 2;
    } elseif ($redes > 2) {
        $score += 1;
    }

    // Bloque de contexto: estres, motivacion y organizacion diaria
    if ($estres >= 10) {
        $score += 12;
    } elseif ($estres >= 8) {
        $score += 10;
    } elseif ($estres >= 6) {
        $score += 6;
    } elseif ($estres >= 4) {
        $score += 3;
    }

    if ($desmotivacion >= 4) {
        $score += 5;
    } elseif ($desmotivacion === 3) {
        $score += 3;
    } elseif ($desmotivacion === 2) {
        $score += 1;
    }

    if ($tiempo <= 1) {
        $score += 6;
    } elseif ($tiempo === 2) {
        $score += 4;
    } elseif ($tiempo === 3) {
        $score += 2;
    }

    if ($entrega <= 1) {
        $score += 4;
    } elseif ($entrega === 2) {
        $score += 2;
    }

    // Factores extra: aunque sean pequenos, tambien suman
    if ((int) ($survey["acceso_internet"] ?? 1) === 0) {
        $score += 2;
    }

    if ((int) ($survey["espacio_estudio"] ?? 1) === 0) {
        $score += 3;
    }

    if ((int) ($survey["trabaja"] ?? 0) === 1) {
        $score += 2;
    }

    return min(100, round($score, 2));
}

function ateneaRiskLevel(float $score): string
{
    // Escala simple para mostrar el nivel en dashboard y ranking
    if ($score >= 65) {
        return "Alto";
    }

    if ($score >= 32) {
        return "Medio";
    }

    return "Bajo";
}

function ateneaRefreshRecommendations(mysqli $conn, int $resultId, array $survey, string $level): void
{
    // Borramos recomendaciones viejas y escribimos las nuevas
    $stmt = $conn->prepare("DELETE FROM recomendaciones WHERE resultado_id = ?");

    if ($stmt) {
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $stmt->close();
    }

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
    // Resumen corto para mostrar en pantalla
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
    $currentScore = ateneaRiskScore($survey);
    $currentLevel = ateneaRiskLevel($currentScore);
    $currentObservation = ateneaObservation($survey, $currentScore, $currentLevel);

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
        if (
            (float) ($result["puntuacion_riesgo"] ?? 0) !== $currentScore ||
            ($result["nivel_riesgo"] ?? "") !== $currentLevel ||
            ($result["observaciones"] ?? "") !== $currentObservation
        ) {
            $stmt = $conn->prepare("
                UPDATE resultados
                SET nivel_riesgo = ?, puntuacion_riesgo = ?, observaciones = ?
                WHERE id = ?
                LIMIT 1
            ");

            if ($stmt) {
                $resultId = (int) $result["id"];
                $stmt->bind_param("sdsi", $currentLevel, $currentScore, $currentObservation, $resultId);
                $stmt->execute();
                $stmt->close();
                ateneaRefreshRecommendations($conn, $resultId, $survey, $currentLevel);
                $result["nivel_riesgo"] = $currentLevel;
                $result["puntuacion_riesgo"] = $currentScore;
                $result["observaciones"] = $currentObservation;
            }
        }

        return $result;
    }

    $score = $currentScore;
    $level = $currentLevel;
    $observation = $currentObservation;

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

    ateneaRefreshRecommendations($conn, $resultId, $survey, $level);

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
