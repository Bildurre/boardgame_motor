import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/registro',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/cuenta',
      name: 'account',
      component: () => import('@/views/account/AccountView.vue'),
      meta: { auth: true },
    },
    {
      path: '/cuenta/seguridad',
      name: 'security',
      component: () => import('@/views/account/SecurityView.vue'),
      meta: { auth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      // token inválido: el interceptor ya lo limpió
    }
  }
  if (to.meta.auth && !auth.isAuthenticated) return { name: 'login' }
  if (to.meta.guest && auth.isAuthenticated) return { name: 'account' }
})

export default router
