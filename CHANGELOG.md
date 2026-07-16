# Changelog — EdC Motor

El motor se versiona **en tren**: `edc-motor/core`, `@edc-motor/ui` y `@edc-motor/admin-kit`
comparten número de versión y se etiquetan juntos con un tag `vX.Y.Z` en este
repositorio. Cada paquete tiene su propio `CHANGELOG.md` con el detalle:

- [`packages/core/CHANGELOG.md`](packages/core/CHANGELOG.md) — backend Laravel (`edc-motor/core`).
- [`packages/ui/CHANGELOG.md`](packages/ui/CHANGELOG.md) — componentes públicos (`@edc-motor/ui`).
- [`packages/admin-kit/CHANGELOG.md`](packages/admin-kit/CHANGELOG.md) — kit del admin (`@edc-motor/admin-kit`).

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/) y el
versionado, [SemVer](https://semver.org/lang/es/) (mientras estemos en `0.x`,
los cambios de API pueden llegar en versiones menores).

## [Sin publicar]

- **Cabecera pública fija SIEMPRE visible y barra derecha contextual fija
  por debajo, con asa propia** (`@edc-motor/ui` + cascarón): la cabecera de
  la web pública deja de auto-ocultarse al scrollear (fuera el translateY y
  su listener de scroll: fija arriba y siempre usable), y `AppRightSidebar`
  deja de ser una columna sticky que scrolleaba con la página y "se acababa"
  al llegar al pie — ahora es fija desde el borde inferior de la cabecera
  (`--app-right-sidebar-top`, la fija el cascarón por breakpoint) hasta
  abajo, por debajo de ella (z 40 < 50), en TODOS los anchos. En ancho,
  desplegada, el cascarón le hace hueco (padding-right con transición de
  contenido y pie; la cabecera no lo necesita: la barra no la tapa); en
  estrecho sigue el drawer superpuesto con telón bajo la cabecera (que
  queda visible y clicable), click fuera y Escape. El botón Funnel sale del
  header: la barra trae su propia ASA anclada al costado (Funnel cerrada /
  X abierta), asomando justo bajo la cabecera, nueva prop `openLabel`. La
  API de `useAppRightSidebar()` no cambia. **Migración del cascarón**:
  copiar de `plantilla/app/` — `src/App.vue`,
  `src/components/AppHeader.vue`, `src/assets/scss/main.scss` y
  `src/assets/scss/components/_app-header.scss` (las claves i18n
  `nav.filters`/`nav.closeFilters` se conservan: App.vue las pasa como
  labels del asa).
- **`SortToggles` sueltos estilo action-button** (`@edc-motor/ui`): fuera el
  grupo segmentado con borde; cada toggle es un botón limpio e individual, y
  el color del activo distingue además ascendente (tinte suave de acento) de
  descendente (acento relleno).
- **Grid de entidades del admin hasta cinco columnas, con escalera densa**
  (`@edc-motor/admin-kit`): `BaseGrid` gana el escalón genérico `xl`
  (`$bp-xl`, 1280px de ancho real del contenedor `content`) para `cols`, y
  el preset `cards` escala 1 → 2 → 3 → 4 → 5 con una escalera densa propia
  medida sobre el contenedor (3/4/5 columnas a 570/660/750px): con el marco
  del admin (nav + panel derecho), las 5 columnas entran a ~1400px de
  viewport en vez de a ~1930.

## [0.4.11] — 2026-07-15

- **Grupos plegables en el menú del admin** (`@edc-motor/admin-kit`): nuevo
  `NavGroup` para el slot `#nav` del `AdminLayout` — cabecera con icono +
  etiqueta + chevron que despliega/pliega sus hijos (mezclable con nav-item
  sueltos), plegado persistido en `localStorage` por clave de grupo (por
  defecto plegado) y auto-despliegue con resalte cuando la ruta actual es de
  un hijo (prop `active`). Con el sidebar colapsado a carril de iconos los
  hijos se muestran siempre. El playground agrupa así sus taxonomías bajo
  "Juego". De propina, en móvil el drawer del menú ya solo se cierra al tocar
  un enlace (no con cualquier click).
