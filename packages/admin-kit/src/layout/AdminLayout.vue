<script setup lang="ts">
import { ref } from 'vue'
import { Menu, X } from 'lucide-vue-next'
import { MotorBadge } from '@bgm/ui'

defineProps<{ title?: string }>()

const open = ref(false)
</script>

<template>
  <div class="admin-layout" :class="{ 'is-open': open }">
    <div class="admin-layout__overlay" @click="open = false" />

    <aside class="admin-layout__sidebar">
      <div class="admin-layout__brand">
        <MotorBadge label="BGM Admin" />
        <button class="admin-layout__close" type="button" aria-label="Cerrar menú" @click="open = false">
          <X :size="18" />
        </button>
      </div>
      <nav class="admin-layout__nav" @click="open = false">
        <slot name="nav" />
      </nav>
    </aside>

    <main class="admin-layout__main">
      <header class="admin-layout__header">
        <button class="admin-layout__burger" type="button" aria-label="Abrir menú" @click="open = true">
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
  min-height: 100vh;
  background: $color-bg;
  color: $color-text;

  // --- Sidebar como drawer (mobile-first) ---
  &__sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 240px;
    z-index: 30;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
    background: $color-surface;
    border-right: 1px solid $color-border;
    padding: $space-6 $space-4;
  }
  &.is-open &__sidebar { transform: none; }

  &__overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }
  &.is-open &__overlay { opacity: 1; pointer-events: auto; }

  &__brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $space-6;
  }

  &__main { display: flex; flex-direction: column; min-width: 0; }

  &__header {
    display: flex;
    align-items: center;
    gap: $space-3;
    padding: $space-4 $space-6;
    border-bottom: 1px solid $color-border;

    h1 { margin: 0; font-size: 1.15rem; flex: 1; min-width: 0; }
  }

  &__actions { display: flex; align-items: center; gap: $space-2; }

  &__body { padding: $space-4 $space-6; }

  // Botones de menú (icon buttons), solo visibles en móvil
  &__burger,
  &__close {
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
  &__close { margin: -$space-2; }

  // --- Desktop: sidebar fijo en grid ---
  @media (min-width: #{$bp-md}) {
    display: grid;
    grid-template-columns: 240px 1fr;

    .admin-layout__sidebar { position: static; transform: none; width: auto; z-index: auto; }
    .admin-layout__overlay,
    .admin-layout__burger,
    .admin-layout__close { display: none; }
  }
}
</style>
