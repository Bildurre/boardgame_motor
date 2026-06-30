<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { SquarePen, Trash2, Eye, EyeOff, RotateCcw, CircleCheck, FilePen, Trash } from '@lucide/vue'
import { ResourceList, FiltersBar, useResource } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton, useToast, useConfirm } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const router = useRouter()
const { t } = useI18n()
const locales = useLocalesStore()
const toast = useToast()
const { confirm } = useConfirm()
const { items, meta, loading, list, remove, action } = useResource(api, '/admin/houses')

const status = ref('published')
let search = ''

const tabs = computed(() => [
  { key: 'published', label: t('houses.tabs.published'), icon: CircleCheck },
  { key: 'draft', label: t('houses.tabs.draft'), icon: FilePen },
  { key: 'trashed', label: t('houses.tabs.trashed'), icon: Trash },
])
const columns = computed(() => [
  { key: 'image', label: '' },
  { key: 'name', label: t('houses.cols.name') },
  { key: 'color', label: t('houses.cols.color') },
  { key: 'status', label: t('houses.cols.status') },
])

// Valor traducible en el locale activo (con fallbacks).
function tr(obj: Record<string, string>) {
  return obj?.[locales.current] || obj?.[locales.defaultLocale] || Object.values(obj || {})[0] || '—'
}
// Slug del locale activo (para construir la URL de edición).
function slugFor(item: any): string {
  return item.slug?.[locales.current] || Object.values(item.slug || {})[0] || ''
}

async function load(page = 1) {
  await list({ search, status: status.value, page })
}
function onFilter(f: { search: string }) {
  search = f.search
  load(1)
}
watch(status, () => load(1))

function editHouse(item: any) {
  router.push({ name: 'house-edit', params: { slug: slugFor(item) } })
}
async function togglePublish(item: any) {
  await action(slugFor(item), 'toggle-published')
  toast.success(item.is_published ? t('houses.toast.unpublished') : t('houses.toast.published'))
  load(meta.value?.current_page ?? 1)
}
async function del(item: any) {
  const ok = await confirm({
    title: t('houses.confirmDelete.title'),
    message: t('houses.confirmDelete.message', { name: tr(item.name) }),
    confirmLabel: t('houses.confirmDelete.confirm'),
    variant: 'danger',
  })
  if (!ok) return
  await remove(slugFor(item))
  toast.success(t('houses.toast.deleted'))
  load(meta.value?.current_page ?? 1)
}
async function restore(item: any) {
  await action(item.id, 'restore')
  toast.success(t('houses.toast.restored'))
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
      <BaseButton @click="router.push({ name: 'house-new' })">{{ t('houses.newButton') }}</BaseButton>
    </div>

    <BaseTabs v-model="status" :tabs="tabs" />
    <FiltersBar :search-placeholder="t('common.search')" @change="onFilter" />

    <ResourceList
      :columns="columns"
      :items="items"
      :meta="meta"
      :loading="loading"
      :loading-text="t('common.loading')"
      :empty-text="t('common.empty')"
      @page="load"
    >
      <template #cell-image="{ item }">
        <img v-if="item.image" :src="item.image" class="thumb" alt="" />
        <span v-else class="thumb thumb--empty" />
      </template>
      <template #cell-name="{ item }">{{ tr(item.name) }}</template>
      <template #cell-color="{ item }">
        <span class="swatch" :style="{ background: item.color || 'transparent' }" />{{ item.color || '—' }}
      </template>
      <template #cell-status="{ item }">
        <span v-if="item.deleted_at" class="chip chip--trashed">{{ t('houses.state.trashed') }}</span>
        <span v-else-if="item.is_published" class="chip chip--pub">{{ t('houses.state.published') }}</span>
        <span v-else class="chip">{{ t('houses.state.draft') }}</span>
      </template>
      <template #actions="{ item }">
        <div class="row-actions">
          <template v-if="item.deleted_at">
            <IconButton variant="info" :title="t('houses.actions.restore')" @click="restore(item)"><RotateCcw :size="18" /></IconButton>
          </template>
          <template v-else>
            <IconButton variant="success" :title="t('houses.actions.edit')" @click="editHouse(item)">
              <SquarePen :size="18" />
            </IconButton>
            <IconButton
              :variant="item.is_published ? 'warning' : 'info'"
              :title="item.is_published ? t('houses.actions.unpublish') : t('houses.actions.publish')"
              @click="togglePublish(item)"
            >
              <component :is="item.is_published ? EyeOff : Eye" :size="18" />
            </IconButton>
            <IconButton variant="danger" :title="t('houses.actions.delete')" @click="del(item)"><Trash2 :size="18" /></IconButton>
          </template>
        </div>
      </template>
    </ResourceList>
  </div>
</template>
