# Changelog — @edc-motor/admin-kit

Kit de construcción del panel de administración (sobre `@edc-motor/ui`). Paquete
**fuente** (se consume vía Vite). Versión de tren con `edc-motor/core` y `@edc-motor/ui`.

## [0.4.25] — 2026-07-20

### Cambiado

- **`MenuManager` rediseñado**: fuera los grupos (una página madre hace de
  desplegable); la jerarquía sale SIEMPRE de las páginas del CRM y una ruta
  puede colgar de una página raíz. Trabaja sobre una copia LOCAL del árbol
  — flechas, drag & drop nativo (asa `GripVertical`; soltar entre filas
  reordena, soltar ENCIMA de una página raíz anida, al hueco raíz saca) y
  el interruptor de visibilidad solo mutan el estado local; NADA se guarda
  hasta pulsar "Guardar" (un único `PUT /admin/menu`), con "Descartar" y
  aviso de cambios sin guardar. Nueva prop `displayLocale`: los títulos en
  el idioma actual del admin, con fallback al primer valor no vacío.
- **`PageBlocks`: anidado en VARIOS niveles y `displayLocale`**: el drag &
  drop (ahora nativo — la dependencia `vue-draggable-plus` se retira del
  paquete) admite soltar ENCIMA de cualquier fila para anidar sin límite de
  niveles (solo se prohíbe uno mismo/un descendiente), moviendo el subárbol
  entero y persistiendo al momento; filas sangradas por profundidad real
  (`--depth`) y el select "Bloque padre" excluye descendientes con prefijo
  por nivel. Los resúmenes y textos traducibles se pintan en el
  `displayLocale` del admin.

## [0.4.24] — 2026-07-20

### Añadido

- **`MenuManager`** (`content/MenuManager.vue`, doc 10 ampliado): configurador
  del menú de la web pública sobre `/api/admin/menu*`. Filas tipo PageBlocks
  (sin drag): icono según tipo (página/ruta/grupo), etiqueta, badges "Oculto"
  y "Borrador", subir/bajar dentro de su nivel, interruptor de visibilidad
  (`.is-on`/`.is-off`), select de grupo (— Raíz — o un grupo existente) y, en
  grupos, editar label (modal con `TranslatableInput`) y borrar (con
  confirm). Botón "Nuevo grupo" arriba. Agnóstico de i18n (DC-29): todos los
  textos por prop (`labels`) y `routeLabels` (etiqueta de cada `route_key`)
  los pone el juego. SCSS nuevo en `scss/components/_menu-manager.scss`.

## [0.4.23] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.22] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.21] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.20] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.19] — 2026-07-19

### Cambiado

- **"Ajustes comunes" del formulario de bloque SIEMPRE visible**: deja de
  ser un `<details>` plegado — es una sección fija al fondo del formulario
  con su titulito discreto, así la alineación/anchura/fondo y los flags no
  pasan desapercibidos.

## [0.4.18] — 2026-07-19

### Corregido

- **Action-buttons del panel derecho: texto legible al rellenarse**: en
  hover y en los interruptores `.is-on`, el color del texto lo decide el
  FONDO (mixin `contrast-text` de los tokens del ui) en vez de `$text-1` —
  en tema oscuro el texto casi blanco no se leía sobre warning/success.

## [0.4.17] — 2026-07-19

### Corregido

- **Cards sin badges ni meta, sin parte inferior vacía** (`EntityCard` y
  `ManagerCard`): los slots se evalúan por su CONTENIDO real (helper
  `slotHasContent`), no por si el padre declara el `<template #…>` — con
  todo v-if falso o un v-for vacío dentro, la zona inferior (padding +
  hueco) ya no se pinta. Además, cuando la cabecera es lo último de la
  card, su divisoria desaparece y el padding inferior se iguala al resto.

## [0.4.16] — 2026-07-19

### Añadido

