# Pruebas y verificación

Documento de la fase 5. Recoge los casos de prueba de la aplicación, el resultado
obtenido, la evidencia de cada uno y las incidencias encontradas con su corrección.

La verificación principal se hace con **Postman**, mediante una colección versionada
en el repositorio. Las pruebas cubren el flujo completo: páginas públicas,
página de login, autenticación, acceso a la zona privada, logout, error de
credenciales, ruta inexistente, reinicio del stack y despliegue desde una copia
limpia.

## 1. Entorno de prueba

| Elemento | Valor |
|---|---|
| Aplicación | Symfony 8.1, PHP 8.4 |
| Servidor local | `docker compose`, contenedor `symfony_app` |
| URL principal | `http://localhost:8080` |
| URL copia limpia | `http://127.0.0.1:8081` |
| Base de datos | SQLite (`var/data_dev.db`) mediante migraciones |
| Herramienta principal | Postman (colección + entornos) |
| Herramienta de consola | Postman CLI 1.80 |

### Colección

```
postman/
├── collections/
│   └── Symfony Login - Fase 5/
│       ├── 01 - Públicas/          P01, P02, P03
│       ├── 02 - Sin sesión/        P06
│       ├── 03 - Login correcto/    P04a, P04b
│       ├── 04 - Logout/            P07, P08
│       ├── 05 - Login incorrecto/  P05a, P05b
│       └── 06 - Errores/           404
└── environments/
    ├── Local.environment.yaml      http://localhost:8080
    └── Clean.environment.yaml      http://127.0.0.1:8081
```

Cada petición lleva sus propias aserciones (`pm.test`), de forma que el resultado
no depende de mirar la respuesta a ojo: si el código HTTP, la cabecera
`Location` o un texto de la página no coinciden, el test falla.

### Variables

| Variable | Ámbito | Contenido |
|---|---|---|
| `baseUrl` | entorno | URL del stack que se está probando |
| `username` | entorno | usuario de prueba |
| `password` | entorno | contraseña local, **no versionada** |
| `csrf_token` | colección | token extraído del formulario en P03 |

La contraseña no se guarda en Git. Los entornos del repositorio dejan
`password` vacía y se rellena en Postman. Los ficheros con credenciales
(`*.private.environment.yaml`) están excluidos en `.gitignore`.

> Al rellenar la contraseña, Postman puede escribirla en el fichero del entorno.
> Antes de hacer commit, revisa `git diff postman/` y deja `password` vacía en el
> fichero versionado.

`csrf_token` debe vivir **solo** en la colección. Si se declara también en el
entorno, la variable de entorno tiene prioridad y anula el valor que guarda P03.

### Usuario de prueba

```bash
docker compose exec web php bin/console app:create-user postman postman@example.test
```

El comando pide la contraseña de forma oculta y guarda el hash, nunca el texto
plano.

### Ejecución

Desde la aplicación de Postman:

1. Abrir la colección `Symfony Login - Fase 5`.
2. Seleccionar el entorno `Local` (o `Clean` para la copia limpia).
3. Comprobar que `baseUrl`, `username` y `password` tienen valor.
4. **Run collection** → ejecutar en el orden de las carpetas.

Desde la consola:

```bash
postman collection run "postman/collections/Symfony Login - Fase 5" \
  -e "postman/environments/Local.environment.yaml"
```

### Detalle importante: CSRF y `Origin`

El login usa CSRF stateless (`SameOriginCsrfTokenManager`). Por eso el
`POST /login` necesita la cabecera:

```
Origin: {{baseUrl}}
```

Sin ella, Symfony rechaza la petición aunque el usuario y la contraseña sean
correctos. La petición P04a ya la incluye.

## 2. Casos de prueba y resultados

