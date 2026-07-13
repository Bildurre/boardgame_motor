<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Check, ChevronDown } from '@lucide/vue'
import { useDropdownPanel } from '../composables/useDropdownPanel'

// Select con buscador (combobox de selección ÚNICA): cerrado ocupa una
// línea; al desplegar muestra un input de búsqueda y las opciones filtradas
// (lista larga => scroll interno del desplegable). Emparentado con el
// TagCombobox de kontuan (multi-etiquetas), del que hereda el teclado
// (flechas + Enter + Escape) y el cierre por mousedown exterior. El filtrado
// lo hace el PADRE (evento `search`, con debounce aquí): en cliente o contra
// el servidor, y con `canLoadMore` pagina. El desplegable abierto vuela a la
// top layer (useDropdownPanel): dentro de un modal se superpone sin
// recortarse ni deformar el modal.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface SearchSelectOption {
  id: number | string
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: number | string | null
    options: SearchSelectOption[]
    placeholder?: string
    searchPlaceholder?: string
    noResults?: string
    loadMoreLabel?: string
    canLoadMore?: boolean
  }>(),
  {
    modelValue: null,
    placeholder: 'Elige…',
    searchPlaceholder: 'Buscar…',
    noResults: 'Sin resultados.',
    loadMoreLabel: 'Cargar más',
    canLoadMore: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [id: number | string]
  search: [query: string]
  loadMore: []
}>()

const open = ref(false)
const query = ref('')
const highlighted = ref(0)
const root = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)
const dropdown = ref<HTMLElement | null>(null)

useDropdownPanel(root, dropdown, open)

const selectedLabel = computed(
  () => props.options.find((o) => o.id === props.modelValue)?.label ?? '',
)

async function toggle() {
  open.value = !open.value
  if (open.value) {
    // Al abrir se resetea la búsqueda (lista completa) y el foco va al input.
    if (query.value !== '') {
      query.value = ''
      emit('search', '')
    }
    highlighted.value = 0
    document.addEventListener('mousedown', onOutside)
    await nextTick()
    input.value?.focus()
  } else {
    close()
  }
}

function close() {
  open.value = false
  document.removeEventListener('mousedown', onOutside)
}

function select(option: SearchSelectOption) {
  emit('update:modelValue', option.id)
  close()
}

// Búsqueda con debounce: el padre filtra (cliente o servidor).
let timer: ReturnType<typeof setTimeout> | null = null
function onInput() {
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => emit('search', query.value), 300)
}

watch(
  () => props.options,
  () => {
    highlighted.value = 0
  },
)

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    // Cierra SOLO el desplegable (que no llegue al listener global de un
    // modal contenedor y lo cierre también).
    e.stopPropagation()
    close()
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (props.options.length) highlighted.value = (highlighted.value + 1) % props.options.length
    return
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (props.options.length) {
      highlighted.value = (highlighted.value - 1 + props.options.length) % props.options.length
    }
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    const option = props.options[highlighted.value]
    if (option) select(option)
  }
}

function onOutside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) close()
}

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onOutside)
  if (timer) clearTimeout(timer)
})
</script>

<template>
  <div ref="root" class="search-select" :class="{ 'is-open': open }">
    <button type="button" class="search-select__trigger" @click="toggle">
      <span class="search-select__value" :class="{ 'is-placeholder': !selectedLabel }">
        {{ selectedLabel || placeholder }}
      </span>
      <ChevronDown :size="16" class="search-select__chevron" />
    </button>

    <div v-if="open" ref="dropdown" class="search-select__dropdown" popover="manual">
      <input
        ref="input"
        v-model="query"
        type="text"
        class="search-select__input"
        :placeholder="searchPlaceholder"
        @input="onInput"
        @keydown="onKeydown"
      />
      <ul class="search-select__options">
        <li v-for="(option, index) in options" :key="option.id">
          <button
            type="button"
            class="search-select__option"
            :class="{
              'is-active': option.id === modelValue,
              'is-highlighted': index === highlighted,
            }"
            @mousedown.prevent="select(option)"
            @mouseenter="highlighted = index"
          >
            <span class="search-select__label">{{ option.label }}</span>
            <Check v-if="option.id === modelValue" :size="14" />
          </button>
        </li>
        <li v-if="!options.length" class="search-select__empty">{{ noResults }}</li>
        <li v-if="canLoadMore" class="search-select__more">
          <button type="button" @mousedown.prevent="emit('loadMore')">{{ loadMoreLabel }}</button>
        </li>
      </ul>
    </div>
  </div>
</template>
