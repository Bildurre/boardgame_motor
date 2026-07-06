# EdC Motor — Plan de acción general

> Hoja de ruta por fases. Cada funcionalidad tiene su plan detallado en
> `documentacion/funcionalidades/`. Aquí va el **orden**, las **dependencias** y
> los **hitos** que cierran cada fase.

---

## Principios del plan

1. **Vertical sobre horizontal.** En vez de "primero todo el backend, luego todo
   el front", cada funcionalidad se lleva de punta a punta (modelo → API →
   componente Vue) para validarla pronto.
2. **El `playground` como banco de pruebas.** Un juego-demo mínimo dentro del
   motor que ejercita cada funcionalidad según se construye. Es lo que evita
   diseñar el motor "a ciegas".
3. **choque como contrato.** Para cada servicio que refactorizamos, choque es la
   referencia de "qué tiene que poder hacer". Lo bueno se conserva, lo que duele
   se rediseña (ver el "qué cambia respecto a choque" en cada doc de funcionalidad).
4. **Nada que rompa después.** Decisiones de esquema y de API pensadas para no
   forzar `major` constantes.

## Fases

### Fase 0 — Andamiaje del monorepo (base) ✅
**Meta:** repos, paquetes vacíos y `playground` arrancando.

- [x] Estructura del monorepo del motor (`packages/*`, `playground/`).
- [x] `core` esqueleto: composer.json, `MotorServiceProvider`, config publicable, ruta `api/motor/ping`.
- [x] `@edc-motor/ui` y `@edc-motor/admin-kit` esqueleto: package.json + barrel exports apuntando a `src` (patrón kontuan, sin build en dev).
- [x] `playground`: Laravel `api` + Vue `admin` + Vue `app` que ya consumen los paquetes por *path*/workspace (enlace local), sin publicar.
- [x] **PWA** (DC-01): `vite-plugin-pwa` en admin y app (manifest + service worker). Faltan iconos reales (los pone cada juego).
- [x] Script `dev` con `concurrently` (estilo kontuan) + `dev:front` + `build`.
- [x] Linters/formatters: Pint (api + core, config de kontuan), ESLint flat + Prettier a nivel de monorepo, Pest en `playground/api` (suite completa de Fases 0–3). Scripts: `npm run lint / lint:fix / format / lint:php / fix:php / test:api / type-check`.
- **Hito:** ✅ `npm run dev` levanta api + admin + app; la app muestra un componente del motor (`MotorBadge`/`AdminLayout`) y consume `api/motor/ping` por el proxy.

### Fase 1 — Auth, usuarios y roles ✅
**Meta:** poder entrar al admin y al panel de usuario. Base transversal del resto.
> Depende de Fase 0. Plan: `funcionalidades/05-auth-usuarios-roles.md`.

- [x] Sanctum + login/logout/registro + `me` (en `edc-motor/core`).
- [x] Roles admin/editor/user (Spatie) + middleware `motor.admin` + comando `motor:install`.
- [x] Panel de usuario (datos de cuenta + cambio de contraseña) en la `app`.
- [x] Stores Pinia de auth + cliente axios `createApi` (`@edc-motor/ui`) en admin y app; guards de router.
- [x] Verificación de email (DC-14): `Registered` + ruta firmada `verification.verify` (verifica y redirige a la app), reenvío con throttle, y cambiar el email invalida y reenvía. Aviso + reenvío en 'Mi cuenta'. *(forgot/reset password: cuando se monte el correo real del juego.)*
- **Hito:** ✅ login como admin/editor entra al admin; `user` no (403/redirección); `user` entra a su panel de cuenta.

### Fase 2 — Comportamientos de modelo + Media + i18n ✅
**Meta:** los cimientos que todas las entidades usarán.
> Plan: `funcionalidades/11-comportamientos-modelo.md`, `07-media-imagenes.md`, `04-i18n-urls-traducibles.md`.

