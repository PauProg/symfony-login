# Práctica Symfony — Aplicación de onboarding con login

Aplicación web de práctica construida con **Symfony 8.1**, PHP 8.4 y Docker Compose.
Tiene dos páginas públicas, un formulario de login y una zona privada (`/dashboard`)
protegida por el firewall de Symfony. La autenticación se guarda en SQLite mediante
Doctrine ORM y migraciones versionadas.

- Repositorio: <https://github.com/PauProg/symfony-login>
- URL local: <http://localhost:8080>
- Documentación de las fases: [`docs/`](docs/)

## 1. Qué es y qué hace la aplicación

La aplicación demuestra el ciclo completo de una petición web con Symfony:

- **Páginas públicas:** `/` muestra una bienvenida con datos dinámicos y `/acerca-de`
  explica la parte técnica del proyecto.
- **Login:** `/login` presenta un formulario con usuario, contraseña y token CSRF.
  Las credenciales se comprueban contra la tabla `app_user`, donde la contraseña se
  guarda como hash.
- **Zona privada:** `/dashboard` solo es accesible con sesión iniciada. Si se pide
  sin sesión, Symfony redirige a `/login` con un `302`.
- **Logout:** `/logout` invalida la sesión y devuelve a la portada; a partir de ahí
  el dashboard vuelve a estar protegido.

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Investigación | ✅ [`docs/investigacion.md`](docs/investigacion.md) |
| 2 | Creación y arranque en Docker | ✅ [`docs/arranque.md`](docs/arranque.md) |
| 3 | Estructura, rutas y Twig | ✅ [`docs/flujo_peticion.md`](docs/flujo_peticion.md) |
| 4 | Login y autenticación | ✅ [`docs/autenticacion.md`](docs/autenticacion.md) y [`docs/persistencia.md`](docs/persistencia.md) |
| 5 | Pruebas y verificación | ✅ [`docs/pruebas.md`](docs/pruebas.md) |
| 6 | Documentación y entrega | ✅ Este README y [`docs/demo.md`](docs/demo.md) |

## 2. Tecnologías utilizadas y por qué

| Tecnología | Versión | Por qué |
|---|---|---|
| PHP | 8.4.1 | Versión que exige Symfony 8.1. |
| Symfony | 8.1.10 | Framework base: routing, controladores, Twig, seguridad, consola. |
| Doctrine ORM | 3.7.6 | Entidad `User`, repositorio y migraciones versionadas. |
| Doctrine Migrations | 4.0.1 | El esquema se crea y evoluciona con migraciones, no con SQL a mano. |
| SQLite | 3 (extensión `pdo_sqlite`) | Base de datos en un archivo local; no hace falta otro contenedor. |
| Twig | 3.30 | Plantillas con herencia (`base.html.twig` + plantillas hijas). |
| SecurityBundle | 8.1.10 | Firewall, `form_login`, hash de contraseñas, CSRF y control de acceso. |
| AssetMapper + Stimulus + Turbo | 8.1.10 / 3.6 / 3.6 | Assets y JavaScript sin Node ni bundler. |
| Docker Compose | — | Entorno reproducible: la misma imagen para cualquier máquina. |
| PHPUnit | 13.4.6 | Pruebas funcionales del flujo de login como complemento a Postman. |
| Postman | Colección v3 | Colección versionada con 11 peticiones y 16 aserciones. |

> **Nota sobre la versión:** el enunciado propone Symfony 7.4 LTS, pero el proyecto
> se generó con Symfony 8.1.10. Los conceptos de la práctica son los mismos; la
> documentación debe consultarse en <https://symfony.com/doc/current/> o en la
> versión 8.1. Symfony 8.1 no es LTS: su fin de mantenimiento está previsto para
> enero de 2027.

## 3. Cómo se inicia desde una copia limpia usando Docker Compose

### Requisitos

En el host solo hacen falta:

- **Docker Desktop** con Docker Compose.
- **Git** para clonar el repositorio.

No es necesario instalar PHP, Composer ni la Symfony CLI: todo se ejecuta dentro del
contenedor. Comprobación:

```bash
docker --version
docker compose version
git --version
```

### Paso 1 — Clonar el repositorio

```bash
git clone https://github.com/PauProg/symfony-login.git
cd symfony-login
```

### Paso 2 — Crear el secreto local

`APP_SECRET` no debe subirse a Git. El `.env` versionado solo contiene un relleno sin
valor secreto; el valor real se guarda en `.env.local`, que está ignorado.

Generar un valor aleatorio:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Si no hay PHP en el host, se puede generar con Docker:

```bash
docker run --rm php:8.4-cli php -r "echo bin2hex(random_bytes(32));"
```

Crear el archivo `.env.local` en la raíz del proyecto con ese valor:

```dotenv
APP_SECRET=<valor-generado>
```

