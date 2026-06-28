import { createApi } from '@bgm/ui'

export const TOKEN_KEY = 'bgm_app_token'

export const api = createApi({
  baseURL: import.meta.env.VITE_API_URL,
  tokenKey: TOKEN_KEY,
  onUnauthorized: () => {
    // El token dejó de ser válido: lo limpiamos (el guard llevará a /login).
    localStorage.removeItem(TOKEN_KEY)
  },
})
