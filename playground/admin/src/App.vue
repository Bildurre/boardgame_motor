<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AdminLayout } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
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
      <RouterLink class="nav-item" to="/">Dashboard</RouterLink>
      <RouterLink class="nav-item" to="/houses">Houses</RouterLink>
    </template>
    <template #actions>
      <span v-if="auth.user" class="who">{{ auth.user.name }}</span>
      <BaseButton variant="secondary" @click="logout">Salir</BaseButton>
    </template>
    <RouterView />
  </AdminLayout>
  <RouterView v-else />
</template>

<style scoped lang="scss">
.nav-item {
  display: block;
  padding: $space-2 $space-3;
  border-radius: $radius-md;
  color: $color-text-muted;
  text-decoration: none;
  &:hover { color: $color-text; }
  &.router-link-exact-active { color: $color-text; background: rgba(108, 92, 231, 0.15); }
}
.who { color: $color-text-muted; margin-right: $space-3; }
</style>