- **Borde por entidad en `EntityCard`** (`@edc-motor/admin-kit`): nueva prop
  `accentColor` — el borde se tiñe con el color de la entidad (p. ej. su
  facción): mezclado con el borde del tema en reposo (sutil en claro y
  oscuro), más presente al hover y pleno en la seleccionada. Sin la prop,
  todo como antes. El playground lo usa en el index de casas (`item.color`).

## [0.4.10] — 2026-07-14

- **Retoques del `EntityCard`** (`@edc-motor/admin-kit`): la franja media
  pierde el fondo, el lápiz de editar pasa a verde (`$success`) y header y
  content respiran menos entre sí (un escalón menos de padding en la
  divisoria).
- **Previews PNG con fondo transparente.** El `PreviewRenderer` del core ya
  captura con `hideBackground()` (omite el fondo por defecto de Chromium),
  pero la ruta `/_render` del cascarón pintaba el fondo del tema en
  `html`/`body` y el PNG salía opaco. `RenderView.vue` fuerza ahora
  `background: transparent` en `html`/`body`: el fondo lo decide el
  componente de cada entidad — las plantillas que pintan el suyo (cartas,
  héroes) salen igual que antes; las que no (contadores/tokens redondos),
  con canal alfa de verdad en las esquinas. **Migración del cascarón**:
  copiar de `plantilla/app/` `src/views/RenderView.vue` y regenerar las
  previews afectadas.

## [0.4.9] — 2026-07-13

- **Fix** (`@edc-motor/ui`): al desplegar un `BaseSelect`/`SearchSelect`
  dentro de un modal, el panel quedaba recortado por el overflow del cuerpo
  y le añadía scroll fantasma (el scrollbar cambiaba el ancho interior y
  disparaba reflows del modal). El panel abierto vuela ahora a la top layer
  del navegador (atributo `popover`, nuevo composable interno
  `useDropdownPanel`): se superpone a cualquier overflow sin tocar el
  layout, sigue pegado al trigger al scrollear (también fuera de modales) y
  Escape con el panel abierto cierra solo el desplegable, no el modal.
- **Barra derecha contextual de la web pública** (`@edc-motor/ui`):
  `AppRightSidebar` + `useAppRightSidebar()`, con la MISMA mecánica que la
  del admin-kit (registro por vista con token y Teleport a
  `#app-right-sidebar-target`; columna junto al contenido en ancho, drawer
  superpuesto con telón + Escape en estrecho). Se despliega con el nuevo
  botón Funnel del header del cascarón, que solo aparece si la vista actual
  ha registrado contenido (sus selects de filtros). **Migración del
  cascarón**: copiar de `plantilla/app/` `src/App.vue`,
  `src/components/AppHeader.vue`, `src/assets/scss/main.scss` y
  `src/assets/scss/components/_app-header.scss`, y añadir a los tres
  `src/i18n/locales/*.json` las claves `nav.filters` y `nav.closeFilters`.
- **Retirado `FiltersModal`** (`@edc-motor/ui`, salió en 0.4.8) y el botón
  "Filtros" del `IndexToolbar` (props `showFilters`/`activeCount`/
  `filtersLabel` y emit `open-filters`): los filtros de los index viven
  ahora en la barra derecha (la del admin-kit en el admin;
  `AppRightSidebar` en la web pública).
- **Cascarón (admin): los filtros del listado, en el panel derecho.**
  `PagesListView` enseña sin selección el "selecciona…" + separador + los
  selects de filtros (aplican en vivo); con selección, el botón
  "← Volver a los filtros" (deselecciona) + separador + el panel de la
  card; y un click en la zona vacía del contenido también deselecciona
  (nuevo `useCardDeselect` del admin-kit). `ListFiltersModal` desaparece y
  `ListToolbar` queda en búsqueda + toggles de orden. **Migración del
  cascarón**: copiar de `plantilla/admin/`
  `src/views/pages/PagesListView.vue` y `src/components/ListToolbar.vue`,
  BORRAR `src/components/ListFiltersModal.vue`, copiar
  `src/assets/scss/views/_cards.scss` y, en los tres
  `src/i18n/locales/*.json`, sustituir `common.clearFilters` por
  `common.backToFilters`.
