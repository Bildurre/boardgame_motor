<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus, Save, X } from '@lucide/vue'
import {
  BaseButton,
  BaseSelect,
  IconButton,
  ImageUpload,
  PaletteColorPicker,
  TranslatableInput,
  useToast,
} from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Configuración de la web pública (doc 10): identidad (título, logo,
// favicon), apariencia (acento fijo o ALEATORIO estilo CDL, fuentes) y pie.
// La SPA pública la aplica al arrancar y re-sortea el acento al navegar.
const { t, te } = useI18n()
const toast = useToast()
const locales = useLocalesStore()

const loading = ref(true)
const saving = ref(false)

const title = ref<Record<string, string>>({})
const description = ref<Record<string, string>>({})
const logo = ref<string | null>(null)
const favicon = ref<string | null>(null)
const accentMode = ref<'fixed' | 'random'>('fixed')
const accentColor = ref('#6c5ce7')
const accentColors = ref<string[]>([])
const fontHeadings = ref('system')
const fontBody = ref('system')
const footerText = ref<Record<string, string>>({})
const fonts = ref<Record<string, string>>({})

const fontOptions = computed(() =>
  Object.keys(fonts.value).map((key) => ({
    value: key,
    label: te(`settings.fonts.${key}`) ? t(`settings.fonts.${key}`) : key,
  })),
)

/** Candidato del modo aleatorio elegido en el picker (se añade a la lista). */
const candidate = ref('#22c55e')

function addColor() {
  if (!accentColors.value.includes(candidate.value)) accentColors.value.push(candidate.value)
}

function removeColor(index: number) {
  accentColors.value.splice(index, 1)
}

async function upload(file: File): Promise<string | null> {
  try {
    const form = new FormData()
    form.append('image', file)
    const { data } = await api.post('/admin/content/uploads', form)
    return data.url
  } catch {
    toast.danger(t('common.errors.action'))
    return null
  }
}

async function uploadLogo(file: File | null) {
  logo.value = file ? ((await upload(file)) ?? logo.value) : null
}

async function uploadFavicon(file: File | null) {
  favicon.value = file ? ((await upload(file)) ?? favicon.value) : null
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/settings/site')
    const s = data.data
    title.value = s.title ?? {}
    description.value = s.description ?? {}
    logo.value = s.logo
    favicon.value = s.favicon
    accentMode.value = s.accent_mode
    accentColor.value = s.accent_color
    accentColors.value = s.accent_colors ?? []
    fontHeadings.value = s.font_headings
    fontBody.value = s.font_body
    footerText.value = s.footer_text ?? {}
    fonts.value = s.fonts ?? {}
  } catch {
    toast.danger(t('common.errors.load'))
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await api.put('/admin/settings/site', {
      title: title.value,
      description: description.value,
      logo: logo.value,
      favicon: favicon.value,
      accent_mode: accentMode.value,
      accent_color: accentColor.value,
      accent_colors: accentColors.value,
      font_headings: fontHeadings.value,
      font_body: fontBody.value,
      footer_text: footerText.value,
    })
    toast.success(t('settings.toast.saved'))
  } catch {
    toast.danger(t('settings.toast.saveError'))
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await locales.load()
  await load()
})
</script>

<template>
  <div v-if="!loading" class="settings-view">
    <div class="list-view__top">
      <BaseButton :disabled="saving" @click="save">
        <template #icon><Save :size="16" /></template>
        {{ t('common.save') }}
      </BaseButton>
    </div>

    <!-- Identidad -->
    <section class="settings-view__section">
      <h2>{{ t('settings.sections.identity') }}</h2>
      <TranslatableInput
        v-model="title"
        :locales="locales.locales"
        :label="t('settings.fields.title')"
      />
      <TranslatableInput
        v-model="description"
        :locales="locales.locales"
        :label="t('settings.fields.description')"
        type="textarea"
        :rows="2"
      />
      <div class="settings-view__uploads">
        <ImageUpload
          :model-value="null"
          :current-url="logo"
          :label="t('settings.fields.logo')"
          accept=".svg,.png,.webp"
          :drag-text="t('common.imageDrag')"
          :hint-text="t('settings.fields.logoHint')"
          @update:model-value="uploadLogo"
          @remove="logo = null"
        />
        <ImageUpload
          :model-value="null"
          :current-url="favicon"
          :label="t('settings.fields.favicon')"
          accept=".png,.svg"
          :drag-text="t('common.imageDrag')"
          :hint-text="t('settings.fields.faviconHint')"
          @update:model-value="uploadFavicon"
          @remove="favicon = null"
        />
      </div>
    </section>

    <!-- Apariencia -->
    <section class="settings-view__section">
      <h2>{{ t('settings.sections.appearance') }}</h2>
      <BaseSelect
        v-model="accentMode"
        :label="t('settings.fields.accentMode')"
        :options="[
          { value: 'fixed', label: t('settings.accentModes.fixed') },
          { value: 'random', label: t('settings.accentModes.random') },
        ]"
      />

      <PaletteColorPicker
        v-if="accentMode === 'fixed'"
        v-model="accentColor"
        :label="t('settings.fields.accentColor')"
      />

      <template v-else>
        <p class="settings-view__hint">{{ t('settings.fields.accentColorsHint') }}</p>
        <ul v-if="accentColors.length" class="settings-view__colors">
          <li v-for="(color, index) in accentColors" :key="color">
            <span class="settings-view__swatch" :style="{ background: color }" />
            <code>{{ color }}</code>
            <IconButton
              variant="danger"
              :title="t('common.actions.delete')"
              @click="removeColor(index)"
              ><X :size="14"
            /></IconButton>
          </li>
        </ul>
        <div class="settings-view__add-color">
          <PaletteColorPicker v-model="candidate" :label="t('settings.fields.accentColors')" />
          <BaseButton variant="secondary" @click="addColor">
            <template #icon><Plus :size="14" /></template>
            {{ t('settings.addColor') }}
          </BaseButton>
        </div>
      </template>

      <div class="settings-view__fonts">
        <div>
          <BaseSelect
            v-model="fontHeadings"
            :label="t('settings.fields.fontHeadings')"
            :options="fontOptions"
          />
          <p class="settings-view__font-preview" :style="{ fontFamily: fonts[fontHeadings] }">
            {{ t('settings.fontPreviewHeading') }}
          </p>
        </div>
        <div>
          <BaseSelect
            v-model="fontBody"
            :label="t('settings.fields.fontBody')"
            :options="fontOptions"
          />
          <p class="settings-view__font-preview" :style="{ fontFamily: fonts[fontBody] }">
            {{ t('settings.fontPreviewBody') }}
          </p>
        </div>
      </div>
    </section>

    <!-- Pie -->
    <section class="settings-view__section">
      <h2>{{ t('settings.sections.footer') }}</h2>
      <TranslatableInput
        v-model="footerText"
        :locales="locales.locales"
        :label="t('settings.fields.footerText')"
        type="textarea"
        :rows="2"
      />
    </section>
  </div>
</template>
