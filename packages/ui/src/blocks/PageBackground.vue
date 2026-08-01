<script setup lang="ts">
// Imagen de fondo de la página (patrón CDL): capa FIJA detrás del
// contenido — desde justo debajo del header (--page-background-top, la
// define el layout del cascarón; 0 si no existe) hasta abajo —, atenuada
// según el tema (--page-bg-opacity) y con un desaturado suave para que el
// texto siga siendo legible. Decorativa.
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
