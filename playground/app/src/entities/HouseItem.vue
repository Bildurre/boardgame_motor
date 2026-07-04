<script setup lang="ts">
import type { House } from '@playground/shared'

// Tarjeta del listado público de casas: nombre con su color, escudo y
// arranque de la descripción.
const props = defineProps<{ item: House; locale: string }>()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[props.locale] || Object.values(obj)[0] || ''
}

function excerpt(html: string): string {
  const text = html
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
  return text.length > 140 ? `${text.slice(0, 140)}…` : text
}
</script>

<template>
  <article class="house-item" :style="{ '--c': item.color ?? undefined }">
    <img v-if="item.image" class="house-item__image" :src="item.image" alt="" />
    <h3 class="house-item__name">{{ tr(item.name) }}</h3>
    <p v-if="tr(item.description)" class="house-item__excerpt">
      {{ excerpt(tr(item.description)) }}
    </p>
  </article>
</template>
