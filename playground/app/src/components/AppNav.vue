<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { LocaleSelector, MotorBadge, ThemeSelector } from '@bgm/ui'
import { api } from '@/lib/api'
import { entitySections } from '@/entities/registry'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'
import { useSiteStore } from '@/stores/site'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const locales = useLocalesStore()
const site = useSiteStore()

// Menú público: páginas raíz publicadas del CRM (título y slug por locale).
interface NavPage {
  id: number
  title: Record<string, string>
  slugs: Record<string, string>
  is_home: boolean
}

const pages = ref<NavPage[]>([])

const navPages = computed(() =>
  pages.value
    .filter((p) => !p.is_home)
    .map((p) => ({
      id: p.id,
      label: p.title[locales.current] || Object.values(p.title)[0] || '',
      slug: p.slugs[locales.current] || Object.values(p.slugs)[0] || '',
    })),
)

// Listados de entidades del juego (doc 10): enlace por sección, con el
// segmento del locale activo.
const navSections = computed(() =>
  entitySections.map((s) => ({
    key: s.key,
    label: t(s.titleKey),
    section: s.paths[locales.current] || s.paths.es,
  })),
)

/** Cambia el idioma NAVEGANDO: el prefijo de la URL es la fuente del locale.
 *  Las vistas de detalle redirigen luego a su slug canónico (DC-12). */
function switchLocale(code: string) {
  if (code === locales.current) return
  router.push({
    name: (route.name as string) || 'home',
    params: { ...route.params, locale: code },
    query: route.query,
  })
}

async function logout() {
  await auth.logout()
  router.push({ name: 'home', params: { locale: locales.current } })
}

onMounted(async () => {
  await locales.load()
  try {
    const { data } = await api.get('/pages/nav')
    pages.value = data.data
  } catch {
    // sin menú de páginas: la nav base sigue funcionando
  }
})
</script>

<template>
  <nav class="nav">
    <RouterLink
      :to="{ name: 'home', params: { locale: locales.current } }"
      class="nav__brand"
      :title="site.title || 'BGM'"
    >
      <!-- Logo SVG inlineado desde el payload: currentColor hereda el
           acento y el modo aleatorio lo recolorea (logo-path de CDL) -->
      <!-- eslint-disable vue/no-v-html -- SVG subido por el admin -->
      <span
        v-if="site.settings?.logo_inline"
        class="nav__logo nav__logo--svg"
        v-html="site.settings.logo_inline"
      />
      <!-- eslint-enable vue/no-v-html -->
      <img
        v-else-if="site.settings?.logo"
        class="nav__logo"
        :src="site.settings.logo"
        :alt="site.title || 'logo'"
      />
      <MotorBadge v-else :label="site.title || 'BGM'" />
    </RouterLink>
    <div class="nav__links">
      <RouterLink :to="{ name: 'home', params: { locale: locales.current } }">{{
        t('nav.home')
      }}</RouterLink>
      <RouterLink
        v-for="page in navPages"
        :key="page.id"
        :to="{ name: 'page', params: { locale: locales.current, slug: page.slug } }"
        >{{ page.label }}</RouterLink
      >
      <RouterLink
        v-for="section in navSections"
        :key="section.key"
        :to="{
          name: 'entity-index',
          params: { locale: locales.current, section: section.section },
        }"
        >{{ section.label }}</RouterLink
      >
      <template v-if="auth.isAuthenticated">
        <RouterLink :to="{ name: 'account', params: { locale: locales.current } }">{{
          t('nav.account')
        }}</RouterLink>
        <button class="nav__logout" @click="logout">{{ t('nav.logout') }}</button>
      </template>
      <template v-else>
        <RouterLink :to="{ name: 'login', params: { locale: locales.current } }">{{
          t('nav.login')
        }}</RouterLink>
      </template>
      <LocaleSelector
        :model-value="locales.current"
        :locales="locales.locales"
        @update:model-value="switchLocale"
      />
      <ThemeSelector />
    </div>
  </nav>
</template>