| ID | Caso | Resultado | Evidencia | Incidencia | Corrección |
|---|---|---|---|---|---|
| P01 | `GET /` devuelve 200 y el texto de bienvenida | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna | — |
| P02 | `GET /acerca-de` devuelve 200 y el contenido esperado | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna | — |
| P03 | `GET /login` devuelve 200, muestra los tres campos y extrae el token CSRF | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | El script guardaba la expresión regular en `html.match` en lugar del resultado; `csrf_token` quedaba vacío | `const match = html.match(/.../)` y aserción que exige que el token exista |
| P04 | Login con credenciales correctas (P04a) y acceso al dashboard (P04b) | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | El entorno declaraba `csrf_token` vacío y anulaba el token de la colección; el POST salía sin token | Eliminar `csrf_token` de los entornos; queda solo como variable de colección |
| P05 | Login con contraseña incorrecta (P05a) y mensaje de error visible (P05b) | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna | — |
| P06 | `GET /dashboard` sin sesión redirige a `/login` (302) | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna. La petición borra la cookie jar antes de ejecutarse, así que la comprobación parte de una sesión limpia | — |
| P07 | `GET /logout` redirige a `/` (302) | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna | — |
| P08 | `GET /dashboard` después del logout vuelve a redirigir a `/login` | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | La petición no tenía URL (`url: ""`); el runner la marcaba como `errored` | Añadir `{{baseUrl}}/dashboard` |
| P09 | Parar y volver a levantar el stack; la aplicación sigue respondiendo igual | ✅ Pasa | `docs/evidencias/p09-reinicio.txt` | Ninguna | — |
| P10 | Copia limpia desde Git en el puerto 8081, en paralelo con el stack actual | ✅ Pasa | `docs/evidencias/p10-copia-limpia.txt` | Ninguna | — |
| Extra | `GET /ruta-inexistente` devuelve 404 | ✅ Pasa | `docs/evidencias/p01-p08-postman.txt` | Ninguna | — |

Los casos P01–P08 están además automatizados con PHPUnit; el resultado está en
`docs/evidencias/phpunit.txt` y se explica en la sección 5.

### Resultado global de la colección

```
|              iterations |                   1 |                  0 |
|                requests |                  11 |                  0 |
|            test-scripts |                  11 |                  0 |
|      prerequest-scripts |                   1 |                  0 |
|              assertions |                  16 |                  0 |
```

Once peticiones, dieciséis aserciones y **cero fallos**, tanto en `localhost:8080`
como en la copia limpia de `127.0.0.1:8081`.

## 3. Qué comprueba cada caso

### P01 y P02 — páginas públicas

Verifican que las rutas públicas responden `200` y que renderizan el contenido
esperado. Son la línea base: si fallan, el problema está en el arranque o en las
plantillas, no en la seguridad.

### P03 — formulario de login y token CSRF

Comprueba tres cosas:

- La respuesta es `200`.
- El HTML contiene `name="_username"`, `name="_password"` y `name="_csrf_token"`.
- El token se extrae y se guarda en `csrf_token` para el `POST /login`.

La extracción se hace con una expresión regular sobre el HTML:

```js
const html = pm.response.text();
const match = html.match(/name="_csrf_token"[\s\S]*?value="([^"]+)"/);

pm.test('P03 - Token CSRF extraído', () => {
    pm.expect(match, 'No se encontró _csrf_token en el HTML').to.not.be.null;
});

pm.collectionVariables.set('csrf_token', match ? match[1] : '');
```

Si el token no aparece, P03 falla de forma explícita en lugar de continuar con
un valor inventado.

### P04 — login correcto

- **P04a**: `POST /login` con `_username`, `_password`, `_csrf_token` y cabecera
  `Origin`. Espera `302` con `Location` hacia `/dashboard`.
- **P04b**: `GET /dashboard` con la cookie de sesión. Espera `200` y que el HTML
  muestre el nombre del usuario del entorno.

No se sigue la redirección (`followRedirects: false`) para poder comprobar el
código `302` y la cabecera `Location` reales.

### P05 — credenciales incorrectas

- **P05a**: `POST /login` con contraseña incorrecta. Espera `302` de vuelta a
  `/login`.
- **P05b**: `GET /login` con la misma sesión. Espera `200` y el mensaje
  «Credenciales no válidas.».

