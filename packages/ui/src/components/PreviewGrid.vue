<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import type { CatalogItem } from '../blocks/catalogRoutes'

// Rejilla PRESENTACIONAL de previews del catálogo público: el fetch lo hace
// la vista del juego (o el bloque `related` con sus datos ya resueltos).
// Los ítems con `to` son RouterLink. La tarjeta por defecto es el PNG de la
// preview con proporción de carta; si no hay PNG (el motor no genera
// placeholder), fallback con el nombre. Agnóstico de i18n (DC-29): textos
// por prop, defaults en castellano.
export interface PreviewGridItem extends CatalogItem {
  to?: RouteLocationRaw | null
}

const props = withDefaults(
  defineProps<{
    items: PreviewGridItem[]
    loading?: boolean
    /** Página actual y total (meta del catálogo). Sin `pages` (o con una
     *  sola página) no se pinta la paginación. */
    page?: number
    pages?: number
    /** Variante compacta (bloque `related`): tarjetas más pequeñas. */
    compact?: boolean
    emptyText?: string
    prevLabel?: string
    nextLabel?: string
  }>(),
  {
    loading: false,
    page: 1,
    pages: 0,
    compact: false,
    emptyText: 'No hay resultados.',
    prevLabel: 'Anterior',
    nextLabel: 'Siguiente',
  },
)

const emit = defineEmits<{ (e: 'page', page: number): void }>()

function go(page: number) {
  if (props.loading || page < 1 || page > props.pages || page === props.page) return
  emit('page', page)
}
</script>

<template>
  <div
    class="preview-grid"
    :class="{ 'preview-grid--compact': compact, 'preview-grid--loading': loading }"
  >
    <p v-if="!loading && !items.length" class="preview-grid__empty">
      <slot name="empty">{{ emptyText }}</slot>
    </p>

    <div v-else class="preview-grid__grid">
      <div v-for="item in items" :key="item.id" class="preview-grid__slot">
        <component
          :is="item.to ? RouterLink : 'div'"
          class="preview-grid__item"
          v-bind="item.to ? { to: item.to } : {}"
        >
          <slot name="item" :item="item">
            <img
              v-if="item.preview"
              class="preview-grid__image"
              :src="item.preview"
              :alt="item.name"
              loading="lazy"
            />
            <span v-else class="preview-grid__fallback">{{ item.name }}</span>
          </slot>
        </component>
        <!-- Acciones por ítem (p. ej. añadir a la colección) -->
        <slot name="actions" :item="item" />
      </div>
    </div>

    <nav v-if="pages > 1" class="preview-grid__pagination">
      <button
        type="button"
        class="preview-grid__page-btn"
        :disabled="loading || page <= 1"
        @click="go(page - 1)"
      >
        {{ prevLabel }}
      </button>
      <span class="preview-grid__page-state">{{ page }} / {{ pages }}</span>
      <button
        type="button"
        class="preview-grid__page-btn"
        :disabled="loading || page >= pages"
        @click="go(page + 1)"
      >
        {{ nextLabel }}
      </button>
    </nav>
  </div>
</template>
