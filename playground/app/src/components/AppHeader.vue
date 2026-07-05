<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { FileDown, Menu, X } from '@lucide/vue'
import { LocaleSelector, MotorBadge, ThemeSelector } from '@bgm/ui'
import { api } from '@/lib/api'
import { entitySections } from '@/entities/registry'
import { DOWNLOAD_PATHS } from '@/router/downloads'
import { useAuthStore } from '@/stores/auth'
import { useCollectionStore } from '@/stores/collection'
import { useLocalesStore } from '@/stores/locales'
import { useSiteStore } from '@/stores/site'

// Cabecera pública estilo CDL: fija, dos filas (marca + acciones arriba;
// nav centrada abajo, con el borde del color de acento), off-canvas en
// móvil y ocultación al hacer scroll hacia abajo. El contador de la
// colección "para imprimir" enlaza con Descargas.
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const locales = useLocalesStore()
const site = useSiteStore()
const collection = useCollectionStore()

interface NavPage {
  id: number
  title: Record<string, string>
  slugs: Record<string, string>
  is_home: boolean
}

const pages = ref<NavPage[]>([])
const navOpen = ref(false)
const hidden = ref(false)

const navPages = computed(() =>
  pages.value
    .filter((p) => !p.is_home)
    .map((p) => ({
      id: p.id,
      label: p.title[locales.current] || Object.values(p.title)[0] || '',
      slug: p.slugs[locales.current] || Object.values(p.slugs)[0] || '',
    })),
)

const navSections = computed(() =>
  entitySections.map((s) => ({
    key: s.key,
    label: t(s.titleKey),
    section: s.paths[locales.current] || s.paths.es,
  })),
)

const downloadsSegment = computed(() => DOWNLOAD_PATHS[locales.current] ?? DOWNLOAD_PATHS.es)

/** Cambia el idioma NAVEGANDO: el prefijo de la URL manda (DC-12). */
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

// Ocultar al bajar, enseñar al subir (patrón CDL).
let lastY = 0
function onScroll() {
  const y = window.scrollY
  hidden.value = y > 80 && y > lastY && !navOpen.value
  lastY = y
}

// El panel móvil se cierra al navegar.
watch(
  () => route.fullPath,
  () => {
    navOpen.value = false
  },
)

onMounted(async () => {
  window.addEventListener('scroll', onScroll, { passive: true })
  collection.load()
  await locales.load()
  try {
    const { data } = await api.get('/pages/nav')
    pages.value = data.data
  } catch {
    // sin menú de páginas: la nav base sigue funcionando
  }
})

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <header class="site-header" :class="{ 'is-hidden': hidden }">
    <div class="site-header__main-bar">
      <div class="site-header__main">
        <button
          class="site-header__burger"
          type="button"
          :aria-expanded="navOpen"
          :title="t('nav.menu')"
          @click="navOpen = !navOpen"
        >
          <X v-if="navOpen" :size="22" />
          <Menu v-else :size="22" />
        </button>

        <RouterLink
          :to="{ name: 'home', params: { locale: locales.current } }"
          class="site-header__brand"
          :title="site.title || 'BGM'"
        >
          <!-- Logo SVG inlineado: currentColor hereda el acento (CDL) -->
          <!-- eslint-disable vue/no-v-html -- SVG subido por el admin -->
          <span
            v-if="site.settings?.logo_inline"
            class="site-header__logo site-header__logo--svg"
            v-html="site.settings.logo_inline"
          />
          <!-- eslint-enable vue/no-v-html -->
          <img
            v-else-if="site.settings?.logo"
            class="site-header__logo"
            :src="site.settings.logo"
            :alt="site.title || 'logo'"
          />
          <MotorBadge v-else :label="site.title || 'BGM'" />
        </RouterLink>

        <div class="site-header__actions">
          <span class="site-header__locale">
            <LocaleSelector
              :model-value="locales.current"
              :locales="locales.locales"
              @update:model-value="switchLocale"
            />
          </span>
          <RouterLink
            class="site-header__collection"
            :to="{ name: 'downloads', params: { locale: locales.current, dl: downloadsSegment } }"
            :title="t('nav.downloads')"
          >
            <FileDown :size="20" />
            <span v-if="collection.count" class="site-header__collection-count">
              {{ collection.count }}
            </span>
          </RouterLink>
          <ThemeSelector />
        </div>
      </div>
    </div>

    <nav class="site-header__nav" :class="{ 'is-open': navOpen }">
      <div class="site-header__nav-inner">
        <div class="site-header__nav-locale">
          <LocaleSelector
            :model-value="locales.current"
            :locales="locales.locales"
            @update:model-value="switchLocale"
          />
        </div>
        <ul class="site-header__list">
          <li v-for="page in navPages" :key="page.id">
            <RouterLink
              class="site-header__link"
              :to="{ name: 'page', params: { locale: locales.current, slug: page.slug } }"
              >{{ page.label }}</RouterLink
            >
          </li>
          <li v-for="section in navSections" :key="section.key">
            <RouterLink
              class="site-header__link"
              :to="{
                name: 'entity-index',
                params: { locale: locales.current, section: section.section },
              }"
              >{{ section.label }}</RouterLink
            >
          </li>
          <li>
            <RouterLink
              class="site-header__link site-header__link--downloads"
              :to="{
                name: 'downloads',
                params: { locale: locales.current, dl: downloadsSegment },
              }"
              >{{ t('nav.downloads') }}</RouterLink
            >
          </li>
          <template v-if="auth.isAuthenticated">
            <li>
              <RouterLink
                class="site-header__link"
                :to="{ name: 'account', params: { locale: locales.current } }"
                >{{ t('nav.account') }}</RouterLink
              >
            </li>
            <li>
              <button class="site-header__link site-header__logout" @click="logout">
                {{ t('nav.logout') }}
              </button>
            </li>
          </template>
          <li v-else>
            <RouterLink
              class="site-header__link"
              :to="{ name: 'login', params: { locale: locales.current } }"
              >{{ t('nav.login') }}</RouterLink
            >
          </li>
        </ul>
      </div>
    </nav>
  </header>
</template>