Así se comprueba el flujo completo del error, no solo el código de estado.

### P06 — acceso sin sesión (sesión limpia)

Antes de la petición se vacía la cookie jar:

```js
pm.cookies.jar().clear(pm.request.url.getHost(), (error) => {
    if (error) {
        console.error(error);
    }
});
```

Después se pide `/dashboard` y se espera `302` hacia `/login`. Es la comprobación
desde una sesión limpia que pide el enunciado: no depende de que el runner se
haya ejecutado antes ni de cookies de ejecuciones anteriores.

### P07 y P08 — logout

- **P07**: `/logout` invalida la sesión y redirige a `/` (`302`).
- **P08**: tras el logout, `/dashboard` vuelve a redirigir a `/login` (`302`).

Juntas demuestran que el logout no solo navega, sino que elimina el acceso real.

### P09 — reinicio del stack

Se ejecuta desde el checkout principal, que es el que monta el contenedor:

```bash
docker compose stop
docker compose up -d
```

Después se comprueba que `/` responde `200`, que `/dashboard` sin sesión sigue
dando `302` y se vuelve a ejecutar la colección completa: 16/16 aserciones.

Esto descarta que el resultado dependa de un proceso PHP en memoria o de una
sesión antigua.

### P10 — copia limpia en paralelo

Se genera una copia **solo con lo versionado en Git**:

```bash
git archive --format=tar HEAD -o copia.tar
```

Eso deja fuera `vendor/`, `var/`, `.git/` y `.env.local`. En la copia:

1. Se crea su propio `.env.local` con un `APP_SECRET` aleatorio.
2. Se levanta con un fichero `compose.clean.yaml` y un nombre de proyecto distinto:

   ```bash
   docker compose -f compose.clean.yaml -p symfony_clean up -d --build
   ```

   El contenedor se llama `symfony_clean` y publica `8081:8080`.
3. Se instalan dependencias y se crean la base de datos y el usuario:

   ```bash
   docker compose -f compose.clean.yaml -p symfony_clean exec -T web composer install
   docker compose -f compose.clean.yaml -p symfony_clean exec -T web php bin/console doctrine:migrations:migrate --no-interaction
   docker compose -f compose.clean.yaml -p symfony_clean exec -T web php bin/console app:create-user postman postman@example.test
   ```

4. Se ejecuta la colección con el entorno `Clean`.

Resultados comprobados:

- Los dos stacks funcionan a la vez: `symfony_app` en 8080 y `symfony_clean` en
  8081.
- La copia tiene su propia base de datos: solo contiene el usuario `postman`.
- La colección pasa 16/16 en `http://127.0.0.1:8081`.
- Las sesiones no se mezclan: una cookie obtenida en `127.0.0.1:8081` no se envía
  a `localhost:8080`, que sigue respondiendo `302` en `/dashboard`.

Se usa `127.0.0.1` para la copia en lugar de `localhost` precisamente para que las
cookies de cada stack queden aisladas por nombre de host.

## 4. Incidencias y correcciones

### Incidencia 1 — El token CSRF no se guardaba (P03)

**Síntoma:** P04a fallaba con `302` hacia `/login` aunque el usuario y la
contraseña eran correctos.

**Causa:** el script de P03 hacía:

```js
const match = html.match = (/name="_csrf_token"[\s\S]*?value="([^"]+)"/);
```

Eso asigna la expresión regular a `html.match`; `match` queda como la propia
expresión regular y `match[1]` es `undefined`. El token se guardaba vacío.

**Corrección:** quitar la asignación intermedia:

```js
const match = html.match(/name="_csrf_token"[\s\S]*?value="([^"]+)"/);
```

Además se añadió una aserción que falla si el token no aparece.

### Incidencia 2 — El entorno anulaba el token (P04)

**Síntoma:** el `POST /login` salía con `_csrf_token=` vacío.

**Causa:** los entornos `Local` y `Clean` declaraban `csrf_token: ''`. En Postman
las variables de entorno tienen prioridad sobre las de colección, así que el
valor guardado por P03 nunca se usaba.

