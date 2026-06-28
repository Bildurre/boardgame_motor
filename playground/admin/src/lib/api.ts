import { createApi } from '@bgm/ui'

export const TOKEN_KEY = 'bgm_admin_token'

export const api = createApi({
  baseURL: import.meta.env.VITE_API_URL,
  tokenKey: TOKEN_KEY,
  onUnauthorized: () => localStorage.removeItem(TOKEN_KEY),
})
