import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, TOKEN_KEY } from '@/lib/api'

export interface User {
  id: number
  name: string
  email: string
  roles: string[]
  can_access_admin: boolean
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const isAuthenticated = computed(() => !!token.value)

  function setToken(value: string | null) {
    token.value = value
    if (value) localStorage.setItem(TOKEN_KEY, value)
    else localStorage.removeItem(TOKEN_KEY)
  }

  async function login(email: string, password: string) {
    const { data } = await api.post('/auth/login', { email, password })
    setToken(data.token)
    user.value = data.user
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }) {
    const { data } = await api.post('/auth/register', payload)
    setToken(data.token)
    user.value = data.user
  }

  async function fetchMe() {
    const { data } = await api.get('/auth/me')
    user.value = data.data
  }

  /** Limpieza local (sin llamar a la API): para 401 del interceptor. */
  function clearSession() {
    setToken(null)
    user.value = null
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch {
      // da igual si falla; limpiamos igualmente
    }
    clearSession()
  }

  return { user, token, isAuthenticated, login, register, fetchMe, logout, clearSession }
})
