<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { SquarePen, Trash2, Eye, EyeOff, RotateCcw, CircleCheck, FilePen, Trash, FlameKindling } from '@lucide/vue'
import { BaseGrid, EntityCard, FilterBar, EmptyState, useResource } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton, useToast, useConfirm } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import HouseFormModal from '@/components/houses/HouseFormModal.vue'

const { t } = useI18n()
const locales = useLocalesStore()
const toast = useToast()
const { confirm } = useConfirm()
const { items, meta, loading, list, remove, action } = useResource(api, '/admin/houses')

const status = ref('published')
const search = ref('')

const tabs = computed(() => [
  { key: 'published', label: t('houses.tabs.published'), icon: CircleCheck },
  { key: 'draft', label: t('houses.tabs.draft'), icon: FilePen },
  { key: 'trashed', label: t('houses.tabs.trashed'), icon: Trash },
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
  await list({ search: search.value, status: status.value, page })
}
watch(status, () => load(1))

let timer: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => load(1), 250)
})

// --- Modal de creación / edición (patrón kontuan) ---
const formOpen = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const formSlug = ref<string | null>(null)

function openCreate() {
  formMode.value = 'create'
  formSlug.value = null
  formOpen.value = true
}
function editHouse(item: any) {
  formMode.value = 'edit'
  formSlug.value = slugFor(item)
  formOpen.value = true
}
function onSaved() {
  load(meta.value?.current_page ?? 1)
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
async function forceDelete(item: any) {
  const ok = await confirm({
    title: t('houses.confirmForceDelete.title'),
    message: t('houses.confirmForceDelete.message', { name: tr(item.name) }),
    confirmLabel: t('houses.actions.forceDelete'),
    variant: 'danger',
  })
  if (!ok) return
  await api.delete(`/admin/houses/${item.id}/force`)
  toast.success(t('houses.toast.forceDeleted'))
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
      <BaseButton @click="openCreate">{{ t('houses.newButton') }}</BaseButton>
    </div>

    <!-- Filtros por encima de las tabs (estilo kontuan) -->
    <FilterBar v-model="search" :placeholder="t('common.search')" />
    <BaseTabs v-model="status" :tabs="tabs" />

    <EmptyState v-if="!loading && !items.length" :title="t('common.empty')" />

    <BaseGrid v-else preset="cards" gap="md">
      <EntityCard
        v-for="item in items"
        :key="item.id"
        :title="tr(item.name)"
        :muted="!!item.deleted_at"
      >
        <template #media>
          <div class="house-emblem" :style="{ '--c': item.color || 'transparent' }">
            <img v-if="item.image" :src="item.image" alt="" />
            <span v-else class="house-emblem__mono">{{ tr(item.name).charAt(0) }}</span>
          </div>
        </template>

        <template #actions>
          <template v-if="item.deleted_at">
            <IconButton variant="info" :title="t('houses.actions.restore')" @click="restore(item)"><RotateCcw :size="18" /></IconButton>
            <IconButton variant="danger" :title="t('houses.actions.forceDelete')" @click="forceDelete(item)"><FlameKindling :size="18" /></IconButton>
          </template>
          <template v-else>
            <IconButton variant="success" :title="t('houses.actions.edit')" @click="editHouse(item)"><SquarePen :size="18" /></IconButton>
            <IconButton
              :variant="item.is_published ? 'warning' : 'info'"
              :title="item.is_published ? t('houses.actions.unpublish') : t('houses.actions.publish')"
              @click="togglePublish(item)"
            >
              <component :is="item.is_published ? EyeOff : Eye" :size="18" />
            </IconButton>
            <IconButton variant="danger" :title="t('houses.actions.delete')" @click="del(item)"><Trash2 :size="18" /></IconButton>
          </template>
        </template>

        <template #badges>
          <span v-if="item.deleted_at" class="chip chip--trashed">{{ t('houses.state.trashed') }}</span>
          <span v-else-if="item.is_published" class="chip chip--pub">{{ t('houses.state.published') }}</span>
          <span v-else class="chip">{{ t('houses.state.draft') }}</span>
        </template>

        <template #meta>
          <span><span class="swatch" :style="{ background: item.color || 'transparent' }" />{{ item.color || '—' }}</span>
        </template>
      </EntityCard>
    </BaseGrid>

    <HouseFormModal v-model="formOpen" :mode="formMode" :target-slug="formSlug" @saved="onSaved" />
  </div>
</template>
