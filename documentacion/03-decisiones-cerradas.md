# Registro de decisiones cerradas (ADR)

> Resuelve **todas** las cuestiones que quedaban abiertas en los docs de
> funcionalidad, más la decisión nueva de **PWA**. Cada entrada: la pregunta, la
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

### DC-02 · Distribución de paquetes: Git + tags
**Decisión:** repos Git con **tags de versión**. Composer vía `repositories`
(type vcs); npm vía Git o **GitHub Packages** (registry privado) si hace falta
scope `@boardgame`. Sin Packagist público ni npm público.
**Por qué:** lo más simple para un dev en solitario; se migra a registry privado
solo si aparece fricción. (Cierra D9.)

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
