<script setup lang="ts">
import BlockShell from './BlockShell.vue'

// Índice automático: enlaces de ancla a los bloques posteriores de la página.
defineProps<{
  settings: Record<string, unknown>
  data: { items?: { id: number; label: string }[] }
}>()
</script>

<template>
  <BlockShell :settings="settings" class="block--index">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <component :is="settings.numbered ? 'ol' : 'ul'" class="block__index">
      <li v-for="item in data.items ?? []" :key="item.id">
        <a :href="`#block-${item.id}`">{{ item.label }}</a>
      </li>
    </component>
  </BlockShell>
</template>
