<script setup lang="ts">
import { computed } from 'vue'
import { Minus, Plus } from '@lucide/vue'

// Input numérico compacto con steppers −/+ (nacido como el contador de
// copias del editor de mazos de CDL): input centrado sin flechas nativas,
// botones que respetan min/max y clamp al teclear. `invalid` pinta el borde
// en danger (p. ej. copias por encima del límite del modo).
const props = withDefaults(
  defineProps<{
    modelValue: number
    min?: number
    max?: number
    step?: number
    disabled?: boolean
    invalid?: boolean
    /** aria-label del input (los botones llevan los suyos). */
    label?: string
    decreaseLabel?: string
    increaseLabel?: string
  }>(),
  { step: 1, disabled: false, invalid: false },
)

const emit = defineEmits<{ 'update:modelValue': [number] }>()

function clamp(value: number): number {
  if (Number.isNaN(value)) value = props.min ?? 0
  if (props.min !== undefined) value = Math.max(props.min, value)
  if (props.max !== undefined) value = Math.min(props.max, value)
  return value
}

const atMin = computed(() => props.min !== undefined && props.modelValue <= props.min)
const atMax = computed(() => props.max !== undefined && props.modelValue >= props.max)

function stepBy(delta: number) {
  emit('update:modelValue', clamp(props.modelValue + delta * props.step))
}

function onChange(event: Event) {
  const input = event.target as HTMLInputElement
  const value = clamp(Number(input.value))
  // El clamp puede dejar el mismo modelValue (sin re-render): se repinta a mano.
  input.value = String(value)
  emit('update:modelValue', value)
}
</script>

<template>
  <span class="number-input" :class="{ 'number-input--invalid': invalid }">
    <button
      type="button"
      class="number-input__step"
      :disabled="disabled || atMin"
      :aria-label="decreaseLabel"
      @click="stepBy(-1)"
    >
      <Minus :size="14" />
    </button>
    <input
      class="number-input__field"
      type="number"
      :value="modelValue"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :aria-label="label"
      @change="onChange"
    />
    <button
      type="button"
      class="number-input__step"
      :disabled="disabled || atMax"
      :aria-label="increaseLabel"
      @click="stepBy(1)"
    >
      <Plus :size="14" />
    </button>
  </span>
</template>
