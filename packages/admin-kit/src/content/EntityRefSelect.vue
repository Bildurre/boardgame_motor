<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { SearchSelect, type SearchSelectOption } from '@edc-motor/ui'

// Selector del campo `entity` del DSL (doc 03): busca entre las opciones
// del endpoint del juego (shape {data: [{id, name: {locale: texto}}]}) y
// guarda el id en settings. El filtrado es en cliente (los options ya
// vienen completos y ligeros).
const props = defineProps<{
  modelValue: number | null
  label: string
  optionsUrl: string
  api: AxiosInstance
}>()

const emit = defineEmits<{ 'update:modelValue': [id: number | null] }>()

interface OptionRow {
  id: number
  name: Record<string, string> | string
}

const rows = ref<OptionRow[]>([])
const query = ref('')

function labelOf(row: OptionRow): string {
  if (typeof row.name === 'string') return row.name
  return Object.values(row.name)[0] ?? String(row.id)
}

const options = computed<SearchSelectOption[]>(() =>
  rows.value
    .map((row) => ({ id: row.id, label: labelOf(row) }))
    .filter((o) => o.label.toLowerCase().includes(query.value.toLowerCase())),
)

onMounted(async () => {
  try {
    const { data } = await props.api.get(props.optionsUrl)
    rows.value = data.data
  } catch {
    rows.value = []
  }
})
</script>

<template>
  <div class="form-field">
    <span class="form-field__label">{{ label }}</span>
    <SearchSelect
      :model-value="modelValue"
      :options="options"
      @update:model-value="(id) => emit('update:modelValue', Number(id))"
      @search="(q) => (query = q)"
    />
  </div>
</template>
