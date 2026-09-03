<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { X } from '@lucide/vue'
import BaseInput from './BaseInput.vue'
import { ICON_CATALOG, iconComponents } from '../icons/iconCatalog'

// Selector de icono lucide (mismo lenguaje que PaletteColorPicker: rejilla
// de casillas, la elegida con anillo). El catálogo CURADO del motor
// (iconCatalog, ~650 iconos) va POR CATEGORÍAS: cada una enseña su primera
// fila y se despliega entera con «ver todos» (y se vuelve a plegar con
// «ver menos»). El buscador filtra por nombre dentro de cada categoría
// (las que se quedan sin iconos desaparecen) y, mientras hay búsqueda, las
// categorías salen completas. El icono elegido se enseña junto al buscador,
// con su aspa para quitarlo. El valor es el nombre kebab-case de lucide
// (p. ej. `layout-grid`).
export interface IconPickerLabels {
  /** Aspa del elegido (quitar icono). */
  none: string
  search: string
  all: string
  less: string
  noResults: string
}

const props = withDefaults(
  defineProps<{
    modelValue: string | null
    label?: string
    labels?: Partial<IconPickerLabels>
    /** Etiquetas de las categorías por clave (iconCategories.<key>). */
    categoryLabels?: Record<string, string>
    /** Iconos visibles de cada categoría plegada. */
    collapsedSize?: number
  }>(),
  { labels: () => ({}), categoryLabels: () => ({}), collapsedSize: 12 },
)

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>()

const L = computed<IconPickerLabels>(() => ({
  none: 'Quitar icono',
  search: 'Buscar icono…',
  all: 'Ver todos',
  less: 'Ver menos',
  noResults: 'Ningún icono con ese nombre.',
  ...props.labels,
}))

const query = ref('')
const expanded = ref<Set<string>>(new Set())

// Categorías con los iconos que casan con la búsqueda; sin búsqueda, todas.
const sections = computed(() => {
  const q = query.value.trim().toLowerCase()
  return ICON_CATALOG.map((category) => {
    const icons = q ? category.icons.filter((name) => name.includes(q)) : category.icons
    const open = !!q || expanded.value.has(category.key)
    return {
      key: category.key,
      label: props.categoryLabels[category.key] ?? category.label,
      total: icons.length,
      icons: open ? icons : icons.slice(0, props.collapsedSize),
      open,
      // Con búsqueda salen completas: el botón solo tiene sentido sin ella.
      toggle: !q && icons.length > props.collapsedSize,
    }
  }).filter((section) => section.total > 0)
})

// Cada búsqueda nueva vuelve al plegado por defecto.
watch(query, () => {
  expanded.value = new Set()
})

function toggle(key: string) {
  const next = new Set(expanded.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  expanded.value = next
}

function pick(name: string | null) {
  emit('update:modelValue', name)
}
</script>

<template>
  <div class="icon-picker">
    <label v-if="label" class="icon-picker__label">{{ label }}</label>

    <!-- Buscador y, al lado, el icono elegido con su aspa -->
    <div class="icon-picker__bar">
      <BaseInput v-model="query" type="search" :placeholder="L.search" />
      <div v-if="modelValue" class="icon-picker__current">
        <component :is="iconComponents[modelValue]" v-if="iconComponents[modelValue]" :size="18" />
        <span class="icon-picker__current-name">{{ modelValue }}</span>
        <button
          type="button"
          class="icon-picker__clear"
          :title="L.none"
          :aria-label="L.none"
          @click="pick(null)"
        >
          <X :size="14" />
        </button>
      </div>
    </div>

    <p v-if="!sections.length" class="icon-picker__empty">{{ L.noResults }}</p>

    <section v-for="section in sections" :key="section.key" class="icon-picker__section">
      <header class="icon-picker__section-head">
        <span class="icon-picker__section-title">{{ section.label }}</span>
        <button
          v-if="section.toggle"
          type="button"
          class="icon-picker__toggle"
          @click="toggle(section.key)"
        >
          {{ section.open ? L.less : `${L.all} (${section.total})` }}
        </button>
      </header>
      <div class="icon-picker__grid">
        <button
          v-for="name in section.icons"
          :key="name"
          type="button"
          class="icon-picker__cell"
          :class="{ 'icon-picker__cell--selected': modelValue === name }"
          :title="name"
          :aria-label="name"
          :aria-pressed="modelValue === name"
          @click="pick(name)"
        >
          <component :is="iconComponents[name]" :size="18" />
        </button>
      </div>
    </section>
  </div>
</template>
