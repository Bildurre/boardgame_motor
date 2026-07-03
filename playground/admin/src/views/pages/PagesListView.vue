<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { ArrowRight, House as HomeIcon, Plus, SquarePen, Trash2 } from '@lucide/vue'
import { BaseButton, BaseCheckbox, IconButton, useConfirm, useToast } from '@bgm/ui'
import { useRightSidebar } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import PageFormModal, { type PageRow } from '@/components/pages/PageFormModal.vue'

// Listado de páginas del CRM. TODA la tarjeta selecciona (salvo controles):
// el panel derecho trae las acciones (patrón kontuan, arriba del todo) y las
// rápidas sin modal (publicar, imprimible, home). El título y la flecha
// entran al single (bloques).
const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()

const sidebar = useRightSidebar()
sidebar.useRegister(t('pages.panelTitle'))

const pages = ref<PageRow[]>([])
const loading = ref(true)
const formOpen = ref(false)
const editing = ref<PageRow | null>(null)
const selectedId = ref<number | null>(null)

const selected = computed(() => pages.value.find((p) => p.id === selectedId.value) ?? null)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/pages')
    pages.value = data.data
  } catch {
    toast.danger(t('common.errors.load'))
  } finally {
    loading.value = false
  }
}

/** Toda la tarjeta selecciona, salvo sus controles interiores. */
function select(page: PageRow, event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (target?.closest('button, a, input, label')) return
  selectedId.value = page.id
  sidebar.reveal()
}

function open(page: PageRow) {
  router.push({ name: 'page', params: { id: page.id } })
}

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(page: PageRow) {
  editing.value = page
  formOpen.value = true
}

/** Acción rápida del panel: alterna un flag sin abrir el modal. */
async function toggleFlag(flag: 'is_published' | 'is_printable', value: boolean) {
  if (!selected.value) return
  try {
    await api.put(`/admin/pages/${selected.value.id}`, { [flag]: value })
    selected.value[flag] = value
    toast.success(t('pages.toast.saved'))
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

async function setHome(page: PageRow) {
  try {
    await api.post(`/admin/pages/${page.id}/set-home`)
    toast.success(t('pages.toast.homeSet'))
    await load()
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

async function remove(page: PageRow) {
  const ok = await confirm({
    message: t('pages.confirmDelete', { name: page.title.es ?? '' }),
    confirmLabel: t('common.actions.delete'),
    cancelLabel: t('common.cancel'),
    variant: 'danger',
  })
  if (!ok) return
  try {
    await api.delete(`/admin/pages/${page.id}`)
    if (selectedId.value === page.id) selectedId.value = null
    toast.success(t('pages.toast.deleted'))
    await load()
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

onMounted(load)
</script>

<template>
  <div class="pages-view">
    <div class="list-view__top">
      <BaseButton @click="openCreate">
        <template #icon><Plus :size="16" /></template>
        {{ t('pages.new') }}
      </BaseButton>
    </div>

    <p v-if="!loading && !pages.length" class="pages-view__empty">{{ t('common.empty') }}</p>

    <div class="pages-view__list">
      <article
        v-for="page in pages"
        :key="page.id"
        class="pages-view__item"
        :class="{ 'is-child': page.parent_id, 'is-active': selectedId === page.id }"
        @click="(e) => select(page, e)"
      >
        <button type="button" class="pages-view__title" @click="open(page)">
          {{ page.title.es ?? Object.values(page.title)[0] }}
        </button>
        <span class="pages-view__slug">/{{ page.slug.es ?? '' }}</span>
        <span class="pages-view__chips">
          <span v-if="page.is_home" class="locale-chip is-ok">{{ t('pages.homeChip') }}</span>
          <span :class="['locale-chip', page.is_published ? 'is-ok' : 'is-missing']">
            {{ page.is_published ? t('pages.published') : t('pages.draft') }}
          </span>
          <span class="locale-chip">{{ page.blocks_count ?? 0 }} ▤</span>
        </span>
        <span class="pages-view__buttons">
          <IconButton variant="info" :title="t('pages.open')" @click="open(page)"
            ><ArrowRight :size="16"
          /></IconButton>
        </span>
      </article>
    </div>

    <PageFormModal v-model="formOpen" :page="editing" :pages="pages" @saved="load" />

    <!-- Acciones de la página seleccionada, en el panel derecho -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!selected" class="manager-panel__empty">{{ t('pages.panelEmpty') }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ t('pages.panelTitle') }}</p>
          <h3 class="manager-detail__title">
            {{ selected.title.es ?? Object.values(selected.title)[0] }}
          </h3>

          <!-- Acciones arriba del todo (patrón kontuan) -->
          <div class="manager-detail__actions">
            <BaseButton @click="open(selected)">
              <template #icon><ArrowRight :size="14" /></template>
              {{ t('pages.open') }}
            </BaseButton>
            <BaseButton variant="secondary" @click="openEdit(selected)">
              <template #icon><SquarePen :size="14" /></template>
              {{ t('common.actions.edit') }}
            </BaseButton>
            <BaseButton v-if="!selected.is_home" variant="secondary" @click="setHome(selected)">
              <template #icon><HomeIcon :size="14" /></template>
              {{ t('pages.setHome') }}
            </BaseButton>
            <BaseButton variant="danger" @click="remove(selected)">
              <template #icon><Trash2 :size="14" /></template>
              {{ t('common.actions.delete') }}
            </BaseButton>
          </div>

          <!-- Acciones rápidas sin modal -->
          <BaseCheckbox
            :model-value="selected.is_published"
            :label="t('pages.fields.published')"
            @update:model-value="(v) => toggleFlag('is_published', v)"
          />
          <BaseCheckbox
            :model-value="selected.is_printable"
            :label="t('pages.fields.printable')"
            @update:model-value="(v) => toggleFlag('is_printable', v)"
          />

          <!-- Info: slugs por idioma -->
          <p v-for="(slugValue, code) in selected.slug" :key="code" class="manager-detail__meta">
            <strong>{{ String(code).toUpperCase() }}</strong> /{{ slugValue }}
          </p>
        </template>
      </div>
    </Teleport>
  </div>
</template>
