<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Check, Pipette } from '@lucide/vue'

// Selector de color (portado/adaptado de kontuan). A diferencia de kontuan
// (que guarda la clave de la paleta), aquí emite el HEX directamente, para
// campos de color guardados como cadena hexadecimal.
const props = withDefaults(
  defineProps<{
    modelValue: string | null
    label?: string
    allowCustom?: boolean
  }>(),
  { allowCustom: true },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

// Paleta base: espectro cálido → frío, con el gris al final (el tono "Slate"
// heredado de kontuan). Los valores viajan como HEX, no como clave.
const PALETTE = [
  { name: 'Rojo', hex: '#f15959' },
  { name: 'Naranja', hex: '#f1753a' },
  { name: 'Lima', hex: '#88b033' },
  { name: 'Verde', hex: '#29ab5f' },
  { name: 'Esmeralda', hex: '#31a28e' },
  { name: 'Cian', hex: '#3999cd' },
  { name: 'Azul', hex: '#408cfd' },
  { name: 'Violeta', hex: '#7a64c8' },
  { name: 'Magenta', hex: '#a75da5' },
  { name: 'Gris', hex: '#64748B' },
]

const norm = (v: string | null) => (v ?? '').toLowerCase()
const isPreset = computed(() => PALETTE.some((c) => norm(c.hex) === norm(props.modelValue)))
const isCustom = computed(() => !!props.modelValue && !isPreset.value)

const customHex = ref(isCustom.value ? (props.modelValue as string) : '#FF7A00')
watch(
  () => props.modelValue,
  (v) => {
    if (v && !PALETTE.some((c) => norm(c.hex) === norm(v))) customHex.value = v
  },
)

function onCustomInput(event: Event) {
  const value = (event.target as HTMLInputElement).value
  customHex.value = value
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="palette-color-picker">
    <label v-if="label" class="palette-color-picker__label">{{ label }}</label>
    <div class="palette-color-picker__grid">
      <button
        v-for="opt in PALETTE"
        :key="opt.hex"
        type="button"
        class="palette-color-picker__swatch"
        :class="{ 'palette-color-picker__swatch--selected': norm(modelValue) === norm(opt.hex) }"
        :style="{ '--swatch-color': opt.hex }"
        :title="opt.name"
        @click="emit('update:modelValue', opt.hex)"
      >
        <Check
          v-if="norm(modelValue) === norm(opt.hex)"
          class="palette-color-picker__swatch-check"
          :size="14"
        />
      </button>

      <label
        v-if="allowCustom"
        class="palette-color-picker__swatch palette-color-picker__swatch--custom"
        :class="{
          'palette-color-picker__swatch--selected': isCustom,
          'palette-color-picker__swatch--custom-idle': !isCustom,
        }"
        :style="isCustom ? { '--swatch-color': customHex } : undefined"
      >
        <input
          type="color"
          class="palette-color-picker__swatch-input"
          :value="customHex"
          @input="onCustomInput"
        />
        <Check v-if="isCustom" class="palette-color-picker__swatch-check" :size="14" />
        <Pipette v-else class="palette-color-picker__swatch-pipette" :size="14" />
      </label>
    </div>
  </div>
</template>
