# Atenea

Atenea es una plataforma web orientada al ODS 4, enfocada en la recopilacion y analisis de datos academicos para detectar riesgo estudiantil, mostrar resultados utiles y dar seguimiento tanto al usuario como al administrador del sistema.

## Proposito del proyecto

El sistema permite:

- registrar estudiantes y su informacion academica;
- guardar encuestas con variables de habitos, rendimiento y contexto;
- calcular una puntuacion de riesgo academico;
- mostrar un dashboard funcional con feed social, ranking, perfil y analisis;
- administrar usuarios con un panel de control separado;
- recuperar contrasenas mediante token temporal;
- dejar preparada una futura capa de asistencia con IA.

La idea central es convertir datos escolares en una experiencia util, clara y escalable.

## Tecnologias utilizadas

- `PHP` como lenguaje principal del backend.
- `MariaDB / MySQL` como motor de base de datos.
- `HTML` y `CSS` para la interfaz.
- `JavaScript` para validaciones y mejoras de experiencia.
- `Python 3.12` para analisis externo de datos.
- `PyMySQL` dentro de un entorno virtual `venv`.
- `XAMPP / LAMP` como entorno local de desarrollo.

## Estructura general

- [PHP/](</opt/lampp/htdocs/proyecto_integrador/PHP>) contiene la logica principal.
- [VIEWS/](</opt/lampp/htdocs/proyecto_integrador/VIEWS>) contiene las vistas internas del dashboard.
- [CSS/](</opt/lampp/htdocs/proyecto_integrador/CSS>) contiene los estilos.
- [JS/](</opt/lampp/htdocs/proyecto_integrador/JS>) contiene validaciones e interacciones.
- [DATABASE/](</opt/lampp/htdocs/proyecto_integrador/DATABASE>) contiene el SQL y la documentacion de la BD.
- [PYTHON/](</opt/lampp/htdocs/proyecto_integrador/PYTHON>) contiene el analisis externo.
- [IMG/](</opt/lampp/htdocs/proyecto_integrador/IMG>) contiene el branding oficial.

## Flujo principal del sistema

1. El usuario entra a la LandingPage.
2. Revisa la propuesta del proyecto, la problematica y las estadisticas.
3. Se registra o inicia sesion.
4. Completa la encuesta academica.
5. El sistema guarda usuario, encuesta y analisis.
6. El usuario entra al dashboard y consulta su feed, ranking, perfil, diagnostico, plan y actividad.
7. Si entra un administrador, se abre un panel separado con control total.

## Landing Page

Archivo principal:

- [PHP/LandingPage.php](/opt/lampp/htdocs/proyecto_integrador/PHP/LandingPage.php)

Funciones principales:

- presenta el proyecto de forma visual y clara;
- usa scroll suave hacia secciones como inicio, problematica, objetivos y estadisticas;
- muestra datos reales de la base de datos;
- integra branding con logo y favicon oficiales;
- ofrece acceso a login, registro y formulario.

La Landing no es estatica: toma datos reales como:

- total de estudiantes;
- total de encuestas;
- promedio academico global;
- perfiles con riesgo alto.

## Registro y encuesta

Archivos:

- [PHP/form.php](/opt/lampp/htdocs/proyecto_integrador/PHP/form.php)
- [PHP/register.php](/opt/lampp/htdocs/proyecto_integrador/PHP/register.php)
- [JS/form.js](/opt/lampp/htdocs/proyecto_integrador/JS/form.js)
- [JS/register.js](/opt/lampp/htdocs/proyecto_integrador/JS/register.js)

Caracteristicas:

- formulario multi-step;
- validacion de progreso;
- registro de datos de encuesta;
- creacion del usuario en la misma transaccion;
- validacion de nombre completo con nombre(s) y dos apellidos;
- seleccion controlada de carreras;
- opcion `Otra` para carrera personalizada.

El formulario no solo recolecta datos: alimenta el motor de analisis academico.

## Autenticacion

Archivos:

- [PHP/login.php](/opt/lampp/htdocs/proyecto_integrador/PHP/login.php)
- [PHP/logout.php](/opt/lampp/htdocs/proyecto_integrador/PHP/logout.php)

Funciones principales:

- inicio de sesion con `password_verify`;
- redireccion por rol;
- bloqueo de cuentas;
- registro de sesion de acceso;
- mensajes de error y exito;
- enlace a recuperacion de contrasena.

## Recuperacion de contrasena

Archivos:

- [PHP/forgot_password.php](/opt/lampp/htdocs/proyecto_integrador/PHP/forgot_password.php)
- [PHP/reset_password.php](/opt/lampp/htdocs/proyecto_integrador/PHP/reset_password.php)
- [PHP/security.php](/opt/lampp/htdocs/proyecto_integrador/PHP/security.php)

Flujo:

1. El usuario escribe su correo.
2. Se genera un token temporal de un solo uso.
3. Se guarda en `password_resets`.
4. Se envia un enlace temporal.
5. El usuario define una nueva contrasena.
6. El token se marca como usado.

## Dashboard del usuario

Archivos:

- [PHP/dashboard.php](/opt/lampp/htdocs/proyecto_integrador/PHP/dashboard.php)
- [VIEWS/home.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/home.php)
- [VIEWS/profile.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/profile.php)
- [VIEWS/ranking.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/ranking.php)
- [VIEWS/analysis.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/analysis.php)
- [VIEWS/plan.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/plan.php)
- [VIEWS/community.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/community.php)
- [VIEWS/activity.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/activity.php)
- [VIEWS/assistant.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/assistant.php)

Secciones disponibles:

