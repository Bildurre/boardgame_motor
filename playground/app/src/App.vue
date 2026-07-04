<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppNav from '@/components/AppNav.vue'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'
import { useSiteStore } from '@/stores/site'

const auth = useAuthStore()
const route = useRoute()
const site = useSiteStore()
const locales = useLocalesStore()

// Las rutas "desnudas" (p. ej. /_render para la captura a PNG) van sin nav.
const bare = computed(() => route.meta.bare === true)

onMounted(async () => {
  if (auth.token && !auth.user) auth.fetchMe().catch(() => {})
  // La configuración usa el locale para título/pie: locales primero.
  await locales.load().catch(() => {})
  await site.load()
})
</script>

<template>
  <AppNav v-if="!bare" />
  <RouterView />
  <footer v-if="!bare && (site.footerText || site.title)" class="app-footer">
    <span>{{ site.footerText || site.title }}</span>
  </footer>
</template>
