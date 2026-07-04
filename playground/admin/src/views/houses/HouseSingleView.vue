<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, SquarePen } from '@lucide/vue'
import { useResource, BaseGrid, EntityCard, EmptyState } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import { usePageCrumb } from '@/composables/usePageCrumb'
import type { House, Scheme } from '@playground/shared'
import HouseFormModal from '@/components/houses/HouseFormModal.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const { find } = useResource<House>(api, '/admin/houses')

const item = ref<House | null>(null)
const loading = ref(true)
const formOpen = ref(false)

function tr(obj: Record<string, string> | null | undefined) {
  return (
    obj?.[locales.current] || obj?.[locales.defaultLocale] || Object.values(obj || {})[0] || '—'
  )
}
function slugFor(obj: Scheme): string {
  return obj?.slug?.[locales.current] || Object.values(obj?.slug || {})[0] || ''
}
const slug = computed(() => route.params.slug as string)

async function load() {
  loading.value = true
  try {
    item.value = await find(slug.value)
  } catch {
    item.value = null
  } finally {
    loading.value = false
  }
}
async function onSaved() {
  await load()
}
function goScheme(s: Scheme) {
  router.push({ name: 'scheme-single', params: { slug: slugFor(s) } })
}

onMounted(async () => {
  await locales.load()
  await load()
})

// El nombre del single como último tramo de la breadcrumb (se actualiza si
// cambia el locale de contenido) y fuera al salir de la vista.
const crumb = usePageCrumb()
watch(
  [item, () => locales.current],
  () => {
    if (item.value) crumb.set(tr(item.value.name))
  },
  { immediate: true },
)
onBeforeUnmount(crumb.clear)
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (sanitización en servidor: DC-09) -->
<template>
  <div v-if="item" class="single">
    <div class="single__bar">
      <BaseButton variant="text" @click="router.push({ name: 'houses' })">
        <template #icon><ArrowLeft :size="16" /></template>
        {{ t('houses.title') }}
      </BaseButton>
      <BaseButton variant="success" @click="formOpen = true">
        <template #icon><SquarePen :size="16" /></template>
        {{ t('common.actions.edit') }}
      </BaseButton>
    </div>

    <div class="single__layout">
      <div class="single__preview">
        <div class="play-card play-card--house" :style="{ '--c': item.color || 'transparent' }">
          <div class="play-card__art">
            <img v-if="item.image" :src="item.image" alt="" />
            <span v-else class="play-card__mono">{{ tr(item.name).charAt(0) }}</span>
          </div>
          <div class="play-card__body">
            <h3 class="play-card__title">{{ tr(item.name) }}</h3>
            <div class="rich-content" v-html="tr(item.description)" />
          </div>
        </div>
      </div>
      <div class="single__info">
        <h1>{{ tr(item.name) }}</h1>
        <p class="single__meta">
          <span class="swatch" :style="{ background: item.color || 'transparent' }" />{{
            item.color || '—'
          }}
        </p>
        <div class="rich-content" v-html="tr(item.description)" />
      </div>
    </div>

    <h2 class="single__section">{{ t('schemes.title') }}</h2>
    <EmptyState v-if="!item.schemes || !item.schemes.length" :title="t('common.empty')" />
    <BaseGrid v-else preset="cards-full" gap="md">
      <EntityCard
        v-for="s in item.schemes"
        :key="s.id"
        :title="tr(s.title)"
        clickable
        @view="goScheme(s)"
      >
        <template #media>
          <div class="card-art">
            <img v-if="s.image" :src="s.image" alt="" />
            <span v-else class="card-art__cost">{{ s.cost }}</span>
          </div>
        </template>
        <template #badges>
          <span class="chip chip--cost">{{ t('schemes.fields.cost') }}: {{ s.cost }}</span>
        </template>
      </EntityCard>
    </BaseGrid>

    <HouseFormModal v-model="formOpen" mode="edit" :target-slug="slug" @saved="onSaved" />
  </div>
  <p v-else-if="!loading" class="single__empty">{{ t('common.empty') }}</p>
</template>
