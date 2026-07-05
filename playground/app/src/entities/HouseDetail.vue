<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { SchemeCard, type House, type Scheme } from '@playground/shared'
import AddToCollection from '@/components/AddToCollection.vue'

// Ficha del single de casa (patrón CDL): escudo a un lado, ficha + lore al
// otro, y sus argucias publicadas en rejilla (cada una con su botón de
// añadir a la colección). El banner lo pone la plantilla estándar.
const props = defineProps<{ item: House & { schemes?: Scheme[] }; locale: string }>()

const { t } = useI18n()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[props.locale] || Object.values(obj)[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (DC-09) -->
<template>
  <article class="entity-single__layout" :style="{ '--c': item.color ?? undefined }">
    <div v-if="item.image" class="entity-single__preview">
      <img class="entity-single__shield" :src="item.image" alt="" />
    </div>

    <div class="entity-single__panel">
      <section class="entity-single__info">
        <h2 class="entity-single__info-title">{{ t('detail.info') }}</h2>
        <dl class="entity-single__rows">
          <div v-if="item.color" class="entity-single__row">
            <dt>{{ t('detail.color') }}</dt>
            <dd><span class="entity-single__swatch" :style="{ background: item.color }" /></dd>
          </div>
          <div class="entity-single__row">
            <dt>{{ t('detail.schemes') }}</dt>
            <dd>{{ item.schemes?.length ?? 0 }}</dd>
          </div>
        </dl>
      </section>

      <div
        v-if="tr(item.description)"
        class="entity-single__text rich-content"
        v-html="tr(item.description)"
      />
    </div>
  </article>

  <section v-if="item.schemes?.length" class="entity-single__related">
    <h2>{{ t('detail.schemes') }}</h2>
    <div class="entity-index__grid">
      <div v-for="scheme in item.schemes" :key="scheme.id" class="entity-index__slot">
        <SchemeCard :item="scheme" :locale="locale" />
        <AddToCollection :id="scheme.id" class="entity-index__add" entity="scheme" />
      </div>
    </div>
  </section>
</template>
