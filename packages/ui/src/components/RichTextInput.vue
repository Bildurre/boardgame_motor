<script setup lang="ts">
import { computed, ref, watch, onBeforeUnmount, onMounted } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import {
  Bold,
  Italic,
  Strikethrough,
  Heading2,
  List,
  ListOrdered,
  Undo2,
  Redo2,
  Smile,
  Code,
} from '@lucide/vue'

// Editor de texto enriquecido (WYSIWYG) basado en TipTap (DC-09).
// v-model = HTML. Agnóstico de i18n. Si se pasan `icons`, muestra un selector
// para insertar iconos del juego (como en CDL): se insertan como <img.rt-icon>.
// Incluye un toggle para editar en modo visual o directamente el HTML.
export interface RichIcon {
  name: string
  url: string
}

// Textos de la barra de herramientas, sobreescribibles por la app (DC-29).
export interface RichTextLabels {
  bold: string
  italic: string
  strike: string
  heading: string
  bulletList: string
  orderedList: string
  undo: string
  redo: string
  insertIcon: string
  editHtml: string
}

const defaultLabels: RichTextLabels = {
  bold: 'Negrita',
  italic: 'Cursiva',
  strike: 'Tachado',
  heading: 'Título',
  bulletList: 'Lista',
  orderedList: 'Lista numerada',
  undo: 'Deshacer',
  redo: 'Rehacer',
  insertIcon: 'Insertar icono',
  editHtml: 'Editar HTML',
}

const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    disabled?: boolean
    icons?: RichIcon[]
    labels?: Partial<RichTextLabels>
  }>(),
  { modelValue: '', disabled: false, icons: () => [], labels: () => ({}) },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const t = computed<RichTextLabels>(() => ({ ...defaultLabels, ...props.labels }))

// Declarado antes del watch de modelValue, que lo lee.
const source = ref(false)

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [
    StarterKit,
    // Los iconos son la única imagen que se inserta: en línea y con clase fija.
    Image.configure({ inline: true, HTMLAttributes: { class: 'rt-icon' } }),
  ],
  onUpdate: ({ editor }) => {
    const html = editor.isEmpty ? '' : editor.getHTML()
    emit('update:modelValue', html)
  },
})

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value || source.value) return
    if (value !== (editor.value.isEmpty ? '' : editor.value.getHTML())) {
      editor.value.commands.setContent(value || '', false)
    }
  },
)
watch(
  () => props.disabled,
  (d) => editor.value?.setEditable(!d),
)

const tools = computed(() => [
  {
    key: 'bold',
    icon: Bold,
    title: t.value.bold,
    run: () => editor.value?.chain().focus().toggleBold().run(),
    active: () => editor.value?.isActive('bold'),
  },
  {
    key: 'italic',
    icon: Italic,
    title: t.value.italic,
    run: () => editor.value?.chain().focus().toggleItalic().run(),
    active: () => editor.value?.isActive('italic'),
  },
  {
    key: 'strike',
    icon: Strikethrough,
    title: t.value.strike,
    run: () => editor.value?.chain().focus().toggleStrike().run(),
    active: () => editor.value?.isActive('strike'),
  },
  {
    key: 'h2',
    icon: Heading2,
    title: t.value.heading,
    run: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
    active: () => editor.value?.isActive('heading', { level: 2 }),
  },
  {
    key: 'ul',
    icon: List,
    title: t.value.bulletList,
    run: () => editor.value?.chain().focus().toggleBulletList().run(),
    active: () => editor.value?.isActive('bulletList'),
  },
  {
    key: 'ol',
    icon: ListOrdered,
    title: t.value.orderedList,
    run: () => editor.value?.chain().focus().toggleOrderedList().run(),
    active: () => editor.value?.isActive('orderedList'),
  },
])

// --- Selector de iconos ---
const pickerOpen = ref(false)
const pickerRef = ref<HTMLElement>()

function insertIcon(icon: RichIcon) {
  editor.value?.chain().focus().setImage({ src: icon.url, alt: icon.name, title: icon.name }).run()
  pickerOpen.value = false
}
function onClickOutside(e: MouseEvent) {
  if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) pickerOpen.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  editor.value?.destroy()
})

// --- Toggle modo visual / HTML (source declarado arriba, antes del watch) ---
const sourceHtml = ref('')

function toggleSource() {
  if (!source.value) {
    // Entrar a modo HTML: cargar el HTML actual.
    sourceHtml.value = editor.value?.isEmpty ? '' : (editor.value?.getHTML() ?? '')
  } else {
    // Volver a visual: aplicar el HTML editado al editor.
    editor.value?.commands.setContent(sourceHtml.value || '', false)
  }
  source.value = !source.value
  pickerOpen.value = false
}

function onSourceInput(value: string) {
  sourceHtml.value = value
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="rich-text" :class="{ 'rich-text--disabled': disabled }">
    <div v-if="editor" class="rich-text__toolbar">
      <template v-if="!source">
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
        <button
          type="button"
          class="rich-text__tool"
          :title="t.undo"
          :disabled="disabled"
          @click="editor?.chain().focus().undo().run()"
        >
          <Undo2 :size="16" />
        </button>
        <button
          type="button"
          class="rich-text__tool"
          :title="t.redo"
          :disabled="disabled"
          @click="editor?.chain().focus().redo().run()"
        >
          <Redo2 :size="16" />
        </button>

        <!-- Selector de iconos del juego -->
        <template v-if="icons.length">
          <span class="rich-text__sep" />
          <div ref="pickerRef" class="rich-text__picker">
            <button
              type="button"
              class="rich-text__tool"
              :class="{ 'rich-text__tool--active': pickerOpen }"
              :title="t.insertIcon"
              :disabled="disabled"
              @click="pickerOpen = !pickerOpen"
            >
              <Smile :size="16" />
            </button>
            <div v-if="pickerOpen" class="rich-text__icons">
              <button
                v-for="icon in icons"
                :key="icon.url"
                type="button"
                class="rich-text__icon"
                :title="icon.name"
                @click="insertIcon(icon)"
              >
                <img :src="icon.url" :alt="icon.name" />
              </button>
            </div>
          </div>
        </template>
      </template>

      <!-- Toggle visual / HTML (siempre a la derecha) -->
      <button
        type="button"
        class="rich-text__tool rich-text__tool--source"
        :class="{ 'rich-text__tool--active': source }"
        :title="t.editHtml"
        :disabled="disabled"
        @click="toggleSource"
      >
        <Code :size="16" />
      </button>
    </div>

    <textarea
      v-if="source"
      class="rich-text__source"
      :value="sourceHtml"
      :disabled="disabled"
      spellcheck="false"
      @input="onSourceInput(($event.target as HTMLTextAreaElement).value)"
    />
    <EditorContent v-show="!source" class="rich-text__content" :editor="editor" />
  </div>
</template>
