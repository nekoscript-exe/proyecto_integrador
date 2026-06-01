<?php
$futurePhases = [
    [
        "title" => "Fase 1",
        "name" => "Encuesta -> Riesgo academico",
        "description" => "Ya esta funcionando. Atenea calcula el nivel de riesgo a partir de la encuesta y tus registros historicos.",
        "state" => "Activo",
    ],
    [
        "title" => "Fase 2",
        "name" => "Encuesta -> IA",
        "description" => "La IA tomara tus datos, por ejemplo promedio, estres, sueno y materias reprobadas, para generar recomendaciones personalizadas.",
        "state" => "Proximamente",
    ],
    [
        "title" => "Fase 3",
        "name" => "Chat academico",
        "description" => "Abriras el Asistente Atenea para preguntar por promedio, Python, derivadas, organizacion del tiempo y mas.",
        "state" => "Proximamente",
    ],
    [
        "title" => "Fase 4",
        "name" => "Memoria academica",
        "description" => "Atenea consultara tu historial real en la base de datos para responder con contexto, avances y tendencia de riesgo.",
        "state" => "Proximamente",
    ],
];
?>

<section class="assistant-hero">
    <div>
        <p class="eyebrow">Asistente Atenea</p>
        <h2>Consejos academicos con IA, memoria y contexto real.</h2>
        <p>
            Esta seccion quedara lista para integrar el modelo local <code>qwen3:8b</code> con Ollama.
            Por ahora queda como anuncio funcional de la proxima etapa.
        </p>
    </div>
    <div class="assistant-banner">
        <span>Proximamente</span>
        <strong>Chat con IA</strong>
        <p>Consejos, explicaciones y seguimiento academico con la informacion de Atenea.</p>
    </div>
</section>

<section class="assistant-notice">
    <h3>Ruta de implementacion</h3>
    <p>
        La futura integracion seguira esta secuencia: encuesta -> riesgo academico -> IA ->
        chat academico -> memoria academica.
    </p>
    <p class="assistant-command">
        Modelo previsto: <code>ollama pull qwen3:8b</code>
    </p>
</section>

<section class="assistant-grid" aria-label="Fases del asistente de IA">
    <?php foreach ($futurePhases as $phase): ?>
        <article class="assistant-card">
            <small><?= h($phase["title"]) ?> · <?= h($phase["state"]) ?></small>
            <h3><?= h($phase["name"]) ?></h3>
            <p><?= h($phase["description"]) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="assistant-example">
    <div>
        <p class="eyebrow">Ejemplo futuro</p>
        <h3>Asistente Atenea</h3>
        <p>
            "Hola Atenea, tengo promedio 7.1, estres 8, duermo 5 horas y reprobe 2 materias.
            ¿Que me recomiendas?"
        </p>
    </div>
    <div class="assistant-example__reply">
        <strong>Respuesta esperada</strong>
        <p>
            "Veo un nivel de riesgo medio-alto. Conviene dormir mas, repartir mejor el tiempo
            y priorizar las materias con mayor carga."
        </p>
    </div>
</section>
