<script setup lang="ts">
import { onMounted } from 'vue'
import { SquarePen, Trash2, Eye, EyeOff, RotateCcw, FlameKindling } from '@lucide/vue'
import { BaseGrid, EntityCard, FilterBar, EmptyState } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton } from '@bgm/ui'
import { useEntityList } from '@/composables/useEntityList'
import type { House } from '@playground/shared'
import HouseFormModal from '@/components/houses/HouseFormModal.vue'

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
} = useEntityList<House>({
  resource: '/admin/houses',
  ns: 'houses',
  singleRoute: 'house-single',
  nameOf: (item) => item.name,
})

onMounted(init)
</script>

<template>
  <div class="houses">
    <div class="list-view__top">
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
        clickable
        @view="goSingle(item)"
      >
        <template #media>
          <div class="house-emblem" :style="{ '--c': item.color || 'transparent' }">
            <img v-if="item.image" :src="item.image" alt="" />
            <span v-else class="house-emblem__mono">{{ tr(item.name).charAt(0) }}</span>
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
            <IconButton variant="danger" :title="t('common.actions.delete')" @click="del(item)"
              ><Trash2 :size="18"
            /></IconButton>
          </template>
        </template>

        <template #badges>
          <span v-if="item.deleted_at" class="chip chip--trashed">{{
            t('houses.state.trashed')
          }}</span>
          <span v-else-if="item.is_published" class="chip chip--pub">{{
            t('houses.state.published')
          }}</span>
          <span v-else class="chip">{{ t('houses.state.draft') }}</span>
        </template>

        <template #meta>
          <span
            ><span class="swatch" :style="{ background: item.color || 'transparent' }" />{{
              item.color || '—'
            }}</span
          >
        </template>
      </EntityCard>
    </BaseGrid>

    <HouseFormModal v-model="formOpen" :mode="formMode" :target-slug="formSlug" @saved="onSaved" />
  </div>
</template>
