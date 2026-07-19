<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--quote">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
    <blockquote class="block__quote">
      <img v-if="settings.image" class="block__avatar" :src="String(settings.image)" alt="" />
      <div class="rich-content" v-html="settings.quote" />
      <footer
        v-if="settings.author"
        class="block__author"
        :class="`block__author--${settings.author_align || 'left'}`"
      >
        — {{ settings.author }}
      </footer>
    </blockquote>
  </BlockShell>
</template>
