# Fase 1 — Investigación: Symfony

> Documento de la práctica de onboarding. Explica los conceptos de la fase 1
> aplicados al proyecto real que está en la raíz de este repositorio.

## 1. ¿Qué es Symfony? ¿Framework o librería?

Symfony es un **framework para construir aplicaciones PHP**. Está formado por dos
piezas que conviene no confundir:

- Un conjunto de **componentes independientes** (`HttpFoundation`, `Routing`,
  `HttpKernel`, `Twig Bridge`, `Security`, `Console`…), que se pueden usar por
  separado en cualquier proyecto PHP.
- El **FrameworkBundle**, que ensambla esos componentes y define la estructura de
  una aplicación: configuración, contenedor de servicios, rutas, eventos, etc.

La diferencia entre framework y librería es quién tiene el control:

| Librería | Framework |
|---|---|
| Tú llamas a su código cuando lo necesitas. | El framework llama a tu código en el momento adecuado. |
| Tú decides el flujo del programa. | El flujo lo marca el framework (inversión de control). |
| Ejemplo: una librería para generar PDFs. | Ejemplo: Symfony recibe la petición, busca la ruta y ejecuta tu controlador. |

En este proyecto eso se ve claramente: nosotros no escribimos el bucle que atiende
peticiones HTTP. Escribimos `MainController` y Symfony se encarga de invocarlo
cuando llega la URL correspondiente.

## 2. Relación entre Symfony, PHP y un servidor web

- **PHP** es el lenguaje y el intérprete que ejecuta el código.
- **El servidor web** recibe la petición HTTP del navegador y se la pasa a PHP.
- **Symfony** es el código PHP que decide qué hacer con esa petición.

En este proyecto el servidor es el **servidor integrado de PHP**, arrancado dentro
del contenedor con este comando de `compose.yaml`:

```yaml
command: php -S 0.0.0.0:8080 -t public/
```

Ese `-t public/` es importante: el **document root** es `public/`, no la raíz del
proyecto. Así, el único archivo PHP accesible desde fuera es `public/index.php`.

En producción lo habitual es Nginx o Apache delante de PHP-FPM, apuntando también
a `public/`. El servidor integrado de PHP es solo para desarrollo.

## 3. Comparación con Node.js + Express

| Concepto | Symfony | Node.js + Express |
|---|---|---|
| Lenguaje | PHP | JavaScript/TypeScript |
| Gestor de dependencias | Composer | npm |
| Punto de entrada | `public/index.php` | `app.js` / `server.js` |
| Rutas | Atributos `#[Route]` | `app.get('/ruta', handler)` |
| Middleware | Listeners/eventos y firewall | `app.use(...)` |
| Plantillas | Twig | EJS, Handlebars, Pug… |
| Acceso a datos | Doctrine ORM | Prisma, Sequelize, Knex… |
| Inyección de dependencias | Contenedor de servicios | Manual o con librerías |
| Estructura | Framework completo con convenciones | Express es minimalista; tú montas las piezas |

Express es más parecido a una librería con middleware; Symfony trae más decisiones
tomadas de serie (estructura de carpetas, configuración, seguridad, formularios,
validación, migraciones). La idea de fondo es la misma: una ruta se asocia a una
función que devuelve una respuesta.

## 4. MVC: modelo, vista y controlador

MVC es un patrón de organización, no una imposición mágica de Symfony:

- **Modelo**: datos y reglas del negocio. En este proyecto aparecerá en la fase 4
  con las entidades de Doctrine (`src/Entity`) y sus repositorios
  (`src/Repository`). Ahora mismo todavía no existe.
- **Vista**: lo que se muestra. Aquí son las plantillas Twig de `templates/`.
- **Controlador**: recibe la petición, coordina y devuelve la respuesta. Aquí es
  `src/Controller/MainController.php`.

Ejemplo real con `/acerca-de`:

1. El controlador `MainController::about()` atiende la ruta.
2. Prepara el dato `detalle_tecnico`.
3. Devuelve la vista `templates/main/about.html.twig`.
4. La vista hereda de `templates/base.html.twig`, que aporta el HTML común y la
   navegación.

## 5. Petición HTTP, respuesta HTTP, ruta y plantilla

- **Petición HTTP**: lo que envía el navegador. Incluye método (`GET`, `POST`…),
  ruta (`/acerca-de`), cabeceras y, si existe, cuerpo.
- **Respuesta HTTP**: lo que devuelve el servidor. Incluye código de estado
  (`200`, `404`, `403`, `500`…), cabeceras y cuerpo (normalmente HTML).
- **Ruta**: la asociación entre una URL y el código que la atiende. En Symfony se
  declara con atributos:

  ```php
  #[Route('/acerca-de', name: 'app_about')]
  ```

  El `name` permite generar la URL con `path('app_about')` sin escribirla a mano.
- **Plantilla**: archivo Twig que mezcla HTML con variables y estructuras de
  control. Ejemplo: `templates/main/about.html.twig`.

## 6. Composer: qué es y qué relación tiene con npm

Composer es el gestor de dependencias de PHP. Su equivalencia con npm es directa:

| Archivo/comando | Para qué sirve |
|---|---|
| `composer.json` | Declara las dependencias y la configuración del proyecto. |
| `composer.lock` | Fija las versiones exactas que se instalaron. Se versiona. |
| `vendor/` | Código descargado de las dependencias. **No se versiona**. |
| `composer install` | Instala exactamente lo que dice `composer.lock`. |
| `composer require paquete` | Añade una dependencia y actualiza el lock. |
| `composer update` | Actualiza dependencias según `composer.json`. |

