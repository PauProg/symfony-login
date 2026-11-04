Verificación del README de la fase 6
=====================================

Fecha: 2026-11-04
Rama: docs/detalles-finales
Commit verificado: fc81beb ("docs: reescribir el README para la entrega y anadir la guia de demo")

Objetivo: comprobar que los pasos del README funcionan desde una copia limpia,
sin depender de archivos ignorados (vendor/, var/, .env.local) y sin parar el
stack de desarrollo.

Método
------
1. La copia se genera solo con lo versionado en Git:
     git archive --format=tar HEAD -o copia.tar
     tar -xf copia.tar -C <directorio-temporal>
2. En la copia se crea su propio .env.local con un APP_SECRET aleatorio.
3. Se levanta con un compose.clean.yaml (mismo contenido que compose.yaml, pero
   container_name: symfony_clean y puerto 8081:8080) y un nombre de proyecto
   distinto:
     docker compose -f compose.clean.yaml -p symfony_clean up -d --build
4. Se ejecutan los pasos del README adaptando el nombre del fichero compose y el
   puerto. La aplicación principal (symfony_app, 8080) NO se detiene en ningún
   momento.
5. La contraseña del usuario de prueba se pasa por stdin y no se escribe en este
   archivo ni en el repositorio.

Resultado: todos los pasos funcionan. La copia limpia responde en
http://127.0.0.1:8081 y el stack de 8080 sigue disponible en paralelo.


1. Contenedor de la copia limpia
--------------------------------
$ docker compose -f compose.clean.yaml -p symfony_clean ps

NAME            IMAGE               COMMAND                  SERVICE   CREATED          STATUS          PORTS
symfony_clean   symfony_clean-web   "docker-php-entrypoi…"   web       12 minutes ago   Up 12 minutes   0.0.0.0:8081->8080/tcp, [::]:8081->8080/tcp


2. Versiones dentro de la copia
-------------------------------
$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web php bin/console about

  Symfony
  Version              v8.1.10
  Environment          dev

  PHP
  Version              8.4.1


3. Dependencias
---------------
$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web composer install --no-interaction

Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
...
Generating autoload files
112 packages you are using are looking for funding.
Run composer recipes at any time to see the status of your Symfony recipes.
Executing script cache:clear [OK]
Executing script assets:install public [OK]
Executing script importmap:install [OK]


4. Base de datos
----------------
$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web \
    php bin/console doctrine:migrations:migrate --no-interaction

[notice] Migrating up to App\Migrations\Version20261103161301
[notice] finished in 93.4ms, used 22M memory, 1 migrations executed, 5 sql queries

 [OK] Successfully migrated to version: App\Migrations\Version20261103161301

$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web \
    php bin/console doctrine:migrations:status

| Versions   | Current  | App\Migrations\Version20261103161301 |
|            | Next     | Already at latest version            |
|            | Latest   | App\Migrations\Version20261103161301 |
| Migrations | Executed | 1                                    |
|            | New      | 0                                    |


5. Usuario de prueba
--------------------
El README indica:
  docker compose exec web php bin/console app:create-user postman postman@example.test

En la verificación la contraseña se pasó por stdin para que el comando no la
muestre ni quede en el historial:

$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web \
    php bin/console app:create-user postman postman@example.test

 Contraseña (no se mostrará):
 >

 [OK] Usuario "postman" guardado con contraseña hasheada.

Comprobación de que se guarda un hash y no texto plano:

$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web \
    php bin/console doctrine:query:dql \
    "SELECT u.id, u.username, u.email, u.password FROM App\Entity\User u"

 array(1) {
  [0]=>
  array(4) {
    ["id"]=>
    int(1)
    ["username"]=>
    string(7) "postman"
    ["email"]=>
    string(20) "postman@example.test"
    ["password"]=>
    string(60) "$2y$13$pFh4WaOLKz8ebIfabjdXG..."
  }
}

La contraseña no aparece en la base de datos: solo el hash bcrypt.


6. Comprobaciones HTTP
----------------------
Todas las peticiones se hacen contra http://127.0.0.1:8081.

$ curl -I http://127.0.0.1:8081/
GET / -> 200

$ curl -I http://127.0.0.1:8081/acerca-de
GET /acerca-de -> 200

$ curl -I http://127.0.0.1:8081/login
GET /login -> 200

$ curl -I http://127.0.0.1:8081/dashboard      (sin sesión)
GET /dashboard -> 302 http://127.0.0.1:8081/login

Login con el usuario de prueba (token CSRF extraído del formulario y cabecera
Origin, como exige el CSRF stateless):

token CSRF extraído -> longitud 10
POST /login -> HTTP/1.1 302 Found
Location: http://127.0.0.1:8081/dashboard

$ curl -I http://127.0.0.1:8081/dashboard      (con sesión)
GET /dashboard con sesión -> 200
Contenido: <h1>Bienvenido, postman</h1>

$ curl -I http://127.0.0.1:8081/logout
GET /logout -> 302 http://127.0.0.1:8081/

$ curl -I http://127.0.0.1:8081/dashboard      (después del logout)
GET /dashboard tras logout -> 302 http://127.0.0.1:8081/login

$ curl -I http://127.0.0.1:8081/ruta-inexistente
GET /ruta-inexistente -> 404


7. Colección de Postman sobre la copia limpia
---------------------------------------------
$ postman collection run "postman/collections/Symfony Login - Fase 5" \
    -e "<entorno privado con baseUrl=http://127.0.0.1:8081 y la contraseña local>"

  GET http://127.0.0.1:8081/ [200 OK]
  Pass  P01 - Home
  ...
  GET http://127.0.0.1:8081/ruta-inexistente [404 Not Found]
  Pass  404 - Ruta no encontrada

------------------------------------------------------------------------
|                         |             executed |              failed |
--------------------------+----------------------+----------------------+
|              iterations |                    1 |                   0 |
|                requests |                   11 |                   0 |
|            test-scripts |                   11 |                   0 |
|      prerequest-scripts |                    1 |                   0 |
|              assertions |                   16 |                   0 |
------------------------------------------------------------------------
| total run duration: 43.7 s                                           |
------------------------------------------------------------------------

11 peticiones, 16 aserciones y 0 fallos en la copia limpia. El entorno privado
con la contraseña queda fuera del repositorio (está cubierto por .gitignore).


8. PHPUnit sobre la copia limpia
--------------------------------
$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web \
    php bin/console doctrine:migrations:migrate --no-interaction --env=test

 [OK] Successfully migrated to version: App\Migrations\Version20261103161301

$ docker compose -f compose.clean.yaml -p symfony_clean exec -T web php bin/phpunit

PHPUnit 13.4.6 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.4.1
Configuration: /var/www/html/phpunit.dist.xml

......                                                              6 / 6 (100%)

OK (6 tests, 24 assertions)


9. El stack de desarrollo sigue intacto
---------------------------------------
$ curl -I http://localhost:8080/
symfony_app / -> 200

El contenedor symfony_app (8080) no se ha parado ni reconstruido durante esta
verificación. Los dos stacks han convivido en paralelo, cada uno con su propia
base de datos y su propio puerto.


Conclusión
----------
El README describe un procedimiento reproducible: partiendo solo del código
versionado, con Docker y Git, se instalan dependencias, se crea la base de datos
por migraciones, se crea el usuario de prueba y se verifican las rutas públicas,
el login, el dashboard protegido, el logout y el 404. Las pruebas de Postman y
PHPUnit pasan también en esa copia limpia.
