# Dataset de rendimiento estudiantil

Esta carpeta guarda archivos CSV usados por ATENEA para analisis de datos reales.

Archivo base:

- `Student_Performance_Dataset.csv`

Flujo aplicado:

1. El CSV se lee con `PYTHON/process_student_dataset.py`.
2. Los datos originales se guardan en `dataset_estudiantes_raw`.
3. Los datos limpios se guardan en `dataset_estudiantes_clean`.
4. Las metricas se guardan en `dataset_analysis_results`.
5. Cada carga queda registrada en `dataset_uploads`.

Desde el panel de administrador se pueden importar nuevos CSV para repetir el ciclo de vida de datos y agregarlos al dashboard.
