<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { MenuManager, type MenuManagerLabels } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Configurador del menú de la web pública (doc 10 ampliado): mezcla páginas
// del CRM y las rutas propias del playground (motor.menu.routes, config del
// api: characters, houses, downloads — deben casar con AppHeader.vue del app
// y con entitySections/DOWNLOAD_PATHS que ya usa).
const { t } = useI18n()
const locales = useLocalesStore()

const routeLabels = computed<Record<string, string>>(() => ({
  characters: t('nav.characters'),
  houses: t('nav.houses'),
  downloads: t('menu.routes.downloads'),
}))

const labels = computed<Partial<MenuManagerLabels>>(() => ({
  newGroup: t('menu.newGroup'),
  newGroupTitle: t('menu.newGroupTitle'),
  editGroupTitle: t('menu.editGroupTitle'),
  groupLabel: t('menu.groupLabel'),
  save: t('common.save'),
  cancel: t('common.cancel'),
  delete: t('common.actions.delete'),
  confirmDelete: t('menu.confirmDelete'),
  empty: t('menu.empty'),
  root: t('menu.root'),
  hidden: t('menu.hidden'),
  draft: t('pages.draft'),
  moveUp: t('pages.moveUp'),
  moveDown: t('pages.moveDown'),
  visible: t('menu.visible'),
  group: t('menu.group'),
  error: t('common.errors.action'),
}))
</script>

<template>
  <div class="menu-view">
    <h1 class="single__title">{{ t('menu.title') }}</h1>
    <p class="menu-view__hint">{{ t('menu.hint') }}</p>

    <MenuManager
      :api="api"
      :locales="locales.locales"
      :route-labels="routeLabels"
      :labels="labels"
    />
  </div>
</template>