### Paso 3 — Construir y arrancar

```bash
docker compose build
docker compose up -d
```

El `compose.yaml` define un único servicio `web`:

- Contenedor `symfony_app`.
- Puerto `8080` del host → `8080` del contenedor.
- El directorio del proyecto se monta en `/var/www/html`.
- El servidor es el PHP built-in server: `php -S 0.0.0.0:8080 -t public/`.

### Paso 4 — Instalar dependencias

`vendor/` no se versiona, así que hay que instalarlo dentro del contenedor:

```bash
docker compose exec web composer install
```

### Paso 5 — Abrir la aplicación

<http://localhost:8080>

Comprobaciones rápidas:

```bash
docker compose ps
docker compose logs -f web
curl -I http://localhost:8080
```

`curl -I` debe devolver `HTTP/1.1 200 OK`.

### Parada y limpieza

```bash
# Parar sin borrar contenedores ni datos
docker compose stop

# Volver a arrancar sin reconstruir
docker compose up -d

# Parar y eliminar contenedores y red (los datos de var/ se conservan)
docker compose down
```

La base de datos SQLite vive en `var/`, que está ignorado por Git. Para empezar de
cero, basta con borrar `var/data_dev.db` y volver a ejecutar las migraciones.

## 4. Cómo se preparan las dependencias y la base de datos

### Dependencias PHP

- `composer.json`: dependencias declaradas.
- `composer.lock`: versiones exactas; **se versiona** para que la instalación sea
  reproducible.
- `vendor/`: código instalado; **no se versiona**.

Comandos útiles:

```bash
docker compose exec web composer install     # instala lo fijado en composer.lock
docker compose exec web composer show        # lista paquetes instalados
```

### Base de datos

La aplicación usa SQLite. La configuración está en `.env`:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

El archivo se crea al conectar: `var/data_dev.db` en desarrollo y
`var/data_test.db` en pruebas. No hay que levantar ningún contenedor de base de datos.

El esquema se crea con la migración versionada
`src/Migrations/Version20261103161301.php`, que crea las tablas `app_user` y
`messenger_messages`:

```bash
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

Para comprobar el estado de las migraciones:

```bash
docker compose exec web php bin/console doctrine:migrations:status
```

> **Importante:** `doctrine:database:create` no se usa. Con SQLite y DBAL 4.5 ese
> comando falla porque el archivo todavía no existe; el archivo se crea
> automáticamente al ejecutar la migración. Tampoco se usa
> `doctrine:schema:update`: todos los cambios de esquema van por migraciones.

### Usuario de prueba

El comando `app:create-user` crea o actualiza un usuario y guarda la contraseña
hasheada. **Nunca se publica una contraseña real en el repositorio.**

```bash
docker compose exec web php bin/console app:create-user postman postman@example.test
```

El comando pide la contraseña de forma oculta y no la muestra al escribirla. También
admite la contraseña como tercer argumento, pero entonces queda en el historial de la
terminal; para la práctica es preferible dejarla vacía y teclearla cuando la pida.

Verificar que el usuario existe y que la contraseña está hasheada:

```bash
docker compose exec web php bin/console doctrine:query:dql \
  "SELECT u.id, u.username, u.email FROM App\Entity\User u"
```

La columna `password` empieza por `$2y$` (bcrypt) o `$argon2` según el hasher
configurado; nunca contiene el texto plano.

### Verificar con las pruebas

La colección de Postman y las pruebas de PHPUnit están descritas en
[`docs/pruebas.md`](docs/pruebas.md). Para ejecutar solo PHPUnit:

```bash
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction --env=test
docker compose exec web php bin/phpunit
```

Resultado esperado:

```
OK (6 tests, 24 assertions)
```

Para ejecutar la colección de Postman desde la consola:

```bash
postman collection run "postman/collections/Symfony Login - Fase 5" \
  -e "postman/environments/Local.environment.yaml"
