<script setup lang="ts">
import { onMounted } from 'vue'
import { ArrowRight, Plus } from '@lucide/vue'
import { BaseGrid, EntityCard, FilterBar, EmptyState } from '@bgm/admin-kit'
import { BaseButton, BaseTabs } from '@bgm/ui'
import { useEntityList } from '@/composables/useEntityList'
import type { Character } from '@playground/shared'
import CharacterFormModal from '@/components/characters/CharacterFormModal.vue'
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
} = useEntityList<Character>({
  resource: '/admin/characters',
  ns: 'characters',
  singleRoute: 'character-single',
  previewKey: 'character',
  nameOf: (item) => item.name,
})

onMounted(init)
</script>

<template>
  <div class="characters">
    <div class="list-view__top">
      <BaseButton @click="openCreate">
        <template #icon><Plus :size="16" /></template>
        {{ t('characters.newButton') }}
      </BaseButton>
    </div>

    <FilterBar v-model="search" :placeholder="t('common.search')" />
    <BaseTabs v-model="status" :tabs="tabs" />

    <EmptyState v-if="!loading && !items.length" :title="t('common.empty')" />

    <BaseGrid v-else preset="cards" gap="md">
      <EntityCard
        v-for="item in items"
        :key="item.id"
        :title="tr(item.name)"
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

        <!-- La tarjeta solo lleva "entrar" al single; el resto, en el panel -->
        <template #actions>
          <button v-if="!item.deleted_at" type="button" class="card-enter" @click="goSingle(item)">
            {{ t('common.actions.enter') }} <ArrowRight :size="14" />
          </button>
        </template>

        <template #badges>
          <span v-if="item.deleted_at" class="chip is-failed">{{
            t('characters.state.trashed')
          }}</span>
          <span v-else-if="item.is_published" class="chip is-ok">{{
            t('characters.state.published')
          }}</span>
          <span v-else class="chip">{{ t('characters.state.draft') }}</span>
          <span class="chip">{{ t('characters.fields.cost') }}: {{ item.cost }}</span>
        </template>

        <template #meta>
          <span class="stat-line">
            <span>{{ t('characters.fields.power') }} {{ item.power }}</span>
            <span>{{ t('characters.fields.prestige') }} {{ item.prestige }}</span>
            <span>{{ t('characters.fields.intrigue') }} {{ item.intrigue }}</span>
            <span>{{ t('characters.fields.money') }} {{ item.money }}</span>
          </span>
        </template>
      </EntityCard>
    </BaseGrid>

    <CharacterFormModal
      v-model="formOpen"
      :mode="formMode"
      :target-slug="formSlug"
      @saved="onSaved"
    />

    <EntityPanel
      :item="selected"
      :name="selected ? tr(selected.name) : ''"
      :kicker="t('characters.panelTitle')"
      :empty="t('characters.panelEmpty')"
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
          <strong>{{ t('characters.fields.cost') }}</strong> {{ selected.cost }} ·
          <strong>{{ t('characters.fields.power') }}</strong> {{ selected.power }} ·
          <strong>{{ t('characters.fields.prestige') }}</strong> {{ selected.prestige }} ·
          <strong>{{ t('characters.fields.intrigue') }}</strong> {{ selected.intrigue }} ·
          <strong>{{ t('characters.fields.money') }}</strong> {{ selected.money }}
        </p>
      </template>
    </EntityPanel>
  </div>
</template>
