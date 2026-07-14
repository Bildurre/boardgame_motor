import type { ComputedRef, InjectionKey } from 'vue'

// Estado "carril de iconos" del sidebar (colapsado en escritorio, sin
// etiquetas). Lo provee AdminLayout y lo consume NavGroup: en el carril un
// grupo plegado dejaría a sus hijos inalcanzables, así que se muestran
// siempre y el toggle queda inerte.
export const SIDEBAR_RAIL: InjectionKey<ComputedRef<boolean>> = Symbol('edc-admin-sidebar-rail')
