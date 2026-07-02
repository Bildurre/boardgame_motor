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

// Paleta base (mismos tonos que kontuan).
const PALETTE = [
  { name: 'Slate', hex: '#64748B' },
  { name: 'Red', hex: '#EF4444' },
  { name: 'Orange', hex: '#F97316' },
  { name: 'Amber', hex: '#F59E0B' },
  { name: 'Yellow', hex: '#EAB308' },
  { name: 'Green', hex: '#22C55E' },
  { name: 'Teal', hex: '#14B8A6' },
  { name: 'Blue', hex: '#3B82F6' },
  { name: 'Indigo', hex: '#6366F1' },
  { name: 'Violet', hex: '#8B5CF6' },
  { name: 'Pink', hex: '#EC4899' },
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
