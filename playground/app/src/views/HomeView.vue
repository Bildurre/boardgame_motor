<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { LogIn, User } from '@lucide/vue'
import { MotorBadge, BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { blockRegistry } from '@/blocks/registry'
import { templateFor } from '@/templates/registry'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'

// Si el CRM tiene una home publicada, la home ES esa página (doc 03); el
// contenido de siempre queda de fallback mientras no la haya.
interface HomePage {
  template: string | null
  meta: { title: string; description: string }
  blocks: {
    id: number
    component: string
    settings: Record<string, unknown>
    data: Record<string, unknown>
  }[]
}

const locales = useLocalesStore()
const homePage = ref<HomePage | null>(null)

async function loadHome() {
  try {
    const { data } = await api.get('/pages/home')
    homePage.value = data.data
    document.title = homePage.value?.meta.title ?? document.title
  } catch {
    homePage.value = null // sin home del CRM: fallback
  }
}

watch(() => locales.current, loadHome)

interface Ping {
  name: string
  version: string
  default_locale: string
  locales: string[]
}

const auth = useAuthStore()
const ping = ref<Ping | null>(null)
const error = ref<string | null>(null)

onMounted(async () => {
  await locales.load()
  await loadHome()
  try {
    const { data } = await api.get('/motor/ping')
    ping.value = data
  } catch (e) {
    error.value = String(e)
  }
})
</script>

<template>
  <!-- Home del CRM (si la hay), con su plantilla -->
  <component :is="templateFor(homePage.template)" v-if="homePage">
    <component
      :is="blockRegistry[block.component]"
      v-for="block in homePage.blocks.filter((b) => blockRegistry[b.component])"
      :key="block.id"
      :settings="block.settings"
      :data="block.data"
    />
  </component>

  <main v-else class="home">
    <MotorBadge label="BGM" :version="ping?.version ?? ''" />
    <h1>Playground · Web pública</h1>
    <p v-if="auth.user">
      Hola, <strong>{{ auth.user.name }}</strong
      >.
    </p>

    <section v-if="ping" class="card">
      <h2>{{ ping.name }}</h2>
      <ul>
        <li>
          Versión del motor: <strong>{{ ping.version }}</strong>
        </li>
        <li>
          Locale por defecto: <strong>{{ ping.default_locale }}</strong>
        </li>
        <li>
          Locales: <strong>{{ ping.locales.join(', ') }}</strong>
        </li>
      </ul>
    </section>
    <p v-else-if="error" class="error">No conecta con la API: {{ error }}</p>

    <RouterLink v-if="!auth.isAuthenticated" to="/login"
      ><BaseButton>
        <template #icon><LogIn :size="16" /></template>
        Entrar
      </BaseButton></RouterLink
    >
    <RouterLink v-else to="/cuenta"
      ><BaseButton>
        <template #icon><User :size="16" /></template>
        Ir a mi cuenta
      </BaseButton></RouterLink
    >
  </main>
</template>
