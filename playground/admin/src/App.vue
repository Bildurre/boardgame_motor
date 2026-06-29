<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LayoutDashboard, Home, LogOut } from '@lucide/vue'
import { AdminLayout } from '@bgm/admin-kit'
import { ToastContainer, ConfirmDialog } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const locales = useLocalesStore()

const isAdminArea = computed(() => route.meta.admin === true)
const title = computed(() => (route.meta.title as string) ?? '')
const initial = computed(() => auth.user?.name?.charAt(0)?.toUpperCase() ?? '?')

// Carga los locales cuando entramos al área de admin (para el selector).
watch(
  isAdminArea,
  (inAdmin) => { if (inAdmin) locales.load() },
  { immediate: true },
)
onMounted(() => { if (isAdminArea.value) locales.load() })

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
    @update:locale="locales.setCurrent"
  >
    <template #nav>
      <RouterLink class="nav-item" :to="{ name: 'dashboard' }">
        <LayoutDashboard class="nav-icon" :size="20" /><span class="nav-label">Dashboard</span>
      </RouterLink>
      <RouterLink class="nav-item" :to="{ name: 'houses' }">
        <Home class="nav-icon" :size="20" /><span class="nav-label">Houses</span>
      </RouterLink>
    </template>

    <template #user="{ collapsed }">
      <div class="who">
        <span class="who__avatar">{{ initial }}</span>
        <span v-if="!collapsed" class="who__name">{{ auth.user?.name }}</span>
      </div>
      <button v-if="!collapsed" class="who-logout" type="button" title="Salir" @click="logout">
        <LogOut :size="20" />
      </button>
    </template>

    <RouterView />
  </AdminLayout>
  <RouterView v-else />

  <ToastContainer />
  <ConfirmDialog />
</template>
