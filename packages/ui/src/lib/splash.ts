import type { AxiosInstance } from 'axios'

// Splash de arranque. La SPA sirve un cascarón instantáneo y pide TODO lo
// real (settings, menús, sesión, contenido de la vista) a la API después de
// montar: hasta que responde, cada componente pinta su estado por defecto.
// En local (API a ~1 ms) ese fotograma provisional no llega a verse; en
// producción sí. El remedio: el index.html estático trae un velo a pantalla
// completa (#edc-splash, HTML autosuficiente pintado desde el fotograma
// cero, antes incluso de descargar el bundle) y este módulo lo retira
// cuando el arranque termina de verdad.
//
// ¿Y cuándo termina «de verdad»? En vez de instrumentar cada vista, se
// observa el cliente axios: se cuentan las peticiones en vuelo y el splash
// cae en el PRIMER REPOSO DE RED (cero peticiones durante `quietMs`). Eso
// cubre solo la cascada inicial —locales → settings → datos de la primera
// vista, encadenados por microtareas que siempre ganan al temporizador— y
// no exige tocar las vistas. Un tope (`maxWaitMs`) garantiza que una API
// caída nunca deja el velo puesto.
//
// Uso (main.ts, ANTES de app.mount() — si se llama después, las peticiones
// del onMounted ya habrían salido sin contar):
//
//   watchSplash({ api })
//   app.mount('#app')

const SPLASH_ID = 'edc-splash'

export interface WatchSplashOptions {
  /** Cliente(s) axios cuyas peticiones marcan el arranque (createApi). */
  api?: AxiosInstance | AxiosInstance[]
  /** Reposo de red que da el arranque por terminado (ms). */
  quietMs?: number
  /** Tope duro: el splash cae aunque la red siga ocupada o caída (ms). */
  maxWaitMs?: number
}

/** Retira el splash con su fundido (idempotente; sin splash, no hace nada). */
export function dismissSplash(): void {
  const el = document.getElementById(SPLASH_ID)
  if (!el || el.classList.contains('edc-splash--done')) return
  el.classList.add('edc-splash--done')
  const remove = () => el.remove()
  el.addEventListener('transitionend', remove, { once: true })
  // Por si no hay transición (prefers-reduced-motion, CSS recortado).
  setTimeout(remove, 600)
}

/** Observa el arranque y retira el splash en el primer reposo de red. */
export function watchSplash(options: WatchSplashOptions = {}): void {
  if (typeof document === 'undefined') return
  if (!document.getElementById(SPLASH_ID)) return

  const quietMs = options.quietMs ?? 200
  const maxWaitMs = options.maxWaitMs ?? 8000
  const apis = Array.isArray(options.api) ? options.api : options.api ? [options.api] : []

  let inflight = 0
  let quietTimer: ReturnType<typeof setTimeout> | undefined

  const done = () => {
    clearTimeout(quietTimer)
    // Doble rAF: el render definitivo llega a pintarse BAJO el velo antes
    // del fundido (sin esto el fundido podría destapar un frame a medias).
    requestAnimationFrame(() => requestAnimationFrame(dismissSplash))
  }
  const armQuiet = () => {
    clearTimeout(quietTimer)
    quietTimer = setTimeout(done, quietMs)
  }

  for (const api of apis) {
    api.interceptors.request.use((config) => {
      inflight++
      clearTimeout(quietTimer)
      return config
    })
    api.interceptors.response.use(
      (response) => {
        if (--inflight <= 0) armQuiet()
        return response
      },
      (error) => {
        if (--inflight <= 0) armQuiet()
        return Promise.reject(error)
      },
    )
  }

  // Armado inicial: si el arranque no llega a pedir nada (o no se pasó
  // `api`), el splash cae solo tras el primer reposo.
  armQuiet()
  setTimeout(dismissSplash, maxWaitMs)
}
