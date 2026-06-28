import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/lib/api'

export interface Locale { code: string; name: string }

export const useLocalesStore = defineStore('locales', () => {
  const locales = ref<Locale[]>([])
  const defaultLocale = ref('es')

  async function load() {
    if (locales.value.length) return
    const { data } = await api.get('/locales')
    locales.value = data.locales
    defaultLocale.value = data.default
  }

  return { locales, defaultLocale, load }
})
