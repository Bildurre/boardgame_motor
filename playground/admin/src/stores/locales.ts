import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/lib/api'

export interface Locale { code: string; name: string }

const LOCALE_KEY = 'bgm_admin_locale'

export const useLocalesStore = defineStore('locales', () => {
  const locales = ref<Locale[]>([])
  const defaultLocale = ref('es')
  // Locale de contenido activo (qué traducción mostrar / enviar a la API).
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
    if (!localStorage.getItem(LOCALE_KEY)) {
      current.value = data.default
      applyToApi(current.value)
    }
  }

  function setCurrent(code: string) {
    current.value = code
    localStorage.setItem(LOCALE_KEY, code)
    applyToApi(code)
  }

  return { locales, defaultLocale, current, load, setCurrent }
})
