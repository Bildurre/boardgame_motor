<script setup lang="ts">
import { reactive, watch } from 'vue'

interface Option { value: string; label: string }

const props = withDefaults(
  defineProps<{
    statusOptions?: Option[]
    searchPlaceholder?: string
    allLabel?: string
  }>(),
  { searchPlaceholder: 'Buscar…', allLabel: 'Todos' },
)

const emit = defineEmits<{ change: [{ search: string; status: string }] }>()

const state = reactive({ search: '', status: '' })

let timer: ReturnType<typeof setTimeout> | null = null
watch(
  () => ({ ...state }),
  (value) => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => emit('change', value), 250)
  },
  { deep: true },
)
</script>

<template>
  <div class="filters">
    <input v-model="state.search" class="filters__search" :placeholder="searchPlaceholder" />
    <select v-if="statusOptions" v-model="state.status" class="filters__status">
      <option value="">{{ allLabel }}</option>
      <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
  </div>
</template>
