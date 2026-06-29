<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { SquarePen, Trash2, Eye, EyeOff, RotateCcw } from '@lucide/vue'
import { ResourceList, FiltersBar, useResource } from '@bgm/admin-kit'
import { BaseButton, IconButton } from '@bgm/ui'
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
        <div class="row-actions">
          <template v-if="item.deleted_at">
            <IconButton variant="info" title="Restaurar" @click="restore(item)"><RotateCcw :size="18" /></IconButton>
          </template>
          <template v-else>
            <IconButton variant="success" title="Editar" @click="router.push({ name: 'house-edit', params: { id: item.id } })">
              <SquarePen :size="18" />
            </IconButton>
            <IconButton
              :variant="item.is_published ? 'warning' : 'info'"
              :title="item.is_published ? 'Despublicar' : 'Publicar'"
              @click="togglePublish(item)"
            >
              <component :is="item.is_published ? EyeOff : Eye" :size="18" />
            </IconButton>
            <IconButton variant="danger" title="Borrar" @click="del(item)"><Trash2 :size="18" /></IconButton>
          </template>
        </div>
      </template>
    </ResourceList>
  </div>
</template>
