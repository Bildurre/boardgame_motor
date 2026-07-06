<script setup lang="ts">
// Componente de prueba de cableado: muestra que el front consume @edc-motor/ui.
withDefaults(
  defineProps<{
    label?: string
    version?: string
  }>(),
  { label: 'EdC', version: '' },
)
</script>

<template>
  <span class="edc-badge">
    <span class="edc-badge__dot" />
    {{ label }}<template v-if="version"> · v{{ version }}</template>
  </span>
</template>
