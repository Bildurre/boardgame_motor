<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

// Envoltorio común de todos los bloques públicos: aplica los campos comunes
// (align, background) que el motor añade a cada tipo. El color de fondo NO
// se aplica opaco: es un tinte semitransparente (--block-tint, distinto por
// tema, patrón CDL) para que la imagen de fondo de la página se vea a través.
const props = defineProps<{ settings: Record<string, unknown> }>()

const style = computed<CSSProperties>(() => {
  const background = props.settings.background as string | undefined
  return {
    '--block-bg': background
      ? `color-mix(in srgb, ${background} var(--block-tint, 15%), transparent)`
      : 'transparent',
    textAlign: ((props.settings.align as string) || 'left') as CSSProperties['textAlign'],
  }
})
</script>

<template>
  <section class="block" :style="style">
    <div class="block__inner">
      <slot />
    </div>
  </section>
</template>
