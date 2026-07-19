<script setup lang="ts">
import BlockShell from './BlockShell.vue'

// Índice automático: enlaces de ancla a los bloques posteriores de la página,
// con sangría por nivel (bloques hijos → depth 1).
defineProps<{
  settings: Record<string, unknown>
  data: { items?: { id: number; label: string; depth?: number }[] }
}>()
</script>

<template>
  <BlockShell :settings="settings" class="block--index">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
    <component :is="settings.numbered ? 'ol' : 'ul'" class="block__index">
      <!-- Nivel visual por profundidad (tamaños decrecientes; 3 = 3 o más) -->
      <li
        v-for="item in data.items ?? []"
        :key="item.id"
        :class="[
          `block__index-level-${Math.min((item.depth ?? 0) + 1, 3)}`,
          { 'block__index-child': (item.depth ?? 0) > 0 },
        ]"
      >
        <a :href="`#block-${item.id}`">{{ item.label }}</a>
      </li>
    </component>
  </BlockShell>
</template>
