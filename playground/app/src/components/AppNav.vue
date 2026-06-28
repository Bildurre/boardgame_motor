<script setup lang="ts">
import { useRouter } from 'vue-router'
import { MotorBadge } from '@bgm/ui'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <nav class="nav">
    <RouterLink to="/" class="nav__brand"><MotorBadge label="BGM" /></RouterLink>
    <div class="nav__links">
      <RouterLink to="/">Inicio</RouterLink>
      <template v-if="auth.isAuthenticated">
        <RouterLink to="/cuenta">Mi cuenta</RouterLink>
        <button class="nav__logout" @click="logout">Salir</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Entrar</RouterLink>
        <RouterLink to="/registro">Registro</RouterLink>
      </template>
    </div>
  </nav>
</template>

<style scoped lang="scss">
.nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-3 $space-6;
  border-bottom: 1px solid $color-border;
  background: $color-surface;

  &__brand { text-decoration: none; }

  &__links {
    display: flex;
    align-items: center;
    gap: $space-4;

    a { color: $color-text-muted; text-decoration: none; &:hover { color: $color-text; } }
    a.router-link-active { color: $color-text; }
  }

  &__logout {
    font: inherit;
    background: none;
    border: none;
    color: $color-text-muted;
    cursor: pointer;
    &:hover { color: $color-accent; }
  }
}
</style>
