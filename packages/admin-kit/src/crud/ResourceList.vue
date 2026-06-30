<script setup lang="ts">
interface Column { key: string; label: string }

withDefaults(
  defineProps<{
    columns: Column[]
    items: any[]
    meta?: Record<string, any> | null
    loading?: boolean
    loadingText?: string
    emptyText?: string
  }>(),
  { loadingText: 'Cargando…', emptyText: 'Sin resultados.' },
)

const emit = defineEmits<{ page: [number] }>()
</script>

<template>
  <div class="rlist">
    <!-- Tabla (pantallas anchas, >= bp-md) -->
    <div class="rlist__table-wrap">
      <table class="rlist__table">
        <thead>
          <tr>
            <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
            <th class="rlist__actions-h"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td :colspan="columns.length + 1" class="rlist__empty">{{ loadingText }}</td></tr>
          <tr v-else-if="!items.length"><td :colspan="columns.length + 1" class="rlist__empty">{{ emptyText }}</td></tr>
          <tr v-for="item in items" :key="item.id">
            <td v-for="col in columns" :key="col.key">
              <slot :name="`cell-${col.key}`" :item="item">{{ item[col.key] }}</slot>
            </td>
            <td class="rlist__actions"><slot name="actions" :item="item" /></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Tarjetas (teléfono, < bp-md) -->
    <div class="rlist__cards">
      <p v-if="loading" class="rlist__empty">{{ loadingText }}</p>
      <p v-else-if="!items.length" class="rlist__empty">{{ emptyText }}</p>
      <div v-for="item in items" :key="item.id" class="rlist__card">
        <div class="rlist__card-fields">
          <div
            v-for="col in columns"
            :key="col.key"
            class="rlist__field"
            :class="{ 'rlist__field--nolabel': !col.label }"
          >
            <span v-if="col.label" class="rlist__field-label">{{ col.label }}</span>
            <span class="rlist__field-value">
              <slot :name="`cell-${col.key}`" :item="item">{{ item[col.key] }}</slot>
            </span>
          </div>
        </div>
        <div class="rlist__card-actions"><slot name="actions" :item="item" /></div>
      </div>
    </div>

    <div v-if="meta && meta.last_page > 1" class="rlist__pager">
      <button :disabled="meta.current_page <= 1" @click="emit('page', meta.current_page - 1)">‹</button>
      <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button :disabled="meta.current_page >= meta.last_page" @click="emit('page', meta.current_page + 1)">›</button>
    </div>
  </div>
</template>
