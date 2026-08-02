<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

// Envoltorio común de todos los bloques públicos: aplica los campos comunes
// (align, width, background) que el motor añade a cada tipo. Un hex de la
// paleta NO se aplica opaco: es un tinte semitransparente (--block-tint,
// distinto por tema, patrón CDL) para que la imagen de fondo de la página se
// vea a través. Los presets DINÁMICOS (`token:*`) sí van opacos: un token de
// superficie ya ES un color de fondo pensado para contrastar con la página,
// y al 15% resultaba inapreciable. La anchura del CONTENIDO va por clase.
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
// serializado como `token:<nombre>` (p. ej. token:surface): se resuelve a
// su custom property (var(--surface)) y se aplica OPACO, sin el mix del
// tinte — un token de superficie ya es un fondo de nivel de tema que sigue
// al claro/oscuro; mezclado al 15% con transparente era invisible sobre la
// página. El nombre se sanea a [a-z0-9-] (el value viene del admin, pero un
// token roto no debe inyectar CSS).
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
