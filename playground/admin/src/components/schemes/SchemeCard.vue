<script setup lang="ts">
import type { Scheme } from '@/types/entities'
// Composición "modo carta" de una argucia, lista para renderizar a PNG (Fase 3).
const props = defineProps<{ item: Scheme; locale: string }>()
function tr(obj: Record<string, string>) {
  return obj?.[props.locale] || Object.values(obj || {})[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (sanitización en servidor: DC-09) -->
<template>
  <div class="play-card play-card--scheme">
    <div class="play-card__art">
      <img v-if="item.image" :src="item.image" alt="" />
      <span class="play-card__cost">{{ item.cost }}</span>
    </div>
    <div class="play-card__body">
      <h3 class="play-card__title">{{ tr(item.title) }}</h3>
      <div class="play-card__text rich-content" v-html="tr(item.description)" />
    </div>
  </div>
</template>
