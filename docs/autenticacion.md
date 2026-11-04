# Autenticación

Documento de la fase 4. Explica el recorrido completo desde que el usuario pulsa
«Iniciar sesión» hasta que ve `/dashboard`, cómo se guarda el usuario, cómo se
comprueba la contraseña y cómo se protege la ruta privada.

## 1. Decisión: login por nombre de usuario

El formulario identifica al usuario con **`username`**. El `email` se guarda como
dato único de contacto, pero no se usa para iniciar sesión.

El enunciado habla de «correo o nombre de usuario». Se ha optado por el nombre de
usuario para simplificar la práctica. Si más adelante se quiere aceptar también el
email, basta con descomentar una línea en `UserRepository::loadUserByIdentifier()`:

```php
->orWhere('LOWER(u.email) = :identifier')
```

El provider de seguridad **no** usa `property: username`. Al no indicar `property`,
Doctrine llama al método personalizado del repositorio:

```yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
```

Eso permite normalizar el identificador (`trim` y minúsculas) antes de consultar.

## 2. Archivos implicados

| Archivo | Papel |
|---|---|
| `config/packages/security.yaml` | Provider, firewall, `form_login`, `logout` y `access_control`. |
| `config/packages/csrf.yaml` | Tokens CSRF stateless para `authenticate` y `logout`. |
| `config/routes/security.yaml` | Importa el route loader que genera `_logout_main`. |
| `src/Entity/User.php` | Usuario de Doctrine y de Security. |
| `src/Repository/UserRepository.php` | Carga el usuario por `username` y actualiza el hash. |
| `src/Controller/SecurityController.php` | Muestra el formulario y los errores. |
| `src/Controller/DashboardController.php` | Página privada. |
| `src/Command/CreateUserCommand.php` | Crea o actualiza el usuario de prueba. |
| `templates/security/login.html.twig` | Formulario de login. |
| `templates/dashboard/index.html.twig` | Página privada. |
| `templates/base.html.twig` | Navegación condicional, importmap y enlace de logout. |
| `config/packages/translation.yaml` | Locale `es` para los mensajes de seguridad. |

## 3. Configuración de seguridad

El bloque relevante de `config/packages/security.yaml` es:

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User

    firewalls:
        dev:
            pattern: ^/(_profiler(/|$)|(_wdt|assets|build)/)
            security: false

        main:
            lazy: true
            provider: app_user_provider

            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
                default_target_path: app_dashboard
                always_use_default_target_path: true
                failure_path: app_login

            logout:
                path: /logout
                target: app_home

    access_control:
        - { path: ^/dashboard, roles: ROLE_USER }
```

| Clave | Para qué sirve |
|---|---|
| `provider: app_user_provider` | El firewall carga los usuarios desde Doctrine. |
| `login_path: app_login` | URL a la que se redirige al usuario no autenticado. |
| `check_path: app_login` | URL que procesa el POST. El firewall la intercepta antes del controlador. |
| `enable_csrf: true` | Valida el campo `_csrf_token` con el token id `authenticate`. |
| `default_target_path: app_dashboard` | Destino tras un login correcto. |
| `always_use_default_target_path: true` | Ignora `_target_path`, evitando redirecciones manipulables. |
| `failure_path: app_login` | Destino tras un login incorrecto. |
| `logout.path: /logout` | Al empezar por `/`, el route loader crea `_logout_main`. |
| `logout.target: app_home` | Destino después del logout. |
| `access_control` | La protección real de `/dashboard`: exige `ROLE_USER`. |

`ROLE_USER` no se guarda necesariamente en la base de datos: `User::getRoles()` lo
añade siempre.

## 4. Recorrido completo

### 4.1. El usuario pulsa «Iniciar sesión»

En `templates/base.html.twig`:

```twig
{% if app.user %}
    <a href="{{ path('app_dashboard') }}">Dashboard</a> |
    <a href="{{ logout_path() }}">Cerrar sesión</a>
{% else %}
    <a href="{{ path('app_login') }}">Iniciar sesión</a>
{% endif %}
```

El enlace genera `/login` a partir del nombre de ruta `app_login`. El navegador
hace un `GET /login`.

### 4.2. `SecurityController::login()` muestra el formulario

```php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('app_dashboard');
    }

    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ]);
}
```

- Si ya hay sesión, redirige al dashboard.
- `AuthenticationUtils` lee de la sesión el último error y el último usuario
  escrito. El firewall es quien escribe esos valores.
- Renderiza `templates/security/login.html.twig`.

Este controlador **no autentica**: solo prepara la vista.

### 4.3. El usuario envía el formulario

El formulario hace `POST /login` con estos campos:

| Campo | Valor |
|---|---|
| `_username` | Nombre de usuario escrito. |
| `_password` | Contraseña en texto plano, solo durante la petición. |
| `_csrf_token` | Token generado con `csrf_token('authenticate')`. |

Los nombres coinciden con los valores por defecto de `form_login`:
`username_parameter`, `password_parameter` y `csrf_parameter`.

El firewall `main` intercepta el POST en `check_path` antes de que llegue al
controlador. Ahí empieza la autenticación real.

### 4.4. Validación CSRF

`form_login` valida el token con el id `authenticate`. En este proyecto,
`config/packages/csrf.yaml` incluye `authenticate` dentro de
`stateless_token_ids`, así que se usa `SameOriginCsrfTokenManager`. Lo explico en
detalle en la sección 6.

### 4.5. El provider carga el usuario

El firewall pide el usuario a `app_user_provider`. Como el provider no tiene
`property`, Doctrine llama a:

```php
UserRepository::loadUserByIdentifier($identificador)
```

La consulta normaliza el valor y busca por `username`:

```php
$identifier = mb_strtolower(trim($identifier));

