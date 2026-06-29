# Registro de decisiones cerradas (ADR)

> Resuelve **todas** las cuestiones que quedaban abiertas en los docs de
> funcionalidad, más las decisiones de **PWA** (DC-01), **naming/BGM** (DC-21),
> **infraestructura** (DC-22) y **locales** (DC-23). Cada entrada: la pregunta, la
> decisión y el porqué. Si una decisión cambia, se edita aquí y en el doc afectado.

## Transversales

### DC-01 · PWA: los frontends son instalables en móvil
**Decisión:** tanto `admin` como `app` son **PWA instalables** (Add to Home
Screen) vía `vite-plugin-pwa`: manifest + service worker, icono, splash, modo
standalone. Caché de *app shell* (carga rápida y arranque offline-light), **no**
offline completo de datos. El motor aporta el andamiaje PWA; cada juego pone su
nombre, iconos y color de tema.
**Por qué:** las quieres instalables en teléfono; con SPA Vite es coste bajo y no
obliga a SSR. (Amplía D6.)

### DC-02 · Monorepo del motor + publicación versionada
**Decisión:** el motor vive en **un solo monorepo** (`boardgame_motor/packages/{core,ui,admin-kit}`).
- **Durante el desarrollo (Fases 0–6):** el `playground` (y cualquier juego)
  consume los paquetes por **enlace local** (Composer `path` repository / npm
  workspace). **Cero infraestructura de publicación.** Es lo más limpio y sencillo.
- **Publicación versionada (Fase 7):** se activa cuando hace falta consumir el motor
  desde un repo de juego externo con versión fijada:
  - **npm** (`@bgm/ui`, `@bgm/admin-kit`) → `npm publish` por paquete a **GitHub
    Packages** (registry privado del scope `@bgm`). Sin repos extra.
  - **Composer** (`bgm/core`) → como Composer no instala un subdirectorio por tag,
    se **espeja** el paquete a un repo read-only (`git subtree split`) que lleva los
    tags, automatizado con una GitHub Action al taggear. Es la única "maquinaria", y
    es estándar.
**Por qué:** monorepo para desarrollar (una sola fuente, simple), y solo se añade la
mecánica de publicación cuando de verdad hace falta (Fase 7). Cumple "no romper webs"
porque cada juego fija versión. (Cierra D9.)

### DC-03 · La API es agnóstica de locale
**Decisión:** el prefijo de idioma (`/es`, `/eu`) vive **solo en el router del
front**. La API recibe el locale por `?locale=` o `Accept-Language` y responde
locale-agnóstica. En modo edición (admin) devuelve el objeto completo `{es,eu,…}`.
**Por qué:** evita acoplar la API a un paquete de routing localizado; SPA + API
limpias. (Cierra abierto de 01 §4 y de 04.)

## Render PNG (doc 01)

### DC-04 · Datos de la ruta `/_render`
**Decisión:** `/_render/:entity/:id` **pide los datos por la API** con un **token
de servicio de vida corta** (firmado) que inyecta el backend al lanzar Browsershot.
La ruta no es públicamente accesible.
**Por qué:** mantiene el componente como única fuente y no expone un render interno.

### DC-05 · Chromium y rendimiento
**Decisión:** Chromium headless en servidor con `noSandbox` + args de producción
(se reutiliza la config de choque). Generación **en cola** con workers acotados
(límite de Chromes concurrentes configurable).
**Por qué:** ya resuelto y probado en choque; controla memoria.

## PDF (doc 02)

### DC-06 · Motor de ensamblado: DomPDF
**Decisión:** **DomPDF** ensambla el PDF a partir de los PNG ya renderizados.
Browsershot se reserva para generar PNG (doc 01), no para el PDF.
**Por qué:** los PNG ya están; abrir Chrome otra vez para el PDF sería gasto de más.

### DC-07 · Layouts de impresión
**Decisión:** presets en `PrintLayout` (p. ej. carta **88×126 mm** con sangrado y
**marcas de corte**, counters, A4/A3, piezas por página). Publicable y ampliable
por juego.
**Por qué:** el recorte físico tiene que cuadrar; se parametriza, no se hardcodea.

