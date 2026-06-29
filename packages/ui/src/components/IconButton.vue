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
  <button :type="type" class="icon-btn" :class="`icon-btn--${variant}`" :title="title" :aria-label="title">
    <slot />
  </button>
</template>

<style scoped lang="scss">
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: $space-2;
  line-height: 0;
  background: none;
  border: none;
  border-radius: $radius-md;
  color: $color-text; // color del texto en reposo
  cursor: pointer;
  transition: color 0.12s ease, background-color 0.12s ease;

  // hover: stroke + fondo semitransparente del color de la acción
  &--neutral:hover { color: $color-text; background: rgba(255, 255, 255, 0.08); }
  &--accent:hover { color: $color-accent; background: rgba(108, 92, 231, 0.18); }
  &--danger:hover { color: #ff6b6b; background: rgba(255, 107, 107, 0.16); }
  &--success:hover { color: #4ade80; background: rgba(74, 222, 128, 0.16); }
  &--warning:hover { color: #fbbf24; background: rgba(251, 191, 36, 0.16); }
  &--info:hover { color: #6c8cff; background: rgba(108, 140, 255, 0.16); }
}
</style>