**Corrección:** eliminar `csrf_token` de los entornos. Solo existe como variable
de colección.

### Incidencia 3 — P08 sin URL

**Síntoma:** el runner mostraba `GET http:/// [errored]` y
`runtime: request url is empty`.

**Causa:** la petición tenía `url: ""`.

**Corrección:** `url: "{{baseUrl}}/dashboard"`.

### Incidencia 4 — Entornos duplicados y vacíos

**Síntoma:** había dos entornos llamados `Local`, uno de ellos sin usar, y los
valores de `baseUrl`, `username` y `password` estaban vacíos en los ficheros del
repositorio.

**Corrección:** se dejó un único entorno por stack (`Local` y `Clean`), con
`baseUrl` fijado y las credenciales rellenadas en Postman, fuera de Git.

## 5. Pruebas automatizadas con PHPUnit

Además de Postman, el proyecto incluye una prueba funcional que hace peticiones
HTTP reales al kernel con `WebTestCase` y `KernelBrowser`. No sustituye a la
colección: comprueba lo mismo desde dentro de Symfony, sin depender de una
herramienta externa, y se puede ejecutar en CI.

`tests/Functional/LoginFlowTest.php` contiene seis pruebas:

| Prueba | Casos que cubre |
|---|---|
| `testPublicPagesAreAccessible` | P01 y P02 |
| `testLoginPageShowsFormAndCsrfToken` | P03 |
| `testDashboardRequiresAuthentication` | P06 |
| `testLoginWithValidCredentialsRedirectsToDashboard` | P04 |
| `testLoginWithWrongPasswordShowsError` | P05 |
| `testLogoutRemovesAccessToDashboard` | P07 y P08 |

Cómo ejecutarlas:

```bash
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction --env=test
docker compose exec web php bin/phpunit
```

Resultado:

```
PHPUnit 13.4.6 by Sebastian Bergmann and contributors.
......                                                              6 / 6 (100%)
OK (6 tests, 24 assertions)
```

La prueba crea su propio usuario `phpunit` en la base de datos de test
(`var/data_test.db`) si no existe, así que no toca los datos de desarrollo.

Evidencia: `docs/evidencias/phpunit.txt`.

## 6. Investigación adicional

### ¿Cómo se consultan los logs de Symfony y Docker?

```bash
# Salida estándar del contenedor (errores de arranque, avisos del servidor PHP)
docker compose logs -f web

# Log de la aplicación, el más útil para depurar
docker compose exec web tail -f var/log/dev.log
```

- `var/log/dev.log` escribe en desarrollo; en producción el fichero es
  `var/log/prod.log` y el nivel de detalle es menor.
- El **profiler** (`http://localhost:8080/_profiler`) muestra la petición,
  las consultas SQL, el tiempo y la excepción con traza, sin tener que leer el log.
- En el navegador, la consola de desarrollo sirve para errores de JavaScript;
  los errores de PHP no aparecen ahí.

### ¿Cómo distinguir un error 404, 403 y 500?

| Código | Significado | Qué lo provoca | Dónde se ve |
|---|---|---|---|
| **404** | No encontrado | Ninguna ruta coincide con la URL | `NotFoundHttpException`; en dev, página de error con la ruta buscada |
| **403** | Prohibido | La ruta existe, pero el usuario no tiene permiso | `AccessDeniedException`; el firewall deniega el acceso |
| **500** | Error interno | Una excepción no controlada en el controlador, el servicio o la plantilla | Traza en `var/log/dev.log` y en el profiler |

En este proyecto el 404 está comprobado con `/ruta-inexistente` (caso extra de la
colección). El 403 no se puede provocar con las rutas actuales porque todas las
rutas privadas piden `ROLE_USER`, que tiene cualquier usuario autenticado: haría
falta una regla con `ROLE_ADMIN` y entrar con un usuario normal. Un 500 se
detectaría por el código de estado, la traza del log y el profiler; no debe
confundirse con un 404, que es una URL mal escrita.

