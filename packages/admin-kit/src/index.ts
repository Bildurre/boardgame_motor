// @bgm/admin-kit — layout del panel + scaffolding CRUD (sobre @bgm/ui).

export { default as AdminLayout } from './layout/AdminLayout.vue'
// Index de entidades: grid de tarjetas (sin tablas) + filtros + estado vacío.
export { default as BaseGrid } from './crud/BaseGrid.vue'
export { default as EntityCard } from './crud/EntityCard.vue'
export { default as FilterBar } from './crud/FilterBar.vue'
export { default as EmptyState } from './crud/EmptyState.vue'
export { default as PreviewManager, type PreviewManagerLabels } from './previews/PreviewManager.vue'
export { useResource, type ResourceMeta } from './crud/useResource'
