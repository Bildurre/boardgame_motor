<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { LocaleSelector, MotorBadge, ThemeSelector } from '@bgm/ui'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'

const auth = useAuthStore()
const router = useRouter()
const locales = useLocalesStore()

// Menú público: páginas raíz publicadas del CRM (título y slug por locale).
interface NavPage {
  id: number
  title: Record<string, string>
  slugs: Record<string, string>
  is_home: boolean
}

const pages = ref<NavPage[]>([])

const navPages = computed(() =>
  pages.value
    .filter((p) => !p.is_home)
    .map((p) => ({
      id: p.id,
      label: p.title[locales.current] || Object.values(p.title)[0] || '',
      slug: p.slugs[locales.current] || Object.values(p.slugs)[0] || '',
    })),
)

async function logout() {
  await auth.logout()
  router.push({ name: 'home' })
}

onMounted(async () => {
  await locales.load()
  try {
    const { data } = await api.get('/pages/nav')
    pages.value = data.data
  } catch {
    // sin menú de páginas: la nav base sigue funcionando
  }
})
</script>

<template>
  <nav class="nav">
    <RouterLink to="/" class="nav__brand"><MotorBadge label="BGM" /></RouterLink>
    <div class="nav__links">
      <RouterLink to="/">Inicio</RouterLink>
      <RouterLink
        v-for="page in navPages"
        :key="page.id"
        :to="{ name: 'page', params: { slug: page.slug } }"
        >{{ page.label }}</RouterLink
      >
      <template v-if="auth.isAuthenticated">
        <RouterLink to="/cuenta">Mi cuenta</RouterLink>
        <button class="nav__logout" @click="logout">Salir</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Entrar</RouterLink>
      </template>
      <!-- Selectores provisionales de idioma y tema (el diseño final llega
           con el andamiaje de la web pública, Fase 6) -->
      <LocaleSelector
        :model-value="locales.current"
        :locales="locales.locales"
        @update:model-value="locales.setCurrent"
      />
      <ThemeSelector />
    </div>
  </nav>
</template>
