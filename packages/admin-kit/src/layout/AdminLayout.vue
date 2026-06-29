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
