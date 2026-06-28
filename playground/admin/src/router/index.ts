import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guest: true } },
    { path: '/', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { admin: true, title: 'Dashboard' } },
    { path: '/houses', name: 'houses', component: () => import('@/views/houses/HousesListView.vue'), meta: { admin: true, title: 'Houses' } },
    { path: '/houses/new', name: 'house-new', component: () => import('@/views/houses/HouseFormView.vue'), meta: { admin: true, title: 'Nueva house' } },
    { path: '/houses/:id/edit', name: 'house-edit', component: () => import('@/views/houses/HouseFormView.vue'), meta: { admin: true, title: 'Editar house' } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (auth.token && !auth.user) {
    try { await auth.fetchMe() } catch { /* token inválido */ }
  }
  if (to.meta.admin) {
    if (!auth.isAuthenticated) return { name: 'login' }
    if (!auth.canAccessAdmin) {
      await auth.logout()
      return { name: 'login', query: { denied: '1' } }
    }
  }
  if (to.meta.guest && auth.isAuthenticated && auth.canAccessAdmin) return { name: 'dashboard' }
})

export default router
