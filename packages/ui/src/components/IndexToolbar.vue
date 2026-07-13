<script setup lang="ts">
import { Search } from '@lucide/vue'
import SortToggles, { type SortValue } from './SortToggles.vue'

// Barra unificada de los index (admin y web pública): búsqueda (lupa a la
// derecha, como el FilterBar del admin-kit) y toggles de ordenación. Los
// filtros del listado viven en la barra derecha (RightSidebar del admin /
// AppRightSidebar de la web), no aquí. En ancho es una fila
// [búsqueda][toggles]; en estrecho (container query propia) la búsqueda
// ocupa su fila y los toggles pasan debajo.
// La búsqueda emite INMEDIATO (como FilterBar): el debounce va fuera.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

withDefaults(
  defineProps<{
    modelValue: string
    sort?: SortValue
    searchPlaceholder?: string
    showSort?: boolean
    latestLabel?: string
    oldestLabel?: string
    nameLabel?: string
    nameDescLabel?: string
  }>(),
  {
    sort: 'latest',
    searchPlaceholder: 'Buscar…',
    showSort: true,
    latestLabel: undefined,
    oldestLabel: undefined,
    nameLabel: undefined,
    nameDescLabel: undefined,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'update:sort': [value: SortValue]
}>()
</script>

<template>
  <div class="index-toolbar">
    <div class="index-toolbar__inner">
      <div class="index-toolbar__search">
        <input
          type="search"
          :value="modelValue"
          :placeholder="searchPlaceholder"
          class="index-toolbar__search-input"
          @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <Search :size="16" class="index-toolbar__search-icon" />
      </div>

      <div v-if="showSort" class="index-toolbar__actions">
        <SortToggles
          :model-value="sort"
          class="index-toolbar__sort"
          :latest-label="latestLabel"
          :oldest-label="oldestLabel"
          :name-label="nameLabel"
          :name-desc-label="nameDescLabel"
          @update:model-value="(v) => emit('update:sort', v)"
        />
      </div>
    </div>
  </div>
</template>
