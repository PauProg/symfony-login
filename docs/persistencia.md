# Persistencia de usuarios

## Decisión

SQLite + Doctrine ORM. Motivos: no requiere otro contenedor, el archivo
vive en `var/` y es suficiente para el alcance de la práctica.

## Por qué Doctrine ORM

- Entidad `App\Entity\User` → tabla `app_user`.
- Repositorio `App\Repository\UserRepository` → consultas.
- EntityManager → guarda y recupera entidades.
- Migraciones → esquema versionado y reproducible.

## Esquema de `app_user`

| Columna | Tipo | Restricciones |
|---|---|---|
| id | INTEGER | PK, autoincremental |
| email | VARCHAR(180) | NOT NULL, UNIQUE |
| username | VARCHAR(50) | NOT NULL, UNIQUE |
| roles | JSON | NOT NULL |
| password | VARCHAR(255) | NOT NULL (hash) |

## Dónde vive la base de datos

`compose.yaml` monta `.:/var/www/html`. Por tanto:

- Host: `var/data_dev.db`
- Contenedor: `/var/www/html/var/data_dev.db`

`var/` está en `.gitignore`. El archivo sobrevive a `stop`, `up`, `down`
y `build`. Se borra si se elimina `var/` o se ejecuta `git clean -xfd`.

## Comandos

- Crear BD: `php bin/console doctrine:database:create`
- Generar migración: `php bin/console make:migration`
- Aplicar: `php bin/console doctrine:migrations:migrate`
- Estado: `php bin/console doctrine:migrations:status`
- Validar: `php bin/console doctrine:schema:validate`

## Por qué migraciones y no `schema:update`

`schema:update` aplica el SQL directamente y no deja historial. Las
migraciones son archivos versionados con `up()` y `down()`, revisables y
reproducibles en cualquier entorno.

## Seguridad

La columna `password` guarda un hash, nunca texto plano. El hash se
generará en el punto 4 con el servicio de hashing de Symfony.