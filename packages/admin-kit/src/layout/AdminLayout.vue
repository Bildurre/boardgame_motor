<script setup lang="ts">
import { MotorBadge } from '@bgm/ui'

defineProps<{ title?: string }>()
</script>

<template>
  <div class="admin-layout">
    <aside class="admin-layout__sidebar">
      <div class="admin-layout__brand">
        <MotorBadge label="BGM Admin" />
      </div>
      <nav class="admin-layout__nav">
        <slot name="nav">
          <!-- el juego registra aquí sus secciones -->
        </slot>
      </nav>
    </aside>
    <main class="admin-layout__main">
      <header class="admin-layout__header">
        <h1>{{ title }}</h1>
        <slot name="actions" />
      </header>
      <div class="admin-layout__body">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped lang="scss">
// Tokens inyectados vía additionalData del vite de cada app.
.admin-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;
  background: $color-bg;
  color: $color-text;

  &__sidebar {
    border-right: 1px solid $color-border;
    padding: $space-6 $space-4;
    background: $color-surface;
  }

  &__brand { margin-bottom: $space-6; }

  &__main { display: flex; flex-direction: column; }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $space-6;
    border-bottom: 1px solid $color-border;

    h1 { margin: 0; font-size: 1.25rem; }
  }

  &__body { padding: $space-6; }
}
</style>
