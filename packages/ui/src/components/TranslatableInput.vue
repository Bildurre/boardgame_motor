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
