<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { CharacterCard, type Character } from '@playground/shared'

// Ficha del single de personaje (patrón CDL): la carta a tamaño natural a
// un lado y la ficha de datos (estadísticas) + descripción al otro. El
// banner (nombre, subtítulo, añadir) lo pone la plantilla estándar.
const props = defineProps<{ item: Character; locale: string }>()

const { t } = useI18n()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[props.locale] || Object.values(obj)[0] || ''
}

const stats = [
  { key: 'cost', value: () => props.item.cost },
  { key: 'power', value: () => props.item.power },
  { key: 'prestige', value: () => props.item.prestige },
  { key: 'intrigue', value: () => props.item.intrigue },
  { key: 'money', value: () => props.item.money },
  { key: 'defense', value: () => props.item.defense },
]
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (DC-09) -->
<template>
  <article class="entity-single__layout">
    <div class="entity-single__preview">
      <CharacterCard :item="item" :locale="locale" />
    </div>

    <div class="entity-single__panel">
      <section class="entity-single__info">
        <h2 class="entity-single__info-title">{{ t('detail.info') }}</h2>
        <dl class="entity-single__rows">
          <div v-for="stat in stats" :key="stat.key" class="entity-single__row">
            <dt>{{ t(`detail.stats.${stat.key}`) }}</dt>
            <dd>{{ stat.value() }}</dd>
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
</template>
