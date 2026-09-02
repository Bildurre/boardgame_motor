<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Check, Pipette } from '@lucide/vue'

/** Preset DINÁMICO del tema: value `token:<nombre>` (p. ej. token:surface),
 *  que se resuelve a la custom property `var(--<nombre>)` del tema ACTIVO
 *  (los tokens del tema son CSS vars también en el admin). */
export interface ColorPreset {
  value: string
  label: string
}

// Selector de color (portado/adaptado de kontuan). A diferencia de kontuan
// (que guarda la clave de la paleta), aquí emite el HEX directamente, para
// campos de color guardados como cadena hexadecimal. Además de la paleta
// fija puede ofrecer presets DINÁMICOS (`presets`, valores token:* — el
// fondo de bloque del CRM los usa): se pintan resueltos con el tema actual,
// con title descriptivo, y se seleccionan/deseleccionan igual que un hex.
const props = withDefaults(
  defineProps<{
    modelValue: string | null
    label?: string
    allowCustom?: boolean
    /** Presets dinámicos del tema, delante de la paleta (vacío = como siempre). */
    presets?: ColorPreset[]
  }>(),
  { allowCustom: true, presets: () => [] },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

// Paleta base: los 18 tonos de la rueda OKLCH del tema (_theme.scss, en el
// orden de la rueda desde el rojo) más el gris. Los valores viajan como HEX,
// no como clave: son los mismos hex que las custom properties --palette-*.
const PALETTE = [
  { name: 'Rojo', hex: '#cf4946' },
  { name: 'Coral', hex: '#cb5300' },
  { name: 'Naranja', hex: '#b26900' },
  { name: 'Amarillo', hex: '#c09000' },
  { name: 'Lima', hex: '#898000' },
  { name: 'Hierba', hex: '#698b00' },
  { name: 'Verde', hex: '#229633' },
  { name: 'Esmeralda', hex: '#0b936b' },
  { name: 'Teal', hex: '#0c9185' },
  { name: 'Cian', hex: '#108e99' },
  { name: 'Celeste', hex: '#0a8aae' },
  { name: 'Azur', hex: '#0083cf' },
  { name: 'Azul', hex: '#4a76e1' },
  { name: 'Índigo', hex: '#7669dc' },
  { name: 'Violeta', hex: '#955dcd' },
  { name: 'Púrpura', hex: '#ae53b4' },
  { name: 'Fucsia', hex: '#c04b94' },
  { name: 'Carmín', hex: '#cb4770' },
  { name: 'Gris', hex: '#64748b' },
]

const TOKEN_PREFIX = 'token:'

/** CSS del swatch de un preset: token:x => var(--x); un hex pasa tal cual. */
function swatchColor(value: string): string {
  if (!value.startsWith(TOKEN_PREFIX)) return value
  return `var(--${value.slice(TOKEN_PREFIX.length).replace(/[^a-z0-9-]/gi, '')})`
}

const norm = (v: string | null) => (v ?? '').toLowerCase()
const isPreset = computed(
  () =>
    PALETTE.some((c) => norm(c.hex) === norm(props.modelValue)) ||
    props.presets.some((p) => norm(p.value) === norm(props.modelValue)),
)
// Un token desconocido tampoco es "custom": el input color solo entiende hex.
const isCustom = computed(
  () => !!props.modelValue && !isPreset.value && !props.modelValue.startsWith(TOKEN_PREFIX),
)

const customHex = ref(isCustom.value ? (props.modelValue as string) : '#FF7A00')
watch(
  () => props.modelValue,
  (v) => {
    if (v && !v.startsWith(TOKEN_PREFIX) && !PALETTE.some((c) => norm(c.hex) === norm(v))) {
      customHex.value = v
    }
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
      <!-- Presets dinámicos del tema (token:*), resueltos con el tema actual -->
      <button
        v-for="preset in presets"
        :key="preset.value"
        type="button"
        class="palette-color-picker__swatch palette-color-picker__swatch--dynamic"
        :class="{
          'palette-color-picker__swatch--selected': norm(modelValue) === norm(preset.value),
        }"
        :style="{ '--swatch-color': swatchColor(preset.value) }"
        :title="preset.label"
        @click="emit('update:modelValue', preset.value)"
      >
        <Check
          v-if="norm(modelValue) === norm(preset.value)"
          class="palette-color-picker__swatch-check"
          :size="14"
        />
      </button>

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
