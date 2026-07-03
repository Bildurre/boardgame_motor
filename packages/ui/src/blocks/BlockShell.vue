<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

// Envoltorio común de todos los bloques públicos: aplica los campos comunes
// (align, background) que el motor añade a cada tipo.
const props = defineProps<{ settings: Record<string, unknown> }>()

const style = computed<CSSProperties>(() => ({
  '--block-bg': (props.settings.background as string) || 'transparent',
  textAlign: ((props.settings.align as string) || 'left') as CSSProperties['textAlign'],
}))
</script>

<template>
  <section class="block" :style="style">
    <div class="block__inner">
      <slot />
    </div>
  </section>
</template>
