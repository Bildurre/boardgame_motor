<script setup lang="ts">
import { computed } from 'vue'
import type { Translations } from '../types'

// Token redondo de una casa (pieza de juego, no carta): es la preview PNG de
// House y lo que imprime el export house-tokens (40x40 mm, 2 cm de radio).
const props = defineProps<{
  item: { id: number; name: Translations; color?: string | null; image?: string | null }
  locale: string
}>()

const name = computed(() => {
  const obj = props.item.name
  return obj?.[props.locale] || Object.values(obj ?? {})[0] || ''
})

const initial = computed(() => name.value.trim().charAt(0).toUpperCase() || '?')
</script>

<template>
  <div class="house-token" :style="{ '--c': item.color || undefined }">
    <img v-if="item.image" class="house-token__art" :src="item.image" alt="" />
    <template v-else>
      <span class="house-token__initial">{{ initial }}</span>
    </template>
    <span class="house-token__name">{{ name }}</span>
  </div>
</template>
