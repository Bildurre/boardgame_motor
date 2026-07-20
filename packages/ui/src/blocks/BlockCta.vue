<script setup lang="ts">
import { computed } from 'vue'
import BlockShell from './BlockShell.vue'

const props = defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()

const position = computed(() => String(props.settings.image_position || 'top'))
const inColumns = computed(
  () => !!props.settings.image && (position.value === 'left' || position.value === 'right'),
)

const layoutStyle = computed(() => {
  if (!inColumns.value) return {}
  const [a, b] = String(props.settings.image_columns || '2:3').split(':')
  return { '--img-cols': `minmax(0, ${a || 1}fr) minmax(0, ${b || 1}fr)` }
})
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell
    :settings="settings"
    class="block--cta"
    :class="[
      settings.image ? `block--image-${position}` : '',
      inColumns ? `block--fit-${settings.image_fit || 'contain'}` : '',
    ]"
  >
    <div class="block__card">
      <!-- Título y subtítulo SIEMPRE a ancho completo, encima del grid: el
           reparto en columnas es solo entre la imagen y el contenido. -->
      <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
      <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
      <div class="block__media-layout" :style="layoutStyle">
        <span v-if="settings.image && inColumns" class="block__image-frame">
          <img class="block__image" :src="String(settings.image)" alt="" />
        </span>
        <img v-else-if="settings.image" class="block__image" :src="String(settings.image)" alt="" />
        <div class="block__cta-body">
          <div v-if="settings.body" class="block__text rich-content" v-html="settings.body" />
          <!-- Alineación propia (button_align; en estrecho el CSS centra
               siempre) y tamaño grande opcional (button_large) -->
          <a
            v-if="settings.button_text && settings.button_url"
            class="block-button"
            :class="[
              `block-button--${settings.button_variant || 'primary'}`,
              `block__cta-button--${settings.button_align || 'left'}`,
              settings.button_large ? 'block-button--large' : '',
            ]"
            :href="String(settings.button_url)"
          >
            {{ settings.button_text }}
          </a>
        </div>
      </div>
    </div>
  </BlockShell>
</template>
