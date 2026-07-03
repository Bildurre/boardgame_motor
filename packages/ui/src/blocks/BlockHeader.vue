<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<template>
  <BlockShell :settings="settings" class="block--header">
    <h1 class="block__title">{{ settings.title }}</h1>
    <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
    <img v-if="settings.image" class="block__banner" :src="String(settings.image)" alt="" />
  </BlockShell>
</template>
