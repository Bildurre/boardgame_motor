<script setup lang="ts">
// Botón de icono. En reposo usa el color del texto; en hover tiñe el icono
// (stroke) y un fondo semitransparente con el color de la variante.
withDefaults(
  defineProps<{
    variant?: 'neutral' | 'accent' | 'danger' | 'success' | 'warning' | 'info'
    title?: string
    type?: 'button' | 'submit'
  }>(),
  { variant: 'neutral', type: 'button' },
)
</script>

<template>
  <button
    :type="type"
    class="icon-btn"
    :class="`icon-btn--${variant}`"
    :title="title"
    :aria-label="title"
  >
    <slot />
  </button>
</template>
