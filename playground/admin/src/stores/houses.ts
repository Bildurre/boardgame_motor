import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/lib/api'

export interface HouseOption {
  id: number
  name: Record<string, string>
  slug: Record<string, string>
}

// Lista ligera de casas para selectores (p. ej. la casa de una argucia).
export const useHousesStore = defineStore('houses', () => {
  const options = ref<HouseOption[]>([])
  const loaded = ref(false)

  async function loadOptions(force = false) {
    if (loaded.value && !force) return
    const { data } = await api.get('/admin/houses/options')
    options.value = data.data
    loaded.value = true
  }

  return { options, loaded, loadOptions }
})
