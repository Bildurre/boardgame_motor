<script setup lang="ts">
import { computed, ref, watch, onBeforeUnmount, onMounted, nextTick } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import UnderlineExtension from '@tiptap/extension-underline'
import LinkExtension from '@tiptap/extension-link'
import TableExtension from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableHeader from '@tiptap/extension-table-header'
import TableCell from '@tiptap/extension-table-cell'
import {
  Bold,
  Italic,
  Strikethrough,
  Underline as UnderlineIcon,
  Heading2,
  Heading3,
  Heading4,
  Heading5,
  List,
  ListOrdered,
  Quote,
  Indent,
  Outdent,
  Link as LinkIcon,
  Unlink,
  Table as TableIcon,
  Rows3,
  TableRowsSplit,
  Columns3,
  TableColumnsSplit,
  TableProperties,
  Trash2,
  Undo2,
  Redo2,
  Smile,
  Code,
} from '@lucide/vue'

// Editor de texto enriquecido (WYSIWYG) basado en TipTap (DC-09).
// v-model = HTML. Agnóstico de i18n. Si se pasan `icons`, muestra un selector
// para insertar iconos del juego (como en CDL): se insertan como <img.rt-icon>.
// Incluye un toggle para editar en modo visual o directamente el HTML (para
// pegar HTML ya hecho, p. ej. una tabla) y soporte de tablas completo
// (insertar, filas/columnas, cabecera, borrar). El HTML que sale de aquí NO
// se sanea en cliente: pasa por `HtmlSanitizer` (core) al guardar.
export interface RichIcon {
  name: string
  url: string
}

// Textos de la barra de herramientas, sobreescribibles por la app (DC-29).
export interface RichTextLabels {
  bold: string
  italic: string
  strike: string
  underline: string
  heading2: string
  heading3: string
  heading4: string
  heading5: string
  bulletList: string
  orderedList: string
  blockquote: string
  indent: string
  outdent: string
  link: string
  unlink: string
  linkUrl: string
  linkApply: string
  insertTable: string
  addRow: string
  removeRow: string
  addColumn: string
  removeColumn: string
  toggleHeaderRow: string
  deleteTable: string
  undo: string
  redo: string
  insertIcon: string
  editHtml: string
}