- [x] Traits: published/draft, soft-delete + restore, filtros. *(coste: cuando un juego lo pida.)* — **2a**
- [x] Media: imagen simple (Spatie MediaLibrary + `MotorPathGenerator` de rutas predecibles, `ImageUpload`). *(Imagen multilingüe: cuando el CRM/previews la pidan, doc 07.)* — **2b**
- [x] i18n: campos traducibles, slugs traducibles (admin y público), resolución de locale en API, selector de locale de contenido en front (`TranslatableInput`). — **2a + 2c**
- [x] CRUD scaffolding del admin-kit: `useResource`, `ResourceList`, `FiltersBar` + vistas de la entidad demo. *(`ResourceForm` dirigido por DSL llegará con el editor de bloques del CRM, DC-08.)* — **2c**
- **Hito:** ✅ entidad demo `House` con CRUD completo en admin —
  traducible es/eu/en, **con imagen**, publicable, filtros, soft-delete + restaurar— **verificado en navegador headless**; slug traducible resuelve en público.

### Fase 3 — Render de componentes a PNG ✅
**Meta:** capturar el componente visual de una entidad a imagen, fiable y fácil de regenerar.
> Plan: `funcionalidades/01-render-png.md`. Es la base del PDF.

- [x] Infra Browsershot (`PreviewRenderer`, config única: noSandbox, scale, waits) + ruta desnuda `/_render/:entity/:id` en `app` con token de servicio (DC-04) y señal `__bgmRenderReady`.
- [x] `PreviewableContract` + `PreviewRegistry` (facade `Previews`) + `HasPreviewImage` + `PreviewService` (PNG versionado por locale, borra el anterior, limpieza de huérfanos, status).
- [x] `GeneratePreviewJob` (cola, único por entidad+locale; DC-05: workers acotan Chromes) + `preview:manage` genérico (status/generate/regenerate/delete/clean, `--type --id --locale --sync --force --dry-run`).
- [x] Invalidación automática declarativa (`previewTriggerFields`; `is_published` no dispara; imagen nueva invalida desde el controlador).
- [x] Playground: `Character` y `Scheme` renderizables; cartas en `@playground/shared` (fuente única admin/app, D8); `PreviewPanel` en el admin (ver PNG + regenerar).
- [x] **Gestor de previews** (`PreviewManager`, admin-kit) + sección "Imágenes" del admin: estado por tipo, lotes (generar pendientes / regenerar todo / borrar todo), acciones por entidad y limpieza de huérfanos, sobre los endpoints `api/admin/previews/*`.
- **Hito:** ✅ editar la entidad demo regenera sus PNG (es/eu/en) por cola sin comandos; `preview:manage regenerate --type=character` en lote funciona; cero glob de CSS y cero base64.

### Fase 4 — Generación de PDF ✅
**Meta:** PDF recortables a partir de los PNG, y regenerar = trivial.
> Plan: `funcionalidades/02-pdf.md`. Depende de Fase 3.

- [x] `GeneratedPdf` **polimórfico** (permanentes + temporales, estado pending/ready/failed con error visible) + ensamblado DomPDF desde los PNG (genera al vuelo las previews que falten).
- [x] `PdfExportRegistry` (facade `Pdfs`) + `PdfExport`: el juego describe el contenido (colección por entidad / global / individual) y opcionalmente su vista Blade; el motor pone el pipeline. `PrintLayout` con presets (card 88×126, counter) en config publicable (DC-07); rejilla genérica `motor::pdf.grid` con marcas de corte (posicionado absoluto en mm: DomPDF fiable).
- [x] Colección temporal por usuario (`/api/pdf-collection`: ítems con copias, snapshot en payload, `expires_at`) + `pdf:cleanup` programable.
- [x] Multi-idioma (un PDF por locale). API `/api/admin/pdfs` (+ catálogo `GET /admin/pdfs/exports`) + descarga pública de permanentes + `PdfManager` en admin-kit: **toda la gestión centralizada en la sección PDF del admin** (catálogo dirigido por los exports registrados; nada en los singles). Layout `card` por defecto = **Magic 63×88** (9/A4); presets propios del juego con `Pdfs::layout()` y **tamaño por export** vía `layout()` (playground: personajes al doble `card-big`, argucias en Magic, y `house-tokens` con 9 tokens redondos de 40 mm por casa).
- **Hito:** ✅ generar y regenerar desde el admin con un clic (fichero versionado, borra el anterior); PDF temporal a la carta cubierto por API + tests (la UI pública llega con la Fase 6). Verificado con PDF real (4 cartas por A4 con marcas de corte).

