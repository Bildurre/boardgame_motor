// @edc-motor/admin-kit — layout del panel + scaffolding CRUD (sobre @edc-motor/ui).

export { default as AdminLayout } from './layout/AdminLayout.vue'
// Grupo plegable para el slot #nav del AdminLayout: mezcla nav-item sueltos
// y grupos (icono + etiqueta + chevron) con plegado persistido en localStorage.
export { default as NavGroup } from './layout/NavGroup.vue'
// Panel derecho contextual (patrón kontuan): AdminLayout lo monta; cada vista
// lo activa con useRightSidebar() y Teleport a #right-sidebar-target.
export { default as RightSidebar } from './layout/RightSidebar.vue'
export { useRightSidebar, type RightSidebarToken } from './composables/useRightSidebar'
// Listados con panel derecho: click en la zona vacía del contenido para
// deseleccionar la card activa (la card entera selecciona; el resto, no).
export { useCardDeselect } from './composables/useCardDeselect'
// Tarjeta colapsable de los gestores (previews/PDF), reutilizable.
export { default as ManagerCard } from './components/ManagerCard.vue'
// Index de entidades: grid de tarjetas (sin tablas) + filtros + estado vacío.
export { default as BaseGrid } from './crud/BaseGrid.vue'
export { default as EntityCard } from './crud/EntityCard.vue'
export { default as FilterBar } from './crud/FilterBar.vue'
export { default as EmptyState } from './crud/EmptyState.vue'
export { default as PreviewManager, type PreviewManagerLabels } from './previews/PreviewManager.vue'
export { default as PdfManager, type PdfManagerLabels } from './pdf/PdfManager.vue'
export { useResource, type ResourceMeta } from './crud/useResource'
// CRM de páginas y bloques (doc 03): editor dirigido por esquema.
export { default as SchemaFields, type FieldSchema } from './content/SchemaFields.vue'
export { default as PageBlocks, type PageBlocksLabels } from './content/PageBlocks.vue'
// Subidas de imagen diferidas al guardar (uploads de contenido sin huérfanos):
// las usan PageBlocks y las vistas del cascarón (Ajustes, form de página).
export {
  uploadContentImage,
  deleteContentImage,
  uploadPendingImages,
  collectImageUrls,
} from './content/deferredImages'