const defaultLabels: RichTextLabels = {
  bold: 'Negrita',
  italic: 'Cursiva',
  strike: 'Tachado',
  underline: 'Subrayado',
  heading2: 'Título 2',
  heading3: 'Título 3',
  heading4: 'Título 4',
  heading5: 'Título 5',
  bulletList: 'Lista',
  orderedList: 'Lista numerada',
  blockquote: 'Cita',
  indent: 'Sangrar',
  outdent: 'Quitar sangría',
  link: 'Enlace',
  unlink: 'Quitar enlace',
  linkUrl: 'URL del enlace',
  linkApply: 'Aplicar',
  insertTable: 'Insertar tabla',
  addRow: 'Añadir fila',
  removeRow: 'Quitar fila',
  addColumn: 'Añadir columna',
  removeColumn: 'Quitar columna',
  toggleHeaderRow: 'Alternar cabecera',
  deleteTable: 'Borrar tabla',
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
    StarterKit.configure({ heading: { levels: [2, 3, 4, 5] } }),
    UnderlineExtension,
    // Enlaces: no se navega al hacer click dentro del editor (openOnClick),
    // y TODOS llevan target="_blank" + rel="noopener noreferrer" por
    // defecto (también los mailto: — ahí son inocuos, pero así no hace
    // falta distinguir el esquema al aplicarlos).
    LinkExtension.configure({
      openOnClick: false,
      autolink: false,
      HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer' },
    }),
    // Tablas: sin resize de columnas (el asa de arrastre metía ruido visual
    // y no hace falta para el caso de uso del editor).
    TableExtension.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
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

/** Alterna un nivel de título (o vuelve a párrafo si ya lo era). */
function toggleHeading(level: 2 | 3 | 4 | 5) {
  editor.value?.chain().focus().toggleHeading({ level }).run()
}

/** Sangrar: solo tiene sentido dentro de un item de lista (anida la sublista). */
function indent() {
  editor.value?.chain().focus().sinkListItem('listItem').run()
}
function canIndent(): boolean {
  return !!editor.value?.can().sinkListItem('listItem')
}

/** Quitar sangría: en una lista, la saca un nivel; si no, saca de la cita. */
function outdent() {
  const e = editor.value
  if (!e) return
  if (e.isActive('listItem')) e.chain().focus().liftListItem('listItem').run()
  else if (e.isActive('blockquote')) e.chain().focus().lift('blockquote').run()
}
function canOutdent(): boolean {
  const e = editor.value
  if (!e) return false
  if (e.isActive('listItem')) return e.can().liftListItem('listItem')
  if (e.isActive('blockquote')) return e.can().lift('blockquote')
  return false
}

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
    key: 'underline',
    icon: UnderlineIcon,
    title: t.value.underline,
    run: () => editor.value?.chain().focus().toggleUnderline().run(),
    active: () => editor.value?.isActive('underline'),
  },
  {
    key: 'h2',
    icon: Heading2,
    title: t.value.heading2,
    run: () => toggleHeading(2),
    active: () => editor.value?.isActive('heading', { level: 2 }),
  },
  {
    key: 'h3',
    icon: Heading3,
    title: t.value.heading3,
    run: () => toggleHeading(3),
    active: () => editor.value?.isActive('heading', { level: 3 }),
  },
  {
    key: 'h4',
    icon: Heading4,
    title: t.value.heading4,
    run: () => toggleHeading(4),
    active: () => editor.value?.isActive('heading', { level: 4 }),
  },
  {
    key: 'h5',
    icon: Heading5,
    title: t.value.heading5,
    run: () => toggleHeading(5),
    active: () => editor.value?.isActive('heading', { level: 5 }),
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
  {
    key: 'blockquote',
    icon: Quote,
    title: t.value.blockquote,
    run: () => editor.value?.chain().focus().toggleBlockquote().run(),
    active: () => editor.value?.isActive('blockquote'),
  },
])

// Controles de tabla contextuales: solo con el cursor DENTRO de una tabla
// (salvo "insertar", que se deshabilita si ya lo está — no hay tablas
// anidadas).
const tableTools = computed(() => [
  {
    key: 'addRow',
    icon: Rows3,
    title: t.value.addRow,
    run: () => editor.value?.chain().focus().addRowAfter().run(),
  },
  {
    key: 'removeRow',
    icon: TableRowsSplit,
    title: t.value.removeRow,
    run: () => editor.value?.chain().focus().deleteRow().run(),
  },
  {
    key: 'addColumn',
    icon: Columns3,
    title: t.value.addColumn,
    run: () => editor.value?.chain().focus().addColumnAfter().run(),
  },
  {
    key: 'removeColumn',
    icon: TableColumnsSplit,
    title: t.value.removeColumn,
    run: () => editor.value?.chain().focus().deleteColumn().run(),
  },
  {
    key: 'toggleHeaderRow',
    icon: TableProperties,
    title: t.value.toggleHeaderRow,
    run: () => editor.value?.chain().focus().toggleHeaderRow().run(),
  },
  {
    key: 'deleteTable',
    icon: Trash2,
    title: t.value.deleteTable,
    run: () => editor.value?.chain().focus().deleteTable().run(),
  },
])

function insertTable() {
  editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
}

// --- Selector de iconos ---
const pickerOpen = ref(false)
const pickerRef = ref<HTMLElement>()

function insertIcon(icon: RichIcon) {
  editor.value?.chain().focus().setImage({ src: icon.url, alt: icon.name, title: icon.name }).run()
  pickerOpen.value = false
}

