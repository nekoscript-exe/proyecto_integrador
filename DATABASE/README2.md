Para definir un perfil como adminsitrador, dentro de la base de datos se debe ejecutar:

UPDATE usuarios
SET rol = 'admin'
WHERE correo = 'tu_correo@gmail.com';
ATENEA - Base de datos

Tablas clave:
- usuarios: incluye rol y estado
- encuestas: respuestas academicas
- resultados: analisis de riesgo
- recomendaciones: sugerencias generadas
- sesiones: historial de acceso
- password_resets: tokens de recuperacion
- admin_historial: acciones de administracion

Migracion recomendada:
1. Ejecuta `DATABASE/admin_migration.sql`
2. Promueve un correo a admin con un `UPDATE usuarios SET rol='admin' WHERE correo='...'`

Paneles:
- Usuario: `PHP/dashboard.php`
- Admin: `PHP/admin_dashboard.php`
- Recuperacion: `PHP/forgot_password.php`
