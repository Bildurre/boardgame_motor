<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'

// Paginación compacta para listados (admin y web): anterior / números de
// página clicables / siguiente. Con una sola página no pinta nada.
// Agnóstica de i18n (DC-29): textos por prop, defaults en castellano (los
// números no necesitan traducción).
const props = withDefaults(
  defineProps<{
    page: number
    pages: number
    prevLabel?: string
    nextLabel?: string
    /** Texto accesible del estado, con {page} y {pages}. */
    ofLabel?: string
  }>(),
  { prevLabel: 'Anterior', nextLabel: 'Siguiente', ofLabel: '{page} de {pages}' },
)

const emit = defineEmits<{ 'update:page': [page: number] }>()

// Números visibles entre anterior/siguiente: patrón clásico de elisión —
// primera + vecinas de la actual + última, con «…» en los tramos omitidos
// (1 … 4 5 6 … 20). Hasta 7 páginas caben todas (elidir no ahorraría
// hueco).
const items = computed<(number | '…')[]>(() => {
  const { page, pages } = props
  if (pages <= 7) return Array.from({ length: pages }, (_, i) => i + 1)
  const start = Math.max(2, page - 1)
  const end = Math.min(pages - 1, page + 1)
  return [
    1,
    ...(start > 2 ? ['…' as const] : []),
    ...Array.from({ length: end - start + 1 }, (_, i) => start + i),
    ...(end < pages - 1 ? ['…' as const] : []),
    pages,
  ]
})

function go(page: number) {
  if (page < 1 || page > props.pages || page === props.page) return
  emit('update:page', page)
}

function status(): string {
  return props.ofLabel.replace('{page}', String(props.page)).replace('{pages}', String(props.pages))
}
</script>

<template>
  <nav v-if="pages > 1" class="base-pagination" :aria-label="status()">
    <button
      type="button"
      class="base-pagination__button"
      :disabled="page <= 1"
      :aria-label="prevLabel"
      :title="prevLabel"
      @click="go(page - 1)"
    >
      <ChevronLeft :size="16" />
    </button>
    <template v-for="(item, index) in items" :key="`${item}-${index}`">
      <span v-if="item === '…'" class="base-pagination__ellipsis" aria-hidden="true">…</span>
      <button
        v-else
        type="button"
        class="base-pagination__number"
        :aria-current="item === page ? 'page' : undefined"
        @click="go(item)"
      >
        {{ item }}
      </button>
    </template>
    <button
      type="button"
      class="base-pagination__button"
      :disabled="page >= pages"
      :aria-label="nextLabel"
      :title="nextLabel"
      @click="go(page + 1)"
    >
      <ChevronRight :size="16" />
    </button>
  </nav>
</template>
