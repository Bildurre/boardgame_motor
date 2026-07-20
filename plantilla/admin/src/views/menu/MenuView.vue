<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { MenuManager, type MenuManagerLabels } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Configurador del menú de la web pública (doc 10 ampliado): mezcla páginas
// del CRM y las rutas propias del juego (motor.menu.routes, config del api).
// `routeLabels` es la etiqueta visible de cada route_key: añade aquí la tuya
// según vayas registrando rutas en tu AppHeader.vue (guia-como-montar-una-web
// §10). Sin ninguna ruta propia (plantilla vacía), el menú solo trae páginas.
const routeLabels: Record<string, string> = {
  // downloads: 'Descargas',
}

const { t } = useI18n()
const locales = useLocalesStore()

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
