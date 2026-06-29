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

    <!-- Tarjetas (teléfono, < bp-md) -->
    <div class="rlist__cards">
      <p v-if="loading" class="rlist__empty">Cargando…</p>
      <p v-else-if="!items.length" class="rlist__empty">Sin resultados.</p>
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

<style scoped lang="scss">
.rlist {
  // --- Tarjetas por defecto (mobile-first) ---
  &__table-wrap { display: none; }
  &__cards { display: grid; grid-template-columns: 1fr; gap: $space-3; }

  &__card {
    border: 1px solid $color-border;
    border-radius: $radius-lg;
    background: $color-surface;
    padding: $space-4;
    display: flex;
    flex-direction: column;
    gap: $space-3;
  }
  &__card-fields { display: flex; flex-direction: column; gap: $space-2; min-width: 0; }
  &__field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-3;
    min-width: 0;
    &--nolabel { justify-content: flex-start; }
  }
  &__field-label { color: $color-text-muted; font-size: 0.8rem; flex-shrink: 0; }
  &__field-value { text-align: right; min-width: 0; overflow-wrap: anywhere; }
  &__card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 2px;
    border-top: 1px solid $color-border;
    padding-top: $space-2;
  }

  &__empty { color: $color-text-muted; text-align: center; padding: $space-6; }

  // --- Tablet vertical: 2 columnas de tarjetas ---
  @media (min-width: #{$bp-md}) {
    &__cards { grid-template-columns: 1fr 1fr; }
  }

  // --- Desktop: tabla (coincide con el sidebar fijo, $bp-lg) ---
  @media (min-width: #{$bp-lg}) {
    &__cards { display: none; }
    &__table-wrap { display: block; overflow-x: auto; }

    &__table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
      th, td { text-align: left; padding: $space-3; border-bottom: 1px solid $color-border; }
      th { font-size: 0.85em; color: $color-text-muted; font-weight: 600; }
    }
    &__actions, &__actions-h { text-align: right; white-space: nowrap; }
  }

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
