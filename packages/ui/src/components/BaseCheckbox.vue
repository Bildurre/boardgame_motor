<script setup lang="ts">
import { Check } from '@lucide/vue'

// Checkbox de formulario (portado de kontuan).
const props = withDefaults(
  defineProps<{
    modelValue?: boolean
    label?: string
    disabled?: boolean
    id?: string
  }>(),
  { modelValue: false, disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

const checkboxId = props.id || `checkbox-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <label :for="checkboxId" class="checkbox" :class="{ 'checkbox--disabled': disabled }">
    <input
      :id="checkboxId"
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      class="checkbox__input"
      @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span class="checkbox__box">
      <Check v-if="modelValue" class="checkbox__icon" :size="14" />
    </span>
    <span v-if="$slots.default || label" class="checkbox__label"><slot>{{ label }}</slot></span>
  </label>
</template>
