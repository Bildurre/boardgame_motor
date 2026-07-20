<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { onBeforeRouteLeave } from 'vue-router'
import { MenuManager, type MenuManagerLabels } from '@edc-motor/admin-kit'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Configurador del menú de la web pública (doc 10 ampliado, rediseño sin
// grupos): mezcla páginas del CRM y las rutas propias del juego
// (motor.menu.routes, config del api). `routeLabels` es la etiqueta visible
// de cada route_key: añade aquí la tuya según vayas registrando rutas en tu
// AppHeader.vue (guia-como-montar-una-web §10). Sin ninguna ruta propia
// (plantilla vacía), el menú solo trae páginas. La jerarquía es la de las
// páginas: "si quieres un grupo, haz una página". El gestor trabaja en
// local y avisa si hay cambios sin guardar (badge propio + este guard de
// navegación, patrón confirm simple — no hay otro precedente en el admin).
const routeLabels: Record<string, string> = {
  // downloads: 'Descargas',
}

const { t } = useI18n()
const locales = useLocalesStore()

const labels = computed<Partial<MenuManagerLabels>>(() => ({
  empty: t('menu.empty'),
  hidden: t('menu.hidden'),
  draft: t('pages.draft'),
  moveUp: t('pages.moveUp'),
  moveDown: t('pages.moveDown'),
  visible: t('menu.visible'),
  save: t('common.save'),
  discard: t('menu.discard'),
  unsaved: t('menu.unsaved'),
  saved: t('menu.toast.saved'),
  error: t('common.errors.action'),
}))

const manager = ref<InstanceType<typeof MenuManager> | null>(null)

onBeforeRouteLeave(() => {
  if (!manager.value?.isDirty) return true

  return window.confirm(t('menu.confirmLeave'))
})

// Cerrar/recargar la pestaña con cambios sin guardar: aviso nativo (no hay
// otro patrón de "salir con cambios" en el admin del que tirar).
function onBeforeUnload(event: BeforeUnloadEvent) {
  if (!manager.value?.isDirty) return
  event.preventDefault()
}
onMounted(() => window.addEventListener('beforeunload', onBeforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload))
</script>

<template>
  <div class="menu-view">
    <h1 class="single__title">{{ t('menu.title') }}</h1>
    <p class="menu-view__hint">{{ t('menu.hint') }}</p>

    <MenuManager
      ref="manager"
      :api="api"
      :route-labels="routeLabels"
      :display-locale="locales.current"
      :labels="labels"
    />
  </div>
</template>
