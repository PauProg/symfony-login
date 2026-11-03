# Práctica Symfony — Aplicación de onboarding

Aplicación web creada con **Symfony 8.1**, PHP 8.4 y Docker Compose como parte de
la práctica de onboarding. Incluye dos páginas públicas y servirá como base para
incorporar, en la siguiente fase, un login real que proteja `/dashboard`.

- Repositorio: <https://github.com/PauProg/symfony-login>
- URL local: <http://localhost:8080>

## Estado de la práctica

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Investigación | ✅ Documentada en `docs/investigacion.md` |
| 2 | Creación y arranque en Docker | ✅ Documentada en `docs/arranque.md` |
| 3 | Estructura, rutas y Twig | ✅ Documentada en `docs/flujo_peticion.md` |
| 4 | Login y autenticación | 🚧 En curso (Doctrine y SQLite configurados; entidad y login pendientes) |
| 5 | Pruebas y verificación | ⏳ Pendiente |
| 6 | Documentación y entrega | 🚧 Documentación parcial |

## Tecnologías utilizadas y por qué

| Tecnología | Versión | Por qué |
|---|---|---|
| PHP | 8.4 | Requisito de Symfony 8.1. |
| Symfony | 8.1.10 | Framework base: routing, controladores, Twig, seguridad. |
| Doctrine ORM | 3.7 | Persistencia de datos y migraciones. |
| SQLite | — | Base de datos en un archivo local, sin servicio adicional. |
| Twig | 3.x | Plantillas HTML con herencia. |
| Docker Compose | — | Entorno reproducible y aislado. |
| PHPUnit | 13.4 | Pruebas automatizadas (fase 5). |
| AssetMapper + Stimulus + Turbo | — | Gestión de assets y JS sin Node. |

> **Nota sobre la versión:** el enunciado pide Symfony 7.4 LTS, pero el proyecto se
> generó con Symfony 8.1.10. Los conceptos de la práctica son los mismos; la
> documentación debe consultarse en <https://symfony.com/doc/current/> o en la
> versión 8.1.

## Requisitos

- Docker Desktop con Docker Compose.
- Git.

No hace falta instalar PHP, Composer ni Symfony CLI en el host: todo se ejecuta
dentro del contenedor.

## Cómo se inicia desde una copia limpia

```bash
git clone https://github.com/PauProg/symfony-login.git
cd symfony-login
```

### 1. Crear el secreto local

`APP_SECRET` no puede estar vacío y no debe subirse a Git. El `.env` versionado solo
tiene un valor de relleno; el real se guarda en `.env.local` (ignorado por Git).

Generar un valor:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Si no hay PHP en el host:

```bash
docker run --rm php:8.4-cli php -r "echo bin2hex(random_bytes(32));"
```

Crear `.env.local`:

```dotenv
APP_SECRET=<valor-generado>
```

### 2. Construir y arrancar

```bash
docker compose build
docker compose up -d
```

### 3. Instalar dependencias dentro del contenedor

```bash
docker compose exec web composer install
```

### 4. Abrir la aplicación

<http://localhost:8080>

Comprobación rápida:

```bash
docker compose ps
docker compose logs -f web
curl -I http://localhost:8080
```

Para parar:

```bash
docker compose stop
```

Para volver a arrancar sin reconstruir:

```bash
docker compose up -d
```

Para eliminar contenedores y red:

```bash
docker compose down
```

## Dependencias y base de datos

Las dependencias PHP se gestionan con Composer:

- `composer.json`: dependencias declaradas.
- `composer.lock`: versiones exactas; se versiona.
- `vendor/`: código instalado; **no se versiona**.

La aplicación está configurada para SQLite en `.env`:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

El archivo se crea dentro de `var/`, que está ignorado por Git. **Todavía no hay
entidades ni migraciones**: se crearán en la fase 4. Cuando existan, los comandos
previstos son:

```bash
docker compose exec web php bin/console make:migration
docker compose exec web php bin/console doctrine:migrations:migrate
```

No hay que levantar ningún contenedor de base de datos: SQLite es un archivo.

## Usuario de prueba

Pendiente de la fase 4. Cuando se implemente, el procedimiento será reproducible y
**no se publicará ninguna contraseña real**: se documentará el comando o script
para crear el usuario de prueba con una contraseña local.

Actualmente no existen credenciales en el repositorio.

## Rutas disponibles

| Ruta | Nombre | Estado | Acceso |
|---|---|---|---|
| `/` | `app_home` | ✅ Implementada | Pública |
| `/acerca-de` | `app_about` | ✅ Implementada | Pública |
| `/login` | — | ⏳ Fase 4 | Pública (formulario) |
| `/dashboard` | — | ⏳ Fase 4 | Privada |

Se pueden listar las rutas reales con:

```bash
docker compose exec web php bin/console debug:router
```

## Estructura del proyecto

