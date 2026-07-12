<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'

// Select de formulario (portado de kontuan). Dropdown personalizado (botón
// trigger + panel de opciones) con la MISMA API que el <select> nativo al que
// sustituye: el trigger reutiliza el aspecto de .form-field__select y el
// panel, la estética del SearchSelect. Teclado completo (flechas, Enter,
// Escape, Home/End) y cierre por mousedown exterior.
//
// Compatibilidad con el nativo:
// - El valor viaja como en el DOM: se emite String(option.value) y la opción
//   activa se compara con String() por ambos lados (un modelValue numérico
//   casa con su opción, como hacía el <select>).
// - El placeholder se pinta en el trigger cuando no hay valor; la antigua
//   <option value="" disabled> no se lista en el panel (nunca era
//   seleccionable: solo servía de texto en reposo, y de eso ya se encarga
//   el trigger).
export interface SelectOption {
  value: string | number
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number
    label?: string
    options: SelectOption[]
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    required?: boolean
    id?: string
  }>(),
  { modelValue: '', disabled: false, required: false },
)

// Se emite siempre string, como entregaba el DOM del <select> nativo.
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const selectId = props.id || `select-${Math.random().toString(36).slice(2, 9)}`
const panelId = `${selectId}-panel`

const open = ref(false)
const highlighted = ref(0)
const root = ref<HTMLElement | null>(null)

const selectedIndex = computed(() =>
  props.options.findIndex((o) => String(o.value) === String(props.modelValue)),
)
const selectedLabel = computed(() => props.options[selectedIndex.value]?.label ?? '')

function optionId(index: number) {
  return `${selectId}-opt-${index}`
}

async function openPanel() {
  if (props.disabled || open.value) return
  open.value = true
  // El resaltado arranca en la opción activa (o la primera).
  highlighted.value = Math.max(selectedIndex.value, 0)
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

function select(option: SelectOption) {
  emit('update:modelValue', String(option.value))
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
    }
    return
  }

  switch (e.key) {
    case 'Escape':
      e.preventDefault()
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
      if (option) select(option)
      break
    }
    case 'Tab':
      close()
      break
  }
}

function onOutside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) close()
}

onBeforeUnmount(() => document.removeEventListener('mousedown', onOutside))
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="selectId" class="form-field__label">
      {{ label }}<span v-if="required" class="form-field__required">*</span>
    </label>
    <div ref="root" class="form-field__select-wrapper base-select" :class="{ 'is-open': open }">
      <button
        :id="selectId"
        type="button"
        class="form-field__select base-select__trigger"
        :disabled="disabled"
        aria-haspopup="listbox"
        :aria-expanded="open"
        :aria-controls="panelId"
        :aria-required="required || undefined"
        :aria-activedescendant="open ? optionId(highlighted) : undefined"
        @click="toggle"
        @keydown="onKeydown"
      >
        <span class="base-select__value" :class="{ 'is-placeholder': !selectedLabel }">
          {{ selectedLabel || placeholder }}
        </span>
      </button>

      <ul v-if="open" :id="panelId" class="base-select__panel" role="listbox">
        <li
          v-for="(option, index) in options"
          :id="optionId(index)"
          :key="option.value"
          role="option"
          class="base-select__option"
          :class="{ 'is-active': index === selectedIndex, 'is-highlighted': index === highlighted }"
          :aria-selected="index === selectedIndex"
          @mousedown.prevent="select(option)"
          @mouseenter="highlighted = index"
        >
          {{ option.label }}
        </li>
      </ul>
    </div>
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
