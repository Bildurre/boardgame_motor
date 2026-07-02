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
  let inflight: Promise<void> | null = null

  function loadOptions(force = false): Promise<void> {
    if (loaded.value && !force) return Promise.resolve()
    inflight ??= api
      .get('/admin/houses/options')
      .then(({ data }) => {
        options.value = data.data
        loaded.value = true
      })
      .finally(() => {
        inflight = null
      })
    return inflight
  }

  return { options, loaded, loadOptions }
})
