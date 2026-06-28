<script setup lang="ts">
interface Column { key: string; label: string }

defineProps<{
  columns: Column[]
  items: any[]
  meta?: Record<string, any> | null
  loading?: boolean
}>()

const emit = defineEmits<{ page: [number] }>()
</script>

<template>
  <div class="rlist">
    <div class="rlist__scroll">
    <table class="rlist__table">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
          <th class="rlist__actions-h"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading"><td :colspan="columns.length + 1" class="rlist__empty">Cargando…</td></tr>
        <tr v-else-if="!items.length"><td :colspan="columns.length + 1" class="rlist__empty">Sin resultados.</td></tr>
        <tr v-for="item in items" :key="item.id">
          <td v-for="col in columns" :key="col.key">
            <slot :name="`cell-${col.key}`" :item="item">{{ item[col.key] }}</slot>
          </td>
          <td class="rlist__actions"><slot name="actions" :item="item" /></td>
        </tr>
      </tbody>
    </table>
    </div>

    <div v-if="meta && meta.last_page > 1" class="rlist__pager">
      <button :disabled="meta.current_page <= 1" @click="emit('page', meta.current_page - 1)">‹</button>
      <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button :disabled="meta.current_page >= meta.last_page" @click="emit('page', meta.current_page + 1)">›</button>
    </div>
  </div>
</template>

<style scoped lang="scss">
.rlist {
  &__scroll { overflow-x: auto; }
  &__table {
    width: 100%;
    border-collapse: collapse;
    th, td { text-align: left; padding: $space-3; border-bottom: 1px solid $color-border; }
    th { font-size: 0.8rem; color: $color-text-muted; font-weight: 600; }
  }
  &__actions, &__actions-h { text-align: right; white-space: nowrap; }
  &__empty { color: $color-text-muted; text-align: center; padding: $space-6; }
  &__pager {
    display: flex; align-items: center; gap: $space-3; justify-content: center; margin-top: $space-4;
    button {
      font: inherit; padding: 0.25rem 0.6rem; background: $color-surface;
      border: 1px solid $color-border; border-radius: $radius-md; color: $color-text; cursor: pointer;
      &:disabled { opacity: 0.4; cursor: default; }
    }
  }
}
</style>
