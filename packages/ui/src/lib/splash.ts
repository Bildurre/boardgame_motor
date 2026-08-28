import type { AxiosInstance } from 'axios'

// Splash de arranque Y velo de navegación. La SPA sirve un cascarón
// instantáneo y pide TODO lo real (settings, menús, sesión, contenido de la
// vista) a la API después de montar: hasta que responde, cada componente
// pinta su estado por defecto. En local (API a ~1 ms) ese fotograma
// provisional no llega a verse; en producción sí. El remedio: el index.html
// estático trae un velo a pantalla completa (#edc-splash, HTML
// autosuficiente pintado desde el fotograma cero, antes incluso de
// descargar el bundle) y este módulo lo retira cuando el arranque termina
// de verdad — y lo REUTILIZA como estado de carga en las navegaciones SPA
// lentas (setupNavigationSplash): la identidad de carga es siempre el
// splash, nunca skeletons.
//
// ¿Y cuándo termina «de verdad»? En vez de instrumentar cada vista, se
// observa el cliente axios: se cuentan las peticiones en vuelo y el velo
// cae en el PRIMER REPOSO DE RED (cero peticiones durante `quietMs`). Eso
// cubre sola la cascada inicial —locales → settings → datos de la primera
// vista, encadenados por microtareas que siempre ganan al temporizador— y
// no exige tocar las vistas. Un tope (`maxWaitMs`) garantiza que una API
// caída nunca deja el velo puesto.
//
// Uso (main.ts, ANTES de app.mount() — si se llama después, las peticiones
// del onMounted ya habrían salido sin contar):
//
//   watchSplash({ api })
//   setupNavigationSplash(router) // opcional: velo también al navegar
//   app.mount('#app')
//
// El index.html puede definir window.__edcSplashRefresh (p. ej. para
// re-elegir el logo según el idioma guardado): se invoca en cada re-show.

const SPLASH_ID = 'edc-splash'
const DONE_CLASS = 'edc-splash--done'

// Peticiones DE FONDO: con `edcBackground: true` en el config de axios, la
// petición no cuenta para el velo — es relleno o refresco (catálogos con su
// propia presentación de carga, revalidaciones SWR, sondeos), no «la página
// aún no puede pintarse». Así el velo queda solo para las cargas de página.
declare module 'axios' {
  interface AxiosRequestConfig {
    edcBackground?: boolean
  }
}

export interface WatchSplashOptions {
  /** Cliente(s) axios cuyas peticiones marcan el arranque (createApi). */
  api?: AxiosInstance | AxiosInstance[]
  /** Reposo de red que da el arranque por terminado (ms). */
  quietMs?: number
  /** Tope duro: el splash cae aunque la red siga ocupada o caída (ms). */
  maxWaitMs?: number
}

/** Contrato mínimo del router (estructural: sin depender de vue-router). */
export interface SplashRouterLike {
  beforeEach(guard: () => void): unknown
  afterEach(hook: () => void): unknown
  onError?(handler: () => void): unknown
}

export interface NavigationSplashOptions {
  /** Espera antes de ENSEÑAR el velo: una navegación que resuelve antes no
   *  lo ve ni un frame (ms). */
  showDelayMs?: number
  /** Tope duro por navegación (ms). */
  maxWaitMs?: number
}

// ---- contador de red compartido (lo alimenta watchSplash) ---------------
let inflight = 0
let quietMs = 200
let quietTimer: ReturnType<typeof setTimeout> | undefined
// Qué hacer al llegar el reposo: el arranque y cada navegación lo reasignan.
let onQuiet: (() => void) | null = null

function armQuiet() {
  clearTimeout(quietTimer)
  quietTimer = setTimeout(() => onQuiet?.(), quietMs)
}

function splashEl(): HTMLElement | null {
  return document.getElementById(SPLASH_ID)
}

/** Retira el splash con su fundido (idempotente; sin splash, no hace nada).
 *  El elemento se OCULTA, no se elimina: las navegaciones lo reutilizan. */