// --- Enlaces: mini-popover con la URL (mismo patrón que el selector de
// iconos: botón + panel flotante, cierra con Escape o click fuera) ---
const linkPopoverOpen = ref(false)
const linkPickerRef = ref<HTMLElement>()
const linkInputRef = ref<HTMLInputElement>()
const linkUrlInput = ref('')

function toggleLinkPopover() {
  if (linkPopoverOpen.value) {
    linkPopoverOpen.value = false
    return
  }
  linkUrlInput.value = (editor.value?.getAttributes('link').href as string) ?? ''
  pickerOpen.value = false
  linkPopoverOpen.value = true
  void nextTick(() => linkInputRef.value?.focus())
}

function applyLink() {
  const url = linkUrlInput.value.trim()
  if (url) editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  else editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
  linkPopoverOpen.value = false
}

function removeLink() {
  editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
}

function onClickOutside(e: MouseEvent) {
  const target = e.target as Node
  if (pickerRef.value && !pickerRef.value.contains(target)) pickerOpen.value = false
  if (linkPickerRef.value && !linkPickerRef.value.contains(target)) linkPopoverOpen.value = false
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
    // Volver a visual: aplicar el HTML editado al editor (pasará por el
    // saneador del servidor al guardar; aquí no hace falta sanear).
    editor.value?.commands.setContent(sourceHtml.value || '', false)
  }
  source.value = !source.value
  pickerOpen.value = false
  linkPopoverOpen.value = false
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

        <button
          type="button"
          class="rich-text__tool"
          :title="t.indent"
          :disabled="disabled || !canIndent()"
          @click="indent"
        >
          <Indent :size="16" />
        </button>
        <button
          type="button"
          class="rich-text__tool"
          :title="t.outdent"
          :disabled="disabled || !canOutdent()"
          @click="outdent"
        >
          <Outdent :size="16" />
        </button>

        <span class="rich-text__sep" />

        <!-- Enlace: botón + mini-popover con la URL -->
        <div ref="linkPickerRef" class="rich-text__picker">
          <button
            type="button"
            class="rich-text__tool"
            :class="{ 'rich-text__tool--active': editor?.isActive('link') || linkPopoverOpen }"
            :title="t.link"
            :disabled="disabled"
            @click="toggleLinkPopover"
          >
            <LinkIcon :size="16" />
          </button>
          <div v-if="linkPopoverOpen" class="rich-text__link-popover">
            <input
              ref="linkInputRef"
              v-model="linkUrlInput"
              type="text"
              class="rich-text__link-input"
              :placeholder="t.linkUrl"
              @keydown.enter.prevent="applyLink"
              @keydown.escape.prevent="linkPopoverOpen = false"
            />
            <button type="button" class="rich-text__tool" :title="t.linkApply" @click="applyLink">
              <LinkIcon :size="14" />
            </button>
          </div>
        </div>
        <button
          v-if="editor?.isActive('link')"
          type="button"
          class="rich-text__tool"
          :title="t.unlink"
          :disabled="disabled"
          @click="removeLink"
        >
          <Unlink :size="16" />
        </button>

        <span class="rich-text__sep" />

        <!-- Tabla: insertar siempre visible (deshabilitado dentro de otra
             tabla); el resto de controles solo con el cursor DENTRO -->
        <button
          type="button"
          class="rich-text__tool"
          :title="t.insertTable"
          :disabled="disabled || editor?.isActive('table')"
          @click="insertTable"
        >
          <TableIcon :size="16" />
        </button>
        <template v-if="editor?.isActive('table')">
          <button
            v-for="tool in tableTools"
            :key="tool.key"
            type="button"
            class="rich-text__tool"
            :title="tool.title"
            :disabled="disabled"
            @click="tool.run()"
          >
            <component :is="tool.icon" :size="16" />
          </button>
        </template>

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
