// @bgm/admin-kit — layout del panel + scaffolding CRUD (sobre @bgm/ui).

export { default as AdminLayout } from './layout/AdminLayout.vue'
// Panel derecho contextual (patrón kontuan): AdminLayout lo monta; cada vista
// lo activa con useRightSidebar() y Teleport a #right-sidebar-target.
export { default as RightSidebar } from './layout/RightSidebar.vue'
export { useRightSidebar, type RightSidebarToken } from './composables/useRightSidebar'
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
