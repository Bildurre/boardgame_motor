<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus, SquarePen, Trash2 } from '@lucide/vue'
import { BaseButton, useConfirm, useToast } from '@bgm/ui'
import { FilterBar, useRightSidebar } from '@bgm/admin-kit'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import UserFormModal, { type UserRow } from '@/components/users/UserFormModal.vue'

// Gestión de usuarios (doc 05), lo típico y básico: listar con búsqueda,
// crear (con rol), editar y borrar. Patrón kontuan: la fila entera
// selecciona y el panel derecho trae las acciones + info.
const { t, te } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()
const auth = useAuthStore()

const sidebar = useRightSidebar()
sidebar.useRegister(t('users.panelTitle'))

const users = ref<UserRow[]>([])
const loading = ref(true)
const search = ref('')
const formOpen = ref(false)
const editing = ref<UserRow | null>(null)
const selectedId = ref<number | null>(null)

const selected = computed(() => users.value.find((u) => u.id === selectedId.value) ?? null)
const isSelf = computed(() => selected.value?.id === auth.user?.id)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/users', { params: { search: search.value } })
    users.value = data.data
  } catch {
    toast.danger(t('common.errors.load'))
  } finally {
    loading.value = false
  }
}

let timer: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(load, 250)
})
onBeforeUnmount(() => {
  if (timer) clearTimeout(timer)
})

/** Toda la fila selecciona, salvo sus controles interiores. */
function select(user: UserRow, event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (target?.closest('button, a, input, label')) return
  selectedId.value = user.id
  sidebar.reveal()
}

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(user: UserRow) {
  editing.value = user
  formOpen.value = true
}

async function remove(user: UserRow) {
  const ok = await confirm({
    message: t('users.confirmDelete', { name: user.name }),
    confirmLabel: t('common.actions.delete'),
    cancelLabel: t('common.cancel'),
    variant: 'danger',
  })
  if (!ok) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    if (selectedId.value === user.id) selectedId.value = null
    toast.success(t('users.toast.deleted'))
    await load()
  } catch {
    toast.danger(t('common.errors.action'))
  }
}

function roleLabel(user: UserRow): string {
  return (
    (user.roles ?? [])
      .map((role) => (te(`users.roles.${role}`) ? t(`users.roles.${role}`) : role))
      .join(', ') || '—'
  )
}

onMounted(load)
</script>

<template>
  <div class="users-view">
    <div class="list-view__top">
      <BaseButton @click="openCreate">
        <template #icon><Plus :size="16" /></template>
        {{ t('users.new') }}
      </BaseButton>
    </div>

    <FilterBar v-model="search" :placeholder="t('common.search')" />

    <p v-if="!loading && !users.length" class="users-view__empty">{{ t('common.empty') }}</p>

    <div class="pages-view__list">
      <article
        v-for="user in users"
        :key="user.id"
        class="pages-view__item"
        :class="{ 'is-active': selectedId === user.id }"
        @click="(e) => select(user, e)"
      >
        <strong class="users-view__name">{{ user.name }}</strong>
        <span class="users-view__email">{{ user.email }}</span>
        <span class="pages-view__chips">
          <span class="locale-chip" :class="{ 'is-ok': user.roles.includes('admin') }">
            {{ roleLabel(user) }}
          </span>
          <span v-if="!user.email_verified" class="locale-chip is-missing">
            {{ t('users.unverified') }}
          </span>
        </span>
      </article>
    </div>

    <UserFormModal v-model="formOpen" :user="editing" @saved="load" />

    <!-- Acciones del usuario seleccionado, en el panel derecho -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!selected" class="manager-panel__empty">{{ t('users.panelEmpty') }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ t('users.panelTitle') }}</p>
          <h3 class="manager-detail__title">{{ selected.name }}</h3>

          <div class="manager-detail__actions">
            <BaseButton variant="secondary" @click="openEdit(selected)">
              <template #icon><SquarePen :size="14" /></template>
              {{ t('common.actions.edit') }}
            </BaseButton>
            <BaseButton v-if="!isSelf" variant="danger" @click="remove(selected)">
              <template #icon><Trash2 :size="14" /></template>
              {{ t('common.actions.delete') }}
            </BaseButton>
          </div>

          <p class="manager-detail__meta">
            <strong>{{ t('users.fields.email') }}</strong> {{ selected.email }}
          </p>
          <p class="manager-detail__meta">
            <strong>{{ t('users.fields.role') }}</strong> {{ roleLabel(selected) }}
          </p>
          <p class="manager-detail__meta">
            <strong>{{ t('users.verified') }}</strong>
            {{ selected.email_verified ? '✓' : '✗' }}
          </p>
          <p v-if="isSelf" class="manager-panel__empty">{{ t('users.selfHint') }}</p>
        </template>
      </div>
    </Teleport>
  </div>
</template>
