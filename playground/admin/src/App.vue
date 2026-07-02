<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { LayoutDashboard, Home, Shapes, ScrollText, Swords, LogOut } from '@lucide/vue'
import { AdminLayout } from '@bgm/admin-kit'
import { ToastContainer, ConfirmDialog, type Crumb } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'

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
const crumbs = computed<Crumb[]>(() => {
  const list = (route.meta.breadcrumbs as { key: string; to?: string }[] | undefined) ?? []
  return list.map((c) => ({
    label: t(`breadcrumbs.${c.key}`),
    to: c.to ? { name: c.to } : undefined,
  }))
})

// Carga los locales cuando entramos al área de admin (para el selector).
watch(
  isAdminArea,
  (inAdmin) => {
    if (inAdmin) locales.load()
  },
  { immediate: true },
)
onMounted(() => {
  if (isAdminArea.value) locales.load()
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
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
      <RouterLink class="nav-item" :to="{ name: 'dashboard' }">
        <LayoutDashboard class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.dashboard')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :to="{ name: 'houses' }">
        <Home class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.houses') }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :to="{ name: 'schemes' }">
        <ScrollText class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.schemes')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :to="{ name: 'characters' }">
        <Swords class="nav-icon" :size="20" /><span class="nav-label">{{
          t('nav.characters')
        }}</span>
      </RouterLink>
      <RouterLink class="nav-item" :to="{ name: 'icons' }">
        <Shapes class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.icons') }}</span>
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
