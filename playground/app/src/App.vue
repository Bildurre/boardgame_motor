<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppNav from '@/components/AppNav.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()

// Las rutas "desnudas" (p. ej. /_render para la captura a PNG) van sin nav.
const bare = computed(() => route.meta.bare === true)

onMounted(() => {
  if (auth.token && !auth.user) auth.fetchMe().catch(() => {})
})
</script>

<template>
  <AppNav v-if="!bare" />
  <RouterView />
</template>