```

Antes hay que rellenar `password` en el entorno `Local` desde la aplicación de
Postman. En el repositorio esa variable está vacía a propósito. Resultado esperado:
11 peticiones, 16 aserciones y 0 fallos.

## 5. Cómo se obtiene un usuario de prueba sin exponer secretos reales

El procedimiento es siempre el mismo y no depende de datos versionados:

1. Elegir un usuario y un correo de ejemplo:
   `postman` / `postman@example.test`.
2. Ejecutar `app:create-user` y teclear una contraseña local cuando la pida.
3. Usar esa misma contraseña en el formulario de login o en el entorno de Postman.

Reglas que se siguen en el repositorio:

- `password` de los entornos de Postman se versiona **vacía**.
- Los entornos con credenciales reales (`*.private.environment.yaml`) están en
  `.gitignore`.
- No hay contraseñas, tokens ni claves en `.env`, en la documentación ni en las
  evidencias.
- `APP_SECRET` real vive solo en `.env.local` (ignorado por Git).
- El `.env.test` tiene un secreto de pruebas, no reutilizable en producción.

## 6. Rutas disponibles y acceso

| Ruta | Nombre | Método | Acceso | Respuesta |
|---|---|---|---|---|
| `/` | `app_home` | GET | Pública | `200` con la portada |
| `/acerca-de` | `app_about` | GET | Pública | `200` con la información del proyecto |
| `/login` | `app_login` | GET | Pública | `200` con el formulario |
| `/login` | `app_login` | POST | Pública | `302` a `/dashboard` si las credenciales son correctas; `302` a `/login` si fallan |
| `/dashboard` | `app_dashboard` | GET | **Privada** (`ROLE_USER`) | `200` con sesión; `302` a `/login` sin sesión |
| `/logout` | `_logout_main` | GET | Privada (sesión) | `302` a `/` e invalida la sesión |

Listar las rutas reales:

```bash
docker compose exec web php bin/console debug:router
```

La protección no está en el HTML ni en el controlador: está en
`access_control` de `config/packages/security.yaml`. Aunque alguien escriba la URL
`/dashboard` a mano, el firewall intercepta la petición antes de ejecutar el
controlador.

## 7. Dónde están los controladores, las plantillas y la seguridad

| Elemento | Ubicación |
|---|---|
| Controlador de la portada y de "Acerca de" | `src/Controller/MainController.php` |
| Controlador del login | `src/Controller/SecurityController.php` |
| Controlador del dashboard | `src/Controller/DashboardController.php` |
| Entidad de usuario | `src/Entity/User.php` |
| Repositorio de usuarios | `src/Repository/UserRepository.php` |
| Comando para crear el usuario de prueba | `src/Command/CreateUserCommand.php` |
| Migración del esquema | `src/Migrations/Version20261103161301.php` |
| Plantilla base y navegación | `templates/base.html.twig` |
| Portada | `templates/main/index.html.twig` |
| Acerca de | `templates/main/about.html.twig` |
| Formulario de login | `templates/security/login.html.twig` |
| Dashboard | `templates/dashboard/index.html.twig` |
| Configuración de seguridad | `config/packages/security.yaml` |
| Configuración de CSRF | `config/packages/csrf.yaml` |
| Configuración de Doctrine | `config/packages/doctrine.yaml` |
| Configuración de servicios | `config/services.yaml` |
| Front controller | `public/index.php` |
| Pruebas funcionales | `tests/Functional/LoginFlowTest.php` |
| Colección de Postman | `postman/collections/Symfony Login - Fase 5/` |

### Configuración de seguridad, en resumen

`config/packages/security.yaml` define:

- **Hasher:** `auto` para `PasswordAuthenticatedUserInterface`; la contraseña se
  verifica con `password_verify`, nunca comparando texto.
- **Provider:** `app_user_provider`, que carga usuarios desde la entidad
  `App\Entity\User` mediante `UserRepository`.
- **Firewall `main`:**
  - `form_login` con `login_path` y `check_path` en `app_login`.
  - `enable_csrf: true`.
  - `default_target_path: app_dashboard` y `always_use_default_target_path: true`.
  - `failure_path: app_login`.
  - Logout en `/logout` con redirección a `app_home`.
- **`access_control`:** `^/dashboard` exige `ROLE_USER`.

El CSRF del login es **stateless** (`config/packages/csrf.yaml`). El navegador envía
la cabecera `Origin`/`Referer` automáticamente; una herramienta externa como Postman
debe enviar `Origin: {{baseUrl}}` en el `POST /login` o Symfony rechaza la petición.

## 8. Problemas encontrados y cómo se solucionaron

1. **Composer no disponible en el contenedor.** El `compose.yaml` inicial montaba
   `/urs/bin/composer` desde el host: ruta mal escrita e inexistente en Windows. Se
   sustituyó por `COPY --from=composer:2` en el `Dockerfile`.
2. **Composer fallaba al descargar paquetes.** La imagen `php:8.4-cli` no incluye
   `git` ni `unzip`. Se instalaron en el `Dockerfile` con `apt-get`.
3. **Servicios de base de datos innecesarios.** El compose inicial traía PostgreSQL y
   Mailpit, pero la aplicación usa SQLite y `MAILER_DSN=null://null`. Se eliminaron
   para no levantar contenedores que no se usan.
4. **`APP_SECRET` versionado.** Estaba en `.env.dev`, que se sube a Git. Se movió a
   `.env.local` (ignorado), se dejó un relleno sin valor secreto en `.env` y se rotó
   el valor. El secreto antiguo permanece en el historial: si el repositorio es
   público, conviene purgarlo con `git filter-repo` y no reutilizarlo nunca.