## CRM (doc 03)

### DC-08 · DSL de campos (compartido con admin-kit)
**Decisión:** tipos base del esquema de campos: `text, richtext, number, boolean,
select, multiselect, image (simple + multilingüe), color, entity-ref, repeater,
group`. **El mismo renderer** sirve al `BlockEditor` (CRM) y al `ResourceForm`
(admin-kit, doc 08). Extensible con campos a medida.
**Por qué:** un único motor de formularios → añadir bloque/recurso no duplica UI.

### DC-09 · Texto rico: TipTap
**Decisión:** **TipTap** como editor de texto rico (sustituye TinyMCE de choque).
Nodos inline a medida para los "dados"/iconos de juego; sanitización en servidor.
**Por qué:** nativo de Vue, extensible para nodos custom, mejor encaje que TinyMCE.

### DC-10 · Caché de bloques-con-datos
**Decisión:** cachear el payload resuelto de la página por `(page, locale)`,
invalidado al cambiar la página/bloques o las entidades referenciadas.
**Por qué:** los bloques-con-datos consultan en cada request; la caché evita coste.

## i18n (doc 04)

### DC-11 · Rendimiento de slugs traducibles
**Decisión:** las traducciones siguen en JSON, **pero** se añade por locale una
**columna generada/almacenada con índice único** para el slug → búsqueda y
unicidad rápidas.
**Por qué:** `JSON_EXTRACT` por locale no escala; el índice sí.

### DC-12 · Fallback de idioma
**Decisión:** a nivel de campo, **fallback al locale por defecto** si falta la
traducción (configurable). En público, una entidad se muestra en un idioma si está
publicada; si se pide un slug en el idioma "equivocado", se resuelve y se hace
**301 a la URL canónica** del idioma correcto.
**Por qué:** evita huecos y duplicados de SEO.

## Auth (doc 05)

### DC-13 · Capacidades de `editor`
**Decisión por defecto:** `editor` gestiona **contenido** (CRM) y **entidades del
juego** (crear/editar), pero **no** usuarios, ajustes, backups ni borrados
destructivos. Cada juego puede ajustar el mapa de capacidades.
**Por qué:** "ayuda con algunas cosas" sin riesgo; configurable si un juego quiere más.

### DC-14 · Registro de usuarios
**Decisión:** **auto-registro público activado** con verificación de email, rol
`user`. `admin`/`editor` se asignan a mano. Toggle por juego para hacerlo
**solo-invitación**.
**Por qué:** deja la puerta lista para funciones de usuario sin obligar a nada.

## Media (doc 07)

### DC-15 · Spatie MediaLibrary con PathGenerator propio
**Decisión:** **`spatie/laravel-media-library`** con un **PathGenerator** que da
rutas predecibles (las necesitan previews y PDF). Imágenes multilingües como
colecciones por locale. Iconos inline (dados) como media con ruta estable.
Almacenamiento por defecto en **disco del droplet**; **S3/Spaces opcional** (DC-22).
**Por qué:** conversiones, colecciones y limpieza "gratis", alineado con kontuan;
el PathGenerator resuelve la única pega (rutas estables).

## Backup (doc 06)

### DC-16 · `spatie/laravel-backup`, export ahora
**Decisión:** **`spatie/laravel-backup`** (retención, incluir media, destinos S3,
programación, notificaciones). De momento **export**; restore **manual
documentado**; restore guiado más adelante. BBDD grandes → en cola.
**Por qué:** más features que `ifsnop/mysqldump-php` con menos código propio.

## Frontend base (docs 08, 09, 10, 11)

### DC-17 · Drag & drop: vue-draggable-plus
**Decisión:** **`vue-draggable-plus`** para reordenar (bloques, listas, recursos).
**Por qué:** moderno, Vue 3, lo mismo que usa kontuan.

