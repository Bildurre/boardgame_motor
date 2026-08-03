<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

// Envoltorio común de todos los bloques públicos: aplica los campos comunes
// (align, width, background) que el motor añade a cada tipo. Un hex de la
// paleta NO se aplica opaco: es un tinte semitransparente (--block-tint,
// distinto por tema, patrón CDL) para que la imagen de fondo de la página se
// vea a través. Los presets DINÁMICOS (`token:*`) se resuelven a su custom
// property TAL CUAL (var(--<nombre>), sin el mix del tinte): los presets
// actuales (token:veil-15/-30/-60/-85 y token:accent-soft) ya SON
// translúcidos en _theme.scss — los velos son el fondo de página del tema
// (--bg) a esa opacidad, ennegrecen en oscuro y emblanquecen en claro — y
// los tokens antiguos aún guardados renderizan como cuando eran preset:
// token:surface*/accent-500 el color OPACO del tema, token:neutral* los
// grises translúcidos de 0.5.10. La anchura del CONTENIDO va por clase.
const props = defineProps<{ settings: Record<string, unknown> }>()

const width = computed(() => `block--w-${(props.settings.width as string) || 'wide'}`)
// Sin valor guardado, JUSTIFICADO (el default del campo común del motor).
const align = computed(() => `block--align-${(props.settings.align as string) || 'justify'}`)

// Alineación propia de título/subtítulo (campos comunes): solo pinta clase
// con un valor explícito — "inherit" (o nada) deja mandar a la del bloque.
const headingAlign = (key: 'title_align' | 'subtitle_align', prefix: string) => {
  const value = props.settings[key] as string | undefined
  return value && value !== 'inherit' ? `block--${prefix}-${value}` : ''
}

// El color de fondo puede ser un hex fijo o un preset DINÁMICO del tema
// serializado como `token:<nombre>` (p. ej. token:veil-30): se resuelve a
// su custom property (var(--veil-30)) SIN el mix del tinte — el grado de
// transparencia de cada token lo fija el TEMA (_theme.scss), no el
// --block-tint del hex libre. El nombre se sanea a [a-z0-9-] (el value
// viene del admin, pero un token roto no debe inyectar CSS).
const TOKEN_PREFIX = 'token:'
function isToken(value: string): boolean {
  return value.startsWith(TOKEN_PREFIX)
}
function resolveToken(value: string): string {
  return `var(--${value.slice(TOKEN_PREFIX.length).replace(/[^a-z0-9-]/gi, '')})`
}

// La alineación va por CLASE (block--align-*, _blocks.scss), no por style en
// línea: un style inline no se puede pisar desde CSS (bloqueaba el cambio a
// izquierda del justificado en estrecho, DC-03 ampliado).
const style = computed<CSSProperties>(() => {
  const background = props.settings.background as string | undefined
  let bg = 'transparent'
  if (background) {
    bg = isToken(background)
      ? resolveToken(background)
      : `color-mix(in srgb, ${background} var(--block-tint, 15%), transparent)`
  }
  return { '--block-bg': bg }
})
</script>

<template>
  <section
    class="block"
    :class="[
      width,
      align,
      headingAlign('title_align', 'title'),
      headingAlign('subtitle_align', 'subtitle'),
    ]"
    :style="style"
  >
    <div class="block__inner">
      <slot />
    </div>
  </section>
</template>
