<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

// Envoltorio común de todos los bloques públicos: aplica los campos comunes
// (align, width, background) que el motor añade a cada tipo. El color de
// fondo NO se aplica opaco: es un tinte semitransparente (--block-tint,
// distinto por tema, patrón CDL) para que la imagen de fondo de la página se
// vea a través. La anchura del CONTENIDO (full/wide/narrow) va por clase.
const props = defineProps<{ settings: Record<string, unknown> }>()

const width = computed(() => `block--w-${(props.settings.width as string) || 'wide'}`)

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
  <section class="block" :class="width" :style="style">
    <div class="block__inner">
      <slot />
    </div>
  </section>
</template>
