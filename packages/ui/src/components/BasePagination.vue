<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue'

// Paginación compacta para listados (admin y web): anterior / "x de y" /
// siguiente. Con una sola página no pinta nada. Agnóstica de i18n (DC-29):
// textos por prop, defaults en castellano.
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
    <span class="base-pagination__status">{{ status() }}</span>
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
