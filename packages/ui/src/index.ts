// @edc-motor/ui — librería de componentes base + tokens + utilidades del motor.

import { defineAsyncComponent } from 'vue'

export { default as BaseButton } from './components/BaseButton.vue'
export { default as IconButton } from './components/IconButton.vue'
export { default as MotorBadge } from './components/MotorBadge.vue'
export { default as BaseInput } from './components/BaseInput.vue'
export { default as BaseTextarea } from './components/BaseTextarea.vue'
export { default as BasePagination } from './components/BasePagination.vue'
export { default as BaseSelect, type SelectOption } from './components/BaseSelect.vue'
export { default as SearchSelect, type SearchSelectOption } from './components/SearchSelect.vue'
export { default as BaseCheckbox } from './components/BaseCheckbox.vue'
export { default as NumericInput } from './components/NumericInput.vue'
export { default as PaletteColorPicker } from './components/PaletteColorPicker.vue'
export { default as TranslatableInput } from './components/TranslatableInput.vue'
export { default as TranslatableImage } from './components/TranslatableImage.vue'
// El WYSIWYG carga DIFERIDO (TipTap pesa ~450 KB): así la web pública no lo
// arrastra a su bundle y el admin lo trocea en su propio chunk.
export const RichTextInput = defineAsyncComponent(() => import('./components/RichTextInput.vue'))
export type { RichIcon, RichTextLabels } from './components/RichTextInput.vue'
export { default as ImageUpload } from './components/ImageUpload.vue'
export { default as FontUpload } from './components/FontUpload.vue'
export { default as BaseModal } from './components/BaseModal.vue'
export { default as EditModal } from './components/EditModal.vue'
export { default as BaseTabs } from './components/BaseTabs.vue'
export { default as BaseToast } from './components/BaseToast.vue'
export { default as ToastContainer } from './components/ToastContainer.vue'
export { default as ConfirmDialog } from './components/ConfirmDialog.vue'
export { default as ThemeSelector } from './components/ThemeSelector.vue'
export { default as LocaleSelector } from './components/LocaleSelector.vue'
export { default as AppBreadcrumbs, type Crumb } from './components/AppBreadcrumbs.vue'
export { default as PreviewGrid, type PreviewGridItem } from './components/PreviewGrid.vue'
export { createApi, type CreateApiOptions } from './lib/createApi'
export { useToast, type Toast } from './composables/useToast'
export { useConfirm, type ConfirmOptions } from './composables/useConfirm'
export { useTheme, type ThemeMode } from './composables/useTheme'
export { useHead, type HeadInput } from './composables/useHead'

// Bloques de presentación del CRM (doc 03).
export * from './blocks'
