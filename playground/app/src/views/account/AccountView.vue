<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { MailCheck, Save } from '@lucide/vue'
import { useRoute } from 'vue-router'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'
import { apiMessage } from '@/lib/apiError'

const auth = useAuthStore()
const route = useRoute()
const locales = useLocalesStore()
const name = ref('')
const email = ref('')
const message = ref<string | null>(null)
const error = ref<string | null>(null)

// Se llega con ?verified=1 desde el enlace del correo de verificación.
const justVerified = computed(() => route.query.verified === '1')
const needsVerification = computed(() => !!auth.user && !auth.user.email_verified)

onMounted(async () => {
  // Tras verificar, refresca los datos aunque ya estuvieran cargados.
  if (!auth.user || justVerified.value) await auth.fetchMe().catch(() => {})
  name.value = auth.user?.name ?? ''
  email.value = auth.user?.email ?? ''
})

async function save() {
  message.value = error.value = null
  try {
    const { data } = await api.put('/account', { name: name.value, email: email.value })
    auth.user = data.data
    message.value = 'Datos guardados.'
  } catch (e) {
    error.value = apiMessage(e, 'No se pudo guardar.')
  }
}

const resending = ref(false)
async function resendVerification() {
  message.value = error.value = null
  resending.value = true
  try {
    const { data } = await api.post('/auth/email/verification-notification')
    message.value = data.message ?? 'Te hemos enviado un correo de verificación.'
  } catch (e) {
    error.value = apiMessage(e, 'No se pudo enviar el correo.')
  } finally {
    resending.value = false
  }
}
</script>

<template>
  <main class="auth">
    <h1>Mi cuenta</h1>
    <p v-if="auth.user" class="roles">
      Rol: <strong>{{ auth.user.roles.join(', ') }}</strong>
    </p>

    <p v-if="justVerified && auth.user?.email_verified" class="ok">
      Tu email ha quedado verificado. ¡Gracias!
    </p>
    <div v-else-if="needsVerification" class="verify-notice">
      <p>
        Tu email <strong>{{ auth.user?.email }}</strong> aún no está verificado. Revisa tu correo o
        vuelve a pedir el enlace.
      </p>
      <BaseButton variant="secondary" :disabled="resending" @click="resendVerification">
        <template #icon><MailCheck :size="16" /></template>
        Reenviar correo de verificación
      </BaseButton>
    </div>

    <form class="form" @submit.prevent="save">
      <div class="field"><label>Nombre</label><input v-model="name" type="text" required /></div>
      <div class="field"><label>Email</label><input v-model="email" type="email" required /></div>
      <p v-if="message" class="ok">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <BaseButton type="submit">
        <template #icon><Save :size="16" /></template>
        Guardar
      </BaseButton>
    </form>

    <p class="hint">
      <RouterLink :to="{ name: 'security', params: { locale: locales.current } }"
        >Cambiar contraseña</RouterLink
      >
    </p>
  </main>
</template>
