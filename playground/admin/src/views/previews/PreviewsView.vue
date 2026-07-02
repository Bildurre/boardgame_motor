<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { PreviewManager, type PreviewManagerLabels } from '@bgm/admin-kit'
import { api } from '@/lib/api'

// Gestor de imágenes (previews PNG): el componente vive en @bgm/admin-kit;
// aquí solo se le pasan la API y los textos traducidos (DC-29).
const { t } = useI18n()

// Nombre traducido de cada tipo renderizable (clave del PreviewRegistry).
const typeLabels = computed<Record<string, string>>(() => ({
  character: t('characters.title'),
  scheme: t('schemes.title'),
}))

const labels = computed<PreviewManagerLabels>(() => ({
  refresh: t('previewsManager.refresh'),
  generate: t('previewsManager.generate'),
  regenerateAll: t('previewsManager.regenerateAll'),
  deleteAll: t('previewsManager.deleteAll'),
  clean: t('previewsManager.clean'),
  total: t('previewsManager.total'),
  complete: t('previewsManager.complete'),
  pending: t('previewsManager.pending'),
  empty: t('common.empty'),
  loadMore: t('previewsManager.loadMore'),
  itemRegenerate: t('previewsManager.itemRegenerate'),
  itemDelete: t('previewsManager.itemDelete'),
  confirmRegenerateAll: t('previewsManager.confirmRegenerateAll'),
  confirmDeleteAll: t('previewsManager.confirmDeleteAll'),
  confirmDeleteItem: t('previewsManager.confirmDeleteItem'),
  confirmClean: t('previewsManager.confirmClean'),
  confirm: t('previewsManager.confirm'),
  cancel: t('common.cancel'),
  error: t('common.errors.action'),
}))
</script>

<template>
  <div class="previews-view">
    <PreviewManager :api="api" :labels="labels" :type-labels="typeLabels" />
  </div>
</template>
