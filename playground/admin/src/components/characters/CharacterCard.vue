<script setup lang="ts">
import { useI18n } from 'vue-i18n'
// Composición "modo carta" de un personaje, lista para renderizar a PNG (Fase 3).
const props = defineProps<{ item: any; locale: string }>()
const { t } = useI18n()
function tr(obj: Record<string, string>) {
  return obj?.[props.locale] || Object.values(obj || {})[0] || ''
}
</script>

<template>
  <div class="play-card play-card--character">
    <div class="play-card__art">
      <img v-if="item.image" :src="item.image" alt="" />
      <span class="play-card__cost">{{ item.cost }}</span>
      <span class="play-card__defense" :title="t('characters.fields.defense')">{{ item.defense }}</span>
    </div>
    <div class="play-card__body">
      <h3 class="play-card__title">{{ tr(item.name) }}</h3>
      <div v-if="tr(item.ability)" class="play-card__text rich-content" v-html="tr(item.ability)" />
      <div v-if="tr(item.description)" class="play-card__desc rich-content" v-html="tr(item.description)" />
      <div class="play-card__stats">
        <span class="stat stat--power">{{ item.power }}</span>
        <span class="stat stat--prestige">{{ item.prestige }}</span>
        <span class="stat stat--intrigue">{{ item.intrigue }}</span>
        <span class="stat stat--money">{{ item.money }}</span>
      </div>
    </div>
  </div>
</template>
