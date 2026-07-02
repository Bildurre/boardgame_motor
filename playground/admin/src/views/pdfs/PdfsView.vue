<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { PdfManager } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { usePdfLabels } from '@/lib/pdfLabels'

// Exports globales de PDF (sin entidad dueña). Los exports por entidad se
// gestionan desde el detalle de cada entidad (PdfManager en el single).
const { t } = useI18n()
const labels = usePdfLabels()
</script>

<template>
  <div class="pdfs-view">
    <PdfManager
      :api="api"
      type="characters"
      :labels="{ ...labels, title: t('pdfs.globalCharacters') }"
    />
  </div>
</template>
