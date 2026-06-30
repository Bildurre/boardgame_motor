<script setup lang="ts">
import { Search } from '@lucide/vue'

// Barra de filtros (estilo kontuan): caja de búsqueda con icono de lupa.
// Controlada por v-model; se compone por encima de las tabs. Slot por defecto
// para filtros extra (selects, fechas, …) a la derecha de la búsqueda.
defineProps<{
  modelValue: string
  placeholder?: string
}>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div class="filter-bar">
    <div class="filter-bar__search">
      <Search :size="16" class="filter-bar__search-icon" />
      <input
        type="search"
        :value="modelValue"
        :placeholder="placeholder"
        class="filter-bar__search-input"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div v-if="$slots.default" class="filter-bar__extra"><slot /></div>
  </div>
</template>