- **Helpers de subida de imagen DIFERIDA** (`content/deferredImages`):
  `uploadContentImage(api, file)` (POST `/admin/content/uploads` → URL),
  `deleteContentImage(api, url)` (borrado silencioso),
  `uploadPendingImages(api, fields, value, uploaded)` (resuelve los `File`
  pendientes de unos settings según su esquema, recursivo en
  group/repeater; va apuntando las URLs nuevas en `uploaded` para poder
  deshacerlas si el guardado falla) y `collectImageUrls(fields, value)`
  (URLs de imagen presentes en unos settings). Los usan `PageBlocks` y las
  vistas del cascarón (Ajustes y form de página).

- **`PageBlocks` refinado**: botón de EDITAR a la IZQUIERDA de cada fila de
  bloque (abre el form sin pasar por el panel); la paleta de "añadir bloque"
  se cierra con Escape y clicando fuera; click en la zona vacía del
  contenido DESELECCIONA el bloque activo (`useCardDeselect`, como los
  index); el título del panel derecho es el TIPO del bloque (no su
  contenido); los checkboxes "entra en el PDF" y "aparece en el índice"
  pasan a botones-interruptor del bloque de acciones, con su estado en
  TEXTO en el panel y badges en la fila. `PageBlocksLabels` gana
  `printableShort`, `indexableShort`, `yes` y `no`.
- **Botones-interruptor del panel derecho**: en `.manager-detail__actions`,
  un `edc-button` con `.is-on` se pinta RELLENO de su color (estado
  activado) y con `.is-off` el contorno se atenúa — mismo contorno/hover que
  los botones de acción del panel, con el on/off visible de un vistazo.

- **Preset `cards-dense` de `BaseGrid`**: el DOBLE de columnas que `cards` en
  todos los breakpoints del contenedor `content` — 2 → 4 → 6 → 8 → 10 a
  base/480/768/1024/1280px. Para piezas pequeñas (el gestor de iconos del
  cascarón lo usa, listando TODOS los iconos sin paginación).
- **`ManagerCard` gana el slot `badges`**: como en `EntityCard`, los chips de
  estado van ARRIBA y el meta (datos secundarios) debajo, dentro de un
  `__content` común; la cabecera gana la divisoria de `EntityCard`.

### Cambiado

- **Imágenes de bloque diferidas al GUARDAR** (`SchemaFields` +
  `PageBlocks`): los campos `image` (simples y traducibles) ya no suben al
  elegir — el `File` queda en los settings del formulario y `PageBlocks` lo
  resuelve en el submit: sube los pendientes, persiste el bloque con las
  URLs y SOLO tras guardar en firme borra del disco las imágenes que el
  bloque ya no referencia (robusto ante filas de repeater reordenadas). Si
  el guardado falla se deshacen las subidas nuevas, y CANCELAR el modal no
  deja rastro en el servidor (sin huérfanos). La prop `api` de
  `SchemaFields` queda para las opciones de los campos `entity`.
- **`FilterBar`: la lupa pasa a la IZQUIERDA del input** y el texto
  (placeholder y valor) empieza a su derecha (padding-left de 34px), sin
  montarse con el icono.
- **Gestores de previews y PDF sin arranque vacío**: al cargar se selecciona
  la PRIMERA tarjeta (tipo/export) — sin abrir el panel en móvil — y el
  combobox del panel arranca con su primer elemento elegido (en las previews
  al cargar la primera página; en PDF, la primera entidad dueña de los
  exports por entidad).
- **Criterio de los selects del admin**: sin un orden explícito, las opciones
  salen en orden ALFABÉTICO. Aplicado en ambos gestores: tarjetas por
  etiqueta traducida (`typeLabels`), combobox de elementos de previews por
  `label` (el servidor manda por id) y combobox de dueñas de PDF por `label`.
- **`.manager-grid` escala como el preset `cards`**: 1 → 2 → 3 → 4 → 5
  columnas a 480/768/1024/1280px del contenedor `content` (antes 1 → 2).

### Corregido

- **Doble separador en los paneles derechos**: cuando una sección
  `.manager-detail` sigue a un `manager-panel__divider` (p. ej. el Contenido
  del bloque seleccionado o los Bloques de la página), su divisoria dashed
  propia — la línea de "puntos suspensivos" — sobraba y se quita; queda solo
  el divider del panel.

## [0.4.15] — 2026-07-17

### Cambiado

