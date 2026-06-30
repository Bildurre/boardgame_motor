<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { TranslatableInput, ImageUpload, BaseButton, useToast } from '@bgm/ui'
import { useResource } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const locales = useLocalesStore()
const toast = useToast()
const { find, createForm, updateForm } = useResource(api, '/admin/houses')

// La ruta de edición usa el slug del locale activo (no el id).
const slug = (route.params.slug as string) || null
const form = reactive<{
  name: Record<string, string>
  description: Record<string, string>
  color: string
  is_published: boolean
}>({ name: {}, description: {}, color: '#888888', is_published: false })

const image = ref<File | null>(null)
const currentImage = ref<string | null>(null)
const error = ref<string | null>(null)
const saving = ref(false)

onMounted(async () => {
  await locales.load()
  if (slug) {
    const h: any = await find(slug)
    form.name = h.name ?? {}
    form.description = h.description ?? {}
    form.color = h.color ?? '#888888'
    form.is_published = !!h.is_published
    currentImage.value = h.image ?? null
  }
})

function toFormData(): FormData {
  const fd = new FormData()
  for (const [k, v] of Object.entries(form.name)) fd.append(`name[${k}]`, v ?? '')
  for (const [k, v] of Object.entries(form.description)) fd.append(`description[${k}]`, v ?? '')
  fd.append('color', form.color)
  fd.append('is_published', form.is_published ? '1' : '0')
  if (image.value) fd.append('image', image.value)
  return fd
}

async function save() {
  error.value = null
  saving.value = true
  try {
    if (slug) {
      await updateForm(slug, toFormData())
      toast.success(t('houses.toast.updated'))
    } else {
      await createForm(toFormData())
      toast.success(t('houses.toast.created'))
    }
    router.push({ name: 'houses' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? t('houses.toast.saveError')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="hform" @submit.prevent="save">
    <TranslatableInput v-model="form.name" :locales="locales.locales" :label="t('houses.fields.name')" />
    <TranslatableInput v-model="form.description" :locales="locales.locales" :label="t('houses.fields.description')" type="textarea" />
    <ImageUpload
      v-model="image"
      :current-url="currentImage"
      :label="t('houses.fields.image')"
      :empty-text="t('houses.fields.imageEmpty')"
      :choose-text="t('houses.fields.imageChoose')"
      :clear-text="t('houses.fields.imageClear')"
    />

    <div class="hform__row">
      <label>{{ t('houses.fields.color') }}</label>
      <input v-model="form.color" type="color" />
    </div>

    <label class="hform__check"><input v-model="form.is_published" type="checkbox" /> {{ t('houses.fields.published') }}</label>

    <p v-if="error" class="error">{{ error }}</p>

    <div class="hform__actions">
      <BaseButton type="submit">{{ saving ? t('common.saving') : t('common.save') }}</BaseButton>
      <BaseButton variant="secondary" type="button" @click="router.push({ name: 'houses' })">{{ t('common.cancel') }}</BaseButton>
    </div>
  </form>
</template>