### DC-18 · SEO de la SPA pública: prerender + sitemap
**Decisión:** **prerender en build** de las rutas públicas (plugin de prerender) +
`useHead` para meta por ruta + **sitemap** generado desde páginas/entidades
publicadas (build/cron). SSR queda descartado salvo que el prerender demuestre ser
insuficiente para páginas de entidad muy dinámicas.
**Por qué:** mantiene SPA (D6) y da indexación; SSR solo como plan B medido.

### DC-19 · DSL del admin con escotillas
**Decisión:** el CRUD declarativo (`defineResource`) cubre lo estándar y **siempre**
permite slots/overrides y caer a componentes a mano para pantallas especiales.
**Por qué:** evita que "lo declarativo" se vuelva una jaula.

### DC-20 · Traits de modelo componibles
**Decisión:** traits opt-in por entidad; formato de filtros **unificado** entre
`HasFilters`, `FiltersBar` y `defineResource`.
**Por qué:** cada entidad usa solo lo que necesita y el admin lo entiende sin código.

## Naming, infra y locales

### DC-21 · Marca y nombres de paquete: `bgm`
**Decisión:** la marca es **BGM** (BoardgameMotor). Vendor/scope **`bgm`**:
- Composer: **`bgm/core`** (backend Laravel).
- npm: **`@bgm/ui`** y **`@bgm/admin-kit`**.
- Directorios del monorepo: `packages/{core,ui,admin-kit}`.
**Por qué:** el proyecto *es* el boardgamemotor; `bgm` es corto y sin redundancias
("motor" ya está implícito).

### DC-22 · Infraestructura: un droplet DigitalOcean por juego
**Decisión:** **cada web/juego completo (api + admin + app) va en su propio droplet
de DigitalOcean.** En el droplet viven también el worker de cola y **Chromium**
(para el render PNG, doc 01). El **almacenamiento es configurable**: por defecto
**disco del droplet**; **S3 / DigitalOcean Spaces opcional** (lo soportan media —
DC-15 — y backup — DC-16 — sin cambiar código). Esquema de despliegue al estilo del
`deploy.sh` de choque.
**Por qué:** aislamiento por juego (un fallo no afecta a otros), simple de operar; el
storage queda abierto para crecer a objetos cuando convenga.

### DC-27 · Estilos: SCSS global, nada de `<style>` en los `.vue`
**Decisión:** los componentes `.vue` **no llevan bloque `<style>`**. Todo el SCSS vive
en **ficheros globales** (`scss/components/_*.scss`, `scss/layouts/…`, `scss/views/…`)
con clases **BEM**, importados una vez desde un `main.scss` por app. Exactamente como
kontuan. Los paquetes del motor exponen su SCSS vía `exports: { "./scss/*": "./scss/*" }`.
**Por qué:** elimina los atributos `data-v-…` (que añade `<style scoped>`), centraliza
estilos y reutiliza tokens; es como está montado kontuan.

### DC-28 · Componentes: mirar primero en kontuan
**Decisión:** cada vez que necesitemos un componente (o un patrón de estilo/layout),
**se busca primero en kontuan**. Si existe, se **copia y adapta**; si no, se crea nuevo.
**Por qué:** kontuan ya tiene un design system maduro y probado; reutilizar acelera y
da consistencia.

### DC-25 · Iconos: Lucide (`@lucide/vue`), siempre
**Decisión:** todos los frontends (admin y app) usan **Lucide** como única librería de
iconos. Paquete **`@lucide/vue`** (el antiguo `lucide-vue-next` está deprecado). Peer
dependency en `@bgm/ui` y `@bgm/admin-kit`; dependency en cada app.
**Por qué:** consistencia visual, una sola librería; alineado con kontuan.

