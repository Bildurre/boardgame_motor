<script setup lang="ts">
import { computed } from 'vue'
import { ArrowDownAZ, ArrowDownZA, CalendarArrowDown, CalendarArrowUp } from '@lucide/vue'

// Par de toggles de ordenación de los index: fecha (latest ⇄ oldest) y
// alfabético (name ⇄ name_desc). Pulsar el inactivo lo activa en su primer
// estado; pulsar el activo invierte el sentido. Los valores casan con el
// ?sort del catálogo público del core. Botones SUELTOS estilo action-button
// (scss): el activo se tiñe de acento y `is-desc` marca el sentido
// DESCENDENTE (latest = más recientes primero, name_desc = Z-A) para que el
// color lo refuerce además del icono.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export type SortValue = 'latest' | 'oldest' | 'name' | 'name_desc'

const props = withDefaults(
  defineProps<{
    modelValue?: SortValue
    latestLabel?: string
    oldestLabel?: string
    nameLabel?: string
    nameDescLabel?: string
  }>(),
  {
    modelValue: 'latest',
    latestLabel: 'Más recientes primero',
    oldestLabel: 'Más antiguos primero',
    nameLabel: 'Alfabético (A-Z)',
    nameDescLabel: 'Alfabético (Z-A)',
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: SortValue] }>()

const dateActive = computed(() => props.modelValue === 'latest' || props.modelValue === 'oldest')
const alphaActive = computed(() => props.modelValue === 'name' || props.modelValue === 'name_desc')

// Cada botón anuncia el estado que representa AHORA: el activo, su sentido
// actual; el inactivo, el primer estado al que llevaría el click.
const dateLabel = computed(() =>
  props.modelValue === 'oldest' ? props.oldestLabel : props.latestLabel,
)
const alphaLabel = computed(() =>
  props.modelValue === 'name_desc' ? props.nameDescLabel : props.nameLabel,
)

function toggleDate() {
  emit('update:modelValue', props.modelValue === 'latest' ? 'oldest' : 'latest')
}

function toggleAlpha() {
  emit('update:modelValue', props.modelValue === 'name' ? 'name_desc' : 'name')
}
</script>

<template>
  <div class="sort-toggles" role="group">
    <button
      type="button"
      class="sort-toggles__btn"
      :class="{ 'is-active': dateActive, 'is-desc': modelValue === 'latest' }"
      :aria-pressed="dateActive"
      :aria-label="dateLabel"
      :title="dateLabel"
      @click="toggleDate"
    >
      <CalendarArrowUp v-if="modelValue === 'oldest'" :size="16" />
      <CalendarArrowDown v-else :size="16" />
    </button>
    <button
      type="button"
      class="sort-toggles__btn"
      :class="{ 'is-active': alphaActive, 'is-desc': modelValue === 'name_desc' }"
      :aria-pressed="alphaActive"
      :aria-label="alphaLabel"
      :title="alphaLabel"
      @click="toggleAlpha"
    >
      <ArrowDownZA v-if="modelValue === 'name_desc'" :size="16" />
      <ArrowDownAZ v-else :size="16" />
    </button>
  </div>
</template>
