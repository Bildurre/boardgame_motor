<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft } from '@lucide/vue'
import { useHead } from '@bgm/ui'
import { api } from '@/lib/api'
import { sectionFor } from '@/entities/registry'
import { useLocalesStore } from '@/stores/locales'
import { useSiteStore } from '@/stores/site'

// Detalle público genérico por slug (doc 10): resuelve la sección por el
// segmento, pide el detalle (el slug vale en cualquier locale) y redirige a
// la URL canónica del idioma activo (DC-12). El cuerpo lo pinta el
// componente de detalle que la sección declara.
interface EntityPayload {
  id: number
  name?: Record<string, string>
  slug: Record<string, string>
  [key: string]: unknown
}

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const locales = useLocalesStore()
const site = useSiteStore()

const item = ref<EntityPayload | null>(null)
const failed = ref(false)

const segment = computed(() => String(route.params.section ?? ''))
const slug = computed(() => String(route.params.slug ?? ''))
const section = computed(() => sectionFor(segment.value))

async function load() {
  if (!section.value) return
  const current = section.value
  failed.value = false

  try {
    const { data } = await api.get(`${current.endpoint}/${slug.value}`)
    item.value = data.data
  } catch {
    failed.value = true
    return
  }

  // Canónica del locale activo: segmento y slug en el idioma de la URL.
  const canonicalSection = current.paths[locales.current] ?? segment.value
  const canonicalSlug = item.value?.slug[locales.current] || slug.value
  if (canonicalSection !== segment.value || canonicalSlug !== slug.value) {
    router.replace({
      params: { ...route.params, section: canonicalSection, slug: canonicalSlug },
    })
    return
  }

  const origin = window.location.origin
  const name = item.value?.name?.[locales.current] || Object.values(item.value?.name ?? {})[0]
  useHead({
    title: site.documentTitle(name),
    canonical: `${origin}/${locales.current}/${canonicalSection}/${canonicalSlug}`,
    alternates: Object.fromEntries(
      Object.entries(current.paths)
        .filter(([code]) => item.value?.slug[code])
        .map(([code, path]) => [code, `${origin}/${code}/${path}/${item.value?.slug[code]}`]),
    ),
  })
}

watch([segment, slug, () => locales.current], load, { immediate: true })
</script>

<template>
  <main v-if="section" class="entity-detail">
    <p v-if="failed" class="entity-detail__missing">404</p>
    <template v-else-if="item">
      <RouterLink
        class="entity-detail__back"
        :to="{ name: 'entity-index', params: { locale: locales.current, section: segment } }"
      >
        <ArrowLeft :size="16" /> {{ t('detail.back') }}
      </RouterLink>
      <component :is="section.detail" :item="item" :locale="locales.current" />
    </template>
  </main>
</template>
