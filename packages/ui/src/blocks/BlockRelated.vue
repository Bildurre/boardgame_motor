<script setup lang="ts">
import { computed, inject } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import BlockShell from './BlockShell.vue'
import PreviewGrid, { type PreviewGridItem } from '../components/PreviewGrid.vue'
import { catalogRoutesKey, type CatalogItem } from './catalogRoutes'

// Bloque `related` (categoría data): título/subtítulo + rejilla compacta de
// previews relacionadas (data.items del catálogo) + botón opcional al índice.
// El paquete ui no conoce las rutas del juego: la app las provee con
// `app.provide(catalogRoutesKey, mapa)`; sin entrada para data.key los ítems
// se pintan sin enlace y el botón no aparece. Agnóstico de i18n (DC-29): el
// texto por defecto del botón llega por prop (castellano).
const props = withDefaults(
  defineProps<{
    settings: Record<string, unknown>
    data?: { key?: string | null; items?: CatalogItem[] }
    /** Texto del botón si el bloque no trae `button_label`. */
    defaultButtonLabel?: string
  }>(),
  { data: () => ({}), defaultButtonLabel: 'Ver todos' },
)

const route = useRoute()
const routes = inject(catalogRoutesKey, {})

const locale = computed(() => String(route.params.locale ?? ''))
const entry = computed(() => (props.data?.key ? routes[props.data.key] : undefined))

const items = computed<PreviewGridItem[]>(() =>
  (props.data?.items ?? []).map((item) => ({
    ...item,
    to: entry.value?.single?.(item, locale.value) ?? undefined,
  })),
)

const indexRoute = computed(() =>
  props.settings.with_button && entry.value?.index ? entry.value.index(locale.value) : null,
)

const buttonLabel = computed(
  () => String(props.settings.button_label ?? '') || props.defaultButtonLabel,
)
</script>

<template>
  <BlockShell :settings="settings" class="block--related">
    <div class="block__related-header">
      <div class="block__related-titles">
        <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
        <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>
      </div>
      <RouterLink
        v-if="indexRoute"
        class="block-button block-button--secondary block__related-button"
        :to="indexRoute"
      >
        {{ buttonLabel }}
      </RouterLink>
    </div>
    <PreviewGrid :items="items" compact />
  </BlockShell>
</template>
