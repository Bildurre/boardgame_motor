<script setup lang="ts">
import { ref } from 'vue'
import { Menu, X, ChevronLeft, ChevronRight } from '@lucide/vue'
import { MotorBadge } from '@bgm/ui'

defineProps<{ title?: string }>()

const drawerOpen = ref(false)
const collapsed = ref(localStorage.getItem('bgm_admin_collapsed') === '1')

function toggleCollapsed() {
  collapsed.value = !collapsed.value
  localStorage.setItem('bgm_admin_collapsed', collapsed.value ? '1' : '0')
}
</script>

<template>
  <div class="admin-layout" :class="{ 'is-drawer-open': drawerOpen, 'is-collapsed': collapsed }">
    <div class="admin-layout__overlay" @click="drawerOpen = false" />

    <aside class="admin-layout__sidebar">
      <div class="admin-layout__brand">
        <MotorBadge label="BGM Admin" />
        <button class="admin-layout__icon-btn admin-layout__close" type="button" aria-label="Cerrar menú" @click="drawerOpen = false">
          <X :size="18" />
        </button>
      </div>

      <nav class="admin-layout__nav" @click="drawerOpen = false">
        <slot name="nav" />
      </nav>

      <button class="admin-layout__icon-btn admin-layout__collapse" type="button" :aria-label="collapsed ? 'Expandir menú' : 'Colapsar menú'" @click="toggleCollapsed">
        <component :is="collapsed ? ChevronRight : ChevronLeft" :size="18" />
      </button>
    </aside>

    <main class="admin-layout__main">
      <header class="admin-layout__header">
        <button class="admin-layout__icon-btn admin-layout__burger" type="button" aria-label="Abrir menú" @click="drawerOpen = true">
          <Menu :size="20" />
        </button>
        <h1>{{ title }}</h1>
        <div class="admin-layout__actions"><slot name="actions" /></div>
      </header>
      <div class="admin-layout__body">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped lang="scss">
.admin-layout {
  --sidebar-w: 240px;
  min-height: 100vh;
  background: $color-bg;
  color: $color-text;
  overflow-x: hidden; // nada se sale del 100% de ancho

  // ---- Drawer (mobile-first). Móvil estrecho: 100% de ancho ----
  &__sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 30;
    display: flex;
    flex-direction: column;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
    background: $color-surface;
    border-right: 1px solid $color-border;
    padding: $space-6 $space-4;
  }
  &.is-drawer-open &__sidebar { transform: none; }

  &__overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }
  &.is-drawer-open &__overlay { opacity: 1; pointer-events: auto; }

  &__brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-2;
    min-width: 0;
    margin-bottom: $space-6;
  }

  &__nav { flex: 1; min-width: 0; }

  &__main { display: flex; flex-direction: column; min-width: 0; }

  &__header {
    display: flex;
    align-items: center;
    gap: $space-3;
    padding: $space-4 $space-6;
    border-bottom: 1px solid $color-border;

    h1 {
      margin: 0;
      flex: 1;
      min-width: 0;
      font-size: 1.15rem;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  &__actions { display: flex; align-items: center; gap: $space-2; flex-shrink: 0; }
  &__body { padding: $space-4 $space-6; min-width: 0; max-width: 100%; }

  &__icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: $space-2;
    background: none;
    border: none;
    color: $color-text;
    cursor: pointer;
    border-radius: $radius-md;
    &:hover { background: rgba(255, 255, 255, 0.06); }
  }

  // El botón de colapsar solo existe en desktop (declarado tras __icon-btn
  // para ganar al display de éste).
  &__collapse { display: none; }

  // ---- Móvil ancho / tablet vertical: el drawer ya no es full width ----
  @media (min-width: #{$bp-sm}) {
    &__sidebar { width: 280px; }
  }

  // ---- Tablet horizontal / desktop: sidebar fijo y colapsable a rail ----
  @media (min-width: #{$bp-lg}) {
    display: grid;
    grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
    transition: grid-template-columns 0.2s ease;

    .admin-layout__sidebar { position: static; transform: none; width: auto; z-index: auto; }
    .admin-layout__overlay,
    .admin-layout__burger,
    .admin-layout__close { display: none; }
    .admin-layout__collapse { display: inline-flex; align-self: flex-start; }

    &.is-collapsed {
      --sidebar-w: 68px;
      .admin-layout__sidebar { padding: $space-6 $space-2; }
      .admin-layout__brand { display: none; }
      .admin-layout__collapse { align-self: center; }
      :deep(.nav-label) { display: none; }
      :deep(.nav-item) { justify-content: center; }
    }
  }
}
</style>