- **Index de entidades más densos** (`@edc-motor/admin-kit`): el preset
  `cards` del `BaseGrid` escala 1 → 2 → 3 → 4 columnas con el ancho real
  del contenedor `content`, y la franja media del `EntityCard` pasa a
  cuadrada (`aspect-ratio: 1/1`) con la imagen contenida
  (`object-fit: contain`): más pequeña y entera, sin deformar ni recortar.

## [0.4.8] — 2026-07-13

- **Sistema de filtros unificado de los index** en `@edc-motor/ui`:
  `IndexToolbar` (búsqueda con lupa a la derecha + toggles de ordenación +
  botón "Filtros" con badge, responsive por container query propia),
  `SortToggles` (fecha latest ⇄ oldest y alfabético name ⇄ name_desc) y
  `FiltersModal` (filtros en vivo sin guardar, grid de 1 → 2 → 3 columnas
  según el ancho del modal, "Quitar filtros" + "Cerrar"). El cascarón adopta
  el patrón: `UsersView` (búsqueda + toggles; `GET /admin/users` del core
  acepta ahora `?sort` con el contrato de los index, alfabético por defecto)
  y `PagesListView` (búsqueda + filtro de estado en el modal; el árbol no se
  reordena), con los wrappers `ListToolbar`/`ListFiltersModal` que ponen los
  textos i18n del admin. **Migración del cascarón**: copiar de
  `plantilla/admin/` `src/components/ListToolbar.vue`,
  `src/components/ListFiltersModal.vue`, `src/views/users/UsersView.vue` y
  `src/views/pages/PagesListView.vue`, y añadir a los tres
  `src/i18n/locales/*.json` las claves `common.filters`,
  `common.clearFilters`, `common.sort.*` y `pages.filters.*`.
- **Controles de formulario compactos** (`@edc-motor/ui` y admin-kit): altura
  de inputs, selects, buscadores y botones de 40px a 36px, padding 8px/10px
  (la paginación se queda a 32px, control secundario).
- **Fix** (`edc-motor/core`): la búsqueda de `HasFilters` hace el LIKE de
  cada campo de `$searchable` sobre el json del locale activo (antes
  mezclaba locales al buscar sobre el json crudo).

- **Fix**: guardar una entidad renderizable con la cola `sync` colgaba la
  petición generando la preview inline (y podía acabar en 500) — ahora se
  difiere a después de la respuesta. La plantilla trae
  `QUEUE_CONNECTION=database`; en juegos existentes, ponerlo en `api/.env`.

## [0.4.7] — 2026-07-12

- **`BasePagination`** en `@edc-motor/ui` (controles de página para los
  listados) y regla global de iconos del wysiwyg a 1.2x el tamaño del texto
  en cualquier render (los paneles sin `.rich-content` los pintaban gigantes).

## [0.4.6] — 2026-07-12

- **`BaseSelect` personalizado** (`@edc-motor/ui`): el `<select>` nativo pasa
  a ser un dropdown propio (trigger + panel con la estética del SearchSelect)
  con teclado y aria completos y la MISMA API: los consumidores no cambian.
- Regla base de ui: todo lo clickable lleva `cursor: pointer` salvo
  deshabilitado.
- El `?sort` del catálogo público acepta también `oldest` (id ascendente).

## [0.4.5] — 2026-07-12

- **Ordenación en el catálogo público** (`edc-motor/core`): el modo lista de
  `GET /api/catalog/{key}` acepta `?sort=name|name_desc|latest` (nombre del
  locale activo asc/desc; default id desc). El modo `random` lo ignora.

## [0.4.4] — 2026-07-12

- **Catálogo público genérico**: `GET /api/catalog/{key}` sirve cualquier
  entidad del registry de previews sin auth y solo publicada — modo lista con
  paginación y `?search`, modo `?mode=random&count=N` y `?exclude` para los
  singles. Ítem `{id, name, slug|null, preview|null}`
  (`Edc\Core\Previews\CatalogItem`, compartido con el bloque `related`).
