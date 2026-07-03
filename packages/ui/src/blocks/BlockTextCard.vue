<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--text-card">
    <div class="block__card">
      <span v-if="settings.label" class="block__label">{{ settings.label }}</span>
      <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
      <div class="block__text rich-content" v-html="settings.body" />
    </div>
  </BlockShell>
</template>
