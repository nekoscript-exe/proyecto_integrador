# Atenea Python Analytics

Esta carpeta contiene utilidades para analizar datos academicos fuera del dashboard PHP.

## Scripts disponibles

### `risk_analysis.py`

Lee la base de datos `atenea`, calcula una puntuacion de riesgo por estudiante y exporta un resumen JSON.

```bash
python3 PYTHON/risk_analysis.py --host localhost --user root --database atenea
```

Si tienes `pymysql`, el script lo usa. Si no, intenta usar el cliente MySQL de XAMPP en `/opt/lampp/bin/mysql`.

El cálculo replica la lógica usada en `PHP/analytics.php`, así que puede servir como base para futuros modelos, reportes o notebooks.

## Criterio de riesgo

La puntuacion se calcula con una logica ponderada que da mas peso a los factores realmente academicos:

- promedio;
- asistencia;
- materias reprobadas;
- horas de estudio;
- horas de sueno;
- nivel de estres;
- administracion del tiempo;
- desmotivacion;
- uso de redes;
- entrega de tareas;
- condiciones de estudio y trabajo.

La idea es que un solo indicador no dispare por si solo un riesgo alto. Atenea busca un resultado mas realista y estable, donde el riesgo alto aparece cuando se combinan varias señales de alerta.

### `process_student_dataset.py`

Procesa un CSV de rendimiento estudiantil con el ciclo de vida de datos:

1. Ingesta del CSV.
2. Guardado raw en `dataset_estudiantes_raw`.
3. Limpieza y normalizacion en `dataset_estudiantes_clean`.
4. Calculo de metricas en `dataset_analysis_results`.
5. Registro de la carga en `dataset_uploads`.

Ejecutar solo como prueba, sin tocar la BD:

```bash
venv/bin/python PYTHON/process_student_dataset.py DATASET/Student_Performance_Dataset.csv --dry-run
```

Procesar y guardar en MariaDB:

```bash
venv/bin/python PYTHON/process_student_dataset.py DATASET/Student_Performance_Dataset.csv \
  --host localhost \
  --user root \
  --database atenea \
  --source-name Student_Performance_Dataset.csv \
  --stored-path DATASET/Student_Performance_Dataset.csv
```

El panel administrador tambien puede ejecutar este proceso desde la interfaz, usando el boton para procesar el dataset incluido o subiendo un CSV nuevo.
