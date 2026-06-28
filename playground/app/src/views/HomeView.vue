<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { MotorBadge, BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

interface Ping { name: string; version: string; default_locale: string; locales: string[] }

const auth = useAuthStore()
const ping = ref<Ping | null>(null)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/motor/ping')
    ping.value = data
  } catch (e) {
    error.value = String(e)
  }
})
</script>

<template>
  <main class="home">
    <MotorBadge label="BGM" :version="ping?.version ?? ''" />
    <h1>Playground · Web pública</h1>
    <p v-if="auth.user">Hola, <strong>{{ auth.user.name }}</strong>.</p>

    <section class="card" v-if="ping">
      <h2>{{ ping.name }}</h2>
      <ul>
        <li>Versión del motor: <strong>{{ ping.version }}</strong></li>
        <li>Locale por defecto: <strong>{{ ping.default_locale }}</strong></li>
        <li>Locales: <strong>{{ ping.locales.join(', ') }}</strong></li>
      </ul>
    </section>
    <p v-else-if="error" class="error">No conecta con la API: {{ error }}</p>

    <RouterLink v-if="!auth.isAuthenticated" to="/login"><BaseButton>Entrar</BaseButton></RouterLink>
    <RouterLink v-else to="/cuenta"><BaseButton>Ir a mi cuenta</BaseButton></RouterLink>
  </main>
</template>

<style scoped lang="scss">
.home {
  max-width: 640px;
  margin: 0 auto;
  padding: $space-8 $space-6;
  display: flex;
  flex-direction: column;
  gap: $space-4;
  align-items: flex-start;

  h1 { margin: 0; }
  .card {
    width: 100%;
    padding: $space-6;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
    background: $color-surface;
  }
  .error { color: #ff6b6b; }
}
</style>
