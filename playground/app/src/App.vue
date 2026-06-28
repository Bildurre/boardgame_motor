<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { MotorBadge, BaseButton } from '@bgm/ui'

interface Ping {
  name: string
  version: string
  default_locale: string
  locales: string[]
}

const apiUrl = import.meta.env.VITE_API_URL
const ping = ref<Ping | null>(null)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const res = await fetch(`${apiUrl}/motor/ping`)
    ping.value = await res.json()
  } catch (e) {
    error.value = String(e)
  }
})
</script>

<template>
  <main class="home">
    <MotorBadge label="BGM" :version="ping?.version ?? ''" />
    <h1>Playground · Web pública</h1>
    <p>Esta app consume <code>@bgm/ui</code> y la API <code>bgm/core</code>.</p>

    <section class="card" v-if="ping">
      <h2>{{ ping.name }}</h2>
      <ul>
        <li>Versión del motor: <strong>{{ ping.version }}</strong></li>
        <li>Locale por defecto: <strong>{{ ping.default_locale }}</strong></li>
        <li>Locales: <strong>{{ ping.locales.join(', ') }}</strong></li>
      </ul>
    </section>
    <p v-else-if="error" class="error">No conecta con la API: {{ error }}</p>
    <p v-else>Cargando datos del motor…</p>

    <BaseButton>Botón del motor</BaseButton>
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
  color: $color-text;

  h1 { margin: 0; }
  code { color: $color-accent; }

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
