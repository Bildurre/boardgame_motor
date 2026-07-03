<script setup lang="ts">
import { onMounted } from 'vue'
import { Camera, Eye, EyeOff, FlameKindling, Plus, RotateCcw, SquarePen, Trash2 } from '@lucide/vue'
import { BaseGrid, EntityCard, FilterBar, EmptyState } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton } from '@bgm/ui'
import { useEntityList } from '@/composables/useEntityList'
import type { Scheme } from '@playground/shared'
import SchemeFormModal from '@/components/schemes/SchemeFormModal.vue'

const {
  t,
  items,
  loading,
  status,
  search,
  tabs,
  tr,
  init,
  formOpen,
  formMode,
  formSlug,
  openCreate,
  edit,
  goSingle,
  onSaved,
  togglePublish,
  del,
  restore,
  forceDelete,
  regeneratePreview,
} = useEntityList<Scheme>({
  resource: '/admin/schemes',
  ns: 'schemes',
  singleRoute: 'scheme-single',
  previewKey: 'scheme',
  nameOf: (item) => item.title,
})

onMounted(init)
</script>

<template>
  <div class="schemes">
    <div class="list-view__top">
      <BaseButton @click="openCreate">
        <template #icon><Plus :size="16" /></template>
        {{ t('schemes.newButton') }}
      </BaseButton>
    </div>

    <FilterBar v-model="search" :placeholder="t('common.search')" />
    <BaseTabs v-model="status" :tabs="tabs" />

    <EmptyState v-if="!loading && !items.length" :title="t('common.empty')" />

    <BaseGrid v-else preset="cards" gap="md">
      <EntityCard
        v-for="item in items"
        :key="item.id"
        :title="tr(item.title)"
        :muted="!!item.deleted_at"
        clickable
        @view="goSingle(item)"
      >
        <template #media>
          <div class="card-art">
            <img v-if="item.image" :src="item.image" alt="" />
            <span v-else class="card-art__cost">{{ item.cost }}</span>
          </div>
        </template>

        <template #actions>
          <template v-if="item.deleted_at">
            <IconButton variant="info" :title="t('common.actions.restore')" @click="restore(item)"
              ><RotateCcw :size="18"
            /></IconButton>
            <IconButton
              variant="danger"
              :title="t('common.actions.forceDelete')"
              @click="forceDelete(item)"
              ><FlameKindling :size="18"
            /></IconButton>
          </template>
          <template v-else>
            <IconButton variant="success" :title="t('common.actions.edit')" @click="edit(item)"
              ><SquarePen :size="18"
            /></IconButton>
            <IconButton
              :variant="item.is_published ? 'warning' : 'info'"
              :title="
                item.is_published ? t('common.actions.unpublish') : t('common.actions.publish')
              "
              @click="togglePublish(item)"
            >
              <component :is="item.is_published ? EyeOff : Eye" :size="18" />
            </IconButton>
            <IconButton
              variant="info"
              :title="t('previews.regenerate')"
              @click="regeneratePreview(item)"
              ><Camera :size="18"
            /></IconButton>
            <IconButton variant="danger" :title="t('common.actions.delete')" @click="del(item)"
              ><Trash2 :size="18"
            /></IconButton>
          </template>
        </template>

        <template #badges>
          <span v-if="item.deleted_at" class="chip chip--trashed">{{
            t('schemes.state.trashed')
          }}</span>
          <span v-else-if="item.is_published" class="chip chip--pub">{{
            t('schemes.state.published')
          }}</span>
          <span v-else class="chip">{{ t('schemes.state.draft') }}</span>
          <span class="chip chip--cost">{{ t('schemes.fields.cost') }}: {{ item.cost }}</span>
        </template>

        <template #meta>
          <span>{{ t('schemes.fields.house') }}: {{ tr(item.house?.name) }}</span>
        </template>
      </EntityCard>
    </BaseGrid>

    <SchemeFormModal v-model="formOpen" :mode="formMode" :target-slug="formSlug" @saved="onSaved" />
  </div>
</template>
