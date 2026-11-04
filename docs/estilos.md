# Guía de estilos

Documento de la fase de estilos. Recoge los tokens, los breakpoints, los
componentes y las reglas que sigue la interfaz. La fuente de verdad es el bloque
`:root` de [`assets/styles/app.css`](../assets/styles/app.css): si este documento y
el CSS no coinciden, manda el CSS y hay que actualizar la guía en el mismo commit.

## 1. Principios

- **Modo oscuro por defecto.** Fondo muy oscuro, superficies algo más claras y texto
  con contraste alto.
- **Desktop-first.** El diseño base es de escritorio y se adapta con `max-width`.
- **Tokens antes que valores.** Los colores, tamaños, espaciados, radios y tiempos
  salen de `:root`. Un valor nuevo se añade primero como token.
- **Accesibilidad.** Contrastes altos, tamaños táctiles mínimos y foco visible.
- **Una sola fuente y una sola escala.** Google Sans (`AppFont`) y la escala
  tipográfica 1.333.

## 2. Tokens

### 2.1 Color

| Token | Valor | Uso |
|---|---|---|
| `--color-bg` | `hsl(220, 15%, 8%)` | Fondo principal. |
| `--color-surface` | `hsl(220, 13%, 14%)` | Tarjetas, nav y formularios. |
| `--color-surface-hover` | `hsl(220, 13%, 20%)` | Hover de superficies (reservado). |
| `--color-text-main` | `hsl(0, 0%, 96%)` | Texto principal y títulos. |
| `--color-text-muted` | `hsl(220, 10%, 75%)` | Texto secundario y metadatos. |
| `--color-accent-primary` | `hsl(330, 85%, 65%)` | Acento gráfico, bordes de foco y palabras destacadas. |
| `--color-accent-accessible` | `hsl(330, 90%, 75%)` | Texto rosa sobre fondo oscuro y fondo de botones. |
| `--color-accent-hover` | `hsl(330, 85%, 69%)` | Hover del botón y de los enlaces. |
| `--color-on-accent` | `hsl(330, 100%, 5%)` | Texto oscuro sobre rosa. |
| `--color-border` | `hsl(220, 10%, 25%)` | Bordes de superficies. |
| `--color-error` | `hsl(0, 80%, 72%)` | Mensajes de error sobre fondo oscuro. |

Los ratios de contraste están comentados en `app.css`. El error se añadió porque el
antiguo `#b00020` sobre el fondo oscuro no llegaba a 4.5:1.

### 2.2 Tipografía

| Token | Valor | Uso |
|---|---|---|
| `--font-family-base` | `"AppFont", sans-serif` | Fuente global. |
| `--font-weight-medium` | `500` | Enlaces de navegación. |
| `--font-weight-semibold` | `600` | Botón primario. |
| `--text-xs` | `0.563em` | Reservado. |
| `--text-sm` | `0.75em` | Texto pequeño y metadatos. |
| `--text-base` | `1em` | Texto general. |
| `--text-md` | `1.333em` | Reservado. |
| `--text-lg` | `1.777em` | `h1` en móvil. |
| `--text-xl` | `2.369em` | `h1` en tablet. |
| `--text-2xl` | `3.157em` | `h1` en escritorio. |
| `--text-3xl` | `4.209em` | Reservado. |

La escala es 1.333 (Perfect Fourth) y se calcula en `em` sobre una base de 16 px.

### 2.3 Espaciado

| Token | Valor | Token | Valor |
|---|---|---|---|
| `--space-1` | `0.25em` (4 px) | `--space-6` | `1.5em` (24 px) |
| `--space-2` | `0.5em` (8 px) | `--space-8` | `2em` (32 px) |
| `--space-3` | `0.75em` (12 px) | `--space-10` | `2.5em` (40 px) |
| `--space-4` | `1em` (16 px) | `--space-12` | `3em` (48 px) |
| `--space-5` | `1.25em` (20 px) | `--space-16` | `4em` (64 px) |

Los `em` son relativos al tamaño de fuente del elemento que los usa. En un `h1`
grande, `--space-2` ocupa más píxeles que en un párrafo; es intencionado para que el
espaciado acompañe a la escala tipográfica.