- Inicio: feed social con tarjetas clicables de otros usuarios.
- Ranking: lista de estudiantes ordenada por desempeno.
- Perfil: informacion personal y academica del usuario o de otro estudiante.
- Analisis: lectura del riesgo academico.
- Plan: recomendaciones de mejora.
- Comunidad: resumen de estadisticas del grupo.
- Actividad: historial de sesiones.
- Asistente IA: vista de proximos pasos para chat academico.

El dashboard esta pensado para no sentirse cerrado: la navegacion lateral da acceso a las secciones principales y el contenido central cambia segun la vista.

## Dashboard de administracion

Archivo principal:

- [PHP/admin_dashboard.php](/opt/lampp/htdocs/proyecto_integrador/PHP/admin_dashboard.php)

Funciones:

- edicion de usuarios;
- cambio de rol entre `usuario` y `admin`;
- bloqueo y desbloqueo de cuentas;
- eliminacion de usuarios y datos asociados;
- historial de modificaciones;
- consola SQL para administracion avanzada;
- confirmacion previa de acciones peligrosas.

El admin tiene un panel separado del usuario normal y acceso a control total del sistema.

## Base de datos

Archivo principal:

- [DATABASE/atenea.sql](/opt/lampp/htdocs/proyecto_integrador/DATABASE/atenea.sql)

Documentacion adicional:

- [DATABASE/README.md](/opt/lampp/htdocs/proyecto_integrador/DATABASE/README.md)
- [DATABASE/README.txt](/opt/lampp/htdocs/proyecto_integrador/DATABASE/README.txt)
- [DATABASE/admin_migration.sql](/opt/lampp/htdocs/proyecto_integrador/DATABASE/admin_migration.sql)

Tablas principales:

- `usuarios`
- `encuestas`
- `resultados`
- `recomendaciones`
- `sesiones`
- `password_resets`
- `admin_historial`

### Rol de cada tabla

- `usuarios`: guarda identidad, correo, edad, carrera, rol y estado.
- `encuestas`: guarda respuestas academicas y de habitos.
- `resultados`: almacena la puntuacion y nivel de riesgo.
- `recomendaciones`: guarda sugerencias personalizadas.
- `sesiones`: registra accesos al sistema.
- `password_resets`: tokens temporales de recuperacion.
- `admin_historial`: auditoria de acciones administrativas.

## Motor de analisis academico

La logica de riesgo esta duplicada de forma consistente en PHP y Python para que el sistema pueda funcionar en la web y tambien fuera de ella.

### En PHP

- [PHP/analytics.php](/opt/lampp/htdocs/proyecto_integrador/PHP/analytics.php)

### En Python

- [PYTHON/risk_analysis.py](/opt/lampp/htdocs/proyecto_integrador/PYTHON/risk_analysis.py)
- [PYTHON/README.md](/opt/lampp/htdocs/proyecto_integrador/PYTHON/README.md)

Variables que influyen en el calculo:

- promedio;
- materias reprobadas;
- asistencia;
- horas de estudio;
- horas de sueno;
- uso de redes;
- nivel de estres;
- desmotivacion;
- administracion del tiempo;
- entrega de tareas;
- acceso a internet;
- espacio de estudio;
- si trabaja o no.

El resultado final clasifica a cada estudiante en:

- `Bajo`
- `Medio`
- `Alto`

## Python y analisis externo

El script de Python:

- lee la base de datos;
- calcula riesgo por estudiante;
- genera un resumen JSON;
- identifica perfiles con mayor riesgo;
- lista mejores promedios.

Esto deja lista la base para futuras capas de visualizacion, reportes automaticos o modelos mas avanzados.

## Futuro asistente con IA

La vista de IA ya fue preparada en:

- [VIEWS/assistant.php](/opt/lampp/htdocs/proyecto_integrador/VIEWS/assistant.php)

La evolucion prevista es:

1. Encuesta -> Riesgo academico
2. Encuesta -> IA
3. Chat academico
4. Memoria academica

Modelo previsto:

- `ollama pull qwen3:8b`

Casos de uso futuros:

- consejos para subir promedio;
- explicaciones academicas;
- ayuda con Python;
- organizacion del tiempo;
- respuestas con contexto real de la BD.

## Branding

Archivos usados:

- [IMG/logo.png](/opt/lampp/htdocs/proyecto_integrador/IMG/logo.png)
- [IMG/favicon.png](/opt/lampp/htdocs/proyecto_integrador/IMG/favicon.png)

El logo se usa donde hay espacio suficiente para lucirlo con claridad. El favicon se usa en zonas compactas como sidebar o pestañas del navegador. Esto evita problemas de tamaño, sobreposicion y saturacion visual.

## Estilo visual

La interfaz usa una paleta consistente:

- azul noche como base;
- cyan como acento principal;
- paneles oscuros translucidos;
- tarjetas con bordes suaves;
- transiciones discretas;
- scroll suave;
- diseño moderno y minimalista.

La intencion es que el sistema se vea serio, limpio y comodo de usar.

## Seguridad y mantenimiento

Puntos ya contemplados:

- contrasenas hasheadas;
- consultas preparadas en las rutas criticas;
- control por rol;
- bloqueo de cuentas;
- tokens temporales para recuperar acceso;
- auditoria administrativa;
- confirmacion de acciones peligrosas.

Puntos a revisar en mantenimiento futuro:

- limpiar archivos heredados o de prueba;
- reforzar la consola SQL si se piensa usar en produccion;
- normalizar algunos datos antiguos de usuarios ya cargados en la BD.

## Estado actual del proyecto

Atenea ya tiene:

- landing funcional;
- registro con encuesta;
- login;
- dashboard de usuario;
- dashboard de admin;
- recuperación de contraseña;
- analisis academico;
- historial de sesiones;
- estructura lista para IA futura.

En resumen, el proyecto ya tiene una base solida y una ruta clara de crecimiento.

