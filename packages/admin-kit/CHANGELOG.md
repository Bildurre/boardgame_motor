# Changelog — @bgm/admin-kit

Kit de construcción del panel de administración (sobre `@bgm/ui`). Paquete
**fuente** (se consume vía Vite). Versión de tren con `bgm/core` y `@bgm/ui`.

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
- i18n por props (DC-29): etiquetas vía `labels`/`typeLabels`.
