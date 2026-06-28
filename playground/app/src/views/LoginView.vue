<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { BaseButton } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'account' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'No se pudo iniciar sesión.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth">
    <h1>Entrar</h1>
    <form class="form" @submit.prevent="submit">
      <div class="field">
        <label>Email</label>
        <input v-model="email" type="email" required autocomplete="email" />
      </div>
      <div class="field">
        <label>Contraseña</label>
        <input v-model="password" type="password" required autocomplete="current-password" />
      </div>
      <p v-if="error" class="error">{{ error }}</p>
      <BaseButton type="submit">{{ loading ? 'Entrando…' : 'Entrar' }}</BaseButton>
    </form>
    <p class="hint">¿No tienes cuenta? <RouterLink to="/registro">Regístrate</RouterLink>.</p>
  </main>
</template>
