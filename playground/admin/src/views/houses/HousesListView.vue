<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ResourceList, FiltersBar, useResource } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const router = useRouter()
const locales = useLocalesStore()
const { items, meta, loading, list, remove, action } = useResource(api, '/admin/houses')

let filters = { search: '', status: '' }

const columns = [
  { key: 'image', label: '' },
  { key: 'name', label: 'Nombre' },
  { key: 'color', label: 'Color' },
  { key: 'status', label: 'Estado' },
]

function label(obj: Record<string, string>) {
  return obj?.[locales.defaultLocale] || Object.values(obj || {})[0] || '—'
}

async function load(page = 1) {
  await list({ ...filters, page })
}

function onFilter(f: { search: string; status: string }) {
  filters = f
  load(1)
}

async function togglePublish(item: any) {
  await action(item.id, 'toggle-published')
  load(meta.value?.current_page ?? 1)
}
async function del(item: any) {
  if (!confirm('¿Enviar a la papelera?')) return
  await remove(item.id)
  load(meta.value?.current_page ?? 1)
}
async function restore(item: any) {
  await action(item.id, 'restore')
  load(meta.value?.current_page ?? 1)
}

onMounted(async () => {
  await locales.load()
  load()
})
</script>

<template>
  <div class="houses">
    <div class="houses__top">
      <BaseButton @click="router.push({ name: 'house-new' })">Nueva house</BaseButton>
    </div>

    <FiltersBar
      :status-options="[{ value: 'published', label: 'Publicadas' }, { value: 'draft', label: 'Borrador' }]"
      @change="onFilter"
    />

    <ResourceList :columns="columns" :items="items" :meta="meta" :loading="loading" @page="load">
      <template #cell-image="{ item }">
        <img v-if="item.image" :src="item.image" class="thumb" alt="" />
        <span v-else class="thumb thumb--empty" />
      </template>
      <template #cell-name="{ item }">{{ label(item.name) }}</template>
      <template #cell-color="{ item }">
        <span class="swatch" :style="{ background: item.color || 'transparent' }" />{{ item.color || '—' }}
      </template>
      <template #cell-status="{ item }">
        <span v-if="item.deleted_at" class="chip chip--trashed">Papelera</span>
        <span v-else-if="item.is_published" class="chip chip--pub">Publicada</span>
        <span v-else class="chip">Borrador</span>
      </template>
      <template #actions="{ item }">
        <template v-if="item.deleted_at">
          <button class="act" @click="restore(item)">Restaurar</button>
        </template>
        <template v-else>
          <button class="act" @click="router.push({ name: 'house-edit', params: { id: item.id } })">Editar</button>
          <button class="act" @click="togglePublish(item)">{{ item.is_published ? 'Despublicar' : 'Publicar' }}</button>
          <button class="act act--danger" @click="del(item)">Borrar</button>
        </template>
      </template>
    </ResourceList>
  </div>
</template>

<style scoped lang="scss">
.houses__top { display: flex; justify-content: flex-end; margin-bottom: $space-4; }
.thumb { width: 32px; height: 32px; border-radius: 6px; object-fit: cover; border: 1px solid $color-border; display: inline-block; vertical-align: middle; }
.thumb--empty { background: $color-surface; }
.swatch { display: inline-block; width: 0.9rem; height: 0.9rem; border-radius: 3px; margin-right: $space-2; vertical-align: -1px; border: 1px solid $color-border; }
.chip {
  display: inline-block; padding: 1px 8px; border-radius: $radius-pill; font-size: 0.75rem;
  background: $color-surface; border: 1px solid $color-border; color: $color-text-muted;
  &--pub { color: #4ade80; border-color: rgba(74, 222, 128, 0.4); }
  &--trashed { color: #ff6b6b; border-color: rgba(255, 107, 107, 0.4); }
}
.act {
  font: inherit; font-size: 0.85rem; background: none; border: none; cursor: pointer;
  color: $color-text-muted; padding: 0 $space-2;
  &:hover { color: $color-text; }
  &--danger:hover { color: #ff6b6b; }
}
</style>
