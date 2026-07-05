<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { BaseButton, useTheme, setThemePersistence } from '@bgm/ui'
import { acceptConsent, consentState, rejectConsent } from '@/lib/consent'
import { TOKEN_KEY, api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import { useAuthStore } from '@/stores/auth'

// Banner de almacenamiento (no usamos cookies): hasta aceptar, NADA se
// persiste en el navegador; al aceptar se guardan las preferencias actuales.
// Al rechazar, todo sigue funcionando pero solo en memoria (se pierde al
// recargar) y se limpia lo que hubiera.
const { t } = useI18n()
const consent = consentState()
const locales = useLocalesStore()
const auth = useAuthStore()
const { themeMode } = useTheme()

setThemePersistence(consent.value === 'accepted')

function accept() {
  acceptConsent(() => {
    setThemePersistence(true)
    // Persistir el estado actual, que hasta ahora vivía solo en memoria.
    localStorage.setItem('bgm_app_locale', locales.current)
    localStorage.setItem('theme', themeMode.value)
    if (auth.token) localStorage.setItem(TOKEN_KEY, auth.token)
    const guest = api.defaults.headers.common['X-Collection-Token']
    if (typeof guest === 'string' && guest) localStorage.setItem('bgm_collection_token', guest)
  })
}

function reject() {
  setThemePersistence(false)
  rejectConsent()
}
</script>

<template>
  <div v-if="consent === null" class="consent-banner" role="dialog" aria-live="polite">
    <p class="consent-banner__text">{{ t('consent.text') }}</p>
    <div class="consent-banner__actions">
      <BaseButton variant="secondary" @click="reject">{{ t('consent.reject') }}</BaseButton>
      <BaseButton @click="accept">{{ t('consent.accept') }}</BaseButton>
    </div>
  </div>
</template>
