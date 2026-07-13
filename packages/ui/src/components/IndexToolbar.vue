<script setup lang="ts">
import { Funnel, Search } from '@lucide/vue'
import SortToggles, { type SortValue } from './SortToggles.vue'

// Barra unificada de los index (admin y web pública): búsqueda (lupa a la
// derecha, como el FilterBar del admin-kit), toggles de ordenación y botón
// "Filtros" con badge de filtros activos (emite `open-filters`; el modal lo
// pone el consumidor). En ancho es una fila [búsqueda][toggles][botón]; en
// estrecho (container query propia) la búsqueda ocupa su fila y debajo se
// reparten el ancho los toggles y el botón.
// La búsqueda emite INMEDIATO (como FilterBar): el debounce va fuera.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

withDefaults(
  defineProps<{
    modelValue: string
    sort?: SortValue
    searchPlaceholder?: string
    filtersLabel?: string
    activeCount?: number
    showFilters?: boolean
    showSort?: boolean
    latestLabel?: string
    oldestLabel?: string
    nameLabel?: string
    nameDescLabel?: string
  }>(),
  {
    sort: 'latest',
    searchPlaceholder: 'Buscar…',
    filtersLabel: 'Filtros',
    activeCount: 0,
    showFilters: false,
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
  'open-filters': []
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

      <div class="index-toolbar__actions">
        <SortToggles
          v-if="showSort"
          :model-value="sort"
          class="index-toolbar__sort"
          :latest-label="latestLabel"
          :oldest-label="oldestLabel"
          :name-label="nameLabel"
          :name-desc-label="nameDescLabel"
          @update:model-value="(v) => emit('update:sort', v)"
        />
        <button
          v-if="showFilters"
          type="button"
          class="index-toolbar__filters"
          @click="emit('open-filters')"
        >
          <Funnel :size="16" />
          <span class="index-toolbar__filters-text">{{ filtersLabel }}</span>
          <span v-if="activeCount > 0" class="index-toolbar__badge">{{ activeCount }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
