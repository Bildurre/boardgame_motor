import { defineStore } from 'pinia'
import { ref } from 'vue'
import { readCache, writeCache } from '@edc-motor/ui'
import { api } from '@/lib/api'

export interface Locale {
  code: string
  name: string
}

const LOCALE_KEY = 'edc_app_locale'
const CACHE_KEY = 'edc_app_cache_locales'

// Idioma de la web pública: se envía a la API (?locale) y decide qué slug y
// título se pintan. Persistido en localStorage.
export const useLocalesStore = defineStore('locales', () => {
  // Arranque con la última lista buena (stale-while-revalidate): en visitas
  // repetidas se pinta al instante y load() refresca en segundo plano.
  const cached = readCache<{ locales: Locale[]; default: string }>(CACHE_KEY)
  const locales = ref<Locale[]>(cached?.locales ?? [])
  const defaultLocale = ref(cached?.default ?? 'es')
  const current = ref(localStorage.getItem(LOCALE_KEY) || 'es')

  function applyToApi(code: string) {
    api.defaults.params = { ...(api.defaults.params || {}), locale: code }
  }
  applyToApi(current.value)

  let fresh = false
  let inflight: Promise<void> | null = null

  function fetchFresh(background = false): Promise<void> {
    // El refresco con caché es DE FONDO: no cuenta para el velo del splash.
    inflight ??= api
      .get('/locales', background ? { edcBackground: true } : undefined)
      .then(({ data }) => {
        locales.value = data.locales
        defaultLocale.value = data.default
        writeCache(CACHE_KEY, { locales: data.locales, default: data.default })
        fresh = true
      })
      .finally(() => {
        inflight = null
      })
    return inflight
  }

  function load(): Promise<void> {
    if (fresh) return Promise.resolve()
    if (locales.value.length) {
      // Con caché no se espera a la red: refresco en segundo plano.
      void fetchFresh(true).catch(() => {})
      return Promise.resolve()
    }
    return fetchFresh()
  }

  function setCurrent(code: string) {
    current.value = code
    localStorage.setItem(LOCALE_KEY, code)
    applyToApi(code)
  }

  return { locales, defaultLocale, current, load, setCurrent }
})
