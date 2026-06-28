<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TranslatableInput, BaseButton } from '@bgm/ui'
import { useResource } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

const route = useRoute()
const router = useRouter()
const locales = useLocalesStore()
const { find, create, update } = useResource(api, '/admin/houses')

const id = route.params.id ? Number(route.params.id) : null
const form = reactive<{
  name: Record<string, string>
  description: Record<string, string>
  color: string
  is_published: boolean
}>({ name: {}, description: {}, color: '#888888', is_published: false })

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
  }
})

async function save() {
  error.value = null
  saving.value = true
  const payload = {
    name: form.name,
    description: form.description,
    color: form.color,
    is_published: form.is_published,
  }
  try {
    if (id) await update(id, payload)
    else await create(payload)
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

<style scoped lang="scss">
.hform {
  max-width: 560px;
  display: flex;
  flex-direction: column;
  gap: $space-4;
}
.hform__row { display: flex; align-items: center; gap: $space-3; }
.hform__row label { color: $color-text-muted; font-size: 0.85rem; }
.hform__check { display: flex; align-items: center; gap: $space-2; color: $color-text; }
.hform__actions { display: flex; gap: $space-3; }
.error { color: #ff6b6b; margin: 0; }
</style>