### 2.4 Radios, líneas y letras

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | `0.25rem` | Inputs. |
| `--radius-md` | `0.5rem` | Reservado. |
| `--radius-lg` | `1rem` | Botones, formulario y tarjetas. |
| `--radius-full` | `9999px` | Reservado para píldoras. |
| `--line-height-heading` | `1.2` | Títulos. |
| `--line-height-body` | `1.6` | Párrafos. |
| `--letter-spacing-heading-large` | `0.04em` | `h1`. |
| `--letter-spacing-heading` | `0.02em` | Títulos medianos. |
| `--letter-spacing-normal` | `0em` | Texto continuo. |

### 2.5 Anchos, transiciones y tamaños

| Token | Valor | Uso |
|---|---|---|
| `--max-width-heading` | `14em` | Longitud máxima de un `h1`. |
| `--max-width-content` | `48em` | Ancho máximo del contenedor (768 px). |
| `--max-width-text` | `60ch` | Longitud de línea de los párrafos. |
| `--max-width-form` | `28em` | Ancho máximo del formulario de login (448 px). |
| `--transition-base` | `0.33s ease` | Transiciones de color y fondo. |
| `--touch-target` | `2.75em` | Altura mínima táctil (44 px a 16 px). |
| `--min-height-viewport` | `100dvh` | Altura mínima del `body` en móvil. |

## 3. Breakpoints

| Breakpoint | Valor | Qué cambia |
|---|---|---|
| Escritorio | Base | `h1` a `--text-2xl`, padding de `main` con `--space-5`. |
| Tablet | `48em` (768 px) | `h1` a `--text-xl`, padding lateral `--space-4`, gap del nav `--space-3`. |
| Móvil | `30em` (480 px) | `h1` a `--text-lg`, padding de `main` `--space-5`/`--space-3`, nav con menos gap y enlaces táctiles, formulario con menos padding y botón a ancho completo. |

Las media queries usan `max-width` porque el diseño es desktop-first:

```css
@media (max-width: 48em) { /* tablet */ }
@media (max-width: 30em) { /* móvil */ }
```

`@media` no admite `var()`, así que `48em` y `30em` se repiten en el CSS. Es la
única excepción a la regla de no repetir valores fuera de `:root`, y está comentada
en `app.css`.

## 4. Layout

- `body`: columna flex, `min-height: var(--min-height-viewport)`.
- `main`: ocupa el espacio restante (`flex: 1`), centra el contenido y aplica el
  gutter lateral. En escritorio: `--space-6` arriba/abajo y `--space-5` a los lados.
- `.container`: `width: 100%`, `max-width: var(--max-width-content)`,
  `min-width: 0` y centrado con `margin-inline: auto`. No lleva padding: el gutter
  lo pone `main`, para no duplicarlo.
- `h1`: `max-width: var(--max-width-heading)` y `overflow-wrap: break-word`.
- `p`: `max-width: var(--max-width-text)`; dos párrafos seguidos se separan con
  `--space-2`.

`.container` se usa en la portada, en “Acerca de” y en el dashboard. El login no lo
usa: su `h1` y su formulario se centran directamente en `main`.

## 5. Componentes

### 5.1 Navegación (`components/nav.css`)

- Fondo `--color-surface`, `display: flex`, `flex-wrap: wrap`, centrada.
- Padding `--space-3` y gap `--space-4`.
- Enlaces con `--font-weight-medium`, color accesible y transición de `color`.
- En móvil, los enlaces tienen `min-height: var(--touch-target)` y el nav reduce
  gap y padding. Si no caben, pasan a una segunda fila.

### 5.2 Botón primario (`app.css`)

- `display: inline-flex`, contenido centrado y `min-height: var(--touch-target)`.
- Padding `--space-2` / `--space-4`, radio `--radius-lg`.
- Fondo `--color-accent-accessible`, texto `--color-on-accent`, peso
  `--font-weight-semibold`.
- Hover con `--color-accent-hover` y transición de `background-color`.
- En móvil, el botón de envío del login ocupa el 100 % del formulario.

### 5.3 Formulario de login (`components/form.css`)

- `width: 100%`, `max-width: var(--max-width-form)`, centrado y sin desbordar.
- Fondo `--color-surface`, borde de 1 px, radio `--radius-lg`.
- Labels en columna con gap `--space-2`.
- Inputs al 100 %, `min-height: var(--touch-target)` y padding
  `--space-2` / `--space-3`. El foco cambia el borde a `--color-accent-primary`.
