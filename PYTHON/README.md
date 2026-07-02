# Atenea Python Analytics

Esta carpeta contiene las herramientas de analisis de datos para ATENEA. El enfoque oficial del proyecto usa los archivos XLSX ubicados en `DATASETS/` y deja fuera el flujo viejo de CSV/Kaggle.

## Fuente oficial

La fuente principal es:

- `DATASETS/2020-2021/`
- `DATASETS/2021-2022/`
- `DATASETS/2022-2023/`
- `DATASETS/2023-2024/`
- `DATASETS/2024-2025/`

Cada periodo contiene reportes academicos oficiales en formato XLSX. Python se encarga de leerlos, limpiar encabezados, normalizar datos y generar salidas listas para la web.

## `process_official_datasets.py`

Procesa los XLSX oficiales y genera los archivos que consume el LandingPage.

```bash
python3 PYTHON/process_official_datasets.py
```

Flujo aplicado:

1. Ingesta de archivos XLSX por periodo escolar.
2. Deteccion de encabezados dentro de hojas con formato institucional.
3. Limpieza de textos, municipios, columnas numericas y valores faltantes.
4. Clasificacion de faltantes como `NULL` cuando no hay dato valido.
5. Calculo de indicadores ponderados por matricula: reprobacion, desercion, eficiencia terminal y riesgo.
6. Generacion de CSV limpio, JSON para PHP, graficas PNG y notebook.

Archivos generados y conservados por ahora en el repositorio:

- `DATASETS/processed/official_education_clean.csv`
- `DATASETS/processed/landing_metrics.json`
- `DATASETS/processed/charts/riesgo_por_periodo.png`
- `DATASETS/processed/charts/factores_ultimo_periodo.png`
- `DATASETS/processed/charts/marginacion_pastel.png`
- `DATASETS/processed/charts/mapa_riesgo_municipal.png`
- `Atenea_Datasets_Oficiales.ipynb`

## `risk_analysis.py`

Lee la base de datos `atenea`, calcula una puntuacion de riesgo por estudiante y exporta un resumen JSON.

```bash
python3 PYTHON/risk_analysis.py --host localhost --user root --database atenea
```

Si tienes `pymysql`, el script lo usa. Si no, intenta usar el cliente MySQL de XAMPP en `/opt/lampp/bin/mysql`.

## Criterio de riesgo

La puntuacion se calcula con una logica ponderada que da mas peso a factores academicos y de habitos:

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

La idea es que un solo indicador no dispare por si solo un riesgo alto. ATENEA busca un resultado mas realista, donde el riesgo alto aparece cuando se combinan varias senales de alerta.

## Dependencias sugeridas

```bash
python3 -m venv venv
source venv/bin/activate
pip install pandas matplotlib openpyxl pymysql
```

Tambien puedes usar el archivo principal de dependencias si esta actualizado:

```bash
pip install -r requirements.txt
```
