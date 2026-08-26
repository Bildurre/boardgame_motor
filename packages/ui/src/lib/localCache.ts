// Caché ligera en localStorage para el ARRANQUE de las SPAs: la última
// respuesta buena de settings/menús/locales se guarda y en la siguiente
// visita se pinta AL INSTANTE con ella mientras se refresca en segundo
// plano (patrón stale-while-revalidate). Todo va en try/catch: sin
// localStorage (incógnito estricto, cuota llena) simplemente no hay caché
// y la app funciona como siempre.

export function readCache<T>(key: string): T | null {
  try {
    const raw = localStorage.getItem(key)
    return raw ? (JSON.parse(raw) as T) : null
  } catch {
    return null
  }
}

export function writeCache(key: string, value: unknown): void {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    // llena o bloqueada: da igual, la caché es solo una mejora
  }
}
