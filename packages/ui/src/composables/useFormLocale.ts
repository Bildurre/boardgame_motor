import {
  computed,
  inject,
  onScopeDispose,
  provide,
  reactive,
  ref,
  watch,
  type ComputedRef,
  type InjectionKey,
  type Ref,
} from 'vue'

/**
 * Locale global de un FORMULARIO con campos traducibles.
 *
 * Cada campo traducible (TranslatableInput/TranslatableImage) conserva sus
 * tabs de locale propias, pero además se SUSCRIBE (inject) al contexto que
 * provee su formulario contenedor (EditModal lo hace solo): un selector
 * compacto en la cabecera cambia el tab activo de TODOS los campos a la vez,
 * y tocar el tab de un campo individual sigue afectando solo a ese campo.
 *
 * Los juegos no tocan nada: los campos del motor se registran/suscriben
 * solos, y el selector solo se pinta si el formulario contiene alguno.
 */

export interface FormLocale {
  code: string
  name: string
}

interface FormLocaleBroadcast {
  code: string
  /** Contador: re-emite aunque se repita el mismo código. */
  tick: number
}

export interface FormLocaleContext {
  /** Unión (sin duplicados) de los locales de los campos registrados. */
  locales: ComputedRef<FormLocale[]>
  /** ¿Hay campos traducibles suscritos? (el selector solo se pinta si sí). */
  hasFields: ComputedRef<boolean>
  /** Última difusión global (la observan los campos). */
  broadcast: Ref<FormLocaleBroadcast | null>
  /** Cambia el locale activo de TODOS los campos suscritos. */
  setAll: (code: string) => void
  /** Alta de un campo; devuelve la baja (la llama useFormLocaleField). */
  register: (locales: ComputedRef<FormLocale[]> | Ref<FormLocale[]>) => () => void
}

export const FormLocaleKey: InjectionKey<FormLocaleContext> = Symbol('edc-form-locale')

/** Lo llama el contenedor del formulario (EditModal ya lo hace). */
export function provideFormLocale(): FormLocaleContext {
  // Mapa id => locales del campo (reactive para que los computed reaccionen).
  const fields = reactive(new Map<number, ComputedRef<FormLocale[]> | Ref<FormLocale[]>>())
  let nextId = 0

  const locales = computed<FormLocale[]>(() => {
    const seen = new Map<string, FormLocale>()
    for (const fieldLocales of fields.values()) {
      for (const locale of fieldLocales.value) {
        if (!seen.has(locale.code)) seen.set(locale.code, locale)
      }
    }
    return [...seen.values()]
  })

  const hasFields = computed(() => fields.size > 0)
  const broadcast = ref<FormLocaleBroadcast | null>(null)

  function setAll(code: string) {
    broadcast.value = { code, tick: (broadcast.value?.tick ?? 0) + 1 }
  }

  function register(fieldLocales: ComputedRef<FormLocale[]> | Ref<FormLocale[]>) {
    const id = nextId++
    fields.set(id, fieldLocales)
    return () => {
      fields.delete(id)
    }
  }

  const context: FormLocaleContext = { locales, hasFields, broadcast, setAll, register }
  provide(FormLocaleKey, context)
  return context
}

/**
 * Lo llaman los campos traducibles en su setup: si hay proveedor por encima,
 * se registran (alimentan el selector) y aplican cada difusión global que
 * incluya alguno de sus locales. Sin proveedor, no hace nada.
 */
export function useFormLocaleField(
  locales: ComputedRef<FormLocale[]> | Ref<FormLocale[]>,
  apply: (code: string) => void,
): void {
  const context = inject(FormLocaleKey, null)
  if (!context) return

  onScopeDispose(context.register(locales))

  watch(context.broadcast, (b) => {
    if (b && locales.value.some((locale) => locale.code === b.code)) apply(b.code)
  })
}
