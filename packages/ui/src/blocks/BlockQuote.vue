<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--quote">
    <blockquote class="block__quote">
      <div class="rich-content" v-html="settings.quote" />
      <footer v-if="settings.author" class="block__author">— {{ settings.author }}</footer>
    </blockquote>
  </BlockShell>
</template>
