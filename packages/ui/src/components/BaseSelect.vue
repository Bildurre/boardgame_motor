<script setup lang="ts">
// Select de formulario (portado de kontuan).
export interface SelectOption {
  value: string | number
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number
    label?: string
    options: SelectOption[]
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    required?: boolean
    id?: string
  }>(),
  { modelValue: '', disabled: false, required: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>()

const selectId = props.id || `select-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="selectId" class="form-field__label">
      {{ label }}<span v-if="required" class="form-field__required">*</span>
    </label>
    <div class="form-field__select-wrapper">
      <select
        :id="selectId"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        class="form-field__select"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </div>
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
