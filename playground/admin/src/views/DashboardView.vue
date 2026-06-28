<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AdminLayout } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

interface Ping { name: string; version: string; locales: string[] }

const auth = useAuthStore()
const router = useRouter()
const ping = ref<Ping | null>(null)

onMounted(async () => {
  const { data } = await api.get('/motor/ping')
  ping.value = data
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <AdminLayout title="Dashboard">
    <template #nav>
      <a class="nav-item is-active">Inicio</a>
      <a class="nav-item">Entidades (futuro)</a>
      <a class="nav-item">CRM (futuro)</a>
    </template>
    <template #actions>
      <BaseButton variant="secondary" @click="logout">Salir</BaseButton>
    </template>

    <p v-if="auth.user">
      Conectado como <strong>{{ auth.user.name }}</strong>
      ({{ auth.user.roles.join(', ') }}).
    </p>
    <p v-if="ping">
      Motor <strong>{{ ping.name }}</strong> v{{ ping.version }} ·
      locales {{ ping.locales.join(', ') }}
    </p>
  </AdminLayout>
</template>

<style scoped lang="scss">
.nav-item {
  display: block;
  padding: $space-2 $space-3;
  border-radius: $radius-md;
  color: $color-text-muted;
  cursor: pointer;
  &:hover { color: $color-text; }
  &.is-active { color: $color-text; background: rgba(108, 92, 231, 0.15); }
}
</style>
