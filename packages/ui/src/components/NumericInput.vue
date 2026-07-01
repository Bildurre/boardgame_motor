<script setup lang="ts">
import { computed } from 'vue'
import { Minus, Plus } from '@lucide/vue'

// Campo numérico con botones -/+ (portado de kontuan). Por defecto acepta solo
// enteros ≥ 0 (estadísticas de cartas, costes…). Para decimales: :integer="false".
const props = withDefaults(
  defineProps<{
    modelValue?: number | null
    label?: string
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    required?: boolean
    id?: string
    min?: number
    max?: number
    step?: number
    integer?: boolean
  }>(),
  { modelValue: 0, disabled: false, required: false, min: 0, step: 1, integer: true },
)

const emit = defineEmits<{ 'update:modelValue': [value: number] }>()

const inputId = props.id || `num-${Math.random().toString(36).slice(2, 9)}`
const numericValue = computed(() => props.modelValue ?? props.min ?? 0)
const canDecrement = computed(() => !props.disabled && (props.min === undefined || numericValue.value > props.min))
const canIncrement = computed(() => !props.disabled && (props.max === undefined || numericValue.value < props.max))

function clamp(value: number): number {
  let v = props.integer ? Math.trunc(value) : value
  if (props.min !== undefined) v = Math.max(props.min, v)
  if (props.max !== undefined) v = Math.min(props.max, v)
  return v
}

function onInput(event: Event) {
  const el = event.target as HTMLInputElement
  const raw = el.value
  if (raw === '') { emit('update:modelValue', props.min ?? 0); return }
  const parsed = Number(raw)
  if (isNaN(parsed)) { el.value = String(numericValue.value); return }
  const next = clamp(parsed)
  emit('update:modelValue', next)
  // Fuerza el campo a mostrar el valor saneado aunque el modelo no cambie
  // (p. ej. teclear "-4" con min 0 -> el modelo sigue en 0 pero el DOM mostraba -4).
  if (el.value !== String(next)) el.value = String(next)
}
function onBlur(event: Event) {
  const el = event.target as HTMLInputElement
  const next = clamp(numericValue.value)
  emit('update:modelValue', next)
  el.value = String(next)
}
function decrement() { if (canDecrement.value) emit('update:modelValue', clamp(numericValue.value - props.step)) }
function increment() { if (canIncrement.value) emit('update:modelValue', clamp(numericValue.value + props.step)) }
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="inputId" class="form-field__label">
      {{ label }}<span v-if="required" class="form-field__required">*</span>
    </label>
    <div class="numeric-input" :class="{ 'numeric-input--disabled': disabled }">
      <button type="button" class="numeric-input__btn" :disabled="!canDecrement" tabindex="-1" @click="decrement">
        <Minus :size="14" />
      </button>
      <input
        :id="inputId"
        type="text"
        inputmode="numeric"
        :value="modelValue ?? ''"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        class="numeric-input__field"
        @input="onInput"
        @blur="onBlur"
      />
      <button type="button" class="numeric-input__btn" :disabled="!canIncrement" tabindex="-1" @click="increment">
        <Plus :size="14" />
      </button>
    </div>
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
