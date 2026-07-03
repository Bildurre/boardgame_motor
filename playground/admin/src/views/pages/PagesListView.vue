<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { House as HomeIcon, Plus, SquarePen, Trash2 } from '@lucide/vue'
import { BaseButton, IconButton, useConfirm, useToast } from '@bgm/ui'
import { api } from '@/lib/api'
import PageFormModal, { type PageRow } from '@/components/pages/PageFormModal.vue'

// Listado de páginas del CRM: pocas por naturaleza, sin paginación. El single
// gestiona los bloques.
const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()

const pages = ref<PageRow[]>([])
const loading = ref(true)
const formOpen = ref(false)
const editing = ref<PageRow | null>(null)

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

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(page: PageRow) {
  editing.value = page
  formOpen.value = true
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
        :class="{ 'is-child': page.parent_id }"
      >
        <button
          type="button"
          class="pages-view__title"
          @click="router.push({ name: 'page', params: { id: page.id } })"
        >
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
          <IconButton
            v-if="!page.is_home"
            variant="info"
            :title="t('pages.setHome')"
            @click="setHome(page)"
            ><HomeIcon :size="16"
          /></IconButton>
          <IconButton variant="info" :title="t('common.actions.edit')" @click="openEdit(page)"
            ><SquarePen :size="16"
          /></IconButton>
          <IconButton variant="danger" :title="t('common.actions.delete')" @click="remove(page)"
            ><Trash2 :size="16"
          /></IconButton>
        </span>
      </article>
    </div>

    <PageFormModal v-model="formOpen" :page="editing" :pages="pages" @saved="load" />
  </div>
</template>