En `composer.json` también está el autoload PSR-4:

```json
"autoload": { "psr-4": { "App\\": "src/" } }
```

Eso significa que la clase `App\Controller\MainController` se busca en
`src/Controller/MainController.php`.

## 7. Symfony CLI, Symfony Console y Twig: no son lo mismo

| Herramienta | Qué es | Cómo se usa aquí |
|---|---|---|
| **Symfony CLI** | Ejecutable `symfony` para el entorno local: servidor con HTTPS, comprobación de requisitos, integración con Composer. | No se usa: el servidor se levanta con Docker. |
| **Symfony Console** | Componente de Symfony para comandos de terminal. Se invoca con `bin/console`. | `docker compose exec web php bin/console …` |
| **Twig** | Motor de plantillas. No es un ejecutable de consola. | `templates/*.twig` |

Comandos útiles de Console en este proyecto:

```bash
docker compose exec web php bin/console about
docker compose exec web php bin/console debug:router
docker compose exec web php bin/console cache:clear
```

## 8. Docker en el entorno local

Docker permite ejecutar la aplicación en un entorno reproducible, sin depender de
la versión de PHP instalada en el ordenador anfitrión.

Conceptos que aparecen en este proyecto:

- **Imagen**: plantilla construida a partir de `Dockerfile`.
- **Contenedor**: instancia en ejecución de esa imagen (`symfony_app`).
- **Volumen**: en `compose.yaml`, `.:/var/www/html` monta el código del host dentro
  del contenedor. Los cambios se ven al instante y `vendor/` queda en el host.
- **Puerto publicado**: `"8080:8080"` conecta el puerto 8080 del host con el 8080
  del contenedor.

`Dockerfile` actual:

```dockerfile
FROM php:8.4.1-cli
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN apt-get update && apt-get install git unzip -y && rm -rf /var/lib/apt/lists/*
```

- Usa PHP 8.4 en modo CLI.
- Copia el binario de Composer desde la imagen oficial `composer:2`.
- Instala `git` y `unzip`, que Composer necesita para descargar paquetes.

No hay servicio de base de datos: la aplicación está configurada para **SQLite**,
cuyo archivo vive en `var/` y no necesita contenedor aparte.

## 9. Requisitos del entorno

| Herramienta | Versión/requisito | Cómo comprobarlo |
|---|---|---|
| PHP | ≥ 8.4 en este proyecto | `php -v` |
| Extensiones PHP | `ctype`, `iconv` (obligatorias); `pdo_sqlite` (fase 4); `intl` (recomendada) | `php -m` |
| Composer | 2.x | `composer --version` |
| Docker | Docker Engine + Compose | `docker --version`, `docker compose version` |
| Git | Cualquier versión reciente | `git --version` |
| Symfony | 8.1.10 en `composer.lock` | `docker compose exec web php bin/console about` |

También se puede comprobar lo que declara el proyecto con:

```bash
composer check-platform-reqs
```

### Nota sobre la versión de Symfony

El enunciado de la práctica pide **Symfony 7.4 LTS**, pero el proyecto se creó con
**Symfony 8.1.10** (ver `composer.json` y `composer.lock`). No es un problema para
lo que se pide, pero conviene saberlo:

- Symfony 7.4 es LTS y requiere PHP ≥ 8.2.
- Symfony 8.1 es una versión estándar (no LTS), requiere PHP ≥ 8.4 y su fin de
  mantenimiento es enero de 2027.
- La documentación oficial debe consultarse en la versión correspondiente:
  `https://symfony.com/doc/8.1/` o `https://symfony.com/doc/current/`.

Los conceptos de esta fase son idénticos; cambian algunos detalles de API y
configuración entre 7.4 y 8.1.

## 10. Recorrido de una petición (esquema)

```
┌──────────────────────────────────────────────────────────────────┐
│ Navegador                                                        │
│ GET http://localhost:8080/acerca-de                              │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ Docker: puerto 8080 → contenedor web                             │
│ php -S 0.0.0.0:8080 -t public/                                   │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ public/index.php  (front controller, único punto de entrada)     │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ src/Kernel.php  (MicroKernelTrait)                               │
│ carga bundles, configuración y definición de rutas               │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ config/routes.yaml                                               │
│ importa las rutas declaradas con atributos en src/Controller     │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ MainController::about()                                          │
│ #[Route('/acerca-de', name: 'app_about')]                        │
└──────────────────────────────┬───────────────────────────────────┘
                               │  $this->render('main/about.html.twig', [...])
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ Twig                                                             │
│ templates/main/about.html.twig extends templates/base.html.twig  │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ Response HTTP 200 + HTML                                         │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                               ▼
                          Navegador
```

## 11. Referencias

- Qué es Symfony: <https://symfony.com/doc/current/introduction.html>
- Instalación y requisitos: <https://symfony.com/doc/current/setup.html>
- Componentes: <https://symfony.com/doc/current/components/index.html>
- MVC y estructura: <https://symfony.com/doc/current/quick_tour/the_architecture.html>
- Composer: <https://getcomposer.org/doc/01-basic-usage.md>
- Docker Compose: <https://docs.docker.com/compose/>
