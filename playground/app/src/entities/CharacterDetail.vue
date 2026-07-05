<script setup lang="ts">
import { CharacterCard, type Character } from '@playground/shared'
import AddToCollection from '@/components/AddToCollection.vue'

// Detalle público de un personaje: la carta a tamaño natural + su
// descripción completa al lado, con "añadir a la colección" (doc 02).
const props = defineProps<{ item: Character; locale: string }>()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[props.locale] || Object.values(obj)[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (DC-09) -->
<template>
  <article class="entity-detail__layout">
    <div class="entity-detail__card">
      <CharacterCard :item="item" :locale="locale" />
    </div>
    <div class="entity-detail__body">
      <h1 class="entity-detail__title">{{ tr(item.name) }}</h1>
      <div
        v-if="tr(item.description)"
        class="entity-detail__text rich-content"
        v-html="tr(item.description)"
      />
      <AddToCollection :id="item.id" entity="character" label />
    </div>
  </article>
</template>
