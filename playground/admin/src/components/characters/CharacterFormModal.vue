<script setup lang="ts">
import { reactive, ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  EditModal,
  TranslatableInput,
  ImageUpload,
  NumericInput,
  BaseCheckbox,
  useToast,
} from '@bgm/ui'
import { useResource } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { fieldErrors } from '@/lib/apiError'
import { useEditorLabels } from '@/lib/editorLabels'
import { useLocalesStore } from '@/stores/locales'
import { useIconsStore } from '@/stores/icons'
import type { Character } from '@/types/entities'

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
const { find, createForm, updateForm } = useResource<Character>(api, '/admin/characters')
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
    if (k === 'name' || k.startsWith('name.')) errors.name = v
  }
}

const form = reactive<{
  name: Record<string, string>
  description: Record<string, string>
  ability: Record<string, string>
  power: number
  prestige: number
  intrigue: number
  money: number
  is_published: boolean
}>({
  name: {},
  description: {},
  ability: {},
  power: 0,
  prestige: 0,
  intrigue: 0,
  money: 0,
  is_published: false,
})

const title = computed(() => (props.mode === 'create' ? t('characters.new') : t('characters.edit')))
const iconList = computed(() =>
  icons.icons.filter((i) => i.url).map((i) => ({ name: i.name, url: i.url as string })),
)
// Coste = suma de las cuatro estadísticas; defensa = coste (solo lectura).
const cost = computed(
  () =>
    (Number(form.power) || 0) +
    (Number(form.prestige) || 0) +
    (Number(form.intrigue) || 0) +
    (Number(form.money) || 0),
)
const hasName = () => Object.values(form.name).some((v) => v && v.trim() !== '')

function reset() {
  form.name = {}
  form.description = {}
  form.ability = {}
  form.power = 0
  form.prestige = 0
  form.intrigue = 0
  form.money = 0
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
      await Promise.all([locales.load(), icons.load()])
    } catch {
      toast.danger(t('common.errors.load'))
    }
    if (props.mode === 'edit' && props.targetSlug) {
      try {
        const c = await find(props.targetSlug)
        form.name = c.name ?? {}
        form.description = c.description ?? {}
        form.ability = c.ability ?? {}
        form.power = c.power ?? 0
        form.prestige = c.prestige ?? 0
        form.intrigue = c.intrigue ?? 0
        form.money = c.money ?? 0
        form.is_published = !!c.is_published
        currentImage.value = c.image ?? null
      } catch {
        toast.danger(t('characters.toast.saveError'))
        emit('update:modelValue', false)
      }
    }
  },
)

function toFormData(): FormData {
  const fd = new FormData()
  for (const [k, v] of Object.entries(form.name)) fd.append(`name[${k}]`, v ?? '')
  for (const [k, v] of Object.entries(form.description)) fd.append(`description[${k}]`, v ?? '')
  for (const [k, v] of Object.entries(form.ability)) fd.append(`ability[${k}]`, v ?? '')
  fd.append('power', String(form.power ?? 0))
  fd.append('prestige', String(form.prestige ?? 0))
  fd.append('intrigue', String(form.intrigue ?? 0))
  fd.append('money', String(form.money ?? 0))
  fd.append('is_published', form.is_published ? '1' : '0')
  if (image.value) fd.append('image', image.value)
  return fd
}

async function submit() {
  clearErrors()
  if (!hasName()) {
    errors.name = t('common.required')
    return
  }
  saving.value = true
  try {
    if (props.mode === 'edit' && props.targetSlug) {
      await updateForm(props.targetSlug, toFormData())
      toast.success(t('characters.toast.updated'))
    } else {
      await createForm(toFormData())
      toast.success(t('characters.toast.created'))
    }
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    mapServerErrors(e)
    toast.danger(t('characters.toast.saveError'))
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
    <TranslatableInput
      v-model="form.name"
      :locales="locales.locales"
      :label="t('characters.fields.name')"
      required
      :error="errors.name"
    />
    <TranslatableInput
      v-model="form.description"
      :locales="locales.locales"
      :label="t('characters.fields.description')"
      type="wysiwyg"
      :icons="iconList"
      :rich-labels="editorLabels"
    />
    <TranslatableInput
      v-model="form.ability"
      :locales="locales.locales"
      :label="t('characters.fields.ability')"
      type="wysiwyg"
      :icons="iconList"
      :rich-labels="editorLabels"
    />

    <div class="stats-grid">
      <NumericInput v-model="form.power" :min="0" :label="t('characters.fields.power')" />
      <NumericInput v-model="form.prestige" :min="0" :label="t('characters.fields.prestige')" />
      <NumericInput v-model="form.intrigue" :min="0" :label="t('characters.fields.intrigue')" />
      <NumericInput v-model="form.money" :min="0" :label="t('characters.fields.money')" />
    </div>
    <p class="stats-derived">
      <span
        >{{ t('characters.fields.cost') }}: <strong>{{ cost }}</strong></span
      >
      <span
        >{{ t('characters.fields.defense') }}: <strong>{{ cost }}</strong></span
      >
    </p>

    <ImageUpload
      v-model="image"
      :current-url="currentImage"
      :label="t('characters.fields.image')"
      :drag-text="t('common.imageDrag')"
      :hint-text="t('common.imageHint')"
      :too-large-text="t('common.fileTooLarge')"
      :invalid-type-text="t('common.fileType')"
    />
    <BaseCheckbox v-model="form.is_published" :label="t('characters.fields.published')" />
  </EditModal>
</template>
