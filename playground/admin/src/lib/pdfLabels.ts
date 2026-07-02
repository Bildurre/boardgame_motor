import { computed, type ComputedRef } from 'vue'
import { useI18n } from 'vue-i18n'
import type { PdfManagerLabels } from '@bgm/admin-kit'

/** Textos traducidos del gestor de PDF (DC-29). */
export function usePdfLabels(): ComputedRef<PdfManagerLabels> {
  const { t } = useI18n()

  return computed(() => ({
    title: t('pdfs.title'),
    generate: t('pdfs.generate'),
    refresh: t('pdfs.refresh'),
    download: t('pdfs.download'),
    regenerate: t('pdfs.regenerate'),
    delete: t('pdfs.delete'),
    confirmDelete: t('pdfs.confirmDelete'),
    cancel: t('common.cancel'),
    empty: t('pdfs.empty'),
    error: t('common.errors.action'),
    statusPending: t('pdfs.statusPending'),
    statusReady: t('pdfs.statusReady'),
    statusFailed: t('pdfs.statusFailed'),
  }))
}