5. **`doctrine:database:create` falla con SQLite.** Con DBAL 4.5 el archivo todavía
   no existe y el comando no lo crea. Solución: no usarlo; ejecutar directamente
   `doctrine:migrations:migrate`, que crea el archivo al conectar.
6. **El `POST /login` fallaba en Postman aunque las credenciales fueran correctas.**
   El CSRF stateless exige la cabecera `Origin`. Se añadió `Origin: {{baseUrl}}` a la
   petición P04a.
7. **El token CSRF se guardaba vacío en la colección.** El script de P03 asignaba la
   expresión regular a `html.match` en lugar del resultado. Se corrigió a
   `const match = html.match(/.../)` y se añadió una aserción que exige el token.
8. **Una variable de entorno anulaba el token CSRF.** El entorno declaraba
   `csrf_token` vacío y tenía prioridad sobre la variable de colección. Se eliminó de
   los entornos; `csrf_token` vive solo en la colección.
9. **P08 no tenía URL.** La petición de dashboard tras el logout estaba vacía y el
   runner la marcaba como `errored`. Se añadió `{{baseUrl}}/dashboard`.
10. **Sesiones mezcladas entre dos stacks.** Al probar una copia limpia en paralelo
    se usa `127.0.0.1:8081` en lugar de `localhost:8081`, para que las cookies queden
    aisladas por nombre de host. Así se comprueba que la sesión de un stack no vale
    en el otro.

Las incidencias de las pruebas (con síntoma, causa y corrección) están detalladas en
[`docs/pruebas.md`](docs/pruebas.md#4-incidencias-y-correcciones).

## 9. Comandos utilizados y explicación de cada uno

| Comando | Para qué sirve |
|---|---|
| `git clone <url>` | Descargar el proyecto. |
| `docker compose build` | Construir la imagen con PHP, Composer, git y unzip. |
| `docker compose up -d` | Arrancar el contenedor en segundo plano. |
| `docker compose ps` | Ver el estado y los puertos del contenedor. |
| `docker compose logs -f web` | Ver la salida del servidor PHP en tiempo real. |
| `docker compose exec web sh` | Abrir una shell dentro del contenedor. |
| `docker compose exec web composer install` | Instalar las dependencias fijadas en `composer.lock`. |
| `docker compose exec web php bin/console about` | Ver versión de Symfony, entorno y rutas internas. |
| `docker compose exec web php bin/console debug:router` | Listar rutas, métodos y controladores. |
| `docker compose exec web php bin/console debug:config security` | Ver la configuración de seguridad resuelta. |
| `docker compose exec web php bin/console lint:container` | Comprobar que el contenedor de servicios compila. |
| `docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction` | Crear o actualizar el esquema de la base de datos. |
| `docker compose exec web php bin/console doctrine:migrations:status` | Ver migraciones aplicadas y pendientes. |
| `docker compose exec web php bin/console app:create-user <usuario> <email>` | Crear o actualizar el usuario de prueba con contraseña hasheada. |
| `docker compose exec web php bin/console doctrine:query:dql "SELECT ..."` | Consultar datos desde consola. |
| `docker compose exec web php bin/console cache:clear` | Limpiar la caché de Symfony. |
| `docker compose exec web tail -f var/log/dev.log` | Seguir el log de la aplicación. |
| `docker compose exec web php bin/phpunit` | Ejecutar las pruebas funcionales. |
| `postman collection run ... -e ...` | Ejecutar la colección de Postman desde consola. |
| `docker compose stop` | Parar el contenedor sin borrarlo. |
| `docker compose down` | Parar y eliminar contenedores y red. |

### Comandos de Git

| Comando | Para qué sirve |
|---|---|
| `git status` | Ver archivos modificados, añadidos y sin seguimiento. |
| `git log --oneline` | Revisar el historial de commits. |
| `git diff` | Ver los cambios antes de confirmarlos. |
| `git add <archivo>` | Preparar un archivo para el commit. |
| `git commit -m "mensaje"` | Guardar un cambio en el historial. |

## Documentación de la práctica

- [Investigación](docs/investigacion.md)
- [Arranque en Docker](docs/arranque.md)
- [Flujo de una petición](docs/flujo_peticion.md)
- [Persistencia](docs/persistencia.md)
- [Autenticación](docs/autenticacion.md)
- [Pruebas y verificación](docs/pruebas.md)
- [Guía de demostración](docs/demo.md)

## Enlaces

- Symfony: <https://symfony.com/doc/current/>
- Seguridad de Symfony: <https://symfony.com/doc/current/security.html>
- Twig: <https://twig.symfony.com/doc/3.x/>
- Doctrine ORM: <https://www.doctrine-project.org/projects/orm.html>
- Docker Compose: <https://docs.docker.com/compose/>
- Postman: <https://learning.postman.com/docs/>
