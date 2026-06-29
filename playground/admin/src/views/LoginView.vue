<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { BaseButton, MotorBadge } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref<string | null>(route.query.denied ? 'Esta cuenta no tiene acceso al panel.' : null)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    if (!auth.canAccessAdmin) {
      await auth.logout()
      error.value = 'Esta cuenta no tiene acceso al panel de administración.'
      return
    }
    router.push({ name: 'dashboard' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'No se pudo iniciar sesión.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="login">
    <div class="login__box">
      <MotorBadge label="BGM Admin" />
      <h1>Acceso al panel</h1>
      <form class="form" @submit.prevent="submit">
        <div class="field"><label>Email</label><input v-model="email" type="email" required autocomplete="email" /></div>
        <div class="field"><label>Contraseña</label><input v-model="password" type="password" required autocomplete="current-password" /></div>
        <p v-if="error" class="error">{{ error }}</p>
        <BaseButton type="submit">{{ loading ? 'Entrando…' : 'Entrar' }}</BaseButton>
      </form>
    </div>
  </main>
</template>
