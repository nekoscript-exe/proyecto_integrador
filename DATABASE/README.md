# ATENEA · Base de Datos

## Estado actual

La tabla `usuarios` usa:

- `rol` (`usuario` | `admin`) para permisos.
- `estado` (`activo` | `bloqueado`) para acceso.
- `admin_historial` guarda cada cambio hecho por un admin con fecha y detalle.
- `password_resets` guarda tokens temporales de un solo uso.

## Migracion en BD existente

Si tu base ya existia antes de estos cambios, ejecuta:

```sql
SOURCE /opt/lampp/htdocs/proyecto_integrador/DATABASE/admin_migration.sql;
```

Ese script crea la estructura de auditoria y recuperacion de contrasena si no existe.

## Crear o promover un administrador

```sql
UPDATE usuarios
SET rol = 'admin'
WHERE correo = 'tu_correo@dominio.com';
```

## Bloquear o desbloquear usuario manualmente

```sql
UPDATE usuarios
SET estado = 'bloqueado'
WHERE id = 10;

UPDATE usuarios
SET estado = 'activo'
WHERE id = 10;
```

## Paneles del sistema

- Usuario normal: `PHP/dashboard.php`
- Administrador: `PHP/admin_dashboard.php`

El `login.php` redirige automaticamente segun `rol`.
