import { computed, type ComputedRef } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ExportManagerLabels } from '@edc-motor/admin-kit'

/** Textos traducidos del exportador a JSON (DC-29). */
export function useExportLabels(): ComputedRef<ExportManagerLabels> {
  const { t } = useI18n()

  return computed(() => ({
    hint: t('export.hint'),
    model: t('export.model'),
    locales: t('export.locales'),
    fields: t('export.fields'),
    all: t('export.all'),
    none: t('export.none'),
    export: t('export.export'),
    exporting: t('export.exporting'),
    needLocale: t('export.needLocale'),
    needField: t('export.needField'),
    done: t('export.done'),
    error: t('export.error'),
    loadError: t('common.errors.load'),
    loading: t('common.loading'),
  }))
}