### Fase 5 — CRM de páginas y bloques ✅ (núcleo)
**Meta:** construir la web pública por bloques, y añadir un bloque nuevo sin sufrir.
> Plan: `funcionalidades/03-crm-paginas-bloques.md`.

- [x] Modelos Page/Block (jerárquicos, traducibles, reordenables), SEO, home única, printable/indexable. Bloques SIN columnas por tipo: todo en `settings` JSON.
- [x] **`BlockType` + `BlockTypeRegistry`** (facade `Blocks`): un tipo se declara UNA vez (esquema de campos DC-08) y de ahí salen formulario, validación, localización y `resolveData`. Motor: header/text/text-card/quote/cta; juego: `Blocks::register(...)`. `HtmlSanitizer` en servidor (DC-09). Caché por (página, locale) invalidada al editar (DC-10).
- [x] Editor en admin-kit: `SchemaFields` (renderer del DSL) + `PageBlocks` (paleta, drag con vue-draggable-plus DC-17, modal generado). Playground: vistas de páginas + i18n.
- [x] Render público en `app`: nav de páginas publicadas, home del CRM, `PageView` por slug traducible con redirección a la canónica (DC-12); `blockRegistry` = componentes del motor (@edc-motor/ui) + los del juego.
- [x] Bloque índice automático (`index`) y **PDF de páginas imprimibles** (export `pages` del motor con vista `motor::pdf.page`).
- [x] Panel derecho en todo el admin (patrón kontuan): páginas (acciones + toggles + **sus bloques resumidos**), bloques (acciones + **contenido por campo**) y listados de entidades (`EntityPanel`: todas las acciones, info y PNG por idioma; en la tarjeta solo editar + abrir).
- [x] **Plantillas de página por juego**: catálogo en `motor.content.templates` (el juego añade las suyas en su AppServiceProvider), select en el modal del admin (`GET /admin/pages/templates`) y `templateRegistry` en la SPA (la clave viaja en el payload; demo `landing` a lo ancho en el playground).
- [x] **Imagen multilingüe**: `Field::image()->translatable()` en todos los bloques del motor — una URL por locale con fallback al default en el render (`localizeSettings`); editor `TranslatableImage` (ui-kit) con el selector de locale de kontuan.
- [x] **Colores e imagen de fondo del CRM público** (patrón CDL): `background_image` por página (capa fija `PageBackground` atenuada por tema, 0.2 claro / 0.1 oscuro + grayscale), color de bloque como **tinte semitransparente** (`--block-tint` 20%/12% vía color-mix) y tarjetas de bloque translúcidas con blur.
- [x] DSL anidado: `Field::group/repeater/entity` con validación, saneado y localización recursivos; editor del admin con filas (añadir/quitar/reordenar) y buscador de entidad; bloques demo `faq` (motor) y `featured-house` (playground) en el seeder.
- **Hito:** ✅ página con bloques (incluidos los con-datos del playground: rejilla de personajes y casas-argucias), reordenable, traducible, publicada y visible en público con URL traducible. **Añadir un bloque = una clase + un componente Vue.**

