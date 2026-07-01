<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Bold, Italic, Strikethrough, Heading2, List, ListOrdered, Undo2, Redo2 } from '@lucide/vue'

// Editor de texto enriquecido (WYSIWYG) basado en TipTap (DC-09).
// v-model = HTML. Agnóstico de i18n.
const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    disabled?: boolean
  }>(),
  { modelValue: '', disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [StarterKit],
  onUpdate: ({ editor }) => {
    const html = editor.isEmpty ? '' : editor.getHTML()
    emit('update:modelValue', html)
  },
})

// Sincroniza cuando el valor cambia por fuera (p. ej. al cambiar de idioma).
watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) return
    if (value !== (editor.value.isEmpty ? '' : editor.value.getHTML())) {
      editor.value.commands.setContent(value || '', false)
    }
  },
)
watch(() => props.disabled, (d) => editor.value?.setEditable(!d))

onBeforeUnmount(() => editor.value?.destroy())

const tools = [
  { key: 'bold', icon: Bold, title: 'Negrita', run: () => editor.value?.chain().focus().toggleBold().run(), active: () => editor.value?.isActive('bold') },
  { key: 'italic', icon: Italic, title: 'Cursiva', run: () => editor.value?.chain().focus().toggleItalic().run(), active: () => editor.value?.isActive('italic') },
  { key: 'strike', icon: Strikethrough, title: 'Tachado', run: () => editor.value?.chain().focus().toggleStrike().run(), active: () => editor.value?.isActive('strike') },
  { key: 'h2', icon: Heading2, title: 'Título', run: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(), active: () => editor.value?.isActive('heading', { level: 2 }) },
  { key: 'ul', icon: List, title: 'Lista', run: () => editor.value?.chain().focus().toggleBulletList().run(), active: () => editor.value?.isActive('bulletList') },
  { key: 'ol', icon: ListOrdered, title: 'Lista numerada', run: () => editor.value?.chain().focus().toggleOrderedList().run(), active: () => editor.value?.isActive('orderedList') },
]
</script>

<template>
  <div class="rich-text" :class="{ 'rich-text--disabled': disabled }">
    <div v-if="editor" class="rich-text__toolbar">
      <button
        v-for="tool in tools"
        :key="tool.key"
        type="button"
        class="rich-text__tool"
        :class="{ 'rich-text__tool--active': tool.active() }"
        :title="tool.title"
        :disabled="disabled"
        @click="tool.run()"
      >
        <component :is="tool.icon" :size="16" />
      </button>
      <span class="rich-text__sep" />
      <button type="button" class="rich-text__tool" title="Deshacer" :disabled="disabled" @click="editor?.chain().focus().undo().run()">
        <Undo2 :size="16" />
      </button>
      <button type="button" class="rich-text__tool" title="Rehacer" :disabled="disabled" @click="editor?.chain().focus().redo().run()">
        <Redo2 :size="16" />
      </button>
    </div>
    <EditorContent class="rich-text__content" :editor="editor" />
  </div>
</template>
