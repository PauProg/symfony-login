# Persistencia de usuarios

Documento de la fase 3. Recoge la decisión de almacenamiento, el esquema de la
tabla de usuarios, el papel de Doctrine y cómo sobrevive la base de datos dentro de
Docker.

## 1. Decisión

Se usa **SQLite** con **Doctrine ORM**.

Motivos:

- No requiere levantar otro contenedor ni configurar un servidor de base de datos.
- La base de datos es un archivo dentro de `var/`, fácil de inspeccionar y borrar.
- Es suficiente para el alcance de la práctica.
- Doctrine aporta entidades, repositorios y migraciones versionadas.

En un entorno real con varios usuarios concurrentes o despliegues distribuidos se
usaría PostgreSQL o MySQL. Para esta práctica, SQLite evita complejidad innecesaria.

## 2. Por qué Doctrine ORM

| Pieza | Qué hace en este proyecto |
|---|---|
| Entidad | `App\Entity\User` representa la tabla `app_user`. |
| Repositorio | `App\Repository\UserRepository` contiene las consultas. |
| EntityManager | Registra, actualiza y borra entidades (`persist`, `flush`). |
| Migraciones | Versionan el esquema en archivos PHP con `up()` y `down()`. |

La configuración está en `config/packages/doctrine.yaml`:

- `DATABASE_URL` apunta a SQLite.
- El mapeo `App` usa atributos y apunta a `src/Entity`.
- La estrategia de nombres convierte CamelCase en `snake_case`; la tabla se fuerza
  a `app_user` con `#[ORM\Table(name: 'app_user')]`.

## 3. Esquema de `app_user`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | INTEGER | PK, autoincremental |
| `email` | VARCHAR(180) | NOT NULL, UNIQUE |
| `username` | VARCHAR(50) | NOT NULL, UNIQUE |
| `roles` | JSON | NOT NULL |
| `password` | VARCHAR(255) | NOT NULL (hash) |

- `email` y `username` tienen índices únicos.
- `roles` se guarda como JSON; `User::getRoles()` añade siempre `ROLE_USER`.
- `password` almacena el hash, nunca la contraseña en texto plano.
- `username` y `email` se normalizan a minúsculas en los setters.

## 4. Migración

La migración generada es:

```
src/Migrations/Version20261103161301.php
```

Crea:

- La tabla `app_user` con sus dos índices únicos.
- La tabla `messenger_messages`, porque `MESSENGER_TRANSPORT_DSN` usa el transporte
  de Doctrine (`doctrine://default`). No es parte del login, pero aparece en la
  misma migración.

`up()` aplica el esquema y `down()` lo revierte. Una migración aplicada no se edita:
si cambia la entidad, se genera una nueva.

## 5. Dónde vive la base de datos en Docker

`compose.yaml` monta el proyecto entero:

```yaml
volumes:
  - .:/var/www/html
```

Por tanto:

| Contexto | Ruta |
|---|---|
| Host | `var/data_dev.db` |
| Contenedor | `/var/www/html/var/data_dev.db` |

`var/` está en `.gitignore`, así que el archivo **no se sube a Git**.

El archivo sobrevive a:

- `docker compose stop` / `docker compose up -d`
- `docker compose down`
- `docker compose down -v` (es un bind mount, no un volumen con nombre)
- `docker compose build`

Se pierde si se elimina `var/` a mano, si se ejecuta `git clean -xfd` o si se borra
el archivo `data_dev.db`.

El nombre incluye `%kernel.environment%`, así que en `dev` se usa `data_dev.db` y en
`test` se usa `data_test.db`.

## 6. Comandos

### Crear la base de datos

En SQLite **no se usa** `doctrine:database:create`: SQLite no soporta
`CREATE DATABASE` y DBAL 4.5 lanza un error. El archivo se crea automáticamente al
abrir la primera conexión.

### Generar y aplicar la migración

```bash
docker compose exec web php bin/console make:migration
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

### Consultar el estado

```bash
docker compose exec web php bin/console doctrine:migrations:status
docker compose exec web php bin/console doctrine:schema:validate
docker compose exec web php bin/console doctrine:mapping:info
```

### Consultar datos

```bash
docker compose exec web php bin/console doctrine:query:dql "SELECT u.username, u.email FROM App\Entity\User u"
```

> En este proyecto no existe `doctrine:query:sql` (DoctrineBundle 3). Para consultas
> directas usa `doctrine:query:dql` o una conexión PDO desde PHP.

### Recrear la base de datos desde cero

```bash
Remove-Item var/data_dev.db
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

El archivo se crea de nuevo al conectar y la migración reconstruye el esquema.

## 7. Por qué migraciones y no `schema:update`

`doctrine:schema:update --force` compara el mapeo con la base de datos y ejecuta el
SQL directamente. Es cómodo en local, pero:

- No deja historial de cambios.
- No es reproducible entre entornos.
- Puede generar SQL distinto según el estado de la base de datos.
- No se puede revisar en un pull request.

Las migraciones son archivos versionados con `up()` y `down()`, revisables y
reproducibles. Por eso el proyecto usa siempre migraciones.

## 8. Seguridad

La columna `password` guarda un hash generado con
`UserPasswordHasherInterface`, usando el algoritmo configurado como `auto` en
`security.yaml` (bcrypt o argon2, según disponibilidad).

El usuario de prueba se crea con:

```bash
docker compose exec web php bin/console app:create-user pau pau@example.test
```

La contraseña se pide de forma oculta y nunca se guarda en texto plano ni se sube
al repositorio. El recorrido completo del login y la creación del usuario están en
[`autenticacion.md`](autenticacion.md).

## 9. Enlaces

- Doctrine ORM: <https://www.doctrine-project.org/projects/orm.html>
- Doctrine Migrations: <https://www.doctrine-project.org/projects/migrations.html>
- SQLite: <https://www.sqlite.org/docs.html>
- Configuración de DoctrineBundle: <https://symfony.com/doc/current/doctrine.html>
