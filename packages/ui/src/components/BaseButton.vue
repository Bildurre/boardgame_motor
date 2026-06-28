<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary'
    type?: 'button' | 'submit'
  }>(),
  { variant: 'primary', type: 'button' },
)
</script>

<template>
  <button :type="type" class="bgm-button" :class="`bgm-button--${variant}`">
    <slot />
  </button>
</template>

<style scoped lang="scss">
// Los tokens se inyectan vía additionalData del vite de cada app (ver vite.config).
.bgm-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $space-2;
  padding: $space-2 $space-4;
  border: 1px solid transparent;
  border-radius: $radius-md;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease;

  &--primary {
    background: $color-accent;
    color: #fff;

    &:hover { background: $color-accent-dark; }
  }

  &--secondary {
    background: transparent;
    border-color: $color-border;
    color: $color-text;

    &:hover { border-color: $color-accent; }
  }
}
</style>
