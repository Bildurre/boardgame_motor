import type { AxiosInstance, AxiosRequestConfig } from 'axios'

// Caché SWR en MEMORIA para los GET de contenido de la web pública
// (stale-while-revalidate): la primera visita a una URL espera a la red
// (y el velo de navegación puede aparecer); las siguientes se sirven de la
// memoria AL INSTANTE — sin velo, sin negro — mientras una revalidación DE
// FONDO (edcBackground: no cuenta para el velo) trae lo fresco y solo
// re-aplica si algo cambió. La memoria vive lo que la pestaña: nada que
// invalidar entre sesiones.
//
// Uso: const swrGet = createSwrGet(api)  // una vez, junto al cliente
//      await swrGet<Payload>('/pages/home', undefined, (data) => { ... })
// El callback puede llegar DOS veces (caché y luego fresco): debe ser
// idempotente y, si la vista pudo cambiar mientras tanto (requestId),
// comprobar dentro que sigue vigente.

export interface SwrGetter {
  <T>(
    url: string,
    config: AxiosRequestConfig | undefined,
    onData: (data: T, fresh: boolean) => void,
  ): Promise<void>
  /** Vacía la memoria (p. ej. al cerrar sesión, si el contenido depende de ella). */
  clear(): void
}

export function createSwrGet(api: AxiosInstance): SwrGetter {
  const memory = new Map<string, unknown>()

  // La clave incluye los params por defecto del cliente (el `?locale` que
  // inyecta el store de locales) además de los de la petición: la misma URL
  // en otro idioma es OTRA entrada.
  function keyFor(url: string, config?: AxiosRequestConfig): string {
    const params: Record<string, unknown> = {
      ...(api.defaults.params as Record<string, unknown> | undefined),
      ...(config?.params as Record<string, unknown> | undefined),
    }
    const query = Object.keys(params)
      .sort()
      .map((k) => `${k}=${JSON.stringify(params[k])}`)
      .join('&')
    return `${url}?${query}`
  }

  async function run<T>(
    url: string,
    config: AxiosRequestConfig | undefined,
    onData: (data: T, fresh: boolean) => void,
  ): Promise<void> {
    const key = keyFor(url, config)
    if (memory.has(key)) {
      const cached = memory.get(key) as T
      onData(cached, false)
      void api
        .get<T>(url, { ...config, edcBackground: true })
        .then(({ data }) => {
          memory.set(key, data)
          if (JSON.stringify(data) !== JSON.stringify(cached)) onData(data, true)
        })
        .catch(() => {
          // la revalidación es oportunista: si falla, se queda lo cacheado
        })
      return
    }
    const { data } = await api.get<T>(url, config)
    memory.set(key, data)
    onData(data, true)
  }

  const getter = run as SwrGetter
  getter.clear = () => memory.clear()
  return getter
}
