<script setup lang="ts">
import { ref } from 'vue'

interface Locale { code: string; name: string }

const props = withDefaults(
  defineProps<{
    modelValue: Record<string, string>
    locales: Locale[]
    label?: string
    type?: 'text' | 'textarea'
    placeholder?: string
  }>(),
  { type: 'text', label: '', placeholder: '' },
)

const emit = defineEmits<{ 'update:modelValue': [Record<string, string>] }>()

const active = ref(props.locales[0]?.code ?? 'es')

function update(value: string) {
  emit('update:modelValue', { ...props.modelValue, [active.value]: value })
}
</script>

<template>
  <div class="t-input">
    <div class="t-input__head">
      <label v-if="label" class="t-input__label">{{ label }}</label>
      <div class="t-input__tabs">
        <button
          v-for="loc in locales"
          :key="loc.code"
          type="button"
          class="t-input__tab"
          :class="{ 'is-active': active === loc.code }"
          @click="active = loc.code"
        >
          {{ loc.code }}
        </button>
      </div>
    </div>

    <textarea
      v-if="type === 'textarea'"
      class="t-input__field"
      rows="3"
      :placeholder="placeholder"
      :value="modelValue[active] ?? ''"
      @input="update(($event.target as HTMLTextAreaElement).value)"
    />
    <input
      v-else
      class="t-input__field"
      :placeholder="placeholder"
      :value="modelValue[active] ?? ''"
      @input="update(($event.target as HTMLInputElement).value)"
    />
  </div>
</template>

<style scoped lang="scss">
.t-input {
  display: flex;
  flex-direction: column;
  gap: $space-2;

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-2;
  }

  &__label { font-size: 0.85rem; color: $color-text-muted; }

  &__tabs { display: flex; gap: 2px; }

  &__tab {
    font: inherit;
    font-size: 0.72rem;
    text-transform: uppercase;
    padding: 2px 6px;
    border: 1px solid $color-border;
    border-radius: $radius-sm;
    background: transparent;
    color: $color-text-muted;
    cursor: pointer;

    &.is-active { color: #fff; background: $color-accent; border-color: $color-accent; }
  }

  &__field {
    font: inherit;
    width: 100%;
    padding: 0.55rem 0.75rem;
    background: $color-bg;
    border: 1px solid $color-border;
    border-radius: $radius-md;
    color: $color-text;
    resize: vertical;

    &:focus { outline: none; border-color: $color-accent; }
  }
}
</style>
