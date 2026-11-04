# Guía de demostración

Guion para la defensa de la práctica (unos 10 minutos). La idea no es enseñar todo
el código, sino demostrar que se entiende el recorrido de una petición y dónde está
la protección real del dashboard.

- URL: <http://localhost:8080>
- Usuario de prueba: `postman` (contraseña elegida en local, no versionada)
- Documento de referencia: [`README.md`](../README.md)

## Antes de empezar

Dejar preparado, sin mostrarlo todavía:

```bash
docker compose up -d
docker compose ps
```

Comprobar que la aplicación responde y que el usuario existe:

```bash
curl -I http://localhost:8080
docker compose exec web php bin/console doctrine:query:dql \
  "SELECT u.username, u.email FROM App\Entity\User u"
```

Si el usuario no existe, crearlo (la contraseña se teclea de forma oculta):

```bash
docker compose exec web php bin/console app:create-user postman postman@example.test
```

Tener abiertas estas pestañas o ventanas:

1. El navegador en `http://localhost:8080`.
2. La terminal en la raíz del proyecto.
3. El editor en `config/packages/security.yaml`.

## Guion de 10 minutos

### 1. Arranque y página pública — 1 min

```bash
docker compose ps
curl -I http://localhost:8080
```

Abrir <http://localhost:8080>.

> "La aplicación corre en un contenedor con PHP 8.4 y Symfony 8.1. El compose
> levanta un único servicio, publica el puerto 8080 y monta el proyecto. La portada
> es pública y muestra datos que le pasa el controlador a Twig."

Enseñar el texto de bienvenida y la navegación. Pinchar en **Acerca de** para
demostrar que hay más de una página y que la navegación usa `path()`.

### 2. De la URL al controlador y a la plantilla — 2 min

Abrir en el editor:

- `src/Controller/MainController.php`
- `templates/main/index.html.twig`
- `templates/base.html.twig`

> "La ruta `/` está declarada con el atributo `#[Route('/', name: 'app_home')]`. El
> router la encuentra en el front controller, ejecuta `MainController::index()` y
> este llama a `render()`. Twig carga primero la plantilla hija y luego la base, que
> define la estructura común."

Señalar:

- `#[Route]` con nombre → `path('app_home')` genera la URL, no se escribe a mano.
- `render()` recibe un array con las variables que usa la plantilla.
- `base.html.twig` tiene el `block body` que rellenan las plantillas hijas.

### 3. Intentar entrar sin sesión — 1 min

Abrir una ventana de incógnito o borrar la sesión y visitar
<http://localhost:8080/dashboard>.

> "El dashboard no se ve. Symfony devuelve un 302 hacia `/login`."

Enseñar la barra de direcciones y, si se quiere, la petición en la pestaña de red:

```bash
curl -I http://localhost:8080/dashboard
```

> "La respuesta es `302` con `Location: /login`. Esto no lo decide el controlador:
> el firewall intercepta la petición antes de que se ejecute."

### 4. Iniciar sesión con el usuario de prueba — 2 min

Ir a `/login`, rellenar `postman` y la contraseña local. Enviar.

> "El formulario envía `_username`, `_password` y `_csrf_token` por POST. El firewall
> comprueba el token CSRF, carga el usuario desde la base de datos con el provider y
> verifica la contraseña con `password_verify` contra el hash guardado. Si todo es
> correcto, crea la sesión y redirige al dashboard."

Enseñar el dashboard: "Bienvenido, postman".

Abrir `config/packages/security.yaml` y señalar:

- `providers` → entidad `App\Entity\User`.
- `form_login` → `login_path`, `check_path`, `enable_csrf`, `default_target_path`.
- `access_control` → `^/dashboard` requiere `ROLE_USER`.
- `logout` → `/logout` redirige a la portada.

> "La protección real está aquí, en la configuración. El HTML solo oculta enlaces;
> el controlador ni siquiera llega a ejecutarse si no hay sesión."

### 5. Cerrar sesión y comprobar que vuelve a estar protegido — 1 min

Pinchar en **Cerrar sesión**.

> "El logout invalida la sesión y redirige a `/`. Si ahora vuelvo a pedir el
> dashboard, otra vez `302` a `/login`."

Volver a intentar `/dashboard` y enseñar la redirección. Opcional: ejecutar la
colección de Postman y enseñar el resumen de 11 peticiones, 16 aserciones y 0 fallos.