- **Bloque `related`** (primer bloque `data` del motor): rejilla de entidades
  relacionadas de cualquier clave del registry de previews — título/subtítulo,
  entidad (opciones en vivo del registry), modo `latest|random`, `count`
  (1..12) y botón opcional al índice. En `@edc-motor/ui` lo pinta
  `BlockRelated`; los enlaces se resuelven con el mapa que la app provee vía
  `catalogRoutesKey`. Requiere versión nueva de `edc-motor/core` y
  `@edc-motor/ui`.
- **`PreviewGrid`** (`@edc-motor/ui`): rejilla presentacional de previews del
  catálogo (paginación prev/next, slots `item`/`actions`/`empty`, fallback
  con el nombre cuando el PNG no está generado) para los índices públicos de
  los juegos. **Migración del cascarón**: copiar de `plantilla/`
  `app/src/main.ts` y `app/src/entities/catalogRoutes.ts` (provide de
  `catalogRoutesKey`, necesario para los enlaces del bloque `related`).

## [0.4.3] — 2026-07-11

- **Tarjetas de entidad del admin**: `EntityCard` gana `editable`/`edit` (botón
  de editar en la propia tarjeta, para entidades sin vista single) y la franja
  del emblema queda reservada a entidades con imagen o preview — las
  taxonomías ya no pintan monograma.

## [0.4.2] — 2026-07-10

- **Bloque CTA**: botones a cuerpo 18, tarjeta un poco más transparente
  (50%) y la imagen sangra hasta el borde de la tarjeta según su posición
  (en columnas toca arriba, abajo y su lateral). La anchura `narrow` de los
  bloques sube a 800px. Requiere versión nueva de `@edc-motor/ui`.
- **Pie de página con wysiwyg**: el campo de Configuración pasa de textarea a
  editor de texto rico y la web pública pinta el HTML (saneado por lista
  blanca en `edc-motor/core`, como los bloques) con la escala discreta del
  pie. Requiere versión nueva de `edc-motor/core`. **Migración del
  cascarón**: copiar de `plantilla/`
  `admin/src/views/settings/SettingsView.vue`, `app/src/App.vue` y
  `app/src/assets/scss/main.scss`.
- **Header afinado**: el logo se acota a 34 → 44 → **50px** (antes subía a
  68) y las acciones pasan a elementos sueltos — sin barras separadoras ni
  grupos, con un único gap; descargas, salir y entrar/usuario sin caja ni
  iconos, en color de texto y color solo al hover (acento; rojo en salir).
  **Migración del cascarón**: copiar de `plantilla/`
  `app/src/assets/scss/components/_app-header.scss` y
  `app/src/components/AppHeader.vue`.

## [0.4.1] — 2026-07-09

- **El header crece con el logo**: la línea 1 de la cabecera pública ya no es
  fija (56px); mide el alto del logo + 22px en cada breakpoint, con el resto
  de elementos centrados verticalmente y la barra lateral móvil y el hueco
  del contenido siguiéndola (antes el logo ancho se salía del header).
  **Migración del cascarón**: copiar
  `app/src/assets/scss/components/_app-header.scss` de `plantilla/`.
- **Scripts de mantenimiento en la plantilla**: los juegos nuevos nacen con
  `update-motor.sh <version>` (sube los paquetes composer/npm del motor, migra
  y limpia cachés), `copiar-plantilla.sh [-t vX.Y.Z] <rutas...>` (trae archivos
  del cascarón desde `plantilla/` del monorepo, para las notas de "migración
  del cascarón" del changelog) y `claude.sh --start/--finish <rama>` (flujo de
  ramas de Claude).
- **Fix de la plantilla (Vite)**: en los juegos que consumen los paquetes
  desde npm (no enlazados), el optimizador de dependencias de Vite
  pre-empaquetaba `@edc-motor/ui` y `@edc-motor/admin-kit` pero externalizaba
  sus `.vue`, duplicando los singletons de los composables (toast, confirm,
  panel derecho): el panel no se abría al seleccionar y los confirms/toasts no
  salían. Los `vite.config.ts` de la plantilla (admin y app) añaden
  `optimizeDeps.exclude` para servir los paquetes como fuente. **Migración de
  juegos existentes**: añadir a `admin/vite.config.ts`
  `optimizeDeps: { exclude: ['@edc-motor/admin-kit', '@edc-motor/ui'] }` y a
  `app/vite.config.ts` `optimizeDeps: { exclude: ['@edc-motor/ui'] }`.

