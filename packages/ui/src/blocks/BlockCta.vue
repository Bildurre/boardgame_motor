<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ settings: Record<string, unknown>; data?: Record<string, unknown> }>()
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell
    :settings="settings"
    class="block--cta"
    :class="settings.image ? `block--image-${settings.image_position || 'top'}` : ''"
  >
    <div class="block__card">
      <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
      <div class="block__media-layout">
        <img v-if="settings.image" class="block__image" :src="String(settings.image)" alt="" />
        <div class="block__cta-body">
          <div v-if="settings.body" class="block__text rich-content" v-html="settings.body" />
          <a
            v-if="settings.button_text && settings.button_url"
            class="bgm-button"
            :class="`bgm-button--${settings.button_variant || 'primary'}`"
            :href="String(settings.button_url)"
          >
            {{ settings.button_text }}
          </a>
        </div>
      </div>
    </div>
  </BlockShell>
</template>
