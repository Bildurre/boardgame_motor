<script setup lang="ts">
import BlockShell from './BlockShell.vue'

// Preguntas frecuentes (doc 03): acordeón nativo (details/summary). Cada
// fila viene ya localizada del repeater {question, answer}.
defineProps<{
  settings: {
    title?: string
    items?: { question?: string; answer?: string }[]
    [key: string]: unknown
  }
  data?: Record<string, unknown>
}>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--faq">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <details v-for="(item, i) in settings.items ?? []" :key="i" class="block__faq-item">
      <summary class="block__faq-question">{{ item.question }}</summary>
      <div v-if="item.answer" class="block__faq-answer rich-content" v-html="item.answer" />
    </details>
  </BlockShell>
</template>
