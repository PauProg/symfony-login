# Fase 3 — Flujo de una petición: de la URL al HTML

> Documento de la práctica de onboarding. Explica paso a paso qué ocurre desde que
> el navegador solicita una URL hasta que aparece la página, usando los archivos
> concretos de este proyecto.

## 1. Resumen del recorrido

Para `GET http://localhost:8080/acerca-de`:

```
Navegador
  → Docker (puerto 8080)
  → public/index.php
  → src/Kernel.php
  → config/routes.yaml
  → atributo #[Route] de MainController::about()
  → $this->render('main/about.html.twig', [...])
  → templates/main/about.html.twig (hereda de templates/base.html.twig)
  → Response HTTP 200 con HTML
  → Navegador
```

## 2. Paso a paso

### Paso 1 — El navegador envía la petición

El navegador hace:

```http
GET /acerca-de HTTP/1.1
Host: localhost:8080
```

El método es `GET`, la ruta pedida es `/acerca-de` y el host es `localhost:8080`.

### Paso 2 — Docker recibe la petición

En `compose.yaml`, el puerto 8080 del host está publicado hacia el 8080 del
contenedor:

```yaml
ports:
  - "8080:8080"
```

Dentro del contenedor se está ejecutando:

```bash
php -S 0.0.0.0:8080 -t public/
```

El servidor integrado de PHP recibe la petición y, como el document root es
`public/`, la dirige al front controller.

### Paso 3 — `public/index.php`: el front controller

`public/index.php` es el único archivo PHP accesible desde el exterior. Carga el
autoload de Composer, arranca el runtime de Symfony y devuelve la respuesta:

