# Reporte de Cambios

Este documento resume los cambios realizados para implementar la recuperacion de contrasena con correo real usando Gmail SMTP y PHPMailer.

## Resumen

Se implemento un flujo real de recuperacion de contrasena:

1. El usuario entra a `forgot_password.php`.
2. Escribe su correo.
3. Atenea genera un token seguro.
4. El token se guarda hasheado en `password_resets`.
5. Atenea envia un correo real usando PHPMailer y Gmail SMTP.
6. El usuario abre el enlace recibido.
7. `reset_password.php` valida el token.
8. El usuario crea una nueva contrasena.
9. El token queda marcado como usado.

## Configuracion privada

Se creo `config.local.php`.

Este archivo contiene:

- datos de conexion a la base de datos;
- datos SMTP de Gmail;
- `APP_URL`;
- la contrasena de prueba solicitada;
- un comentario junto a `SMTP_PASS` indicando donde cambiar la contrasena de aplicacion.

Este archivo no debe subirse a GitHub.

Tambien se creo `config.local.example.php`, que sirve como plantilla segura sin contrasenas reales.

## Seguridad en Git

Se actualizo `.gitignore` para ignorar:

```gitignore
vendor/
config.local.php
```

Esto evita subir:

- credenciales de base de datos;
- contrasena SMTP;
- dependencias instaladas por Composer.

## Conexion a base de datos

Se modifico `PHP/conexion.php`.

Antes tenia credenciales escritas directamente en el codigo.

Ahora carga:

```php
require_once __DIR__ . "/../config.local.php";
```

Y usa:

```php
DB_HOST
DB_USER
DB_PASS
DB_NAME
```

Si falta `config.local.php`, muestra un mensaje indicando que se debe copiar `config.local.example.php`.

## PHPMailer

Se confirmo que PHPMailer ya estaba instalado con Composer.

Se dejaron versionados:

- `composer.json`
- `composer.lock`

Se ignora:

- `vendor/`

En el VPS debera instalarse con:

```bash
composer install --no-dev --optimize-autoloader
```

## Envio de correos

Se creo `PHP/mailer.php`.

Este archivo define:

```php
ateneaSendMail(...)
```

La funcion:

- carga `vendor/autoload.php`;
- usa PHPMailer;
- conecta con Gmail SMTP;
- usa TLS en puerto 587;
- toma credenciales desde `config.local.php`;
- envia correo HTML y texto plano;
- registra errores en `error_log`.

## Recuperacion de contrasena

Se modifico `PHP/forgot_password.php`.

Antes:

- generaba token;
- mostraba enlace debug en localhost;
- intentaba enviar con `mail()`.

Ahora:

- busca al usuario por correo;
- genera token seguro;
- guarda el hash del token;
- crea enlace usando `APP_URL`;
- envia correo real con PHPMailer;
- ya no muestra enlaces debug;
- muestra un mensaje generico:

```text
Si el correo existe en la plataforma, enviaremos un enlace de recuperacion.
```

Esto evita revelar si un correo esta registrado o no.

## Restablecimiento de contrasena

Se modifico `PHP/reset_password.php`.

El archivo mantiene:

- lectura del token;
- validacion de token existente;
- validacion de token no usado;
- validacion de token no expirado;
- formulario para nueva contrasena;
- `password_hash`;
- actualizacion de la contrasena del usuario.

Se elimino el `UPDATE password_resets` directo desde este archivo.

Ahora el token se consume mediante `security.php`.

## Seguridad del token

Se modifico `PHP/security.php`.

La funcion:

```php
ateneaConsumePasswordReset(...)
```

Ahora:

- valida el token;
- marca el token como usado con `used_at = NOW()`;
- devuelve los datos del token si todo fue correcto.

La tabla `password_resets` conserva:

- token hasheado;
- expiracion;
- uso unico;
- IP solicitante;
- relacion con usuario.

## Documentacion del VPS

Se creo `VPS.md`.

Incluye:

- como entrar al VPS;
- como crear `config.local.php`;
- ejemplo seguro para produccion;
- como instalar Composer;
- como ejecutar `composer install`;
- como actualizar desde Git;
- como revisar permisos;
- como probar el sitio;
- como revisar logs de Apache;
- recordatorio de no subir `config.local.php`.

## Validaciones realizadas

Se ejecuto:

```bash
/opt/lampp/bin/php -l PHP/conexion.php
/opt/lampp/bin/php -l PHP/mailer.php
/opt/lampp/bin/php -l PHP/forgot_password.php
/opt/lampp/bin/php -l PHP/reset_password.php
/opt/lampp/bin/php -l PHP/security.php
```

Resultado:

```text
No syntax errors detected
```

Tambien se ejecuto:

```bash
composer validate
```

Resultado:

```text
./composer.json is valid
```

Se verifico que Git ignore correctamente:

```bash
git check-ignore -v config.local.php vendor/autoload.php
```

Resultado:

- `config.local.php` esta ignorado.
- `vendor/` esta ignorado.

## Archivos modificados

- `.gitignore`
- `PHP/conexion.php`
- `PHP/forgot_password.php`
- `PHP/reset_password.php`
- `PHP/security.php`
- `composer.json`
- `composer.lock`

## Archivos creados

- `config.local.php`
- `config.local.example.php`
- `PHP/mailer.php`
- `VPS.md`
- `Document/cambios.md`

## Prueba manual pendiente

Probar desde:

```text
http://localhost/proyecto_integrador/PHP/forgot_password.php
```

Flujo esperado:

1. Escribir correo registrado.
2. Recibir correo real.
3. Abrir enlace.
4. Cambiar contrasena.
5. Iniciar sesion con la nueva contrasena.
6. Intentar reutilizar el mismo enlace.
7. Confirmar que el enlace usado ya no funciona.

## Nota importante

Nunca subir `config.local.php` a GitHub.

Ahi viven las credenciales reales de base de datos y SMTP.