export function dismissSplash(): void {
  const el = splashEl()
  if (!el || el.classList.contains(DONE_CLASS)) return
  el.classList.add(DONE_CLASS)
  // visibility al terminar el fundido: para el pulso del logo mientras el
  // velo no se ve (el guard evita apagar un re-show que haya interrumpido).
  const finish = () => {
    if (el.classList.contains(DONE_CLASS)) el.style.visibility = 'hidden'
  }
  el.addEventListener('transitionend', finish, { once: true })
  setTimeout(finish, 600)
}

/** Re-enseña el velo (fundido de entrada por la misma transición). */
function showSplash(): void {
  const el = splashEl()
  if (!el) return
  ;(window as unknown as { __edcSplashRefresh?: () => void }).__edcSplashRefresh?.()
  el.style.visibility = ''
  // reflow: sin él, quitar la clase en el mismo frame se salta la transición
  void el.offsetWidth
  el.classList.remove(DONE_CLASS)
}

/** Observa el arranque y retira el splash en el primer reposo de red. */
export function watchSplash(options: WatchSplashOptions = {}): void {
  if (typeof document === 'undefined') return
  if (!splashEl()) return

  quietMs = options.quietMs ?? 200
  const maxWaitMs = options.maxWaitMs ?? 8000
  const apis = Array.isArray(options.api) ? options.api : options.api ? [options.api] : []

  // Doble rAF: el render definitivo llega a pintarse BAJO el velo antes
  // del fundido (sin esto el fundido podría destapar un frame a medias).
  onQuiet = () => requestAnimationFrame(() => requestAnimationFrame(dismissSplash))

  for (const api of apis) {
    api.interceptors.request.use((config) => {
      if (!config.edcBackground) {
        inflight++
        clearTimeout(quietTimer)
      }
      return config
    })
    api.interceptors.response.use(
      (response) => {
        if (!response.config.edcBackground && --inflight <= 0) armQuiet()
        return response
      },
      (error: { config?: { edcBackground?: boolean } }) => {
        if (!error?.config?.edcBackground && --inflight <= 0) armQuiet()
        return Promise.reject(error)
      },
    )
  }

  // Armado inicial: si el arranque no llega a pedir nada (o no se pasó
  // `api`), el splash cae solo tras el primer reposo.
  armQuiet()
  setTimeout(dismissSplash, maxWaitMs)
}

/**
 * Velo de carga en las navegaciones SPA: si tras `showDelayMs` la
 * navegación sigue pendiente o hay peticiones en vuelo, el splash vuelve a
 * cubrir la ventana (tapando el «negro» de la vista sin datos) y cae en el
 * siguiente reposo de red. Una navegación instantánea no lo ve ni un frame.
 * Requiere watchSplash({ api }) antes (es quien alimenta el contador).
 */
export function setupNavigationSplash(
  router: SplashRouterLike,
  options: NavigationSplashOptions = {},
): void {
  if (typeof document === 'undefined') return
  if (!splashEl()) return

  // 250 ms: una API razonable responde antes y el velo ni aparece; por
  // debajo saltaba en CADA navegación de producción (feo y alarmante).
  const showDelayMs = options.showDelayMs ?? 250
  const maxWaitMs = options.maxWaitMs ?? 8000

  let navPending = false
  let showTimer: ReturnType<typeof setTimeout> | undefined
  let maxTimer: ReturnType<typeof setTimeout> | undefined

  const hide = () => {
    clearTimeout(showTimer)
    clearTimeout(maxTimer)
    requestAnimationFrame(() => requestAnimationFrame(dismissSplash))
  }

  router.beforeEach(() => {
    navPending = true
    onQuiet = hide
    clearTimeout(showTimer)
    showTimer = setTimeout(() => {
      if (navPending || inflight > 0) showSplash()
    }, showDelayMs)
    clearTimeout(maxTimer)
    maxTimer = setTimeout(hide, maxWaitMs)
  })

  router.afterEach(() => {
    navPending = false
    // Vista sin peticiones propias: el reposo ya está en curso → fundido.
    if (inflight <= 0) armQuiet()
  })

  // Navegación abortada/errónea: no dejar el velo puesto.
  router.onError?.(() => {
    navPending = false
    hide()
  })
}