## [0.4.0] — 2026-07-07

- **Bloques anidados** (un nivel) con índice automático **indentado** y
  tarjetas hijas sangradas en el admin.
- Imagen de los bloques texto/CTA: **modo de escalado** (contener / cubrir /
  rellenar, al alto del texto de al lado) y **reparto de columnas** (1:1 …
  4:3). Subidas hasta 10 MB.
- **Subtítulo en todos los bloques**; ningún título es obligatorio. Título h1
  solo en la cabecera (h2 en el resto, nunca justificado).
- Tipografía con tokens: texto 16 · subtítulo 20 · título 28 (cabecera
  32/24); anchura por defecto `wide` **~1200px**. Wysiwyg con márgenes entre
  elementos, escala h3–h6 y sin párrafos vacíos.
- El single de página recupera el **panel de la Página** en la barra derecha
  (acciones + flags + slugs) cuando no hay bloque seleccionado.
- El logo del header de la web crece con el ancho: 34px en estrecho y hasta
  el doble (68px) en pantallas anchas.

## [0.3.1] — 2026-07-07

- Fix: la subida de imágenes rechazaba los SVG ("debe ser una imagen");
  vuelven a admitirse y se guardan saneados (sin scripts ni handlers).

## [0.3.0] — 2026-07-07

- **Logo traducible** en la configuración de la web: uno por idioma desde el
  admin (`TranslatableImage`), con fallback al locale por defecto y el SVG
  inlineado por idioma (currentColor hereda el acento). El formato antiguo
  (URL única) se sigue aceptando.
- **Subidas de imagen sin huérfanos** (logo, favicon, fondos e imágenes de
  bloques): se guardan con el nombre original del fichero y al sustituir o
  quitar una imagen la anterior se borra del disco.

## [0.2.0] — 2026-07-06

### Cambiado

- **Renombrado del vendor/scope a `edc-motor`** (DC-21 revisada): el paquete
  Composer pasa de `bgm/core` a **`edc-motor/core`** (namespace PHP
  `Edc\Core`) y los npm a **`@edc-motor/ui`** y **`@edc-motor/admin-kit`**.
  Migración de un juego existente: actualizar `composer.json`/`package.json`,
  los imports (`@bgm/` → `@edc-motor/`), el namespace en `config/motor.php` y
  las clases propias, y las clases CSS `bgm-*` → `edc-*`.
- **Licencia GPL-3.0-only** y publicación en registros públicos: Packagist
  (`edc-motor/core`, vía el repo split `bildurre/edc-core`) y npmjs
  (org `edc-motor`). El consumo por clon hermano deja de ser necesario.
- **`plantilla/`**: esqueleto mínimo de juego (api + admin + app +
  `packages/shared`) con toda la infraestructura del motor funcionando y SIN
  entidades demo, cubierto por los gates del monorepo. `tools/crear-juego.sh`
  ahora genera los juegos desde ella, con dependencias por versión de los
  registros (sin carpeta `motor/` hermana).

## [0.1.0] — 2026-07-05

Primera versión etiquetada: el resultado de las Fases 0–7 del
[plan de acción](documentacion/02-plan-de-accion.md). Incluye auth y roles,
comportamientos de modelo + media + i18n, render de componentes a PNG,
generación de PDF (catálogo + colección de usuario/invitado), CRM de páginas
y bloques, configuración de la web, gestión de usuarios, backup de BBDD,
web pública (locale/SEO/sitemap/prerender, descargas) y panel de usuario
extensible. Ver el changelog de cada paquete.

Herramientas del monorepo:

- `tools/crear-juego.sh <destino> [ruta-al-motor]` — genera un proyecto de
  juego limpio a partir del playground (api + admin + app + packages/shared,
  tooling raíz y CI adaptados, solo las guías de documentación) que consume
  el motor por versión desde una carpeta hermana.
- `tools/consumo-externo/probar-consumo.sh` — prueba reproducible de consumo
  del motor por versión (Composer `path` + npm `file:`).
