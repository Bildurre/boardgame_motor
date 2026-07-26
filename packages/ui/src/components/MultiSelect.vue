<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { Check } from '@lucide/vue'
import { useDropdownPanel } from '../composables/useDropdownPanel'
import type { SelectOption } from './BaseSelect.vue'

// Select MÚLTIPLE de formulario: el hermano de BaseSelect para filtros y
// campos de varios valores. Mismo trigger (.form-field__select) y mismo
// panel en la top layer (useDropdownPanel), pero cada opción es un toggle
// con marca de check y el panel NO se cierra al marcar (se cierra con
// Escape, Tab o click fuera). El valor viaja como array de strings (mismo
// criterio String() que BaseSelect). El trigger pinta las etiquetas
// elegidas unidas por comas (el CSS las trunca con elipsis) — así no hace
// falta ningún texto "N seleccionadas" que traducir.
const props = withDefaults(
  defineProps<{
    modelValue?: (string | number)[]
    label?: string
    options: SelectOption[]
    /** Texto en reposo (sin nada marcado), p. ej. "Todas". */
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    id?: string
  }>(),
  { modelValue: () => [], disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>()

const selectId = props.id || `mselect-${Math.random().toString(36).slice(2, 9)}`
const panelId = `${selectId}-panel`

const open = ref(false)
const highlighted = ref(0)
const root = ref<HTMLElement | null>(null)
const panel = ref<HTMLElement | null>(null)

useDropdownPanel(root, panel, open)

const selectedValues = computed(() => new Set(props.modelValue.map((v) => String(v))))
const isSelected = (option: SelectOption) => selectedValues.value.has(String(option.value))
const triggerLabel = computed(() =>
  props.options
    .filter((o) => isSelected(o))
    .map((o) => o.label)
    .join(', '),
)

function optionId(index: number) {
  return `${selectId}-opt-${index}`
}

async function openPanel() {
  if (props.disabled || open.value) return
  open.value = true
  highlighted.value = 0
  document.addEventListener('mousedown', onOutside)
  await nextTick()
  scrollToHighlighted()
}

function close() {
  open.value = false
  document.removeEventListener('mousedown', onOutside)
}

function toggle() {
  if (open.value) close()
  else void openPanel()
}

/** Marca/desmarca la opción SIN cerrar el panel (a diferencia del simple). */
function toggleOption(option: SelectOption) {
  const value = String(option.value)
  const next = props.modelValue.map((v) => String(v))
  const at = next.indexOf(value)
  if (at === -1) next.push(value)
  else next.splice(at, 1)
  emit('update:modelValue', next)
}

function highlight(index: number) {
  if (!props.options.length) return
  highlighted.value = (index + props.options.length) % props.options.length
  scrollToHighlighted()
}

function scrollToHighlighted() {
  document.getElementById(optionId(highlighted.value))?.scrollIntoView({ block: 'nearest' })
}

// El foco vive siempre en el trigger (patrón aria-activedescendant).
function onKeydown(e: KeyboardEvent) {
  if (props.disabled) return

  if (!open.value) {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
      e.preventDefault()
      void openPanel()
    }
    return
  }

  switch (e.key) {
    case 'Escape':
      // Escape con el panel abierto cierra SOLO el panel (que no llegue al
      // listener global de un modal contenedor y lo cierre también).
      e.preventDefault()
      e.stopPropagation()
      close()
      break
    case 'ArrowDown':
      e.preventDefault()
      highlight(highlighted.value + 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      highlight(highlighted.value - 1)
      break
    case 'Home':
      e.preventDefault()
      highlight(0)
      break
    case 'End':
      e.preventDefault()
      highlight(props.options.length - 1)
      break
    case 'Enter':
    case ' ': {
      e.preventDefault()
      const option = props.options[highlighted.value]
      if (option) toggleOption(option)
      break
    }
    case 'Tab':
      close()
      break
  }
}

function onOutside(e: MouseEvent) {
  const target = e.target as Node
  // El panel vive en la top layer pero sigue dentro del árbol del root.
  if (root.value && !root.value.contains(target)) close()
}

onBeforeUnmount(() => document.removeEventListener('mousedown', onOutside))
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="selectId" class="form-field__label">{{ label }}</label>
    <div
      ref="root"
      class="form-field__select-wrapper base-select multi-select"
      :class="{ 'is-open': open }"
    >
      <button
        :id="selectId"
        type="button"
        class="form-field__select base-select__trigger"
        :disabled="disabled"
        aria-haspopup="listbox"
        :aria-expanded="open"
        :aria-controls="panelId"
        :aria-activedescendant="open ? optionId(highlighted) : undefined"
        @click="toggle"
        @keydown="onKeydown"
      >
        <span class="base-select__value" :class="{ 'is-placeholder': !triggerLabel }">
          {{ triggerLabel || placeholder }}
        </span>
      </button>

      <ul
        v-if="open"
        :id="panelId"
        ref="panel"
        class="base-select__panel"
        popover="manual"
        role="listbox"
        aria-multiselectable="true"
      >
        <li
          v-for="(option, index) in options"
          :id="optionId(index)"
          :key="option.value"
          role="option"
          class="base-select__option multi-select__option"
          :class="{ 'is-active': isSelected(option), 'is-highlighted': index === highlighted }"
          :aria-selected="isSelected(option)"
          @mousedown.prevent="toggleOption(option)"
          @mouseenter="highlighted = index"
        >
          <span class="multi-select__box" :class="{ 'is-checked': isSelected(option) }">
            <Check v-if="isSelected(option)" :size="12" />
          </span>
          {{ option.label }}
        </li>
      </ul>
    </div>
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
