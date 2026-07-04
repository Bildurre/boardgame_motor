<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Download, Plus, Trash2 } from '@lucide/vue'
import { BaseButton, useConfirm, useToast } from '@bgm/ui'
import { useRightSidebar } from '@bgm/admin-kit'
import { api } from '@/lib/api'

// Copias de seguridad (doc 06): crear con un clic, listar, descargar y
// borrar. La fila entera selecciona y el panel derecho trae las acciones.
interface BackupRow {
  file: string
  date: string
  size: number
}

const { t, locale } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const sidebar = useRightSidebar()
sidebar.useRegister(t('backups.panelTitle'))

const backups = ref<BackupRow[]>([])
const loading = ref(true)
const creating = ref(false)
const selectedFile = ref<string | null>(null)

const selected = computed(() => backups.value.find((b) => b.file === selectedFile.value) ?? null)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/backups')
    backups.value = data.data
  } catch {
    toast.danger(t('common.errors.load'))
  } finally {
    loading.value = false
  }
}

function select(backup: BackupRow, event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (target?.closest('button, a')) return
  selectedFile.value = backup.file
  sidebar.reveal()
}

async function create() {
  creating.value = true
  try {
    const { data } = await api.post('/admin/backups')
    backups.value = data.data
    toast.success(t('backups.toast.created'))
  } catch {
    toast.danger(t('common.errors.action'))
  } finally {
    creating.value = false
  }
}

/** Descarga autenticada: el zip llega por la API con el token. */
async function download(backup: BackupRow) {
  try {
    const { data } = await api.get(`/admin/backups/${backup.file}/download`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(data)
    const link = document.createElement('a')
    link.href = url
    link.download = backup.file
    link.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

async function remove(backup: BackupRow) {
  const ok = await confirm({
    message: t('backups.confirmDelete', { name: backup.file }),
    confirmLabel: t('common.actions.delete'),
    cancelLabel: t('common.cancel'),
    variant: 'danger',
  })
  if (!ok) return
  try {
    await api.delete(`/admin/backups/${backup.file}`)
    if (selectedFile.value === backup.file) selectedFile.value = null
    toast.success(t('backups.toast.deleted'))
    await load()
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(iso),
  )
}

function formatSize(bytes: number): string {
  if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  if (bytes >= 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${bytes} B`
}

onMounted(load)
</script>

<template>
  <div class="backups-view">
    <div class="list-view__top">
      <BaseButton :disabled="creating" @click="create">
        <template #icon><Plus :size="16" /></template>
        {{ t('backups.create') }}
      </BaseButton>
    </div>

    <p class="backups-view__hint">{{ t('backups.hint') }}</p>

    <p v-if="!loading && !backups.length" class="backups-view__empty">{{ t('backups.empty') }}</p>

    <div class="pages-view__list">
      <article
        v-for="backup in backups"
        :key="backup.file"
        class="pages-view__item"
        :class="{ 'is-active': selectedFile === backup.file }"
        @click="(e) => select(backup, e)"
      >
        <strong class="backups-view__name">{{ backup.file }}</strong>
        <span class="backups-view__date">{{ formatDate(backup.date) }}</span>
        <span class="pages-view__chips">
          <span class="locale-chip">{{ formatSize(backup.size) }}</span>
        </span>
      </article>
    </div>

    <!-- Acciones de la copia seleccionada, en el panel derecho -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!selected" class="manager-panel__empty">{{ t('backups.panelEmpty') }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ t('backups.panelTitle') }}</p>
          <h3 class="manager-detail__title">{{ selected.file }}</h3>

          <div class="manager-detail__actions">
            <BaseButton @click="download(selected)">
              <template #icon><Download :size="14" /></template>
              {{ t('backups.download') }}
            </BaseButton>
            <BaseButton variant="danger" @click="remove(selected)">
              <template #icon><Trash2 :size="14" /></template>
              {{ t('common.actions.delete') }}
            </BaseButton>
          </div>

          <p class="manager-detail__meta">
            <strong>{{ t('backups.fields.date') }}</strong> {{ formatDate(selected.date) }}
          </p>
          <p class="manager-detail__meta">
            <strong>{{ t('backups.fields.size') }}</strong> {{ formatSize(selected.size) }}
          </p>
        </template>
      </div>
    </Teleport>
  </div>
</template>
