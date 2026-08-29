// ¿Corre la web como PWA INSTALADA (ventana propia, sin pestañas ni barra)?
// Importa para los PDFs: en la app instalada no hay «pestaña nueva» ni visor
// con controles — navegar a un PDF inline deja la ventana en blanco sin
// escape mientras bajan decenas de MB. En standalone se sirve la descarga
// nativa (Content-Disposition: attachment): el gestor de descargas del
// sistema da progreso y la app sigue viva.
export function isStandalonePwa(): boolean {
  if (typeof window === 'undefined') return false
  // display-mode cubre Chrome/Edge/Android; navigator.standalone es el
  // equivalente histórico de iOS Safari.
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    window.matchMedia('(display-mode: minimal-ui)').matches ||
    window.matchMedia('(display-mode: fullscreen)').matches ||
    ('standalone' in window.navigator &&
      Boolean((window.navigator as { standalone?: boolean }).standalone))
  )
}
