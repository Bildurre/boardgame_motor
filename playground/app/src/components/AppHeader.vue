<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { FileDown, LogIn, LogOut, Menu, X } from '@lucide/vue'
import { LocaleSelector, MotorBadge, ThemeSelector } from '@bgm/ui'
import { api } from '@/lib/api'
import { entitySections } from '@/entities/registry'
import { DOWNLOAD_PATHS } from '@/router/downloads'
import { useAuthStore } from '@/stores/auth'
import { useCollectionStore } from '@/stores/collection'
import { useLocalesStore } from '@/stores/locales'
import { useSiteStore } from '@/stores/site'

// Cabecera pública estilo kontuan en dos líneas: arriba la marca y las
// acciones (idioma, colección, tema y el botón de entrar/usuario); debajo,
// la barra de navegación. En móvil TODO lo del header (salvo el logo) pasa
// a la barra lateral off-canvas. Se oculta al bajar y asoma al subir.
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

const userInitial = computed(() => auth.user?.name?.charAt(0)?.toUpperCase() ?? '?')

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

// Ocultar al bajar, enseñar al subir.
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
    <!-- Línea 1: marca + acciones (solo escritorio; en móvil, burger + logo) -->
    <div class="site-header__bar">
      <div class="site-header__bar-inner">
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
          <!-- Logo SVG inlineado: currentColor hereda el acento -->
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
          <LocaleSelector
            :model-value="locales.current"
            :locales="locales.locales"
            @update:model-value="switchLocale"
          />
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

          <!-- Entrar / usuario: SIEMPRE en la cabecera (patrón kontuan) -->
          <template v-if="auth.isAuthenticated">
            <RouterLink
              class="site-header__user"
              :to="{ name: 'account', params: { locale: locales.current } }"
              :title="t('nav.account')"
            >
              <span class="site-header__avatar">{{ userInitial }}</span>
              <span class="site-header__user-name">{{ auth.user?.name }}</span>
            </RouterLink>
            <button
              class="site-header__logout"
              type="button"
              :title="t('nav.logout')"
              @click="logout"
            >
              <LogOut :size="18" />
            </button>
          </template>
          <RouterLink
            v-else
            class="site-header__login"
            :to="{ name: 'login', params: { locale: locales.current } }"
          >
            <LogIn :size="16" />
            {{ t('nav.login') }}
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Línea 2 (escritorio): la barra de navegación -->
    <nav class="site-header__nav">
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
            :to="{ name: 'downloads', params: { locale: locales.current, dl: downloadsSegment } }"
            >{{ t('nav.downloads') }}</RouterLink
          >
        </li>
      </ul>
    </nav>

    <!-- Barra lateral (móvil): TODO lo del header salvo el logo -->
    <aside class="site-sidebar" :class="{ 'is-open': navOpen }">
      <div class="site-sidebar__prefs">
        <LocaleSelector
          :model-value="locales.current"
          :locales="locales.locales"
          @update:model-value="switchLocale"
        />
        <ThemeSelector />
      </div>
      <hr class="site-sidebar__divider" />

      <ul class="site-sidebar__list">
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
            :to="{ name: 'downloads', params: { locale: locales.current, dl: downloadsSegment } }"
          >
            {{ t('nav.downloads') }}
            <span v-if="collection.count" class="site-header__collection-count">
              {{ collection.count }}
            </span>
          </RouterLink>
        </li>
      </ul>

      <hr class="site-sidebar__divider" />
      <div class="site-sidebar__user">
        <template v-if="auth.isAuthenticated">
          <RouterLink
            class="site-header__user"
            :to="{ name: 'account', params: { locale: locales.current } }"
          >
            <span class="site-header__avatar">{{ userInitial }}</span>
            <span class="site-header__user-name">{{ auth.user?.name }}</span>
          </RouterLink>
          <button
            class="site-header__logout"
            type="button"
            :title="t('nav.logout')"
            @click="logout"
          >
            <LogOut :size="18" />
          </button>
        </template>
        <RouterLink
          v-else
          class="site-header__login"
          :to="{ name: 'login', params: { locale: locales.current } }"
        >
          <LogIn :size="16" />
          {{ t('nav.login') }}
        </RouterLink>
      </div>
    </aside>
  </header>
</template>
