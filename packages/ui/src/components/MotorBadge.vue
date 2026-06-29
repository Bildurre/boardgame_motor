<script setup lang="ts">
// Componente de prueba de cableado: muestra que el front consume @bgm/ui.
withDefaults(
  defineProps<{
    label?: string
    version?: string
  }>(),
  { label: 'BGM', version: '' },
)
</script>

<template>
  <span class="bgm-badge">
    <span class="bgm-badge__dot" />
    {{ label }}<template v-if="version"> · v{{ version }}</template>
  </span>
</template>
