<script setup lang="ts">
// Imagen de fondo de la página (patrón CDL): capa FIJA a toda la ventana,
// detrás del contenido, atenuada según el tema (--page-bg-opacity) y con un
// desaturado suave para que el texto siga siendo legible. Decorativa.
defineProps<{ image?: string | null }>()
</script>

<template>
  <div
    v-if="image"
    class="page-background"
    :style="{ backgroundImage: `url('${image}')` }"
    aria-hidden="true"
  />
</template>
