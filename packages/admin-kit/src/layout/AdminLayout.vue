<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, type RouteLocationRaw } from 'vue-router'
import { ChevronLeft, ChevronRight, Menu, X } from '@lucide/vue'
import {
  MotorBadge,
  ThemeSelector,
  LocaleSelector,
  AppBreadcrumbs,
  type Crumb,
} from '@edc-motor/ui'
import RightSidebar from './RightSidebar.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Layout del panel — portado del AppLayout de kontuan (DC-28): sidebar
// colapsable en escritorio, drawer a pantalla completa en móvil, preferencias
// (tema + idioma) en la cabecera del sidebar y migas de pan en el contenido.
interface Locale {
  code: string
  name: string
}

const props = withDefaults(
  defineProps<{
    title?: string
    brand?: string
    locales?: Locale[]
    /** Locale de contenido (v-model:locale). */
    locale?: string
    /** Ruta a la que lleva el logo (la app puede no llamarla 'dashboard'). */
    homeRoute?: RouteLocationRaw
    /** Miga "home"; pasa null para ocultarla. */
    homeCrumb?: Crumb | null
    /** Migas ya traducidas (i18n desde la app). */
    breadcrumbs?: Crumb[] | null
  }>(),
  {
    title: '',
    brand: 'EdC Admin',
    locales: () => [],
    locale: '',
    homeRoute: () => ({ name: 'dashboard' }),
    homeCrumb: () => ({ label: 'Inicio', to: { name: 'dashboard' } }),
    breadcrumbs: null,
  },
)

const emit = defineEmits<{ 'update:locale': [code: string] }>()

// Umbral móvil (igual que kontuan: MOBILE_BREAKPOINT = bp-md = 768).
const MOBILE_BREAKPOINT = 768

const route = useRoute()
const sidebarCollapsed = ref(localStorage.getItem('edc_admin_collapsed') === '1')
const sidebarMobileOpen = ref(false)
const isMobile = ref(false)

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
  if (!isMobile.value) sidebarMobileOpen.value = false
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})
onUnmounted(() => window.removeEventListener('resize', checkMobile))

watch(
  () => route.fullPath,
  () => {
    if (isMobile.value) sidebarMobileOpen.value = false
  },
)

// Al abrir el drawer IZQUIERDO se CIERRA el derecho (no solo se oculta):
// así, al cerrar el izquierdo, el derecho sigue cerrado (queda su asa).
const rightSidebar = useRightSidebar()
watch(sidebarMobileOpen, (open) => {
  if (open) rightSidebar.closeMobile()
})

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
  localStorage.setItem('edc_admin_collapsed', sidebarCollapsed.value ? '1' : '0')
}
</script>

<template>
  <div
    class="app-layout"
    :class="{
      'sidebar-collapsed': sidebarCollapsed,
      'is-mobile': isMobile,
      'left-drawer-open': isMobile && sidebarMobileOpen,
    }"
  >
    <div
      v-if="isMobile && sidebarMobileOpen"
      class="sidebar-overlay"
      @click="sidebarMobileOpen = false"
    />

    <aside class="sidebar" :class="{ 'sidebar--mobile-open': sidebarMobileOpen }">
      <div
        class="sidebar-header"
        :class="{ 'sidebar-header--collapsed': sidebarCollapsed && !isMobile }"
      >
        <RouterLink
          v-if="!sidebarCollapsed || isMobile"
          :to="props.homeRoute"
          class="sidebar-logo-link"
        >
          <MotorBadge :label="brand" />
        </RouterLink>
        <button class="sidebar-toggle" type="button" @click="toggleSidebar">
          <ChevronRight v-if="sidebarCollapsed" :size="16" />
          <ChevronLeft v-else :size="16" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <div v-if="!sidebarCollapsed || isMobile" class="sidebar-preferences">
          <ThemeSelector />
          <LocaleSelector
            v-if="locales.length"
            :model-value="locale"
            :locales="locales"
            @update:model-value="(c: string) => emit('update:locale', c)"
          />
        </div>
        <hr v-if="!sidebarCollapsed || isMobile" class="sidebar-divider" />

        <div class="sidebar-items" @click="isMobile && (sidebarMobileOpen = false)">
          <slot name="nav" />
        </div>
      </nav>

      <div
        class="sidebar-user"
        :class="{ 'sidebar-user--collapsed': sidebarCollapsed && !isMobile }"
      >
        <slot name="user" :collapsed="sidebarCollapsed && !isMobile" />
      </div>
    </aside>

    <div class="main-wrapper">
      <header class="navbar">
        <div class="navbar-left">
          <button
            v-if="isMobile"
            class="hamburger"
            type="button"
            @click="sidebarMobileOpen = !sidebarMobileOpen"
          >
            <X v-if="sidebarMobileOpen" :size="20" />
            <Menu v-else :size="20" />
          </button>
          <!-- En estrecho el sidebar (y su marca) están ocultos: la marca
               pasa a la barra superior -->
          <RouterLink v-if="isMobile" :to="props.homeRoute" class="navbar-brand">
            <MotorBadge :label="brand" />
          </RouterLink>
          <span class="navbar-title">{{ title }}</span>
        </div>
        <div class="navbar-right">
          <slot name="actions" />
        </div>
      </header>

      <div class="main-body">
        <main class="main-content">
          <AppBreadcrumbs :home="homeCrumb" :crumbs="breadcrumbs" />
          <slot />
        </main>
        <!-- Panel derecho contextual: cada vista lo activa con useRightSidebar() -->
        <RightSidebar />
      </div>
    </div>
  </div>
</template>
