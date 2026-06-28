import { ref } from 'vue'
import type { AxiosInstance } from 'axios'

/**
 * Composable de CRUD genérico sobre la API REST del motor. Cada juego lo usa
 * para sus entidades sin reescribir el ir-y-venir con axios.
 */
export function useResource<T = any>(api: AxiosInstance, basePath: string) {
  const items = ref<T[]>([])
  const meta = ref<Record<string, any> | null>(null)
  const loading = ref(false)

  async function list(params: Record<string, any> = {}) {
    loading.value = true
    try {
      const { data } = await api.get(basePath, { params })
      items.value = data.data
      meta.value = data.meta ?? null
    } finally {
      loading.value = false
    }
  }

  async function find(id: number | string): Promise<T> {
    const { data } = await api.get(`${basePath}/${id}`)
    return data.data
  }

  async function create(payload: Record<string, any>): Promise<T> {
    const { data } = await api.post(basePath, payload)
    return data.data
  }

  async function update(id: number | string, payload: Record<string, any>): Promise<T> {
    const { data } = await api.put(`${basePath}/${id}`, payload)
    return data.data
  }

  async function remove(id: number | string): Promise<void> {
    await api.delete(`${basePath}/${id}`)
  }

  /** Acciones POST extra: /{id}/{verb} (toggle-published, restore, …). */
  async function action(id: number | string, verb: string): Promise<T> {
    const { data } = await api.post(`${basePath}/${id}/${verb}`)
    return data.data
  }

  return { items, meta, loading, list, find, create, update, remove, action }
}
