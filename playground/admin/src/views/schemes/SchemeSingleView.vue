<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, SquarePen } from '@lucide/vue'
import { useResource } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import SchemeCard from '@/components/schemes/SchemeCard.vue'
import SchemeFormModal from '@/components/schemes/SchemeFormModal.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const { find } = useResource(api, '/admin/schemes')

const item = ref<any>(null)
const loading = ref(true)
const formOpen = ref(false)

function tr(obj: Record<string, string>) {
  return obj?.[locales.current] || Object.values(obj || {})[0] || '—'
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

<template>
  <div v-if="item" class="single">
    <div class="single__bar">
      <BaseButton variant="secondary" @click="router.push({ name: 'schemes' })"
        ><ArrowLeft :size="16" /> {{ t('schemes.title') }}</BaseButton
      >
      <BaseButton variant="success" @click="formOpen = true"
        ><SquarePen :size="16" /> {{ t('houses.actions.edit') }}</BaseButton
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

    <SchemeFormModal v-model="formOpen" mode="edit" :target-slug="slug" @saved="onSaved" />
  </div>
  <p v-else-if="!loading" class="single__empty">{{ t('common.empty') }}</p>
</template>
