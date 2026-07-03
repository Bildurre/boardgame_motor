<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  FileText,
  LayoutDashboard,
  Home,
  Images,
  Shapes,
  ScrollText,
  Swords,
  LogOut,
} from '@lucide/vue'
import { AdminLayout } from '@bgm/admin-kit'
import { ToastContainer, ConfirmDialog, type Crumb } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'
import { usePageCrumb } from '@/composables/usePageCrumb'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()
const locales = useLocalesStore()

const isAdminArea = computed(() => route.meta.admin === true)
const title = computed(() => (route.meta.titleKey ? t(route.meta.titleKey as string) : ''))
const initial = computed(() => auth.user?.name?.charAt(0)?.toUpperCase() ?? '?')

const homeCrumb = computed<Crumb>(() => ({
  label: t('breadcrumbs.home'),
  to: { name: 'dashboard' },
}))
const { tail } = usePageCrumb()
const crumbs = computed<Crumb[]>(() => {
  const list = (route.meta.breadcrumbs as { key: string; to?: string }[] | undefined) ?? []
  const mapped: Crumb[] = list.map((c) => ({
    label: t(`breadcrumbs.${c.key}`),
    to: c.to ? { name: c.to } : undefined,
  }))
  // Tramo dinámico (el nombre del single), fijado por la vista.
  if (tail.value) mapped.push({ label: tail.value })
  return mapped
})

// Carga los locales cuando entramos al área de admin (para el selector).
// El store deduplica peticiones en vuelo; si falla, los selectores quedan
// vacíos y cada vista ya avisa de sus propios errores de carga.
watch(
  isAdminArea,
  (inAdmin) => {
    if (inAdmin) locales.load().catch(() => {})
  },
  { immediate: true },
)

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

// Resalta el ítem del menú también en las vistas hijas (single de heroe →
// Personajes; single de página → Páginas): cada ruta declara su sección en
// meta.nav y aquí se aplica la clase `active` (mismo estilo que
// router-link-active, que solo cubre la lista).
function navActive(section: string) {
  return { active: route.meta.nav === section }
}
</script>

<template>
  <AdminLayout
    v-if="isAdminArea"
    :title="title"
    brand="BGM Admin"
    :locales="locales.locales"
    :locale="locales.current"
    :home-crumb="homeCrumb"
    :breadcrumbs="crumbs"
    @update:locale="locales.setCurrent"
  >
    <template #nav>
      <RouterLink class="nav-item" :class="navActive('dashboard')" :to="{ name: 'dashboard' }">
        <LayoutDashboard class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.dashboard')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('houses')" :to="{ name: 'houses' }">
        <Home class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.houses') }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('schemes')" :to="{ name: 'schemes' }">
        <ScrollText class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.schemes')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('characters')" :to="{ name: 'characters' }">
        <Swords class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.characters')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('icons')" :to="{ name: 'icons' }">
        <Shapes class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.icons') }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('pages')" :to="{ name: 'pages' }">
        <FileText class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.pages') }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('previews')" :to="{ name: 'previews' }">
        <Images class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.previews') }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :class="navActive('pdfs')" :to="{ name: 'pdfs' }">
        <FileText class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.pdfs') }}</span>
      </RouterLink>
    </template>

    <template #user="{ collapsed }">
      <div class="who">
        <span class="who__avatar">{{ initial }}</span>
        <span v-if="!collapsed" class="who__name">{{ auth.user?.name }}</span>
      </div>
      <button
        v-if="!collapsed"
        class="who-logout"
        type="button"
        :title="t('common.logout')"
        @click="logout"
      >
        <LogOut :size="20" />
      </button>
    </template>

    <RouterView />
  </AdminLayout>
  <RouterView v-else />

  <ToastContainer />
  <ConfirmDialog />
</template>