### ¿Qué comprobaciones se pueden automatizar con tests en Symfony?

- Código de estado y redirecciones (`assertResponseStatusCodeSame`,
  `assertResponseRedirects`).
- Contenido HTML renderizado: campos del formulario, textos, enlaces.
- Flujos completos: enviar el formulario de login, seguir la redirección y entrar
  al dashboard.
- Seguridad: acceso anónimo, acceso autenticado, logout y pérdida de acceso.
- Consultas a base de datos y servicios con `KernelTestCase`.

Lo que no cubre `WebTestCase` es el navegador real: CSS, JavaScript y
comportamiento visual. Para eso haría falta un test de navegador (por ejemplo, Symfony Panther). En esta
práctica el CSS y el JavaScript no intervienen en la seguridad, así que el HTML y
el HTTP son suficientes.

La colección de Postman se puede ejecutar también en CI con
`postman collection run`, y `bin/phpunit` en el mismo pipeline. Así se comprueba
el contrato HTTP por fuera y el comportamiento interno por dentro.

### ¿Qué cambia si abres el dashboard en una ventana privada del navegador?

Nada en el servidor: la ventana privada empieza sin cookies, así que no envía la
cookie `PHPSESSID` y `/dashboard` responde `302` hacia `/login`. Es exactamente
el mismo caso que P06, donde la colección vacía la cookie jar antes de pedir la
página.

Esto confirma que la protección está en el servidor (`access_control` y firewall)
y no en el HTML: aunque alguien conociera la URL, sin sesión válida no se ejecuta
el controlador del dashboard. La ventana privada solo cambia el almacén de
cookies del cliente, no la configuración de la aplicación.

## 7. Evidencias

| Fichero | Contenido |
|---|---|
| `docs/evidencias/p01-p08-postman.txt` | Salida completa de la colección contra `http://localhost:8080`: 11 peticiones, 16 aserciones, 0 fallos |
| `docs/evidencias/p09-reinicio.txt` | `docker compose stop/up`, comprobación HTTP y colección completa tras el reinicio |
| `docs/evidencias/p10-copia-limpia.txt` | Creación de la copia desde Git, arranque en 8081, migraciones, usuario, colección 16/16, aislamiento de sesión y borrado final |
| `docs/evidencias/phpunit.txt` | Migración de la base de datos de test y ejecución de `bin/phpunit`: 6 tests, 24 aserciones, 0 fallos |

Las evidencias son texto reproducible: cualquiera puede volver a ejecutar los
mismos comandos y obtener la misma salida. No dependen de capturas de pantalla.

Cada fichero indica el commit sobre el que se ejecutó (`f2028e8`). Después de ese
commit solo cambiaron la documentación y los propios ficheros de evidencia; el
código de la aplicación, la colección y los tests son los mismos.

## 8. Limitaciones

- No se prueban navegadores reales ni JavaScript del cliente: se verifica el HTML
  y el comportamiento HTTP, que es lo que decide la seguridad.
- Los tests funcionales de PHPUnit usan `WebTestCase`, no un navegador real; CSS
  y JavaScript quedan fuera de su alcance.
- No hay pruebas de carga ni de concurrencia; el alcance de la fase es funcional.
- El usuario y la contraseña de prueba son locales. No se usan credenciales
  corporativas ni se versionan secretos.
- La colección asume que existe el usuario `postman`; el comando
  `app:create-user` deja crearlo de forma reproducible.

## 9. Conclusión

Los diez casos de la fase 5 pasan. El flujo público, el login, la zona privada, el
logout, el error de credenciales, la sesión limpia, el reinicio del stack y la
copia limpia se comportan como se espera.

Las tres incidencias que bloqueaban el login estaban en la propia herramienta de
pruebas (script del token, variable de entorno y URL vacía), no en la aplicación.
Corregidas, la colección queda como una comprobación repetible que se puede
ejecutar en cualquier momento y en cualquier copia del proyecto.

Las mismas comprobaciones están automatizadas con PHPUnit, de modo que el flujo
de autenticación se puede verificar también sin abrir Postman.