return $this->createQueryBuilder('u')
    ->where('LOWER(u.username) = :identifier')
    ->setParameter('identifier', $identifier)
    ->getQuery()
    ->getOneOrNullResult();
```

Si no hay usuario, el provider lanza `UserNotFoundException`. Symfony lo convierte
en un error genérico para no revelar si el usuario existe.

### 4.6. Se comprueba la contraseña

El usuario cargado implementa `PasswordAuthenticatedUserInterface`, así que
Symfony puede llamar a `getPassword()` y obtener el **hash** almacenado.

El `CheckCredentialsListener` usa el servicio de hashing configurado en
`password_hashers` (`auto`) para comparar la contraseña enviada con el hash:

```php
$hasher->isPasswordValid($user, $plainPassword);
```

No se compara texto con texto. El hash incluye salt y la comparación la hace la
librería de contraseñas de PHP.

### 4.7. Se crea el token y la sesión

Si las credenciales son correctas:

1. Symfony crea un token de autenticación para el firewall `main`.
2. Guarda el token en la sesión, bajo la clave `_security_main`.
3. Redirige a `default_target_path` (`app_dashboard`), o a la ruta que hubiera
   intentado visitar si `always_use_default_target_path` estuviera desactivado.

El navegador recibe un `302` con `Location: /dashboard` y guarda la cookie de
sesión (`PHPSESSID` por defecto).

### 4.8. `GET /dashboard`

En la siguiente petición:

1. El firewall `main` lee la cookie de sesión.
2. Recupera el token de `_security_main` y refresca el usuario con el provider.
3. `access_control` comprueba que la ruta empieza por `/dashboard` y que el usuario
   tiene `ROLE_USER`.
4. Si todo es correcto, se ejecuta `DashboardController::index()`.

```php
#[Route('/dashboard', name: 'app_dashboard')]
public function index(#[CurrentUser] User $user): Response
{
    return $this->render('dashboard/index.html.twig', [
        'user' => $user,
    ]);
}
```

`#[CurrentUser]` inyecta el usuario autenticado. La plantilla muestra
`{{ user.userIdentifier }}`, que llama a `User::getUserIdentifier()` y devuelve el
username.

### 4.9. Credenciales incorrectas

Si la contraseña no coincide:

1. Se lanza `BadCredentialsException`.
2. El firewall redirige a `failure_path` (`app_login`).
3. Guarda el error y el último usuario en la sesión.
4. `SecurityController::login()` los recupera con `AuthenticationUtils`.
5. La plantilla muestra el mensaje traducido:

```twig
{{ error.messageKey|trans(error.messageData, 'security') }}
```

Con `default_locale: es`, Symfony traduce `Invalid credentials.` como
`Credenciales no válidas.` usando `security.es.xlf`.

Por defecto, `expose_security_errors` es `None`: no se distingue entre «el usuario
no existe» y «la contraseña es incorrecta».

### 4.10. Logout

El enlace del dashboard y de la navegación usa:

```twig
<a href="{{ logout_path() }}">Cerrar sesión</a>
```

`logout_path()` genera `/logout`. Como `logout.path` empieza por `/`, el route
loader de `config/routes/security.yaml` crea la ruta `_logout_main`. **No hace falta
un método de controlador para el logout.**

Al recibir la petición, el `LogoutListener` del firewall:

1. Invalida la sesión (`invalidate_session: true` por defecto).
2. Borra el token de seguridad.
3. Redirige a `target` (`app_home`).

Después del logout, `GET /dashboard` vuelve a ser una petición sin token y el
firewall redirige a `/login`.

> El logout está configurado como enlace GET y sin `enable_csrf`. Es suficiente para
> la práctica, porque el enunciado pide CSRF en el formulario de login. Si se quiere
> endurecer, se puede activar `enable_csrf: true` en `logout`; entonces
> `logout_path()` añade el token automáticamente. Para máxima robustez, usar un
> formulario `POST` en lugar de un enlace.
>
> `config/packages/csrf.yaml` incluye `logout` en la lista de tokens stateless, pero
> como `logout.enable_csrf` no está activado, ese token no se exige actualmente.

## 5. Diagrama del recorrido

```
Navegador
  │ GET /login
  ▼
SecurityController::login()
  │ AuthenticationUtils (error + último usuario)
  ▼
templates/security/login.html.twig
  │ POST /login  (_username, _password, _csrf_token)
  ▼
Firewall main
  │
  ├─ form_login valida CSRF
  ├─ app_user_provider → UserRepository::loadUserByIdentifier()
  ├─ PasswordHasher::isPasswordValid()
  └─ crea token y lo guarda en la sesión (_security_main)
  │
  ▼
302 → /dashboard
  │
  ▼
Firewall main lee el token de la sesión
  │
  ├─ access_control: ^/dashboard exige ROLE_USER
  ▼
DashboardController::index()
  │
  ▼
templates/dashboard/index.html.twig
  │
  ▼
HTML con «Bienvenido, <username>»
```

## 6. CSRF en el login

CSRF (*Cross-Site Request Forgery*) es un ataque en el que otro sitio consigue que
el navegador del usuario envíe una petición autenticada sin que este lo sepa.

En el login, el riesgo es que un atacante haga que la víctima inicie sesión en la
cuenta del atacante sin darse cuenta. El token CSRF garantiza que el formulario fue
generado por la aplicación y que la petición viene del mismo origen.

### Cómo está configurado aquí

- `form_login.enable_csrf: true`.
- El token se genera con `csrf_token('authenticate')`.
- El campo se llama `_csrf_token`.
- `config/packages/csrf.yaml` incluye `authenticate` en `stateless_token_ids`.

### Qué significa «stateless»

`SameOriginCsrfTokenManager` no guarda el token en la sesión. Acepta la petición si
se cumple una de estas condiciones:

1. El navegador envía `Sec-Fetch-Site: same-origin`.
2. El `Origin` o el `Referer` coinciden con el origen de la aplicación.
3. Se envía un token doble: el valor del campo y una cookie generada por JavaScript.

Los navegadores modernos envían `Sec-Fetch-Site: same-origin` en un POST del mismo
sitio, así que el formulario funciona sin JavaScript.

### El papel del importmap

`templates/base.html.twig` carga:

```twig
{{ importmap('app') }}
```

Eso carga `assets/app.js`, que arranca Stimulus y registra
`assets/controllers/csrf_protection_controller.js`. Ese controlador, al enviar el
formulario, genera un token aleatorio, lo pone en el campo y crea la cookie de doble
envío. Es la tercera vía de validación, útil cuando el navegador no envía las
cabeceras de origen.

Si se prefiere el CSRF de sesión clásico, se puede quitar `authenticate` de
`stateless_token_ids` en `config/packages/csrf.yaml`. En ese caso el token se guarda
en la sesión y no depende de JavaScript ni de las cabeceras.

## 7. Usuario de prueba

El usuario se crea con un comando reproducible que forma parte del repositorio:

```bash
docker compose exec web php bin/console app:create-user pau pau@example.test
```

El comando pide la contraseña de forma oculta (no se muestra en pantalla). También
se puede pasar como tercer argumento, pero no es recomendable porque queda en el
historial de la shell:

```bash
docker compose exec web php bin/console app:create-user pau pau@example.test "MiClaveLocal123"
```

### Qué hace el comando

`src/Command/CreateUserCommand.php`:

1. Normaliza `username` y `email` (`trim` y minúsculas).
2. Valida el formato del email.
3. Pide la contraseña si no se pasa como argumento.
4. Busca si el usuario ya existe; si existe, actualiza; si no, crea uno nuevo.
5. Hashea la contraseña con `UserPasswordHasherInterface::hashPassword()`.
6. Guarda con `persist()` + `flush()`.

La contraseña **nunca** se guarda en texto plano ni se imprime por pantalla.

### Verificar que se ha guardado el hash

```bash
docker compose exec web php bin/console doctrine:query:dql "SELECT u.username, u.password FROM App\Entity\User u"
```

El valor de `password` debe empezar por `$2y$` (bcrypt) o `$argon2id$` (argon2),
nunca por la contraseña escrita.

> En este proyecto no existe el comando `doctrine:query:sql` (DoctrineBundle 3).
> Para consultar datos usa `doctrine:query:dql`.

### Condiciones de seguridad

- No se usan credenciales corporativas.
- La contraseña del usuario de prueba es local y no se versiona.
- `APP_SECRET` vive en `.env.local`, ignorado por Git.
- `.env` solo contiene un valor de relleno sin carácter secreto.

## 8. Respuestas a las preguntas del enunciado

### ¿Cómo identifica Symfony al usuario que hace la petición?

A través del **token de seguridad** guardado en la sesión, bajo la clave
`_security_main`. En cada petición, el firewall lee la cookie de sesión, recupera el
token y refresca el usuario con el provider. El identificador visible es el que
devuelve `User::getUserIdentifier()`, en este caso el `username`.

### ¿Qué papel tienen el firewall, el user provider y las reglas de acceso?

- **Firewall** (`main`): decide qué peticiones pasan por seguridad, carga el token
  de la sesión, ejecuta los authenticators y gestiona el logout.
- **User provider** (`app_user_provider`): carga el usuario desde Doctrine usando
  `UserRepository::loadUserByIdentifier()`.
- **Reglas de acceso** (`access_control`): autorizan o deniegan según la ruta y los
  roles. Son la protección real de `/dashboard`.

### ¿Qué diferencia hay entre comprobar una contraseña y compararla como texto?

Comparar texto plano sería `if ($password === $guardada)`. Eso obliga a guardar la
contraseña original y, si la base de datos se filtra, todas las cuentas quedan
expuestas.

Con hash:

- Se guarda un hash irreversible, con salt.
- Se usa un algoritmo lento (bcrypt o argon2) para dificultar la fuerza bruta.
- La comprobación la hace la librería (`isPasswordValid`), no una comparación
  manual.
- Nunca se guarda ni se registra la contraseña original.

### ¿Cómo se mantiene la autenticación entre dos páginas?

Con la **sesión**. Tras el login, Symfony guarda el token en la sesión y el
navegador conserva la cookie `PHPSESSID`. En la siguiente petición, el firewall lee
esa cookie, recupera el token y vuelve a cargar el usuario. Por eso `/dashboard`
sabe quién eres sin volver a pedir la contraseña.

### ¿Dónde está la protección real del dashboard: en el HTML, en el controlador o en la configuración?

En la **configuración de seguridad**, concretamente en `access_control` y en el
firewall. El controlador y la plantilla no protegen nada: si quitas
`access_control`, `/dashboard` sería accesible aunque el Twig oculte cosas. La regla
`- { path: ^/dashboard, roles: ROLE_USER }` es la que impide el acceso.

### ¿Qué pasa si alguien introduce directamente la URL privada?

El firewall detecta que no hay token válido y redirige a `/login` con un `302`. No
se ejecuta el controlador del dashboard ni se renderiza su plantilla. Tras
autenticarse, `default_target_path` lo lleva a `/dashboard`.

### ¿Qué es CSRF y por qué se tiene en cuenta en el login?

Es un ataque que fuerza al navegador de la víctima a enviar una petición
autenticada a la aplicación sin su consentimiento. En el login se protege con un
token que demuestra que el formulario fue generado por la aplicación, más la
validación de origen. Así un sitio externo no puede iniciar sesión en nombre del
usuario ni reutilizar su sesión.

## 9. Comprobación manual

| ID | Acción | Resultado esperado |
|---|---|---|
| P03 | Abrir `/login` | Se muestra el formulario. |
| P04 | Usuario y contraseña correctos | Redirección a `/dashboard`. |
| P05 | Contraseña incorrecta | Vuelve a `/login` con «Credenciales no válidas.». |
| P06 | Abrir `/dashboard` sin sesión | Redirección a `/login`. |
| P07 | Pulsar «Cerrar sesión» | Redirección a `/`. |
| P08 | Abrir `/dashboard` tras el logout | Vuelve a redirigir a `/login`. |

Comprobaciones útiles:

```bash
docker compose exec web php bin/console debug:router
docker compose exec web php bin/console debug:firewall main
curl -I http://localhost:8080/dashboard
```

`curl -I http://localhost:8080/dashboard` debe devolver `302` con
`Location: http://localhost:8080/login`.

Para una sesión limpia, usa una ventana privada del navegador. Los resultados
detallados de las pruebas están en `docs/pruebas.md` (fase 5), que incluye la
colección de Postman, las pruebas funcionales de PHPUnit y las evidencias.

## 10. Enlaces

- Seguridad en Symfony: <https://symfony.com/doc/current/security.html>
- Form login: <https://symfony.com/doc/current/security.html#form-login>
- CSRF: <https://symfony.com/doc/current/security/csrf.html>
- Hashing de contraseñas: <https://symfony.com/doc/current/security/passwords.html>
- Sesiones: <https://symfony.com/doc/current/session.html>
