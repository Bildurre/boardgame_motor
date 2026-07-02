import { ref } from 'vue'
import type { AxiosInstance } from 'axios'

/** Meta de paginación que devuelve la API ({ data, meta }). */
export interface ResourceMeta {
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

/**
 * Composable de CRUD genérico sobre la API REST del motor. Cada juego lo usa
 * para sus entidades sin reescribir el ir-y-venir con axios.
 */
export function useResource<T = unknown>(api: AxiosInstance, basePath: string) {
  const items = ref<T[]>([])
  const meta = ref<ResourceMeta | null>(null)
  const loading = ref(false)

  async function list(params: Record<string, unknown> = {}) {
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

  async function create(payload: Record<string, unknown>): Promise<T> {
    const { data } = await api.post(basePath, payload)
    return data.data
  }

  async function update(id: number | string, payload: Record<string, unknown>): Promise<T> {
    const { data } = await api.put(`${basePath}/${id}`, payload)
    return data.data
  }

  async function remove(id: number | string): Promise<void> {
    await api.delete(`${basePath}/${id}`)
  }

  /** Alta con multipart (FormData) — para subir ficheros. */
  async function createForm(form: FormData): Promise<T> {
    const { data } = await api.post(basePath, form)
    return data.data
  }

  /** Edición con multipart. PHP no parsea multipart en PUT: se usa POST + _method. */
  async function updateForm(id: number | string, form: FormData): Promise<T> {
    form.append('_method', 'PUT')
    const { data } = await api.post(`${basePath}/${id}`, form)
    return data.data
  }

  /** Acciones POST extra: /{id}/{verb} (toggle-published, restore, …). */
  async function action(id: number | string, verb: string): Promise<T> {
    const { data } = await api.post(`${basePath}/${id}/${verb}`)
    return data.data
  }

  return {
    items,
    meta,
    loading,
    list,
    find,
    create,
    update,
    createForm,
    updateForm,
    remove,
    action,
  }
}
