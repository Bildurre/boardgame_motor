# Changelog — @edc-motor/admin-kit

Kit de construcción del panel de administración (sobre `@edc-motor/ui`). Paquete
**fuente** (se consume vía Vite). Versión de tren con `edc-motor/core` y `@edc-motor/ui`.

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
- **Retoques del `EntityCard`**: la franja `__media` pierde el fondo
  (`$surface-2`) — la imagen contenida se apoya directamente sobre la
  superficie de la tarjeta; el lápiz de editar (`editable`) pasa a verde
  (`$success`, con hover de fondo semitransparente al estilo de los
  icon-btn del ui); y header y content respiran menos entre sí
  (`padding-bottom` del header y `padding-top` del content bajan un
  escalón, de `$space-3` a `$space-2`).

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
