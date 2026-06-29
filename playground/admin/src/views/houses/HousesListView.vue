<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { SquarePen, Trash2, Eye, EyeOff, RotateCcw, CircleCheck, FilePen, Trash } from '@lucide/vue'
import { ResourceList, FiltersBar, useResource } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton, useToast, useConfirm } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const router = useRouter()
const locales = useLocalesStore()
const toast = useToast()
const { confirm } = useConfirm()
const { items, meta, loading, list, remove, action } = useResource(api, '/admin/houses')

const status = ref('published')
let search = ''

const tabs = [
  { key: 'published', label: 'Publicadas', icon: CircleCheck },
  { key: 'draft', label: 'Borrador', icon: FilePen },
  { key: 'trashed', label: 'Papelera', icon: Trash },
]
const columns = [
  { key: 'image', label: '' },
  { key: 'name', label: 'Nombre' },
  { key: 'color', label: 'Color' },
  { key: 'status', label: 'Estado' },
]

function label(obj: Record<string, string>) {
  return obj?.[locales.current] || obj?.[locales.defaultLocale] || Object.values(obj || {})[0] || '—'
}

async function load(page = 1) {
  await list({ search, status: status.value, page })
}
function onFilter(f: { search: string }) {
  search = f.search
  load(1)
}
watch(status, () => load(1))

async function togglePublish(item: any) {
  await action(item.id, 'toggle-published')
  toast.success(item.is_published ? 'House despublicada' : 'House publicada')
  load(meta.value?.current_page ?? 1)
}
async function del(item: any) {
  const ok = await confirm({
    title: 'Enviar a la papelera',
    message: `¿Enviar "${label(item.name)}" a la papelera?`,
    confirmLabel: 'Borrar',
    variant: 'danger',
  })
  if (!ok) return
  await remove(item.id)
  toast.success('House enviada a la papelera')
  load(meta.value?.current_page ?? 1)
}
async function restore(item: any) {
  await action(item.id, 'restore')
  toast.success('House restaurada')
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

    <BaseTabs v-model="status" :tabs="tabs" />
    <FiltersBar @change="onFilter" />

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
