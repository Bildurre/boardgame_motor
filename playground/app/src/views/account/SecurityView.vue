<script setup lang="ts">
import { ref } from 'vue'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'

const currentPassword = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const message = ref<string | null>(null)
const error = ref<string | null>(null)

async function save() {
  message.value = error.value = null
  try {
    await api.put('/account/password', {
      current_password: currentPassword.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    message.value = 'Contraseña actualizada.'
    currentPassword.value = password.value = passwordConfirmation.value = ''
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'No se pudo cambiar la contraseña.'
  }
}
</script>

<template>
  <main class="auth">
    <h1>Cambiar contraseña</h1>
    <form class="form" @submit.prevent="save">
      <div class="field"><label>Contraseña actual</label><input v-model="currentPassword" type="password" required /></div>
      <div class="field"><label>Nueva contraseña</label><input v-model="password" type="password" required /></div>
      <div class="field"><label>Repite la nueva</label><input v-model="passwordConfirmation" type="password" required /></div>
      <p v-if="message" class="ok">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <BaseButton type="submit">Actualizar</BaseButton>
    </form>
    <p class="hint"><RouterLink to="/cuenta">Volver a mi cuenta</RouterLink></p>
  </main>
</template>