```
symfony-project/
├── Dockerfile
├── compose.yaml
├── composer.json
├── composer.lock
├── .env                     # valores por defecto, sin secretos
├── .env.local               # secretos locales (ignorado por Git)
├── bin/console              # Symfony Console
├── config/
│   ├── bundles.php
│   ├── routes.yaml
│   ├── services.yaml
│   ├── packages/            # configuración de bundles
│   └── routes/              # rutas de sistema
├── docs/                    # entregables de la práctica
│   ├── investigacion.md
│   ├── arranque.md
│   └── flujo_peticion.md
├── public/
│   └── index.php            # front controller
├── src/
│   ├── Controller/
│   │   └── MainController.php
│   ├── Entity/              # vacío (fase 4)
│   ├── Migrations/          # vacío (fase 4)
│   ├── Repository/          # vacío (fase 4)
│   └── Kernel.php
├── templates/
│   ├── base.html.twig
│   └── main/
│       ├── index.html.twig
│       └── about.html.twig
├── tests/                   # pendiente (fase 5)
└── var/                     # caché, logs y SQLite (ignorado por Git)
```

## Dónde está cada cosa

| Elemento | Ubicación |
|---|---|
| Controladores | `src/Controller/` |
| Plantillas Twig | `templates/` |
| Layout común y navegación | `templates/base.html.twig` |
| Definición de rutas | Atributos `#[Route]` en `src/Controller/` |
| Configuración de rutas | `config/routes.yaml` |
| Entidades (fase 4) | `src/Entity/` |
| Repositorios (fase 4) | `src/Repository/` |
| Migraciones (fase 4) | `src/Migrations/` |
| Configuración de seguridad | `config/packages/security.yaml` |
| Configuración de Doctrine | `config/packages/doctrine.yaml` |
| Configuración de servicios | `config/services.yaml` |
| Front controller | `public/index.php` |

## Seguridad: estado actual

`config/packages/security.yaml` está todavía en el estado que genera la receta:

- `password_hashers` preparado para `PasswordAuthenticatedUserInterface`.
- Provider `users_in_memory` (sin usuarios definidos).
- Firewall `main` sin `form_login` ni `logout`.
- `access_control` vacío: **ninguna ruta está protegida**.

El login, el usuario de prueba, el logout y la protección de `/dashboard`
corresponden a la fase 4 y aún no están implementados.

## Problemas encontrados y cómo se solucionaron

1. **Composer no disponible en el contenedor.** El `compose.yaml` inicial montaba
   `/urs/bin/composer` desde el host (ruta mal escrita e inexistente en Windows).
   Se sustituyó por `COPY --from=composer:2` en el `Dockerfile`.
2. **Composer fallaba al descargar paquetes.** La imagen `php:8.4-cli` no incluye
   `git` ni `unzip`. Se instalaron en el `Dockerfile`.
3. **Servicios de base de datos innecesarios.** El compose inicial traía PostgreSQL
   y Mailpit, pero la aplicación usa SQLite y `MAILER_DSN=null://null`. Se
   eliminaron.
4. **`APP_SECRET` versionado.** Estaba en `.env.dev`, que se sube a Git. Se movió a
   `.env.local` (ignorado), se dejó un relleno sin valor secreto en `.env` y se
   rotó el valor. El secreto antiguo permanece en el historial de Git: si el repo
   es público, conviene purgarlo con `git filter-repo` y no reutilizarlo.

## Comandos utilizados

| Comando | Para qué sirve |
|---|---|
| `docker compose build` | Construir la imagen. |
| `docker compose up -d` | Arrancar en segundo plano. |
| `docker compose ps` | Ver el estado del contenedor. |
| `docker compose logs -f web` | Ver logs en tiempo real. |
| `docker compose exec web sh` | Shell dentro del contenedor. |
| `docker compose exec web composer install` | Instalar dependencias. |
| `docker compose exec web php bin/console about` | Ver versión y entorno. |
| `docker compose exec web php bin/console debug:router` | Listar rutas. |
| `docker compose exec web php bin/console cache:clear` | Limpiar caché. |
| `docker compose stop` | Parar sin borrar. |
| `docker compose down` | Parar y eliminar contenedores. |
| `git status` / `git log` | Revisar cambios e historial. |

## Documentación de la práctica

- [Investigación](docs/investigacion.md)
- [Arranque](docs/arranque.md)
- [Flujo de una petición](docs/flujo_peticion.md)

Los enunciados originales de las seis fases están en la carpeta `docs/` del
repositorio de onboarding, fuera de este proyecto.

## Enlaces

- Symfony: <https://symfony.com/doc/current/>
- Twig: <https://twig.symfony.com/doc/3.x/>
- Doctrine ORM: <https://www.doctrine-project.org/projects/orm.html>
- Docker Compose: <https://docs.docker.com/compose/>
