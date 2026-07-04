<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { SchemeCard, type House, type Scheme } from '@playground/shared'

// Detalle público de una casa: cabecera con su color + descripción + sus
// argucias publicadas en rejilla de cartas.
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
    </div>
    <section v-if="item.schemes?.length" class="entity-detail__related">
      <h2>{{ t('detail.schemes') }}</h2>
      <div class="entity-index__grid">
        <div v-for="scheme in item.schemes" :key="scheme.id" class="entity-index__slot">
          <SchemeCard :item="scheme" :locale="locale" />
        </div>
      </div>
    </section>
  </article>
</template>
