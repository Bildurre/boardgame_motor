<script setup lang="ts">
import { onMounted } from 'vue'
import AppNav from '@/components/AppNav.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
onMounted(() => {
  if (auth.token && !auth.user) auth.fetchMe().catch(() => {})
})
</script>

<template>
  <AppNav />
  <RouterView />
</template>
