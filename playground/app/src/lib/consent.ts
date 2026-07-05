import { ref } from 'vue'

// Consentimiento de almacenamiento local (no usamos cookies): la web solo
// guarda en localStorage preferencias funcionales (idioma, tema), la sesión
// y el token de la colección de PDF. Nada se PERSISTE hasta que el visitante
// acepta el banner; si rechaza, todo funciona igual pero solo en memoria.
const CONSENT_KEY = 'bgm_consent'

export type Consent = 'accepted' | 'rejected' | null

const state = ref<Consent>((localStorage.getItem(CONSENT_KEY) as Consent) ?? null)

/** Claves que gestionamos: para limpiarlas si se rechaza. */
const MANAGED_KEYS = ['bgm_app_token', 'bgm_collection_token', 'bgm_app_locale', 'theme']

export function consentState() {
  return state
}

export function canPersist(): boolean {
  return state.value === 'accepted'
}

/** Guarda respetando el consentimiento (no-op si no está aceptado). */
export function persist(key: string, value: string) {
  if (canPersist()) localStorage.setItem(key, value)
}

export function acceptConsent(onAccept?: () => void) {
  state.value = 'accepted'
  localStorage.setItem(CONSENT_KEY, 'accepted')
  onAccept?.()
}

export function rejectConsent() {
  state.value = 'rejected'
  localStorage.setItem(CONSENT_KEY, 'rejected') // recordar el "no" también es funcional
  for (const key of MANAGED_KEYS) localStorage.removeItem(key)
}
