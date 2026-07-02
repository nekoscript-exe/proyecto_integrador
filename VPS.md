# Atenea VPS

Guia para activar recuperacion de contrasena por correo real en el VPS.

## 1. Entrar al servidor

```bash
ssh azael@2.25.68.198
cd /var/www/ateneanalyticsai.com
```

## 2. Crear `config.local.php`

Este archivo no se sube a GitHub. Debe crearse manualmente en el VPS.

```bash
nano config.local.php
```

Contenido de ejemplo:

```php
<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'atenea');
define('DB_USER', 'atenea_user');
define('DB_PASS', 'TU_CONTRASENA_REAL_DB');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ateneanalyticsai@gmail.com');
define('SMTP_PASS', 'TU_CONTRASENA_DE_APLICACION_SIN_ESPACIOS'); // Cambia aqui la contrasena de aplicacion de Gmail.

define('APP_URL', 'https://ateneanalyticsai.com');
```

## 3. Instalar Composer si falta

```bash
composer --version
```

Si no existe:

```bash
sudo apt update
sudo apt install composer -y
```

## 4. Instalar dependencias PHP

No subimos `vendor/` a GitHub. En el VPS se instala con Composer:

```bash
composer install --no-dev --optimize-autoloader
```

## 5. Preparar base de datos nueva

Si el VPS esta limpio, importa el schema sin datos reales:

```bash
mysql -u atenea_user -p atenea < DATABASE/schema.sql
```

Si ya tienes la base funcionando, no ejecutes este comando sin respaldo.

## 6. Procesar datasets oficiales

ATENEA usa los XLSX oficiales de `DATASETS/`. Si necesitas regenerar el JSON, el CSV limpio y las graficas:

```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python3 PYTHON/process_official_datasets.py
```

Verifica que existan:

```bash
test -f DATASETS/processed/landing_metrics.json
test -f DATASETS/processed/official_education_clean.csv
```

## 7. Actualizar codigo

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

Si `config.local.php` no existe despues del pull, crealo de nuevo manualmente.

## 8. Revisar permisos basicos

```bash
sudo chown -R www-data:www-data /var/www/ateneanalyticsai.com
sudo find /var/www/ateneanalyticsai.com -type d -exec chmod 755 {} \;
sudo find /var/www/ateneanalyticsai.com -type f -exec chmod 644 {} \;
```

## 9. Probar sitio

```bash
curl -I https://ateneanalyticsai.com
```

Debe responder algo parecido a:

```text
HTTP/1.1 200 OK
```

## 10. Probar recuperacion de contrasena

1. Abre `https://ateneanalyticsai.com/PHP/forgot_password.php`.
2. Escribe un correo registrado.
3. Revisa que llegue un correo desde Gmail SMTP.
4. Abre el enlace.
5. Cambia la contrasena.
6. Intenta usar el mismo enlace otra vez; debe fallar.

## 11. Revisar logs si falla

```bash
sudo tail -50 /var/log/apache2/ateneanalyticsai_error.log
sudo tail -50 /var/log/apache2/error.log
```

## Recordatorio importante

Nunca subas `config.local.php` a GitHub. Ahi viven las contrasenas reales de base de datos y SMTP.