- **Card seleccionada más evidente** (`EntityCard` y `ManagerCard`): el
  borde pasa al acento del tema y se dobla con un anillo de 1px (box-shadow,
  sin mover el layout), más una sombra suave del acento. Con `accentColor`
  (tinte de facción), la selección manda: la card seleccionada va SIEMPRE
  con el acento del tema, no con el color de la entidad.

## [0.4.14] — 2026-07-16

### Cambiado

- **El preset `cards` deja la escalera densa por los breakpoints canónicos**
  del contenedor `content` — 2/3/4/5 columnas a 480 (`$bp-sm`) / 768
  (`$bp-md`) / 1024 (`$bp-lg`) / 1280 (`$bp-xl`): la escalera densa de
  0.4.13 dejaba las tarjetas demasiado estrechas.

## [0.4.13] — 2026-07-16

### Cambiado

- **`BaseGrid` hasta cinco columnas en el preset `cards`**: el sistema
  genérico de breakpoints del grid gana el escalón `xl` (`$bp-xl`, 1280px
  de ancho REAL del contenedor `content`, como los demás) — `cols` acepta
  `{ xl: n }` — y el preset `cards` escala 1 → 2 → 3 → 4 → **5** con una
  escalera densa medida sobre el contenedor (3/4/5 a 570/660/750px). El
  resto de presets, como estaban.

## [0.4.12] — 2026-07-15

- Sin cambios propios: versión de tren.

## [0.4.11] — 2026-07-15

### Añadido

- **`NavGroup`**: grupo plegable para el slot `#nav` del `AdminLayout`,
  mezclable con nav-item sueltos. Cabecera-botón (slot `icon` + `label` +
  chevron que rota) que despliega/pliega sus hijos con una animación
  discreta (`grid-template-rows`), `aria-expanded`/`aria-controls` y manejo
  nativo de teclado. El plegado se persiste en `localStorage` por
  `storageKey` (`edc_admin_nav_<clave>`, por defecto plegado) y con la prop
  `active` (la ruta actual es de un hijo, la app la calcula igual que el
  `active` de sus nav-item) la cabecera se resalta y el grupo se
  auto-despliega (sin persistir: solo los toggles del usuario guardan
  preferencia). Con el sidebar colapsado a carril de iconos los hijos se
  muestran siempre y la cabecera queda inerte (plegar sin etiquetas
  ocultaría rutas).
- **`accentColor` en `EntityCard`**: borde teñido con el color de la
  entidad (p. ej. su facción, de los datos del juego). En reposo nunca va
  puro — `color-mix` al 45 % con el `$border` del tema, sutil en claro y
  oscuro —, al hover sube al 75 % sobre `$border-strong` y en la tarjeta
  seleccionada (`is-active`) es pleno. Sin la prop, todo exactamente como
  antes.

### Arreglado

- **`AdminLayout`**: en móvil el drawer del menú se cerraba con CUALQUIER
  click dentro de la lista; ahora solo al tocar un enlace (los toggles de
  `NavGroup` no navegan y deben dejarlo abierto).

## [0.4.10] — 2026-07-14

### Cambiado

- **Retoques del `EntityCard`**: la franja `__media` pierde el fondo
  (`$surface-2`) — la imagen contenida se apoya directamente sobre la
  superficie de la tarjeta; el lápiz de editar (`editable`) pasa a verde
  (`$success`, con hover de fondo semitransparente al estilo de los
  icon-btn del ui); y header y content respiran menos entre sí
  (`padding-bottom` del header y `padding-top` del content bajan un
  escalón, de `$space-3` a `$space-2`).

## [0.4.9] — 2026-07-13

### Añadido

- **`useCardDeselect(onDeselect, extraIgnore?)`**: deselección de la card
  activa clickando la zona "vacía" del cuerpo de la vista (huecos del grid,
  espacio bajo las cards, alrededor del toolbar…). Escucha en document y
  solo actúa dentro de `.main-content`; ignora los clicks que nacen en una
  card (`.manager-card`/`.entity-card`), un control interactivo o las
  migas; `extraIgnore` añade selectores propios de la vista.
