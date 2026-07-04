<script setup lang="ts">
import { ref } from 'vue'
import { UserPlus } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { BaseButton } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'
import { apiMessage } from '@/lib/apiError'

const auth = useAuthStore()
const router = useRouter()
const locales = useLocalesStore()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref<string | null>(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push({ name: 'account', params: { locale: locales.current } })
  } catch (e) {
    error.value = apiMessage(e, 'No se pudo completar el registro.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth">
    <h1>Crear cuenta</h1>
    <form class="form" @submit.prevent="submit">
      <div class="field">
        <label>Nombre</label>
        <input v-model="name" type="text" required />
      </div>
      <div class="field">
        <label>Email</label>
        <input v-model="email" type="email" required autocomplete="email" />
      </div>
      <div class="field">
        <label>Contraseña</label>
        <input v-model="password" type="password" required autocomplete="new-password" />
      </div>
      <div class="field">
        <label>Repite la contraseña</label>
        <input
          v-model="passwordConfirmation"
          type="password"
          required
          autocomplete="new-password"
        />
      </div>
      <p v-if="error" class="error">{{ error }}</p>
      <BaseButton type="submit">
        <template #icon><UserPlus :size="16" /></template>
        {{ loading ? 'Creando…' : 'Crear cuenta' }}
      </BaseButton>
    </form>
    <p class="hint">
      ¿Ya tienes cuenta?
      <RouterLink :to="{ name: 'login', params: { locale: locales.current } }">Entra</RouterLink>.
    </p>
  </main>
</template>
