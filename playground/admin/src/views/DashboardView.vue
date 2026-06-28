<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

interface Ping { name: string; version: string; locales: string[] }
const auth = useAuthStore()
const ping = ref<Ping | null>(null)

onMounted(async () => {
  const { data } = await api.get('/motor/ping')
  ping.value = data
})
</script>

<template>
  <p v-if="auth.user">Conectado como <strong>{{ auth.user.name }}</strong> ({{ auth.user.roles.join(', ') }}).</p>
  <p v-if="ping">Motor <strong>{{ ping.name }}</strong> v{{ ping.version }} · locales {{ ping.locales.join(', ') }}</p>
</template>
