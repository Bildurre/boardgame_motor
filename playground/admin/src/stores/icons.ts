import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/lib/api'

export interface Icon {
  id: number
  name: string
  slug: string
  url: string | null
}

// Biblioteca de iconos del juego (la sirve el motor: GET /icons).
export const useIconsStore = defineStore('icons', () => {
  const icons = ref<Icon[]>([])
  const loaded = ref(false)

  async function load(force = false) {
    if (loaded.value && !force) return
    const { data } = await api.get('/icons')
    icons.value = data.data
    loaded.value = true
  }

  return { icons, loaded, load }
})
