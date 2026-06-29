<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'success'
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
