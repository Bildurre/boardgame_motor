<script setup lang="ts">
import { computed } from 'vue'

// Grid responsive de tarjetas (portado de kontuan). Responde al ancho del
// contenedor `content` (no al viewport), así coincide con el espacio real.
type Breakpoint = 'base' | 'sm' | 'md' | 'lg'
type ResponsiveCols = Partial<Record<Breakpoint, number>>

const props = withDefaults(
  defineProps<{
    cols?: number | ResponsiveCols
    gap?: 'sm' | 'md' | 'lg'
    preset?: 'cards' | 'cards-wide' | 'cards-narrow' | 'cards-full' | 'halves' | 'thirds'
  }>(),
  { cols: () => ({ base: 1, sm: 2, lg: 3 }), gap: 'md' },
)

const presetCols: Record<string, ResponsiveCols> = {
  cards: { base: 1, sm: 2, lg: 3 },
  'cards-wide': { base: 1, sm: 2, lg: 4 },
  'cards-narrow': { base: 1, sm: 2 },
  'cards-full': { base: 1, sm: 2, md: 3, lg: 4 },
  halves: { base: 1, md: 2 },
  thirds: { base: 1, sm: 2, md: 3 },
}

const gridClasses = computed(() => {
  const classes = ['grid', `grid--gap-${props.gap}`]
  const cols = props.preset ? presetCols[props.preset] : props.cols
  if (typeof cols === 'number') classes.push(`grid--base-${cols}`)
  else for (const [bp, n] of Object.entries(cols ?? {})) classes.push(`grid--${bp}-${n}`)
  return classes
})
</script>

<template>
  <div :class="gridClasses"><slot /></div>
</template>
