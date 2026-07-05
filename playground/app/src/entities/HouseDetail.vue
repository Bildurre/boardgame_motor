<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { SchemeCard, type House, type Scheme } from '@playground/shared'
import AddToCollection from '@/components/AddToCollection.vue'

// Detalle público de una casa: cabecera con su color + descripción + sus
// argucias publicadas en rejilla de cartas; el token de la casa y cada
// argucia pueden añadirse a la colección "para imprimir" (doc 02).
const props = defineProps<{ item: House & { schemes?: Scheme[] }; locale: string }>()

const { t } = useI18n()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[props.locale] || Object.values(obj)[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (DC-09) -->
<template>
  <article class="entity-detail__layout entity-detail__layout--house">
    <div class="entity-detail__body">
      <h1 class="entity-detail__title" :style="{ '--c': item.color ?? undefined }">
        {{ tr(item.name) }}
      </h1>
      <img v-if="item.image" class="entity-detail__image" :src="item.image" alt="" />
      <div
        v-if="tr(item.description)"
        class="entity-detail__text rich-content"
        v-html="tr(item.description)"
      />
      <AddToCollection :id="item.id" entity="house" label />
    </div>
    <section v-if="item.schemes?.length" class="entity-detail__related">
      <h2>{{ t('detail.schemes') }}</h2>
      <div class="entity-index__grid">
        <div v-for="scheme in item.schemes" :key="scheme.id" class="entity-index__slot">
          <SchemeCard :item="scheme" :locale="locale" />
          <AddToCollection :id="scheme.id" class="entity-index__add" entity="scheme" />
        </div>
      </div>
    </section>
  </article>
</template>
