<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/lib/api'
import { blockRegistry } from '@/blocks/registry'
import { useLocalesStore } from '@/stores/locales'

// Página pública del CRM (doc 03): pide el payload por slug (resuelto en
// cualquier locale), redirige a la URL canónica del idioma activo (DC-12) y
// monta el componente de cada bloque desde el blockRegistry.
interface PagePayload {
  id: number
  title: string
  meta: { title: string; description: string }
  slugs: Record<string, string>
  blocks: {
    id: number
    component: string
    settings: Record<string, unknown>
    data: Record<string, unknown>
  }[]
}

const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()

const page = ref<PagePayload | null>(null)
const failed = ref(false)

const slug = computed(() => String(route.params.slug ?? ''))

async function load() {
  failed.value = false
  try {
    const { data } = await api.get(`/pages/${slug.value}`)
    page.value = data.data

    // Canónica: si el slug de la URL no es el del idioma activo, se sustituye.
    const canonical = page.value?.slugs?.[locales.current]
    if (canonical && canonical !== slug.value) {
      router.replace({ name: 'page', params: { slug: canonical } })
      return
    }

    document.title = page.value?.meta.title ?? ''
    const meta = document.querySelector('meta[name="description"]')
    if (meta && page.value) meta.setAttribute('content', page.value.meta.description)
  } catch {
    failed.value = true
  }
}

watch([slug, () => locales.current], load, { immediate: true })
</script>

<template>
  <main class="page-view">
    <p v-if="failed" class="page-view__missing">404</p>
    <template v-else-if="page">
      <component
        :is="blockRegistry[block.component]"
        v-for="block in page.blocks.filter((b) => blockRegistry[b.component])"
        :key="block.id"
        :settings="block.settings"
        :data="block.data"
      />
    </template>
  </main>
</template>