- **`.manager-panel__back`**: botón-volver de texto con flecha para el
  panel derecho (del detalle de la card seleccionada a los filtros del
  listado).

### Cambiado

- **Index de entidades a 4 columnas**: el preset `cards` del `BaseGrid`
  pasa de `{base:1, sm:2, lg:3}` a `{base:1, sm:2, md:3, lg:4}` — escala
  1 → 2 → 3 → 4 con el ancho real del contenedor `content`.
- **`.entity-card__media` cuadrada**: `aspect-ratio` de `16/9` a `1/1` y la
  imagen de `object-fit: cover` a `contain` — con tarjetas más estrechas la
  imagen queda contenida y centrada (más bien pequeña), nunca deformada ni
  recortada.

## [0.4.8] — 2026-07-13

### Cambiado

- **`FilterBar` compacto**: la caja de búsqueda pasa a `$input-height`
  (36px) y padding 10px, alineada con los nuevos tokens compactos del ui.

## [0.4.7] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.6] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.5] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.4] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.3] — 2026-07-11

### Añadido

- `EntityCard`: prop `editable` + evento `edit` — botón de lápiz integrado en
  la cabecera para editar desde la propia tarjeta (pensado para entidades sin
  vista single). `editLabel` opcional para el texto accesible (DC-29).

### Cambiado

- Convención de la franja `media` de `EntityCard`: solo para entidades con
  imagen o preview; las entidades sin imagen (taxonomías) no llevan emblema.

## [0.4.0] — 2026-07-07

### Añadido

- `PageBlocks`: **bloque padre** en el formulario (anidado de un nivel),
  tarjetas hijas **indentadas** bajo su padre (y recolocadas tras el drag), y
  slot **`#panel-default`** para que la vista pinte su propio panel (p. ej.
  las acciones de la página) cuando no hay bloque seleccionado.

### Corregido

- Aire entre el selector de color de fondo y los checkboxes de los ajustes
  comunes del bloque.

## [0.3.1] — 2026-07-07

- Sin cambios propios: versión de tren (fix de subida de SVG en `edc-motor/core`).

## [0.3.0] — 2026-07-07

### Cambiado

- `SchemaFields` (imágenes de bloques): al sustituir una imagen se manda
  `replaces` y el backend borra la anterior; el botón "quitar" borra la
  subida del disco.

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

## [0.1.0] — 2026-07-05

Primera versión etiquetada (Fases 0–7 del plan).

### Añadido

- **Listados (DC-30)**: `FilterBar` (búsqueda + filtros), `BaseTabs`,
  `BaseGrid` (responsive por `@container`), `EntityCard` (badges/meta/media,
  clicable), `EmptyState`.
- **Edición**: `EditModal`, `useResource` (CRUD por slug + restore/force +
  toggle), `fieldErrors` de validación, `SearchSelect` (combobox con
  buscador en servidor o filtro en cliente).
- **Gestores**: `PreviewManager` (imágenes PNG por tipo: lotes, por entidad,
  huérfanos), `PdfManager` (catálogo de exports: estado por idioma,
  generar/regenerar/descargar/borrar, por-entidad con combobox),
  `PageBlocks` (árbol de bloques reordenable con vue-draggable-plus) y
  `SchemaFields` (formulario **autorecursivo** del DSL: group, repeater
  con añadir/quitar/mover, entity-ref con `EntityRefSelect`).
- **Layout kontuan**: panel derecho contextual (acciones arriba, contenido
  del elemento), breadcrumbs dinámicas, nav con secciones por permiso.
- `FontUpload` (webfonts del sitio) y utilidades compartidas.
- **Panel derecho estandarizado**: acciones primero + separadores
  (`.manager-panel__divider`) entre secciones, en todos los gestores; los
  botones de acción del panel van en CONTORNO con el color de su acción
  (hover: el color pasa al fondo). Las filas de páginas/bloques no hacen
  wrap: cambian de layout en bloque (container query).
- `PdfManager` con el resumen de las previews (total + listos por idioma) y
  acciones Generar faltantes / Regenerar todo / Borrar todo.
- i18n por props (DC-29): etiquetas vía `labels`/`typeLabels`.
