<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Ban, Check } from '@lucide/vue'
import BaseInput from './BaseInput.vue'
import { ICON_CATALOG, iconComponents } from '../icons/iconCatalog'

// Selector de icono lucide (mismo lenguaje que PaletteColorPicker: rejilla
// de casillas, la elegida con anillo). Ofrece el catálogo CURADO del motor
// (iconCatalog, ~650 iconos por categorías) por tandas — «mostrar más» va
// destapando— y un buscador por nombre que filtra el catálogo entero. El
// valor es el nombre kebab-case de lucide (p. ej. `layout-grid`); la
// primera casilla («sin icono») lo vacía.
export interface IconPickerLabels {
  none: string
  search: string
  showMore: string
  /** «{count} más» del botón, con el número de iconos que faltan. */
  remaining: string
  noResults: string
}

const props = withDefaults(
  defineProps<{
    modelValue: string | null
    label?: string
    labels?: Partial<IconPickerLabels>
    /** Casillas visibles al abrir y cuántas destapa cada «mostrar más». */
    pageSize?: number
  }>(),
  { labels: () => ({}), pageSize: 48 },
)

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>()

const L = computed<IconPickerLabels>(() => ({
  none: 'Sin icono',
  search: 'Buscar icono…',
  showMore: 'Mostrar más',
  remaining: '{count} más',
  noResults: 'Ningún icono con ese nombre.',
  ...props.labels,
}))

const query = ref('')
const shown = ref(props.pageSize)

// Catálogo aplanado en el orden de sus categorías; el buscador casa por
// nombre (kebab-case) y por la etiqueta de la categoría.
const all = computed(() =>
  ICON_CATALOG.flatMap((category) =>
    category.icons.map((name) => ({ name, category: category.label.toLowerCase() })),
  ),
)

const matches = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return all.value.map((entry) => entry.name)
  return all.value
    .filter((entry) => entry.name.includes(q) || entry.category.includes(q))
    .map((entry) => entry.name)
})

const visible = computed(() => matches.value.slice(0, shown.value))
const remaining = computed(() => Math.max(0, matches.value.length - shown.value))

// Cada búsqueda nueva vuelve a la primera tanda.
watch(query, () => {
  shown.value = props.pageSize
})

function showMore() {
  shown.value += props.pageSize * 2
}

function pick(name: string | null) {
  emit('update:modelValue', name)
}
</script>

<template>
  <div class="icon-picker">
    <div class="icon-picker__head">
      <label v-if="label" class="icon-picker__label">{{ label }}</label>
      <span v-if="modelValue" class="icon-picker__current">
        <component :is="iconComponents[modelValue]" v-if="iconComponents[modelValue]" :size="14" />
        {{ modelValue }}
      </span>
    </div>

    <BaseInput v-model="query" type="search" :placeholder="L.search" />

    <div class="icon-picker__grid">
      <!-- Sin icono -->
      <button
        type="button"
        class="icon-picker__cell icon-picker__cell--none"
        :class="{ 'icon-picker__cell--selected': !modelValue }"
        :title="L.none"
        :aria-label="L.none"
        @click="pick(null)"
      >
        <Ban :size="16" />
      </button>

      <button
        v-for="name in visible"
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
        <Check v-if="modelValue === name" class="icon-picker__check" :size="10" />
      </button>
    </div>

    <p v-if="!matches.length" class="icon-picker__empty">{{ L.noResults }}</p>

    <button v-if="remaining" type="button" class="icon-picker__more" @click="showMore">
      {{ L.showMore }} · {{ L.remaining.replace('{count}', String(remaining)) }}
    </button>
  </div>
</template>
