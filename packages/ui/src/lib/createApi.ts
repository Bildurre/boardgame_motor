import axios, { type AxiosInstance } from 'axios'

export interface CreateApiOptions {
  /** URL base de la API (p. ej. http://localhost:8010/api). */
  baseURL: string
  /** Clave de localStorage donde se guarda el token. */
  tokenKey: string
  /** Se invoca cuando la API responde 401 (token inválido/expirado). */
  onUnauthorized?: () => void
  /**
   * Locale ACTIVO de la interfaz (getter, se evalúa en cada petición): viaja
   * como `?locale=` para que el SetLocale del servidor busque y ordene por el
   * idioma que el usuario está viendo, no por el Accept-Language del
   * navegador. Una petición con `locale` propio en params no se pisa.
   */
  locale?: () => string | null | undefined
}

/**
 * Crea una instancia de axios con el token Bearer inyectado desde localStorage
 * y manejo de 401. Patrón compartido entre admin y app (estilo kontuan).
 */
export function createApi(options: CreateApiOptions): AxiosInstance {
  const api = axios.create({
    baseURL: options.baseURL,
    headers: { Accept: 'application/json' },
  })

  api.interceptors.request.use((config) => {
    const token = localStorage.getItem(options.tokenKey)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    const locale = options.locale?.()
    if (locale && !(config.params && 'locale' in config.params)) {
      config.params = { ...config.params, locale }
    }
    return config
  })

  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        localStorage.removeItem(options.tokenKey)
        options.onUnauthorized?.()
      }
      return Promise.reject(error)
    },
  )

  return api
}
