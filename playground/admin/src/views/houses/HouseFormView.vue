<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TranslatableInput, ImageUpload, BaseButton, useToast } from '@bgm/ui'
import { useResource } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const toast = useToast()
const { find, createForm, updateForm } = useResource(api, '/admin/houses')

const id = route.params.id ? Number(route.params.id) : null
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
  if (id) {
    const h: any = await find(id)
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
    if (id) {
      await updateForm(id, toFormData())
      toast.success('Casa actualizada correctamente.')
    } else {
      await createForm(toFormData())
      toast.success('Casa creada correctamente.')
    }
    router.push({ name: 'houses' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="hform" @submit.prevent="save">
    <TranslatableInput v-model="form.name" :locales="locales.locales" label="Nombre" />
    <TranslatableInput v-model="form.description" :locales="locales.locales" label="Descripción" type="textarea" />
    <ImageUpload v-model="image" :current-url="currentImage" label="Emblema" />

    <div class="hform__row">
      <label>Color</label>
      <input v-model="form.color" type="color" />
    </div>

    <label class="hform__check"><input v-model="form.is_published" type="checkbox" /> Publicada</label>

    <p v-if="error" class="error">{{ error }}</p>

    <div class="hform__actions">
      <BaseButton type="submit">{{ saving ? 'Guardando…' : 'Guardar' }}</BaseButton>
      <BaseButton variant="secondary" type="button" @click="router.push({ name: 'houses' })">Cancelar</BaseButton>
    </div>
  </form>
</template>
