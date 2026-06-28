<script setup lang="ts">
import { reactive, watch } from 'vue'

interface Option { value: string; label: string }

const props = withDefaults(
  defineProps<{
    statusOptions?: Option[]
    searchPlaceholder?: string
  }>(),
  { searchPlaceholder: 'Buscar…' },
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
      <option value="">Todos</option>
      <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
  </div>
</template>

<style scoped lang="scss">
.filters {
  display: flex;
  gap: $space-3;
  margin-bottom: $space-4;

  &__search { flex: 1; }
  &__search, &__status {
    font: inherit;
    padding: 0.5rem 0.75rem;
    background: $color-surface;
    border: 1px solid $color-border;
    border-radius: $radius-md;
    color: $color-text;
    &:focus { outline: none; border-color: $color-accent; }
  }
}
</style>
