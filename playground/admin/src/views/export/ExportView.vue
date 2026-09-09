<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ExportManager } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { useExportLabels } from '@/lib/exportLabels'

// Sección Exportación (doc 12), solo administradores: el gestor del motor
// lee los modelos exportables registrados (Exports::register) y descarga el
// JSON con los idiomas y campos elegidos. Aquí solo los textos: los nombres
// de modelos, grupos y campos salen del i18n por convención
// (export.models.<clave>, export.groups.<grupo>, export.fieldLabels.<modelo>).
const { tm } = useI18n()
const labels = useExportLabels()

// tm() devuelve el subárbol de mensajes crudo (sin interpolar: aquí no hay).
const asRecord = (value: unknown): Record<string, string> =>
  value && typeof value === 'object' ? (value as Record<string, string>) : {}

const modelLabels = computed(() => asRecord(tm('export.models')))
const groupLabels = computed(() => asRecord(tm('export.groups')))
const fieldLabels = computed(() => {
  const raw = tm('export.fieldLabels')
  const out: Record<string, Record<string, string>> = {}
  if (raw && typeof raw === 'object') {
    for (const [model, fields] of Object.entries(raw as Record<string, unknown>)) {
      out[model] = asRecord(fields)
    }
  }
  return out
})
</script>

<template>
  <div class="export-view">
    <ExportManager
      :api="api"
      :labels="labels"
      :model-labels="modelLabels"
      :group-labels="groupLabels"
      :field-labels="fieldLabels"
    />
  </div>
</template>
