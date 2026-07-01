<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, defineAsyncComponent } from 'vue'
import { ChevronDown } from '@lucide/vue'

// Editor WYSIWYG cargado en diferido: TipTap solo se descarga si algún campo
// traducible usa type="wysiwyg".
const RichTextInput = defineAsyncComponent(() => import('./RichTextInput.vue'))

// Campo traducible (portado de kontuan): usa el estilo `.form-field` y un
// selector desplegable de locale con contador de rellenados.
interface Locale { code: string; name: string }

const props = withDefaults(
  defineProps<{
    modelValue?: Record<string, string>
    locales: Locale[]
    label?: string
    type?: 'text' | 'textarea' | 'wysiwyg'
    placeholder?: string
    rows?: number
    required?: boolean
    id?: string
    /** Iconos del juego para el selector del editor (solo type="wysiwyg"). */
    icons?: { name: string; url: string }[]
  }>(),
  { modelValue: () => ({}), type: 'text', rows: 4, required: false, icons: () => [] },
)

const emit = defineEmits<{ 'update:modelValue': [Record<string, string>] }>()

const codes = computed(() => props.locales.map((l) => l.code))
const active = ref(codes.value[0] ?? 'es')
const open = ref(false)
const dropdownRef = ref<HTMLElement>()
const inputId = props.id || `translatable-${Math.random().toString(36).slice(2, 9)}`

const currentValue = computed(() => props.modelValue?.[active.value] || '')
const filledCount = computed(() => codes.value.filter((c) => !!props.modelValue?.[c]).length)
const hasContent = (code: string) => !!props.modelValue?.[code]

function selectLocale(code: string) {
  active.value = code
  open.value = false
}
function update(value: string) {
  emit('update:modelValue', { ...props.modelValue, [active.value]: value })
}
function onClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div class="form-field">
    <div class="translatable-header">
      <label v-if="label" :for="`${inputId}-${active}`" class="form-field__label">
        {{ label }}<span v-if="required" class="form-field__required">*</span>
      </label>
      <div ref="dropdownRef" class="translatable-selector">
        <button type="button" class="translatable-trigger" @click="open = !open">
          <span class="translatable-trigger__code">{{ active.toUpperCase() }}</span>
          <span class="translatable-trigger__count">{{ filledCount }}/{{ codes.length }}</span>
          <ChevronDown class="translatable-trigger__chevron" :size="12" />
        </button>
        <Transition name="dropdown">
          <div v-if="open" class="translatable-dropdown">
            <button
              v-for="loc in locales"
              :key="loc.code"
              type="button"
              :class="['translatable-option', {
                'translatable-option--active': loc.code === active,
                'translatable-option--empty': !hasContent(loc.code),
              }]"
              @click="selectLocale(loc.code)"
            >
              <span class="translatable-option__code">{{ loc.code.toUpperCase() }}</span>
              <span class="translatable-option__label">{{ loc.name }}</span>
              <span v-if="hasContent(loc.code)" class="translatable-option__filled" />
            </button>
          </div>
        </Transition>
      </div>
    </div>

    <RichTextInput
      v-if="type === 'wysiwyg'"
      :key="active"
      :model-value="currentValue"
      :placeholder="placeholder"
      :icons="icons"
      @update:model-value="update"
    />
    <textarea
      v-else-if="type === 'textarea'"
      :id="`${inputId}-${active}`"
      :value="currentValue"
      :placeholder="placeholder"
      :rows="rows"
      class="form-field__textarea"
      @input="update(($event.target as HTMLTextAreaElement).value)"
    />
    <input
      v-else
      :id="`${inputId}-${active}`"
      type="text"
      :value="currentValue"
      :placeholder="placeholder"
      class="form-field__input"
      @input="update(($event.target as HTMLInputElement).value)"
    />
  </div>
</template>
