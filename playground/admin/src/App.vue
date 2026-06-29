<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LayoutDashboard, Home } from '@lucide/vue'
import { AdminLayout } from '@bgm/admin-kit'
import { BaseButton, ToastContainer, ConfirmDialog } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const isAdminArea = computed(() => route.meta.admin === true)
const title = computed(() => (route.meta.title as string) ?? '')

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <AdminLayout v-if="isAdminArea" :title="title">
    <template #nav>
      <RouterLink class="nav-item" to="/"><LayoutDashboard :size="18" /><span class="nav-label">Dashboard</span></RouterLink>
      <RouterLink class="nav-item" to="/houses"><Home :size="18" /><span class="nav-label">Houses</span></RouterLink>
    </template>
    <template #actions>
      <span v-if="auth.user" class="who">{{ auth.user.name }}</span>
      <BaseButton variant="secondary" @click="logout">Salir</BaseButton>
    </template>
    <RouterView />
  </AdminLayout>
  <RouterView v-else />

  <ToastContainer />
  <ConfirmDialog />
</template>
