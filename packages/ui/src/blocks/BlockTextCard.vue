<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell
    :settings="settings"
    class="block--text-card"
    :class="settings.image ? `block--image-${settings.image_position || 'top'}` : ''"
  >
    <div class="block__card">
      <span v-if="settings.label" class="block__label">{{ settings.label }}</span>
      <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
      <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
      <div class="block__media-layout">
        <img v-if="settings.image" class="block__image" :src="String(settings.image)" alt="" />
        <div class="block__text rich-content" v-html="settings.body" />
      </div>
    </div>
  </BlockShell>
</template>
