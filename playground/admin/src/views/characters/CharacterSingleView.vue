<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, SquarePen } from '@lucide/vue'
import { useResource } from '@bgm/admin-kit'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import CharacterCard from '@/components/characters/CharacterCard.vue'
import CharacterFormModal from '@/components/characters/CharacterFormModal.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const { find } = useResource(api, '/admin/characters')

const item = ref<any>(null)
const loading = ref(true)
const formOpen = ref(false)

function tr(obj: Record<string, string>) {
  return obj?.[locales.current] || Object.values(obj || {})[0] || '—'
}
const slug = computed(() => route.params.slug as string)

async function load() {
  loading.value = true
  try { item.value = await find(slug.value) } catch { item.value = null } finally { loading.value = false }
}
async function onSaved() { await load() }

onMounted(async () => { await locales.load(); await load() })
</script>

<template>
  <div class="single" v-if="item">
    <div class="single__bar">
      <BaseButton variant="secondary" @click="router.push({ name: 'characters' })"><ArrowLeft :size="16" /> {{ t('characters.title') }}</BaseButton>
      <BaseButton variant="success" @click="formOpen = true"><SquarePen :size="16" /> {{ t('houses.actions.edit') }}</BaseButton>
    </div>

    <div class="single__layout">
      <div class="single__preview">
        <CharacterCard :item="item" :locale="locales.current" />
      </div>
      <div class="single__info">
        <h1>{{ tr(item.name) }}</h1>
        <p class="single__meta">
          {{ t('characters.fields.cost') }}: {{ item.cost }} · {{ t('characters.fields.defense') }}: {{ item.defense }}
          · {{ t('characters.fields.power') }} {{ item.power }} / {{ t('characters.fields.prestige') }} {{ item.prestige }}
          / {{ t('characters.fields.intrigue') }} {{ item.intrigue }} / {{ t('characters.fields.money') }} {{ item.money }}
        </p>
        <div v-if="tr(item.ability)" class="rich-content" v-html="tr(item.ability)" />
        <div v-if="tr(item.description)" class="rich-content" v-html="tr(item.description)" />
      </div>
    </div>

    <CharacterFormModal v-model="formOpen" mode="edit" :target-slug="slug" @saved="onSaved" />
  </div>
  <p v-else-if="!loading" class="single__empty">{{ t('common.empty') }}</p>
</template>
