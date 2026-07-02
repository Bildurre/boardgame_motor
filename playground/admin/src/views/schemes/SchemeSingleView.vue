<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, SquarePen } from '@lucide/vue'
import { useResource } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import PreviewPanel from '@/components/previews/PreviewPanel.vue'
import { useLocalesStore } from '@/stores/locales'
import { type Scheme, SchemeCard } from '@playground/shared'
import SchemeFormModal from '@/components/schemes/SchemeFormModal.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const { find } = useResource<Scheme>(api, '/admin/schemes')

const item = ref<Scheme | null>(null)
const loading = ref(true)
const formOpen = ref(false)

function tr(obj: Record<string, string> | null | undefined) {
  return (
    obj?.[locales.current] || obj?.[locales.defaultLocale] || Object.values(obj || {})[0] || '—'
  )
}
const slug = computed(() => route.params.slug as string)

async function load() {
  loading.value = true
  try {
    item.value = await find(slug.value)
  } catch {
    item.value = null
  } finally {
    loading.value = false
  }
}
async function onSaved() {
  await load()
}

onMounted(async () => {
  await locales.load()
  await load()
})
</script>

<!-- eslint-disable vue/no-v-html -- HTML del WYSIWYG propio (sanitización en servidor: DC-09) -->
<template>
  <div v-if="item" class="single">
    <div class="single__bar">
      <BaseButton variant="secondary" @click="router.push({ name: 'schemes' })"
        ><ArrowLeft :size="16" /> {{ t('schemes.title') }}</BaseButton
      >
      <BaseButton variant="success" @click="formOpen = true"
        ><SquarePen :size="16" /> {{ t('common.actions.edit') }}</BaseButton
      >
    </div>

    <div class="single__layout">
      <div class="single__preview">
        <SchemeCard :item="item" :locale="locales.current" />
      </div>
      <div class="single__info">
        <h1>{{ tr(item.title) }}</h1>
        <p class="single__meta">
          {{ t('schemes.fields.house') }}: {{ tr(item.house?.name) }} ·
          {{ t('schemes.fields.cost') }}: {{ item.cost }}
        </p>
        <div class="rich-content" v-html="tr(item.description)" />
      </div>
    </div>

    <PreviewPanel v-if="item" :id="item.id" entity="scheme" />

    <SchemeFormModal v-model="formOpen" mode="edit" :target-slug="slug" @saved="onSaved" />
  </div>
  <p v-else-if="!loading" class="single__empty">{{ t('common.empty') }}</p>
</template>