### 6. Enseñar dónde está la seguridad y explicar lo entendido — 2 min

Recorrer brevemente:

- `config/packages/security.yaml`: firewall, provider, `form_login`, `access_control`.
- `config/packages/csrf.yaml`: CSRF stateless.
- `src/Entity/User.php`: implementa `UserInterface` y
  `PasswordAuthenticatedUserInterface`; la contraseña es un hash.
- `src/Repository/UserRepository.php`: `loadUserByIdentifier()` busca por `username`.
- `src/Command/CreateUserCommand.php`: crea el usuario de prueba sin guardar texto
  plano.

> "Lo importante es que la autenticación no es un `if` en el controlador: es el
> firewall de Symfony. El controlador solo recibe al usuario ya autenticado con
> `#[CurrentUser]`."

### 7. Cambio pequeño en vivo (si lo piden) — 1 min

Preparar de antemano una modificación trivial y saber revertirla. Por ejemplo,
cambiar el texto de bienvenida en `templates/main/index.html.twig`, guardar y
recargar (el bind mount hace que el cambio se vea al instante). Alternativa sin
tocar Twig: añadir una ruta nueva en `MainController`:

```php
#[Route('/estado', name: 'app_status')]
public function status(): Response
{
    return $this->render('main/about.html.twig', [
        'detalle_tecnico' => 'Aplicación operativa.',
    ]);
}
```

Después, `docker compose exec web php bin/console debug:router` para enseñar que la
ruta aparece. Explicar que en Symfony los cambios de configuración pueden requerir
`cache:clear`, pero las plantillas y los controladores se recargan solos en `dev`.

## Preguntas que pueden hacer

| Pregunta | Respuesta corta |
|---|---|
| ¿Dónde se protege `/dashboard`? | En `access_control` de `security.yaml`, no en el HTML ni en el controlador. |
| ¿Qué pasa si escribo la URL privada sin sesión? | El firewall devuelve `302` a `/login` antes de ejecutar el controlador. |
| ¿Cómo se guarda la contraseña? | Como hash con `password_hash` a través del hasher `auto`; nunca en texto plano. |
| ¿Cómo se mantiene la sesión entre páginas? | Symfony guarda el token de seguridad en una cookie de sesión; el firewall lo lee en cada petición. |
| ¿Por qué hay CSRF en el login? | Para que un formulario enviado desde otro sitio no pueda autenticarse como el usuario. |
| ¿Dónde están los logs? | `docker compose logs -f web` y `var/log/dev.log`; el profiler está en `/_profiler`. |
| ¿Qué diferencia hay entre 404, 403 y 500? | 404 no hay ruta; 403 hay ruta pero falta permiso; 500 excepción no controlada. |
| ¿Por qué SQLite? | No requiere otro servicio y es suficiente para la práctica; en producción se usaría PostgreSQL o MySQL. |

## Cierre: reflexión de cinco líneas

> **Borrador.** El enunciado pide una reflexión personal, así que hay que reescribirla
> con palabras propias y con lo que realmente te haya pasado. No conviene entregarla
> tal cual: se nota cuando un texto no es de quien firma.

1. **Qué sabía antes:** había visto PHP y algo de HTML, pero no había montado una
   aplicación completa ni entendía qué hacía un framework entre la URL y la página.
2. **Qué aprendí:** a seguir el recorrido de una petición (router → controlador →
   Twig), a configurar un firewall con `form_login` y a comprobar el resultado con
   Postman y PHPUnit en lugar de mirar la pantalla a ojo.
3. **Qué fue lo más difícil:** el CSRF y el login: el formulario fallaba aunque las
   credenciales fueran correctas, y la causa estaba en cómo se extraía y se guardaba
   el token, no en la contraseña.
4. **Cómo lo resolví:** leyendo el log y el profiler, comparando la petición que
   enviaba Postman con la del navegador, y aislando el fallo con una aserción que
   comprobara el token antes de usarlo.
5. **Qué me gustaría investigar después:** las pruebas en integración continua y el
   despliegue con un servidor real (Nginx o FrankenPHP) en lugar del servidor
   integrado de PHP, además de la autenticación con roles más finos.

## Después de la demo

```bash
docker compose down
```

Los datos de `var/` se conservan. Si se quiere dejar el entorno completamente limpio,
borrar también `var/data_dev.db` y volver a migrar.
