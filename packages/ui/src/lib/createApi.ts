import axios, { type AxiosInstance } from 'axios'

export interface CreateApiOptions {
  /** URL base de la API (p. ej. http://localhost:8010/api). */
  baseURL: string
  /** Clave de localStorage donde se guarda el token. */
  tokenKey: string
  /** Se invoca cuando la API responde 401 (token inválido/expirado). */
  onUnauthorized?: () => void
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
