<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isPaolaSkin = (int) ($_SESSION["usuario_id"] ?? 0) === 15
    || strtolower((string) ($_SESSION["usuario_correo"] ?? "")) === "paola.moralesj2005@gmail.com";
?>
<!DOCTYPE html>
<html lang="es"<?= $isPaolaSkin ? ' data-skin="paola"' : "" ?>>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link rel="icon" type="image/png" href="../IMG/favicon.png">

    <title>
        Atenea | Encuesta Académica
    </title>

    <!-- Cargamos la hoja de estilos -->
    <link
        rel="stylesheet"
        href="../CSS/form.css"
    >
    <script src="../JS/theme.js" defer></script>

</head>

<body>

    <div class="container">

        <!-- Panel izquierdo con marca y progreso -->
        <div class="left-panel">

            <div class="overlay"></div>

            <div class="left-content">

                <div class="brand-lockup">
                    <img src="../IMG/logo.png" alt="Logo Atenea" class="brand-logo">
                </div>

                <span class="badge">
                    ODS 4 · EDUCACIÓN DE CALIDAD
                </span>

                <p class="description">

                    Completa la encuesta académica para generar
                    un análisis inicial sobre posibles factores
                    relacionados con riesgo académico estudiantil.

                </p>

                <!-- Barra de progreso general -->
                <div class="progress-container">

                    <div class="progress-info">

                        <span>
                            Progreso
                        </span>

                        <span id="progressText">
                            25%
                        </span>

                    </div>

                    <div class="progress-bar">

                        <div
                            class="progress"
                            id="progress"
                        ></div>

                    </div>

                </div>

                <!-- Indicador de pasos -->
                <div class="steps">

                    <div class="step active">

                        <span>1</span>

                        <p>Académico</p>

                    </div>

                    <div class="step">

                        <span>2</span>

                        <p>Hábitos</p>

                    </div>

                    <div class="step">

                        <span>3</span>

                        <p>Factores</p>

                    </div>

                    <div class="step">

                        <span>4</span>

                        <p>Bienestar</p>

                    </div>

                </div>

            </div>

        </div>

        <!-- Panel derecho con la encuesta -->
        <div class="right-panel">

            <div class="theme-row">
                <button type="button" class="theme-toggle" data-theme-toggle>Modo oscuro</button>
            </div>

            <!-- Este formulario manda la info a register.php -->
            <form
                class="form-card"
                action="register.php"
                method="POST"
                id="multiStepForm"
            >

                <!-- Paso 1: datos academicos -->
                <div class="form-step active">

                    <h2>
                        Datos Académicos
                    </h2>

                    <p class="subtitle">
                        Información relacionada con tu desempeño escolar.
                    </p>

                    <div class="input-group">

                        <label>
                            1. ¿Cuál es tu promedio actual?
                        </label>

                        <input
                            type="number"
                            name="promedio"
                            step="0.1"
                            min="0"
                            max="10"
                            placeholder="Ejemplo: 8.7"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            2. ¿Cuántas materias has reprobado?
                        </label>

                        <input
                            type="number"
                            name="materias_reprobadas"
                            min="0"
                            placeholder="Ejemplo: 2"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            3. ¿Cuál es tu porcentaje aproximado de asistencia?
                        </label>

                        <input
                            type="number"
                            name="asistencia"
                            min="0"
                            max="100"
                            placeholder="Ejemplo: 90"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            4. ¿Cuántas horas estudias al día?
                        </label>

                        <input
                            type="number"
                            name="horas_estudio"
                            step="0.1"
                            min="0"
                            placeholder="Ejemplo: 3.5"
                            required
                        >

                    </div>

                </div>

                <!-- Paso 2: habitos -->
                <div class="form-step">

                    <h2>
                        Hábitos
                    </h2>

                    <p class="subtitle">
                        Hábitos relacionados con tu rutina diaria.
                    </p>

                    <div class="input-group">

                        <label>
                            5. ¿Cuántas horas duermes al día?
                        </label>

                        <input
                            type="number"
                            name="horas_sueno"
                            step="0.1"
                            min="0"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            6. ¿Cuántas horas usas redes sociales al día?
                        </label>

                        <input
                            type="number"
                            name="uso_redes"
                            step="0.1"
                            min="0"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            7. ¿Realizas actividad física?
                        </label>

                        <select
                            name="actividad_fisica"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>
                            8. ¿Con qué frecuencia entregas tareas a tiempo?
                        </label>

                        <select
                            name="entrega_tareas"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Nunca
                            </option>

                            <option value="2">
                                A veces
                            </option>

                            <option value="3">
                                Frecuentemente
                            </option>

                            <option value="4">
                                Siempre
                            </option>

                        </select>

                    </div>

                </div>

                <!-- Paso 3: factores de apoyo -->
                <div class="form-step">

                    <h2>
                        Factores Externos
                    </h2>

                    <p class="subtitle">
                        Factores relacionados con tu entorno.
                    </p>

                    <div class="input-group">

                        <label>
                            9. ¿Cuánto tiempo tardas en llegar a la escuela? (min)
                        </label>

                        <input
                            type="number"
                            name="tiempo_transporte"
                            min="0"
                            required
                        >

                    </div>

                    <div class="input-group">

                        <label>
                            10. ¿Trabajas actualmente?
                        </label>

                        <select
                            name="trabaja"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>
                            11. ¿Tienes acceso estable a internet?
                        </label>

                        <select
                            name="acceso_internet"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>
                            12. ¿Tienes un espacio adecuado para estudiar?
                        </label>

                        <select
                            name="espacio_estudio"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                </div>

                <!-- Paso 4: bienestar y organizacion -->
                <div class="form-step">

                    <h2>
                        Bienestar y Organización
                    </h2>

                    <p class="subtitle">
                        Organización personal y bienestar académico.
                    </p>

                    <div class="input-group">

                        <label>
                            13. ¿Cómo calificarías tu nivel de estrés académico?
                        </label>

                        <div class="range-shell">

                            <div class="range-header">
                                <span class="range-hint">Nada</span>
                                <span class="range-value" data-range-output="nivel_estres">5 / 10</span>
                                <span class="range-hint">Mucho</span>
                            </div>

                            <input
                                type="range"
                                name="nivel_estres"
                                min="1"
                                max="10"
                                value="5"
                                data-range-input="nivel_estres"
                                aria-label="Nivel de estrés académico"
                            >

                        </div>

                    </div>

                    <div class="input-group">

                        <label>
                            14. ¿Con qué frecuencia te sientes desmotivado académicamente?
                        </label>

                        <select
                            name="desmotivacion"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Nunca
                            </option>

                            <option value="2">
                                Rara vez
                            </option>

                            <option value="3">
                                A veces
                            </option>

                            <option value="4">
                                Frecuentemente
                            </option>

                            <option value="5">
                                Siempre
                            </option>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>
                            15. ¿Usas herramientas digitales para organizar tus estudios?
                        </label>

                        <select
                            name="herramientas_digitales"
                            required
                        >

                            <option value="">
                                Selecciona
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>
                            16. ¿Consideras que administras bien tu tiempo?
                        </label>

                        <div class="range-shell">

                            <div class="range-header">
                                <span class="range-hint">Poco</span>
                                <span class="range-value" data-range-output="administracion_tiempo">3 / 5</span>
                                <span class="range-hint">Muy bien</span>
                            </div>

                            <input
                                type="range"
                                name="administracion_tiempo"
                                min="1"
                                max="5"
                                value="3"
                                data-range-input="administracion_tiempo"
                                aria-label="Administración del tiempo"
                            >

                        </div>

                    </div>

                </div>

                <!-- Botones para avanzar o volver -->
                <div class="buttons">

                    <button
                        type="button"
                        id="prevBtn"
                    >
                        Anterior
                    </button>

                    <button
                        type="button"
                        id="nextBtn"
                    >
                        Siguiente
                    </button>

                    <button
                        type="submit"
                        id="submitBtn"
                    >
                        Finalizar Encuesta
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- Cargamos el script del formulario -->
    <script src="../JS/form.js"></script>

</body>

</html>