### Fase 5.5 — Configuración de la web ✅
**Meta:** que el admin configure la identidad y apariencia de la web pública sin tocar código.

- [x] Módulo de ajustes del motor: tabla `settings` (JSON por clave), servicio `SiteSettings` cacheado, `GET /api/site` público y `GET/PUT /api/admin/settings/site` (validación completa).
- [x] Página **Configuración** en el admin: título y descripción (traducibles), logo SVG/PNG y favicon (subidos al momento), **acento fijo o ALEATORIO estilo CDL** (lista de colores candidatos), fuentes de títulos y de texto (catálogo `motor.site.fonts`, ampliable por juego) con vista previa, y texto del pie.
- [x] Aplicación en la SPA pública (`stores/site.ts`): título del documento (`página · sitio`), favicon, fuentes por variable CSS (`--font-headings`/`--font-body`), footer, logo en la barra (el SVG viaja **inlineado** en el payload y hereda el acento vía currentColor) y acento con tonos derivados por `color-mix`. En modo aleatorio se sortea al cargar **y se re-sortea en cada navegación** (`router.afterEach`), recoloreando también el logo — el efecto logo-path de CDL.
- [x] **Webfonts elegibles + font uploader**: 10 familias woff2 en `public/fonts` del API (catálogo `motor.site.fonts` con `{label, stack, files}`), servidas con CORS por `GET /api/site/fonts/{path}`; @font-face generados por la SPA (y por el admin para las vistas previas); subida de fuentes propias desde Configuración (`custom_fonts`).
- **Hito:** ✅ cambiar título/logo/fuentes/acento desde el admin y verlo aplicado en la web al recargar; con el modo aleatorio, cada visita y cada navegación estrenan color.

### Fase 5.6 — Gestión de usuarios y permisos ✅
**Meta:** crear usuarios (editores y demás) desde el admin y separar lo que ve cada rol.

- [x] **Permisos del motor** (Spatie vía Gate, doc 05): `manage-game` (entidades del juego, iconos, PNG, PDF), `manage-web` (CRM + configuración) y `manage-users`; reparto por rol en config (`admin` todo, `editor` solo `manage-game`) y sincronía única en `MotorAuth::syncRolesAndPermissions()` (instalador, seeder y tests). Rutas protegidas con `can:` en el motor y en el juego.
- [x] **CRUD de usuarios** en el motor (`/api/admin/users`): listar con búsqueda, crear con rol, editar (contraseña opcional) y borrar; guardas de no borrarse ni cambiarse el rol a uno mismo.
- [x] **Admin SPA**: vista Usuarios (filas + panel derecho kontuan + modal), nav y rutas filtradas por permiso (`auth.can()` + guard del router), permisos en el payload de `/auth/me`.
- **Hito:** ✅ un editor entra al panel y solo ve/gestiona el juego (casas, argucias, personajes, iconos, imágenes, PDF); páginas, configuración y usuarios son solo de admin — verificado en API (403) y en SPA (nav + redirección).

### Fase 6 — Backup, web pública y panel de usuario extensible
**Meta:** rematar lo transversal y dejar ganchos de extensión por juego.
> Plan: `funcionalidades/06-backup-bbdd.md`, `10-web-publica-y-panel-usuario.md`.

