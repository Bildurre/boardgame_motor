<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { AdminLayout } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'

interface Ping {
  name: string
  version: string
  locales: string[]
}

const ping = ref<Ping | null>(null)

onMounted(async () => {
  const res = await fetch('/api/motor/ping')
  ping.value = await res.json()
})
</script>

<template>
  <AdminLayout title="Dashboard">
    <template #nav>
      <a class="nav-item is-active">Inicio</a>
      <a class="nav-item">Entidades (futuro)</a>
      <a class="nav-item">CRM (futuro)</a>
    </template>
    <template #actions>
      <BaseButton variant="secondary">Acción</BaseButton>
    </template>

    <p>Admin del playground, montado sobre <code>@bgm/admin-kit</code>.</p>
    <p v-if="ping">
      Motor <strong>{{ ping.name }}</strong> v{{ ping.version }} ·
      locales {{ ping.locales.join(', ') }}
    </p>
  </AdminLayout>
</template>

<style scoped lang="scss">
.nav-item {
  display: block;
  padding: $space-2 $space-3;
  border-radius: $radius-md;
  color: $color-text-muted;
  cursor: pointer;

  &:hover { color: $color-text; }
  &.is-active { color: $color-text; background: rgba(108, 92, 231, 0.15); }
}
code { color: $color-accent; }
</style>
