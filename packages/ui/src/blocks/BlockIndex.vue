<script setup lang="ts">
import { computed } from 'vue'
import BlockShell from './BlockShell.vue'

// Índice automático: enlaces de ancla a los bloques posteriores de la
// página, con sangría por nivel (sin límite de profundidad: la escribe
// IndexBlock::resolveData subiendo por la cadena de padres del bloque).
const props = defineProps<{
  settings: Record<string, unknown>
  data: { items?: { id: number; label: string; depth?: number }[] }
}>()

// Numeración ANIDADA del modo numerado (1, 1.1, 1.2, 1.2.1…): la lista
// llega plana en preorden con `depth`, así que basta una pila de contadores.
const numbered = computed(() => {
  const counters: number[] = []
  return (props.data.items ?? []).map((item) => {
    const depth = item.depth ?? 0
    counters.length = depth + 1
    counters[depth] = (counters[depth] ?? 0) + 1
    return { ...item, number: counters.join('.') }
  })
})
</script>

<template>
  <BlockShell :settings="settings" class="block--index">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
    <component
      :is="settings.numbered ? 'ol' : 'ul'"
      class="block__index"
      :class="{ 'block__index--numbered': settings.numbered }"
    >
      <!-- Tamaño por nivel (el 3 agrupa "3 o más"); la SANGRÍA, en cambio,
           escala con la profundidad real (--depth, sin tope). Numerado: la
           numeración es ANIDADA (1, 1.1, 1.2.1…), calculada aquí — el ol
           nativo no sabe numerar una lista plana con profundidades. -->
      <li
        v-for="item in numbered"
        :key="item.id"
        :style="{ '--depth': item.depth ?? 0 }"
        :class="[
          `block__index-level-${Math.min((item.depth ?? 0) + 1, 3)}`,
          { 'block__index-child': (item.depth ?? 0) > 0 },
        ]"
      >
        <span v-if="settings.numbered" class="block__index-number">{{ item.number }}</span>
        <a :href="`#block-${item.id}`">{{ item.label }}</a>
      </li>
    </component>
  </BlockShell>
</template>
