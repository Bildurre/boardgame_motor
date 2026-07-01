<script setup lang="ts">
import { reactive, ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { EditModal, TranslatableInput, ImageUpload, PaletteColorPicker, BaseCheckbox, useToast } from '@bgm/ui'
import { useResource } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'
import { useIconsStore } from '@/stores/icons'

// Formulario de House en modal (patrón kontuan): se abre desde el listado.
const props = defineProps<{
  modelValue: boolean
  mode: 'create' | 'edit'
  targetSlug?: string | null
}>()

const emit = defineEmits<{ 'update:modelValue': [boolean]; saved: [] }>()

const { t } = useI18n()
const toast = useToast()
const locales = useLocalesStore()
const icons = useIconsStore()
const { find, createForm, updateForm } = useResource(api, '/admin/houses')

const saving = ref(false)
const error = ref<string | null>(null)
const image = ref<File | null>(null)
const currentImage = ref<string | null>(null)

const form = reactive<{
  name: Record<string, string>
  description: Record<string, string>
  color: string
  is_published: boolean
}>({ name: {}, description: {}, color: '#888888', is_published: false })

const title = computed(() => (props.mode === 'create' ? t('houses.new') : t('houses.edit')))
// Iconos con URL para el selector del editor.
const iconList = computed(() =>
  icons.icons.filter((i) => i.url).map((i) => ({ name: i.name, url: i.url as string })),
)

function reset() {
  form.name = {}
  form.description = {}
  form.color = '#888888'
  form.is_published = false
  image.value = null
  currentImage.value = null
  error.value = null
}

// Al abrir: en edición carga la casa por slug; en alta limpia.
watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    reset()
    await locales.load()
    await icons.load()
    if (props.mode === 'edit' && props.targetSlug) {
      try {
        const h: any = await find(props.targetSlug)
        form.name = h.name ?? {}
        form.description = h.description ?? {}
        form.color = h.color ?? '#888888'
        form.is_published = !!h.is_published
        currentImage.value = h.image ?? null
      } catch {
        toast.danger(t('houses.toast.saveError'))
        emit('update:modelValue', false)
      }
    }
  },
)

function toFormData(): FormData {
  const fd = new FormData()
  for (const [k, v] of Object.entries(form.name)) fd.append(`name[${k}]`, v ?? '')
  for (const [k, v] of Object.entries(form.description)) fd.append(`description[${k}]`, v ?? '')
  fd.append('color', form.color)
  fd.append('is_published', form.is_published ? '1' : '0')
  if (image.value) fd.append('image', image.value)
  return fd
}

async function submit() {
  error.value = null
  saving.value = true
  try {
    if (props.mode === 'edit' && props.targetSlug) {
      await updateForm(props.targetSlug, toFormData())
      toast.success(t('houses.toast.updated'))
    } else {
      await createForm(toFormData())
      toast.success(t('houses.toast.created'))
    }
    emit('saved')
    emit('update:modelValue', false)
  } catch (e: any) {
    error.value = e.response?.data?.message ?? t('houses.toast.saveError')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <EditModal
    :model-value="modelValue"
    :title="title"
    :loading="saving"
    :submit-label="t('common.save')"
    :cancel-label="t('common.cancel')"
    @update:model-value="(v: boolean) => emit('update:modelValue', v)"
    @submit="submit"
  >
    <TranslatableInput v-model="form.name" :locales="locales.locales" :label="t('houses.fields.name')" />
    <TranslatableInput v-model="form.description" :locales="locales.locales" :label="t('houses.fields.description')" type="wysiwyg" :icons="iconList" />
    <ImageUpload
      v-model="image"
      :current-url="currentImage"
      :label="t('houses.fields.image')"
      :drag-text="t('houses.fields.imageDrag')"
      :hint-text="t('houses.fields.imageHint')"
    />

    <PaletteColorPicker v-model="form.color" :label="t('houses.fields.color')" />

    <BaseCheckbox v-model="form.is_published" :label="t('houses.fields.published')" />

    <p v-if="error" class="error">{{ error }}</p>
  </EditModal>
</template>