- `.form-error` usa `--color-error`, el mismo ancho que el formulario y
  `--space-4` de separación. En el HTML lleva `role="alert"`.

### 5.4 Portada (`pages/welcome.css`)

- `.welcome-page` es una columna flex con `gap: var(--space-6)`.
- En móvil el gap baja a `--space-4`.
- Los metadatos usan `<small>`, con `--text-sm` y color muted.

### 5.5 Texto destacado

- `.accent-text` pinta una palabra con `--color-accent-primary`. Se usa en los
  `h1` de la portada y de “Acerca de”.

## 6. Reglas de escritura

1. **Todo valor de diseño nace en `:root`.** Si un color, tamaño, peso, tiempo,
   radio o ancho se repite o tiene intención de reutilizarse, se crea un token.
2. **Fuera de `:root` solo se permiten valores estructurales:** `0`, `100%`,
   `auto`, `flex: 1`, keywords (`none`, `center`, `stretch`…), los `px` de un borde
   y los breakpoints de `@media`.
3. **No se usan estilos inline** en las plantillas. El caso del error del login se
   pasó a `.form-error`.
4. **Nombres BEM** para componentes: `.site-nav`, `.site-nav__element`,
   `.login-form`, `.login-form__input`.
5. **Un archivo por responsabilidad:** base y tokens en `app.css`; componentes en
   `styles/components/`; estilos de página en `styles/pages/`.
6. **Orden de imports:** `app.css` primero y después componentes y páginas, para que
   las reglas específicas puedan sobrescribir la base.
7. **Los `@font-face` no usan tokens:** `font-family`, el rango `100 900` y la URL
   describen el archivo de fuente, no el diseño aplicado.

## 7. Accesibilidad

- **Contraste:** texto principal y muted por encima de 7:1; rosa accesible y error
  por encima de 4.5:1. El rosa principal se reserva para elementos gráficos y
  bordes, no para texto pequeño.
- **Tamaño táctil:** inputs, botón y enlaces del nav en móvil usan
  `--touch-target` (44 px).
- **Foco:** los inputs cambian de borde al enfocarse; no se elimina el foco de
  enlaces ni botones.
- **Idioma y viewport:** `<html lang="es">` y
  `<meta name="viewport" content="width=device-width, initial-scale=1">`.
- **Texto largo:** `overflow-wrap: break-word` en `h1` para evitar desbordes.
- **Error anunciado:** el contenedor del error usa `role="alert"`.

Mejora pendiente si se añaden más animaciones: respetar
`prefers-reduced-motion`.

## 8. Estructura de estilos

```
assets/
├── app.js
└── styles/
    ├── app.css                 # tokens, base, layout y botón
    ├── components/
    │   ├── form.css            # formulario de login y error
    │   └── nav.css             # navegación
    └── pages/
        └── welcome.css         # portada
```

Para añadir un componente nuevo:

1. Crear el archivo en `components/` o `pages/`.
2. Usar tokens existentes; si falta uno, añadirlo a `:root` con comentario.
3. Importarlo en `assets/app.js` después de `app.css`.
4. Comprobar 320, 768 y 1440 px.
5. Actualizar esta guía si el componente o los tokens cambian.

## 9. Checklist antes de commitear

- [ ] No hay colores, tamaños, pesos ni tiempos hardcodeados fuera de `:root`.
- [ ] Los bordes usan `1px`/`2px` y las media queries sus breakpoints.
- [ ] El componente se ve bien a 320, 375, 480, 768, 1024 y 1440 px.
- [ ] No hay scroll horizontal (`scrollWidth === innerWidth`).
- [ ] El nav no se corta y el formulario no se sale de la pantalla.
- [ ] Los estados hover y focus son visibles.
- [ ] Los textos siguen siendo los mismos que esperan las pruebas de Postman y
      PHPUnit.

## 10. Verificación

La comprobación visual se hizo con Chrome headless y Playwright sobre una copia
servida en el puerto 8081, con viewport de 320, 375, 480, 768, 1024 y 1440 px. En
todos los casos `document.documentElement.scrollWidth` coincidió con
`window.innerWidth`, es decir, sin desbordamiento horizontal.

Las pruebas funcionales (`php bin/phpunit`) y la colección de Postman siguen
pasando porque los cambios son de presentación: no se han modificado textos,
nombres de campos ni rutas.