- [x] Backup BBDD (doc 06, DC-16): `spatie/laravel-backup` configurado por el motor (`MotorBackup::applyConfig()` desde `motor.backup`; SQLite como fichero en el zip, media opcional), API `/api/admin/backups` (crear/listar/descargar/borrar, `manage-web`) y vista **Copias** en el admin. La copia AUTOMÁTICA se configura desde esa vista (activada, frecuencia diaria/semanal, día, hora, retención — `BackupSettings` + `PUT /api/admin/backups/schedule`) y la programa el motor (`MotorBackup::schedule()`): el juego solo necesita el cron de `schedule:run`.
- [x] Andamiaje de la web pública (doc 10): router con **prefijo de locale** (`/es`·`/eu`·`/en`, el cambio de idioma conserva la entidad y redirige a la canónica, DC-12), vue-i18n para los textos de la interfaz, **SEO** con `useHead` de @edc-motor/ui (title/description/canonical/hreflang), **sitemap.xml** del motor (páginas del CRM + entidades registradas con la facade `Sitemap`), **prerender en build** (`npm run prerender`, DC-18) y **listados de entidades genéricos** (patrón índice+detalle por slug configurado en el `entityRegistry` de la app; playground: personajes y casas).
- [x] Panel de usuario extensible (doc 10): `AccountLayout` con menú lateral y una child route por sección registrada en `src/account/registry.ts` — el motor aporta Mis datos y Contraseña (doc 05) y el juego cuelga las suyas; el playground engancha **"Para imprimir"** (colección PDF temporal del doc 02: añadir cartas, copias, generar y descargar). Vistas de auth/cuenta traducidas (vue-i18n).
- **Hito:** ✅ backup descargable desde admin; web pública navegable con locale/SEO/sitemap/prerender; el playground rellena un "slot" del panel de usuario (Para imprimir) — PDF generado y descargado de punta a punta.

### Fase 7 — Publicación de paquetes y endurecido ✅
**Meta:** dejar el motor consumible por versión y documentado.

- [x] Distribución (DC-33): monorepo etiquetado (`vX.Y.Z`, versión de tren para los 3 paquetes), consumo por clon/submódulo al tag — Composer con repositorio `path` (`edc-motor/core` por versión) y npm con `file:` (paquetes fuente compilados por el Vite del juego). **Prueba de consumo desde un repo externo**: `tools/consumo-externo/probar-consumo.sh` (composer install real + vite build de una app externa con @edc-motor/ui y @edc-motor/admin-kit).
- [x] Versionado **0.1.0** en los 4 manifiestos, `CHANGELOG.md` en raíz + por paquete (Keep a Changelog), y guía nueva `guia-arrancar-un-juego-nuevo.md` (esqueleto, backend, frontends, checklist del primer día, cómo etiquetar).
- [x] CI en GitHub Actions (`.github/workflows/ci.yml`): job frontend (npm ci + eslint + vue-tsc + build app/admin) y job backend (PHP 8.4 + Pint api/core + Pest con SQLite en memoria); corre en push a main, tags `v*` y PRs. La suite Pest (111 tests) cubre auth/roles, entidades, previews, PDF (catálogo + colección usuario/invitado), CRM, settings, backups y web pública.
- **Hito:** ✅ el consumo por versión desde un proyecto externo está probado con script reproducible; con el playground de plantilla y la guía nueva, un juego nuevo arranca en < 1 día.

### Fase 8 (posterior) — Migración de choque
Fuera del alcance inicial. Cuando el motor esté terminado, choque pasa a ser el
primer juego sobre el motor. Plan propio cuando lleguemos.

## Dependencias entre fases

```
0 ─► 1 ─► 2 ─► 3 ─► 4
            └► 5 ─► 6 ─► 7 ─► (8)
```

- 3 y 5 dependen de 2 (traits, media, i18n).
- 4 depende de 3 (PNG antes que PDF).
- 5 puede empezar en paralelo a 3/4 una vez cerrada la 2.

## Cómo trabajaremos cada funcionalidad

Para cada una, su doc en `funcionalidades/` sigue el mismo guión:

1. **Qué hace** y alcance.
2. **Qué hay hoy en choque** (lo que se conserva / lo que duele).
3. **Diseño nuevo**: modelo de datos, API, servicios, componentes Vue.
4. **Frontera motor ↔ juego** y puntos de extensión.
5. **Cómo se construye** (pasos) y **hito de aceptación**.
6. **Decisiones (cerradas)** (ver `03-decisiones-cerradas.md`) **y riesgos.**
