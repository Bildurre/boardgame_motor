# Changelog — @edc-motor/admin-kit

Kit de construcción del panel de administración (sobre `@edc-motor/ui`). Paquete
**fuente** (se consume vía Vite). Versión de tren con `edc-motor/core` y `@edc-motor/ui`.

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