### DC-26 · Mobile-first + 4 tiers responsivos
**Decisión:** CSS **mobile-first** (base móvil, `@media (min-width: …)` para ampliar).
**Nada debe superar el 100% del ancho de pantalla** (grids con `minmax(0,1fr)`,
`min-width:0`, `overflow-x:hidden` de seguridad, contenedores con scroll propio).
Cuatro tiers, con los breakpoints de los tokens:
- **Móvil estrecho** (`< $bp-sm` 480): sidebar = drawer a **100% de ancho**.
- **Móvil ancho / tablet vertical** (`$bp-sm`–`$bp-lg`): drawer de 280px.
- **Tablet horizontal / desktop** (`≥ $bp-lg` 1024): sidebar **fijo** y **colapsable a
  rail** de iconos (toggle persistido en localStorage).
- **Wide** (`≥ $bp-xl` 1280): tier amplio.

**Listados (`ResourceList`)**: tarjetas mientras el menú es hamburguesa, tabla cuando
es fijo — el cambio coincide en **`$bp-lg`**. Las tarjetas van a **1 columna** (`< $bp-md`)
y **2 columnas** (`$bp-md`–`$bp-lg`) antes de pasar a tabla.
**Por qué:** la gestión será mucho en móvil; el rail da más espacio en escritorio;
y los breakpoints de menú y de listado deben coincidir para no dar saltos raros.

### DC-24 · Migraciones: `datetimes()` en vez de `timestamps()`
**Decisión:** todas las migraciones (motor y juegos) usan **`$table->datetimes()`** y
**`$table->softDeletesDatetime()`** en lugar de `timestamps()`/`softDeletes()`.
También código, modelos y nombres de fichero **en inglés**.
**Por qué:** las columnas `TIMESTAMP` de MySQL están limitadas al año **2038**
(enteros de 32 bits); `DATETIME` llega hasta el 9999. Soporte a largo plazo.

### DC-23 · Locales por defecto: es / eu / en
**Decisión:** los locales de contenido del motor son **`es`, `eu`, `en`** (euskera
incluido), con **`es`** por defecto. Configurable por juego en `config/motor.php`.
**Por qué:** es el set que usas; el motor lo trae listo y cada juego lo ajusta.

---

## Tabla resumen

| ID | Tema | Decisión |
|---|---|---|
| DC-01 | PWA | admin y app instalables (vite-plugin-pwa), app-shell offline |
| DC-02 | Distribución | Git + tags (Composer vcs / GitHub Packages) |
| DC-03 | API locale | agnóstica; prefijo solo en el front |
| DC-04 | Render data | API + token de servicio corto |
| DC-05 | Chromium | noSandbox + cola con workers acotados |
| DC-06 | PDF | DomPDF ensambla; Browsershot solo PNG |
| DC-07 | Layouts | presets en PrintLayout (88×126, marcas de corte) |
| DC-08 | DSL campos | base común CRM + admin-kit |
| DC-09 | Texto rico | TipTap |
| DC-10 | Caché CRM | por (page, locale), invalidada por cambios |
| DC-11 | Slug perf | columna generada + índice único por locale |
| DC-12 | Fallback i18n | a default por campo; 301 a canónica |
| DC-13 | Editor | contenido + entidades; no users/ajustes/backup |
| DC-14 | Registro | público con verificación; toggle invitación |
| DC-15 | Media | Spatie MediaLibrary + PathGenerator propio |
| DC-16 | Backup | spatie/laravel-backup; export ahora, restore luego |
| DC-17 | Drag | vue-draggable-plus |
| DC-18 | SEO | prerender + sitemap; SSR plan B |
| DC-19 | Admin DSL | declarativo con slots/escape |
| DC-20 | Traits | componibles; filtros unificados |
| DC-21 | Naming | marca BGM; `bgm/core`, `@bgm/ui`, `@bgm/admin-kit` |
| DC-22 | Infra | droplet DO por juego; storage configurable (disco / S3 opcional) |
| DC-23 | Locales | es / eu / en (default es) |
| DC-24 | Migraciones | `datetimes()`/`softDeletesDatetime()` (no TIMESTAMP, año 2038); código en inglés |
| DC-25 | Iconos | Lucide (`lucide-vue-next`), siempre, en admin y app |
| DC-26 | Mobile-first | CSS mobile-first + breakpoints en tokens; sidebar admin = drawer/hamburguesa |
