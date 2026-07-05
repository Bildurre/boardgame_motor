<script setup lang="ts">
import { onMounted } from 'vue'
import { ArrowRight, Plus } from '@lucide/vue'
import { BaseGrid, EntityCard, FilterBar, EmptyState } from '@bgm/admin-kit'
import { BaseButton, BaseTabs, IconButton } from '@bgm/ui'
import { useEntityList } from '@/composables/useEntityList'
import type { Scheme } from '@playground/shared'
import SchemeFormModal from '@/components/schemes/SchemeFormModal.vue'
import EntityPanel from '@/components/EntityPanel.vue'

// La tarjeta selecciona (panel derecho con TODAS las acciones + PNG por
// idioma); en la tarjeta quedan solo las básicas: abrir y editar.
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
  selectedId,
  selected,
  select,
  hasPreview,
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
        :active="selectedId === item.id"
        clickable
        @view="select(item)"
      >
        <template #media>
          <div class="card-art">
            <img v-if="item.image" :src="item.image" alt="" />
            <span v-else class="card-art__cost">{{ item.cost }}</span>
          </div>
        </template>

        <!-- La tarjeta solo lleva "abrir": el resto vive en el panel derecho -->
        <template #actions>
          <IconButton
            v-if="!item.deleted_at"
            variant="info"
            :title="t('common.actions.open')"
            @click="goSingle(item)"
            ><ArrowRight :size="18"
          /></IconButton>
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

    <EntityPanel
      :item="selected"
      :name="selected ? tr(selected.title) : ''"
      :kicker="t('schemes.panelTitle')"
      :empty="t('schemes.panelEmpty')"
      :has-preview="hasPreview"
      @open="selected && goSingle(selected)"
      @edit="selected && edit(selected)"
      @toggle-publish="selected && togglePublish(selected)"
      @regenerate="selected && regeneratePreview(selected)"
      @del="selected && del(selected)"
      @restore="selected && restore(selected)"
      @force-delete="selected && forceDelete(selected)"
    >
      <template #meta>
        <p v-if="selected" class="manager-detail__meta">
          <strong>{{ t('schemes.fields.house') }}</strong> {{ tr(selected.house?.name) }} ·
          <strong>{{ t('schemes.fields.cost') }}</strong> {{ selected.cost }}
        </p>
      </template>
    </EntityPanel>
  </div>
</template>