```php
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

Todas las peticiones pasan por aquí, tanto `/` como `/acerca-de` o cualquier otra
ruta.

### Paso 4 — `src/Kernel.php`: arranque de la aplicación

`App\Kernel` usa `MicroKernelTrait`. Symfony:

1. Registra los bundles de `config/bundles.php`.
2. Carga la configuración de `config/packages/*.yaml`.
3. Carga las rutas definidas en `config/routes.yaml` y `config/routes/`.
4. Construye el contenedor de servicios.

Kernel no decide qué página mostrar; solo prepara la aplicación.

### Paso 5 — El router busca una coincidencia

`config/routes.yaml` contiene:

```yaml
controllers:
    resource: routing.controllers
```

Ese `routing.controllers` le dice a Symfony que importe las rutas declaradas con
atributos en las clases de `src/Controller/`.

En `src/Controller/MainController.php`:

```php
#[Route('/acerca-de', name: 'app_about')]
public function about(): Response
{
    // ...
}
```

El router compara la ruta pedida (`/acerca-de`) con las rutas registradas y
encuentra esta coincidencia. Además del path, la ruta tiene un **nombre**
(`app_about`) que se usará después para generar enlaces.

Se puede ver el listado completo con:

```bash
docker compose exec web php bin/console debug:router
```

### Paso 6 — Se ejecuta el controlador

Symfony instancia `MainController` y llama al método `about()`.

```php
public function about(): Response
{
    return $this->render('main/about.html.twig', [
        'detalle_tecnico' => 'La aplicación utiliza PHP 8.4 en un contenedor CLI y procesa las vistas mediante el motor de plantillas Twig.'
    ]);
}
```

El método no devuelve un string: devuelve un objeto `Response`.

### Paso 7 — `render()` prepara la plantilla

`render()` es un método que aporta `AbstractController`. Hace tres cosas:

1. Localiza la plantilla indicada. El nombre `main/about.html.twig` se resuelve
   como `templates/main/about.html.twig`.
2. Pasa a Twig el array de variables. La clave `detalle_tecnico` estará disponible
   dentro de la plantilla como `{{ detalle_tecnico }}`.
3. Renderiza el HTML y lo envuelve en una `Response` con estado 200.

### Paso 8 — Twig renderiza la plantilla hija

`templates/main/about.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Acerca de este proyecto{% endblock %}

{% block body %}
    <h1>Acerca de este proyecto</h1>
    <p>Esta es una explicación breve de la plataforma orientada al desarrollo de aplicaciones distribuidas y modulares.</p>
    <p><strong>Detalles del entorno:</strong> {{ detalle_tecnico }}</p>
{% endblock %}
```

Aquí ocurren varias cosas:

- `{% extends 'base.html.twig' %}` indica que esta plantilla **hereda** del layout.
- `{% block title %}` y `{% block body %}` rellenan los huecos definidos en la
  plantilla base.
- `{{ detalle_tecnico }}` imprime el valor enviado desde el controlador.

### Paso 9 — Twig renderiza la plantilla base

`templates/base.html.twig` define la estructura común:

```twig
<!DOCTYPE html>
<html>
    <head>
        <title>{% block title %}Mi App Symfony{% endblock %}</title>
    </head>
    <body>
        <nav>
            <a href="{{ path('app_home') }}">Inicio</a> |
            <a href="{{ path('app_about') }}">Acerca de</a>
        </nav>

        <main>
            {% block body %}{% endblock %}
        </main>
    </body>
</html>
```

Twig sustituye los bloques por el contenido de la plantilla hija y genera el HTML
final. La navegación se define **una sola vez**; por eso no se duplica en cada
página.

### Paso 10 — La respuesta vuelve al navegador

Symfony devuelve una `Response` con:

- Código de estado `200`.
- Cabecera `Content-Type: text/html; charset=UTF-8`.
- Cuerpo con el HTML generado.

El servidor de PHP envía esa respuesta y el navegador la interpreta y pinta la
página.

## 3. El mismo recorrido para `/`

En `MainController::index()`:

```php
#[Route('/', name: 'app_home')]
public function index(): Response
{
    return $this->render('main/index.html.twig', [
        'nombre_alumno' => 'Pau Medina Vázquez',
        'descripcion_proyecto' => '...',
        'fecha_actual' => (new \DateTime())->format('d/m/Y H:i')
    ]);
}
```

Cambian el método, la plantilla (`main/index.html.twig`) y las variables. El resto
del recorrido es idéntico.

`templates/main/index.html.twig` también hereda de `base.html.twig` y usa los
mismos bloques. Las variables se muestran con:

```twig
{{ nombre_alumno }}
{{ descripcion_proyecto }}
{{ fecha_actual }}
```

`fecha_actual` se calcula en el controlador en el momento de cada petición, así que
cambia si se recarga la página.

## 4. Tabla de correspondencias

| Concepto | Archivo concreto | Qué hace |
|---|---|---|
| Front controller | `public/index.php` | Punto de entrada único. |
| Kernel | `src/Kernel.php` | Arranca bundles, configuración y rutas. |
| Definición de rutas | `config/routes.yaml` | Importa las rutas por atributos. |
| Controlador | `src/Controller/MainController.php` | Atiende `/` y `/acerca-de`. |
| Ruta de inicio | `#[Route('/', name: 'app_home')]` | Asocia `/` con `index()`. |
| Ruta secundaria | `#[Route('/acerca-de', name: 'app_about')]` | Asocia `/acerca-de` con `about()`. |
| Plantilla base | `templates/base.html.twig` | Layout común y navegación. |
| Vista de inicio | `templates/main/index.html.twig` | HTML de `/`. |
| Vista secundaria | `templates/main/about.html.twig` | HTML de `/acerca-de`. |
| Configuración de servicios | `config/services.yaml` | Autowiring y registro de `App\`. |

## 5. Detalles de Twig que aparecen aquí

| Sintaxis | Significado |
|---|---|
| `{{ variable }}` | Imprime un valor. |
| `{% extends 'base.html.twig' %}` | Hereda de otra plantilla. |
| `{% block nombre %}...{% endblock %}` | Define o rellena un bloque. |
| `{{ path('app_home') }}` | Genera la URL de la ruta con ese nombre. |
| `{# comentario #}` | Comentario de Twig (no se envía al navegador). |

### Por qué `path()` y no una URL escrita a mano

```twig
<a href="{{ path('app_home') }}">Inicio</a>
```

es mejor que:

```twig
<a href="/">Inicio</a>
```

Porque si mañana cambia la URL de la ruta, solo hay que cambiar el atributo
`#[Route]`; las plantillas siguen funcionando. Además, Symfony conoce el nombre de
la ruta y puede generar URLs absolutas con `url()`.

## 6. Qué pasa si algo falla

| Situación | Código | Origen |
|---|---|---|
| La URL no coincide con ninguna ruta | `404 Not Found` | Router. |
| El controlador lanza una excepción | `500 Internal Server Error` | Kernel / PHP. |
| La ruta existe pero el usuario no tiene permiso | `403 Forbidden` | `access_control` / firewall. |
| El usuario no está autenticado y la ruta es privada | `302` a `/login` | Firewall `main` + `form_login`. |
| Login correcto | `302` a `/dashboard` | `form_login` (`default_target_path`). |
| Logout | `302` a `/` | `logout` (`target: app_home`). |

## 7. Estado actual de las rutas

- `/` y `/acerca-de` son públicas.
- `/login` es pública y muestra el formulario de acceso.
- `/dashboard` está protegida por `access_control` y exige `ROLE_USER`.
- Si se solicita `/dashboard` sin sesión, el firewall responde con un `302` hacia
  `/login`; el controlador del dashboard no llega a ejecutarse.
- Tras un login correcto, `default_target_path` redirige a `/dashboard`.
- Tras el logout, volver a `/dashboard` redirige de nuevo a `/login`.

El recorrido completo del login, el provider y el logout está en
[`autenticacion.md`](autenticacion.md).

## 8. Enlaces

- Creación de páginas: <https://symfony.com/doc/current/page_creation.html>
- Routing: <https://symfony.com/doc/current/routing.html>
- Templates con Twig: <https://symfony.com/doc/current/templates.html>
- Herencia de plantillas: <https://twig.symfony.com/doc/3.x/tags/extends.html>
