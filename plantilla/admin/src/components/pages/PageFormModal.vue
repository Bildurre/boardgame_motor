<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BaseCheckbox,
  BaseSelect,
  EditModal,
  ImageUpload,
  TranslatableInput,
  useToast,
} from '@edc-motor/ui'
import { deleteContentImage, uploadContentImage } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Formulario de página (crear/editar) en modal, patrón kontuan.
export interface PageRow {
  id: number
  title: Record<string, string>
  description: Record<string, string>
  slug: Record<string, string>
  meta_title: Record<string, string>
  meta_description: Record<string, string>
  parent_id: number | null
  template: string | null
  background_image: string | null
  is_published: boolean
  is_home: boolean
  is_printable: boolean
  blocks_count?: number
}

const props = defineProps<{
  modelValue: boolean
  page?: PageRow | null
  /** Para el selector de página madre. */
  pages: PageRow[]
}>()

const emit = defineEmits<{ 'update:modelValue': [boolean]; saved: [] }>()

const { t, te } = useI18n()
const toast = useToast()
const locales = useLocalesStore()

const saving = ref(false)
const title = ref<Record<string, string>>({})
const description = ref<Record<string, string>>({})
const metaTitle = ref<Record<string, string>>({})
const metaDescription = ref<Record<string, string>>({})
const parentId = ref<string>('')
const template = ref('default')
// Imagen de fondo DIFERIDA: el estado lleva la URL guardada (string) o el
// File pendiente; NADA viaja al servidor hasta el GUARDAR (cancelar el modal
// no deja rastro). "Quitar" también se difiere (el estado queda a null).
const backgroundImage = ref<string | File | null>(null)
const savedBackground = ref<string | null>(null)
const isPublished = ref(false)
const isPrintable = ref(false)

const backgroundFile = computed(() =>
  backgroundImage.value instanceof File ? backgroundImage.value : null,
)
const backgroundUrl = computed(() =>
  typeof backgroundImage.value === 'string' ? backgroundImage.value : null,
)

// Plantillas del juego (config del motor): el select solo aparece si hay más
// de una. Etiquetas localizables por convención (pages.templates.{clave}).
const templates = ref<{ key: string; label: string }[]>([])

function templateLabel(tpl: { key: string; label: string }): string {
  return te(`pages.templates.${tpl.key}`) ? t(`pages.templates.${tpl.key}`) : tpl.label
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    title.value = { ...(props.page?.title ?? {}) }
    description.value = { ...(props.page?.description ?? {}) }
    metaTitle.value = { ...(props.page?.meta_title ?? {}) }
    metaDescription.value = { ...(props.page?.meta_description ?? {}) }
    parentId.value = props.page?.parent_id ? String(props.page.parent_id) : ''
    template.value = props.page?.template ?? 'default'
    backgroundImage.value = props.page?.background_image ?? null
    savedBackground.value = props.page?.background_image ?? null
    isPublished.value = props.page?.is_published ?? false
    isPrintable.value = props.page?.is_printable ?? false
    if (!templates.value.length) {
      try {
        const { data } = await api.get('/admin/pages/templates')
        templates.value = data.data
      } catch {
        // sin catálogo: el campo no se muestra
      }
    }
  },
)

async function save() {
  saving.value = true
  const uploaded: string[] = []
  try {
    // El fondo pendiente se sube AHORA (dos pasos, pero solo en el submit).
    let background = backgroundImage.value
    if (background instanceof File) {
      background = await uploadContentImage(api, background)
      uploaded.push(background)
    }
    const payload = {
      title: title.value,
      description: description.value,
      meta_title: metaTitle.value,
      meta_description: metaDescription.value,
      parent_id: parentId.value ? Number(parentId.value) : null,
      template: template.value,
      background_image: background,
      is_published: isPublished.value,
      is_printable: isPrintable.value,
    }
    if (props.page) await api.put(`/admin/pages/${props.page.id}`, payload)
    else await api.post('/admin/pages', payload)
    // Guardado en firme: el fondo sustituido o quitado, fuera del disco.
    if (savedBackground.value && savedBackground.value !== background) {
      await deleteContentImage(api, savedBackground.value)
    }
    toast.success(t('pages.toast.saved'))
    emit('saved')
    emit('update:modelValue', false)
  } catch {
    // Guardado fallido: se deshace la subida (el File sigue en el form).
    await Promise.all(uploaded.map((url) => deleteContentImage(api, url)))
    toast.danger(t('pages.toast.saveError'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <EditModal
    :model-value="modelValue"
    :title="page ? t('pages.edit') : t('pages.new')"
    :submit-label="t('common.save')"
    :cancel-label="t('common.cancel')"
    :loading="saving"
    @update:model-value="(v) => emit('update:modelValue', v)"
    @submit="save"
  >
    <TranslatableInput
      v-model="title"
      :locales="locales.locales"
      :label="t('pages.fields.title')"
      required
    />
    <TranslatableInput
      v-model="description"
      :locales="locales.locales"
      :label="t('pages.fields.description')"
      type="textarea"
    />
    <BaseSelect
      v-model="parentId"
      :label="t('pages.fields.parent')"
      :options="[
        { value: '', label: '—' },
        ...pages
          .filter((p) => p.id !== page?.id)
          .map((p) => ({
            value: String(p.id),
            label: p.title[locales.current] ?? p.title.es ?? String(p.id),
          })),
      ]"
    />
    <BaseSelect
      v-if="templates.length > 1"
      v-model="template"
      :label="t('pages.fields.template')"
      :options="templates.map((tpl) => ({ value: tpl.key, label: templateLabel(tpl) }))"
    />
    <ImageUpload
      :model-value="backgroundFile"
      :current-url="backgroundUrl"
      :label="t('pages.fields.backgroundImage')"
      :drag-text="t('common.imageDrag')"
      :hint-text="t('pages.fields.backgroundImageHint')"
      @update:model-value="(f: File | null) => (backgroundImage = f)"
      @remove="backgroundImage = null"
    />
    <TranslatableInput
      v-model="metaTitle"
      :locales="locales.locales"
      :label="t('pages.fields.metaTitle')"
    />
    <TranslatableInput
      v-model="metaDescription"
      :locales="locales.locales"
      :label="t('pages.fields.metaDescription')"
      type="textarea"
    />
    <BaseCheckbox v-model="isPublished" :label="t('pages.fields.published')" />
    <BaseCheckbox v-model="isPrintable" :label="t('pages.fields.printable')" />
  </EditModal>
</template>
