<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { Check, X } from '@lucide/vue'
import { useDropdownPanel } from '../composables/useDropdownPanel'
import type { SelectOption } from './BaseSelect.vue'

// Select MÚLTIPLE de formulario: el hermano de BaseSelect para filtros y
// campos de varios valores. Mismo trigger (.form-field__select) y mismo
// panel en la top layer (useDropdownPanel); cada opción es un toggle con
// marca de check y el panel SE CIERRA al elegir (como el simple: un valor
// por apertura — para añadir otro se reabre y las marcas siguen ahí). El
// valor viaja como array de strings (mismo criterio String() que
// BaseSelect). El trigger pinta las elegidas como etiquetas con aspa: se
// quitan desde ahí sin abrir el panel (también con Backspace); así no
// hace falta ningún texto "N seleccionadas" que traducir.
// Con `compactTrigger` (opt-in) el trigger NO crece: mantiene el alto fijo
// del select simple y, en vez de etiquetas, pinta un resumen en una línea
// (las elegidas separadas por comas, con elipsis); quitar una elegida
// queda para fuera (el consumidor pinta sus propios chips) o para el
// propio panel/Backspace. Sin la prop, todo sigue como siempre.
const props = withDefaults(
  defineProps<{
    modelValue?: (string | number)[]
    label?: string
    options: SelectOption[]
    /** Texto en reposo (sin nada marcado), p. ej. "Todas". */
    placeholder?: string
    /**
     * Trigger compacto de alto fijo: resumen en una línea (labels con
     * comas y elipsis) en lugar de etiquetas con aspa dentro del trigger.
     */
    compactTrigger?: boolean
    error?: string
    hint?: string
    disabled?: boolean
    id?: string
  }>(),
  { modelValue: () => [], disabled: false, compactTrigger: false },
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
const selectedOptions = computed(() => props.options.filter((o) => isSelected(o)))

/** Resumen de una línea del trigger compacto: labels separadas por comas. */
const summary = computed(() => selectedOptions.value.map((o) => o.label).join(', '))

/** Quita una elegida desde su etiqueta del trigger, sin tocar el panel. */
function removeOption(option: SelectOption) {
  if (props.disabled) return
  const value = String(option.value)
  emit(
    'update:modelValue',
    props.modelValue.map((v) => String(v)).filter((v) => v !== value),
  )
}

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

/** Marca/desmarca la opción y CIERRA el panel (como el select simple). */
function toggleOption(option: SelectOption) {
  const value = String(option.value)
  const next = props.modelValue.map((v) => String(v))
  const at = next.indexOf(value)
  if (at === -1) next.push(value)
  else next.splice(at, 1)
  emit('update:modelValue', next)
  close()
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
    } else if (e.key === 'Backspace' && selectedOptions.value.length) {
      // Quitar la última elegida sin abrir el panel (espejo del aspa).
      e.preventDefault()
      removeOption(selectedOptions.value[selectedOptions.value.length - 1])
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
      :class="{ 'is-open': open, 'multi-select--compact': compactTrigger }"
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
        <!-- Trigger compacto (opt-in): resumen en una línea, sin etiquetas -->
        <span v-if="compactTrigger && selectedOptions.length" class="base-select__value">
          {{ summary }}
        </span>
        <!-- Elegidas como etiquetas con aspa (el aspa NO abre el panel);
             span con role=button porque un button no puede anidar otro -->
        <span v-else-if="selectedOptions.length" class="multi-select__tags">
          <span v-for="option in selectedOptions" :key="option.value" class="multi-select__tag">
            {{ option.label }}
            <span
              class="multi-select__tag-remove"
              role="button"
              :aria-label="String(option.label)"
              @click.stop="removeOption(option)"
            >
              <X :size="12" />
            </span>
          </span>
        </span>
        <span v-else class="base-select__value is-placeholder">{{ placeholder }}</span>
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
