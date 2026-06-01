# Atenea Python Analytics

Esta carpeta contiene utilidades para analizar los datos académicos fuera del dashboard PHP ;-;"

## Script disponible

`risk_analysis.py` lee la base de datos `atenea`, calcula una puntuación de riesgo por estudiante y exporta un resumen JSON.

```bash
python3 PYTHON/risk_analysis.py --host localhost --user root --database atenea
```

Si tienes `pymysql`, el script lo usa. Si no, intenta usar el cliente MySQL de XAMPP en `/opt/lampp/bin/mysql`.

El cálculo replica la lógica usada en `PHP/analytics.php`, así que puede servir como base para futuros modelos, reportes o notebooks.
