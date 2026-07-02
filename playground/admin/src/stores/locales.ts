import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/lib/api'
import i18n, { LOCALE_KEY } from '@/i18n'
import { onLocaleChange } from '@/router'

export interface Locale {
  code: string
  name: string
}

// Idioma único de la app: UI (vue-i18n) + rutas (segmentos traducidos) +
// contenido (locale enviado a la API). El selector llama a setCurrent().
export const useLocalesStore = defineStore('locales', () => {
  const locales = ref<Locale[]>([])
  const defaultLocale = ref('es')
  const current = ref(localStorage.getItem(LOCALE_KEY) || 'es')

  // La API prioriza ?locale (ver SetLocale del motor).
  function applyToApi(code: string) {
    api.defaults.params = { ...(api.defaults.params || {}), locale: code }
  }
  applyToApi(current.value)

  async function load() {
    if (locales.value.length) return
    const { data } = await api.get('/locales')
    locales.value = data.locales
    defaultLocale.value = data.default
  }

  function setCurrent(code: string) {
    if (code === current.value) return
    current.value = code
    localStorage.setItem(LOCALE_KEY, code)
    applyToApi(code)
    ;(i18n.global.locale as unknown as { value: string }).value = code
    onLocaleChange(code)
  }

  return { locales, defaultLocale, current, load, setCurrent }
})
