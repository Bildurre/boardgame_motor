<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const name = ref('')
const email = ref('')
const message = ref<string | null>(null)
const error = ref<string | null>(null)

onMounted(async () => {
  if (!auth.user) await auth.fetchMe().catch(() => {})
  name.value = auth.user?.name ?? ''
  email.value = auth.user?.email ?? ''
})

async function save() {
  message.value = error.value = null
  try {
    const { data } = await api.put('/account', { name: name.value, email: email.value })
    auth.user = data.data
    message.value = 'Datos guardados.'
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'No se pudo guardar.'
  }
}
</script>

<template>
  <main class="auth">
    <h1>Mi cuenta</h1>
    <p class="roles" v-if="auth.user">Rol: <strong>{{ auth.user.roles.join(', ') }}</strong></p>

    <form class="form" @submit.prevent="save">
      <div class="field"><label>Nombre</label><input v-model="name" type="text" required /></div>
      <div class="field"><label>Email</label><input v-model="email" type="email" required /></div>
      <p v-if="message" class="ok">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <BaseButton type="submit">Guardar</BaseButton>
    </form>

    <p class="hint"><RouterLink to="/cuenta/seguridad">Cambiar contraseña</RouterLink></p>
  </main>
</template>
