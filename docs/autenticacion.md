# Autenticación

## 1. Pulsar «Iniciar sesión»
- Enlace `path('app_login')` en `base.html.twig`.
- GET /login → `SecurityController::login()`.
- `AuthenticationUtils` recupera error y último usuario.
- Renderiza `templates/security/login.html.twig`.

## 2. Enviar el formulario
- POST /login con `_username`, `_password` y `_csrf_token`.
- El firewall `main` intercepta el POST antes del controlador.
- `form_login` valida el CSRF.
- `app_user_provider` carga el usuario:
  `UserRepository::loadUserByIdentifier()`.
- `UserPasswordHasherInterface` compara la contraseña con el hash.
- Si es correcto, se crea el token y se guarda en la sesión.
- Redirección a `app_dashboard`.

## 3. Ver el dashboard
- GET /dashboard.
- El firewall lee el token de la sesión.
- `access_control` exige `ROLE_USER`.
- `DashboardController` renderiza la plantilla.
- Twig muestra `user.userIdentifier`.

## 4. Credenciales incorrectas
- `BadCredentialsException`.
- Redirección a `/login`.
- `AuthenticationUtils` muestra el error genérico.

## 5. Logout
- `logout_path()` genera `/logout`.
- El firewall invalida la sesión.
- Redirección a `app_home`.
- `/dashboard` vuelve a redirigir a `/login`.

## 6. Preguntas del enunciado
- Autenticación vs autorización.
- Papel del firewall, provider y access_control.
- Hash vs comparación de texto plano.
- Persistencia de la sesión entre páginas.
- Dónde está la protección real.
- Qué pasa al entrar directamente a `/dashboard`.
- Qué es CSRF y cómo se aplica al login.