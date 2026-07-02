# Atenea

ATENEA es una plataforma web orientada al ODS 4. Combina registro de estudiantes, encuesta academica, analisis de riesgo y visualizacion de datos oficiales para explicar problemas de permanencia, reprobacion y desempeno educativo.

## Enfoque actual

El proyecto migro definitivamente al uso de `DATASETS/` oficiales. El flujo viejo basado en CSV/Kaggle fue retirado para que la pagina tenga un enfoque mas cercano a ciencia de datos, procesamiento y analisis educativo real.

La plataforma conserva:

- registro e inicio de sesion;
- encuesta academica;
- dashboard de usuario;
- ranking;
- perfil;
- analisis de riesgo;
- panel de administracion;
- recuperacion de contrasena por correo SMTP;
- LandingPage con indicadores procesados desde XLSX oficiales.

## Tecnologias

- `PHP` para backend y renderizado de vistas.
- `MariaDB / MySQL` para la base de datos de usuarios, encuestas, resultados y auditoria.
- `HTML`, `CSS` y `JavaScript` para interfaz y experiencia de usuario.
- `Python` para procesamiento, limpieza y visualizacion de datasets oficiales.
- `pandas`, `matplotlib` y `openpyxl` para analisis de datos.
- `PHPMailer` para recuperacion de contrasena con correo real.
- `Composer` para dependencias PHP.

## Estructura principal

- `PHP/`: controladores principales, login, registro, dashboard, admin, correo y seguridad.
- `VIEWS/`: secciones internas del dashboard.
- `CSS/`: estilos de LandingPage, dashboard, formularios, login, registro y admin.
- `JS/`: interacciones de interfaz, tema, formularios y navegacion.
- `DATASETS/`: fuente oficial de datos academicos en XLSX y salidas procesadas.
- `PYTHON/`: scripts de analisis y limpieza.
- `DATABASE/`: dumps y schema limpio.
- `Document/`: documentacion del proyecto.
- `IMG/`: logo y favicon.

## Datos oficiales

La fuente oficial se conserva completa en:

- `DATASETS/2020-2021/`
- `DATASETS/2021-2022/`
- `DATASETS/2022-2023/`
- `DATASETS/2023-2024/`
- `DATASETS/2024-2025/`

El procesamiento genera:

- `DATASETS/processed/official_education_clean.csv`
- `DATASETS/processed/landing_metrics.json`
- `DATASETS/processed/charts/*.png`
- `Atenea_Datasets_Oficiales.ipynb`

Para regenerar las salidas:

```bash
python3 PYTHON/process_official_datasets.py
```

## LandingPage

Archivo:

- `PHP/LandingPage.php`

La LandingPage muestra datos reales procesados desde `DATASETS/processed/landing_metrics.json`, incluyendo KPIs, graficas y explicacion del ciclo de vida de datos. Si el JSON no existe, la pagina debe seguir cargando y mostrar valores de respaldo.

## Dashboard de usuario

Archivo:

- `PHP/dashboard.php`

Vistas:

- `VIEWS/home.php`
- `VIEWS/profile.php`
- `VIEWS/ranking.php`
- `VIEWS/analysis.php`
- `VIEWS/plan.php`
- `VIEWS/community.php`
- `VIEWS/activity.php`
- `VIEWS/assistant.php`

El dashboard mantiene la experiencia social y academica: feed, ranking, perfil, diagnostico, plan de mejora, actividad y anuncio del asistente IA.

## Dashboard de administracion

Archivo:

- `PHP/admin_dashboard.php`

Funciones:

- gestion de usuarios;
- cambio de rol;
- bloqueo y desbloqueo;
- eliminacion de usuarios;
- historial de modificaciones;
- consola SQL;
- estado del procesamiento de datos oficiales.

El panel admin ya no importa CSV Kaggle ni depende de tablas `dataset_*`.

## Base de datos

Archivos:

- `DATABASE/atenea.sql`: dump historico completo. No modificar por ahora.
- `DATABASE/schema.sql`: estructura limpia sin datos reales.

Tablas funcionales principales:

- `usuarios`
- `encuestas`
- `resultados`
- `recomendaciones`
- `sesiones`
- `password_resets`
- `admin_historial`

## Recuperacion de contrasena

Archivos:

- `PHP/forgot_password.php`
- `PHP/reset_password.php`
- `PHP/security.php`
- `PHP/mailer.php`

El flujo usa token seguro, expiracion, uso unico y envio por Gmail SMTP mediante PHPMailer. Las credenciales viven en `config.local.php`, que no debe subirse a GitHub.

## Instalacion local minima

1. Crear `config.local.php` basado en `config.local.example.php`.
2. Instalar dependencias PHP:

```bash
composer install
```

3. Crear la base de datos usando `DATABASE/schema.sql` o importar el dump historico si se requiere un entorno con datos.
4. Regenerar datos oficiales si hace falta:

```bash
python3 PYTHON/process_official_datasets.py
```

## Produccion

En produccion debe bastar con:

1. clonar el repositorio;
2. crear `config.local.php`;
3. ejecutar `composer install --no-dev --optimize-autoloader`;
4. importar `DATABASE/schema.sql`;
5. revisar que `DATASETS/processed/landing_metrics.json` exista;
6. probar login, registro, dashboard, admin y recuperacion de contrasena.

## Archivos privados

No deben versionarse:

- `config.local.php`
- `vendor/`
- `venv/`
- `.vscode/`

## Nota de mantenimiento

El enfoque del proyecto ahora es claro: la aplicacion web es el medio para presentar analisis educativo, pero el valor central esta en el procesamiento de datos oficiales y en la interpretacion del riesgo academico.
