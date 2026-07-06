<script setup lang="ts">
import { reactive, ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  EditModal,
  TranslatableInput,
  ImageUpload,
  NumericInput,
  BaseSelect,
  BaseCheckbox,
  useToast,
} from '@edc-motor/ui'
import { useResource } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { fieldErrors } from '@/lib/apiError'
import { useEditorLabels } from '@/lib/editorLabels'
import { useLocalesStore } from '@/stores/locales'
import { useIconsStore } from '@/stores/icons'
import { useHousesStore } from '@/stores/houses'
import type { Scheme } from '@playground/shared'

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
const houses = useHousesStore()
const { find, createForm, updateForm } = useResource<Scheme>(api, '/admin/schemes')
const editorLabels = useEditorLabels()

const saving = ref(false)
const image = ref<File | null>(null)
const currentImage = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})

function clearErrors() {
  for (const k of Object.keys(errors)) delete errors[k]
}
function mapServerErrors(e: unknown) {
  for (const [k, v] of Object.entries(fieldErrors(e))) {
    if (k === 'title' || k.startsWith('title.')) errors.title = v
    if (k === 'house_id') errors.house_id = v
    if (k === 'cost') errors.cost = v
  }
}

const form = reactive<{
  house_id: string
  title: Record<string, string>
  description: Record<string, string>
  cost: number
  is_published: boolean
}>({
  house_id: '',
  title: {},
  description: {},
  cost: 0,
  is_published: false,
})

const title = computed(() => (props.mode === 'create' ? t('schemes.new') : t('schemes.edit')))
const iconList = computed(() =>
  icons.icons.filter((i) => i.url).map((i) => ({ name: i.name, url: i.url as string })),
)
const houseOptions = computed(() =>
  houses.options.map((h) => ({
    value: h.id,
    label: h.name?.[locales.current] || Object.values(h.name || {})[0] || `#${h.id}`,
  })),
)
const hasTitle = () => Object.values(form.title).some((v) => v && v.trim() !== '')

function reset() {
  form.house_id = ''
  form.title = {}
  form.description = {}
  form.cost = 0
  form.is_published = false
  image.value = null
  currentImage.value = null
  clearErrors()
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    reset()
    try {
      await Promise.all([locales.load(), icons.load(), houses.loadOptions()])
    } catch {
      toast.danger(t('common.errors.load'))
    }
    if (props.mode === 'edit' && props.targetSlug) {
      try {
        const s = await find(props.targetSlug)
        form.house_id = String(s.house_id ?? '')
        form.title = s.title ?? {}
        form.description = s.description ?? {}
        form.cost = s.cost ?? 0
        form.is_published = !!s.is_published
        currentImage.value = s.image ?? null
      } catch {
        toast.danger(t('schemes.toast.saveError'))
        emit('update:modelValue', false)
      }
    }
  },
)

function toFormData(): FormData {
  const fd = new FormData()
  fd.append('house_id', form.house_id)
  for (const [k, v] of Object.entries(form.title)) fd.append(`title[${k}]`, v ?? '')
  for (const [k, v] of Object.entries(form.description)) fd.append(`description[${k}]`, v ?? '')
  fd.append('cost', String(form.cost ?? 0))
  fd.append('is_published', form.is_published ? '1' : '0')
  if (image.value) fd.append('image', image.value)
  return fd
}

async function submit() {
  clearErrors()
  if (!form.house_id) errors.house_id = t('common.required')
  if (!hasTitle()) errors.title = t('common.required')
  if (errors.house_id || errors.title) return
  saving.value = true
  try {
    if (props.mode === 'edit' && props.targetSlug) {
      await updateForm(props.targetSlug, toFormData())
      toast.success(t('schemes.toast.updated'))
    } else {
      await createForm(toFormData())
      toast.success(t('schemes.toast.created'))
    }
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    mapServerErrors(e)
    toast.danger(t('schemes.toast.saveError'))
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
    <BaseSelect
      v-model="form.house_id"
      :label="t('schemes.fields.house')"
      :options="houseOptions"
      :placeholder="t('schemes.fields.house')"
      required
      :error="errors.house_id"
    />
    <TranslatableInput
      v-model="form.title"
      :locales="locales.locales"
      :label="t('schemes.fields.title')"
      required
      :error="errors.title"
    />
    <TranslatableInput
      v-model="form.description"
      :locales="locales.locales"
      :label="t('schemes.fields.description')"
      type="wysiwyg"
      :icons="iconList"
      :rich-labels="editorLabels"
    />
    <NumericInput
      v-model="form.cost"
      :min="0"
      :label="t('schemes.fields.cost')"
      :error="errors.cost"
    />
    <ImageUpload
      v-model="image"
      :current-url="currentImage"
      :label="t('schemes.fields.image')"
      :drag-text="t('common.imageDrag')"
      :hint-text="t('common.imageHint')"
      :too-large-text="t('common.fileTooLarge')"
      :invalid-type-text="t('common.fileType')"
    />
    <BaseCheckbox v-model="form.is_published" :label="t('schemes.fields.published')" />
  </EditModal>
</template>
