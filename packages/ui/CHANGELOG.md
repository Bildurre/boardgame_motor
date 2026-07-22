# Changelog — @edc-motor/ui

Componentes Vue 3 + SCSS para las webs públicas (y piezas compartidas con el
admin). Paquete **fuente** (se consume vía Vite). Versión de tren con
`edc-motor/core` y `@edc-motor/admin-kit`.

## [Sin publicar]

### Añadido

- **`RichTextInput`: tablas, encabezados h2-h5, subrayado, listas anidadas,
  cita y enlaces**. Extensiones oficiales de TipTap (`@tiptap/extension-
  table` + `-table-row`/`-table-header`/`-table-cell`, `^2.11`, sin resize
  de columnas — metía ruido visual y no hace falta): insertar tabla
  (3×3 con cabecera), añadir/quitar fila y columna, alternar fila de
  cabecera y borrar tabla (controles contextuales: los de editar solo
  aparecen con el cursor dentro de una tabla). `StarterKit` amplía sus
  niveles de título a `[2, 3, 4, 5]` (botones H2-H5). Subrayado
  (`@tiptap/extension-underline`). Sangrar/des-sangrar (`Indent`/
  `Outdent`): anidan o sacan un item de lista (`sinkListItem`/
  `liftListItem`), y des-sangrar con un blockquote activo lo saca
  (`lift`); toggle de cita añadido a la toolbar. Enlaces
  (`@tiptap/extension-link`): botón con mini-popover (URL + aplicar,
  mismo patrón que el selector de iconos) y botón de quitar enlace; TODOS
  los enlaces creados desde el editor llevan `target="_blank"` +
  `rel="noopener noreferrer"` por defecto (también los `mailto:`, donde
  son inocuos — así no hace falta distinguir el esquema al aplicarlos).
  Todos los botones nuevos llevan icono de lucide y texto por
  `RichTextLabels` (prop `labels`), como el resto de la toolbar —
  **RUPTURA**: la clave `heading` se sustituye por `heading2`/`heading3`/
  `heading4`/`heading5` (un consumidor que pasara `labels.heading` debe
  repartirlo entre las cuatro). El modo HTML (fuente) YA EXISTÍA (toggle
  con icono `Code`, textarea monoespaciado, `setContent` al volver a
  visual) — es la vía para pegar HTML directo (tablas incluidas); pasa
  por el saneador del servidor al guardar, no se sanea en cliente.
- **Tabla del wysiwyg, visible y editable** (`_rich-text.scss`, editor) y
  **estilo público de tabla** (`_rich-content.scss`, `.rich-content`):
  ancho completo, `border-collapse: collapse`, bordes (`$border-strong`
  en el editor, `$border` en público), `th` semibold con fondo sutil
  (`$surface-2`), `td`/`th` con padding `$space-2`/`$space-3`, y overflow
  horizontal seguro en estrecho (`display: block; overflow-x: auto` en la
  propia tabla). Las clases de fila que use cada juego (p. ej.
  `green-bg`) las estila el JUEGO, no el motor.

### Cambiado

- **Selects: flecha → CHEVRON** (`BaseSelect` + todo lo que comparte
  `.form-field__select-wrapper`, admin y app por igual — es el único
  sitio del paquete que pintaba una flecha): el triángulo CSS se
  sustituye por el `chevron-down` de lucide pintado con `mask-image`
  (`background-color: $text-3`, así seguía el tema claro/oscuro y
  cualquier tema de juego sin grabar un color en el SVG).

## [0.4.27] — 2026-07-21

### Cambiado

- **Tipografía de bloques revisada** (sustituye a la escala de 0.4.26): el
  texto y el wysiwyg de bloque VUELVEN a base $fs-16; subtítulo $fs-24
  ($fs-28 en la cabecera); título $fs-32 y el h2 del bloque header sube a
  $fs-40; el wysiwyg (`.rich-content`, global) fija su escala de
  encabezados h2 $fs-32 · h3 $fs-24 · h4 22px (sin token) · h5 $fs-20 ·
  h6 $fs-18; la cita baja de 36 a $fs-32; el índice vuelve a 24/22/20;
  autor $fs-14 y botón de bloque $fs-18.
- **Alineación del bloque por CLASE, no por `style` en línea**
  (`BlockShell` + `_blocks.scss`), y el JUSTIFICADO pasa a la IZQUIERDA
  por debajo de 480px, como ya hacían título y subtítulo.
- **La imagen del CTA en columnas, otra vez a TODA la altura de la
  tarjeta**: título y subtítulo vuelven a la columna de texto (dentro de
  `.block__cta-body`, en todos los anchos) y la imagen sangra por su
  lateral, arriba y abajo. El "título a ancho completo por encima del
  grid" queda solo para la tarjeta de texto.
- **"Contener" respeta el tamaño NATURAL de la imagen**: en columnas deja
  el marco absoluto — la imagen va en flujo (ancho de su columna, alto
  según su proporción, anclada arriba), sin ampliarse ni encogerse; si es
  más alta que el texto la fila crece, si es más baja queda aire debajo.
  "Cubrir"/"rellenar" siguen con el marco estirado al alto del texto. Las
  FLOTADAS (clear) llevan $space-4 (16px) de margen por los tres lados
  libres (arriba, abajo y hacia el texto), pegadas a su lado exterior.
- **Anchuras de bloque**: el preset "estrecho" sube de 800 a 880px, y las
  tarjetas (CTA, tarjeta de texto y cita) son algo más angostas que el
  resto EN CADA preset con diferencia PROPORCIONAL
  (`max-width: min(tope, 90%)`): 1080px en ancho, 780px en estrecho.
  "Ancho completo" no lleva tope.
- **Menos aire entre bloques en estrecho**: `padding-block` de 56 a 40px
  por debajo de 480 del contenedor `content`.
- **Cabecera del bloque "Relacionados" a 600px** (antes 480; container
  query del contenedor `content`): por debajo, título, subtítulo y botón
  en TRES filas.
- **`EditModal` con talla `wide`**: nueva prop `size` (`normal`/`wide`) y
  `BaseModal` gana la talla `wide` (940px) — la usa el formulario de
  bloque del admin-kit. RUPTURA suave: `EditModal` ya no reenvía las
  tallas `sm`/`md`/`lg` de `BaseModal` — un juego que usara
  `size="lg"` en sus form-modals debe pasar a `size="wide"` (los usos
  sin `size` no cambian).

## [0.4.26] — 2026-07-21

### Cambiado

- **Tipografía de bloques subida ~×1.125 (base 18px)**: texto y wysiwyg de
  bloque a $fs-18, subtítulo 22px, título $fs-32, cabecera 36/28 (nuevos
  tokens $fs-36 y $fs-40 en la escala), cita $fs-36, índice 27/25/22,
  autor $fs-16, botón de bloque $fs-20 y pregunta del FAQ $fs-18.
- **Las negritas del wysiwyg, en color de acento** (`.rich-content strong/b`).
- **Índice numerado con numeración ANIDADA** (1, 1.1, 1.2, 1.2.1…):
  la calcula `BlockIndex` sobre la lista plana con profundidades (fuera los
  números nativos del `ol`), con los números en `tabular-nums`.
- **Las imágenes junto al texto, ancladas ARRIBA (mientras no estén en
  vertical)**: en columnas, el marco estirado a la altura del texto
  centraba la imagen en vertical con "contain" — pasa a `object-position:
  top` (también con "cover"); y las flotadas (clear) quedan explícitamente
  ancladas arriba del texto que las rodea.
- **Bloques con imagen: a vertical en 768 y la imagen SIEMPRE encima**: las
  columnas, los flotados y el modo vertical del CTA pasan del breakpoint sm
  (480) al md (768) del contenedor; al apilarse, la imagen va encima del
  texto da igual su posición configurada (derecha/abajo incluidas). Las
  imágenes flotadas (clear) dejan $space-4 (16px) de margen con el texto
  también por debajo.
- **Las columnas del CTA respetan título y subtítulo**: van SIEMPRE a ancho
  completo por encima del grid (el reparto es solo imagen ↔ contenido); la
  imagen sangra por su lateral y por abajo, y por arriba solo si la tarjeta
  abre con ella.
- **Etiqueta de la tarjeta de texto sin chip**: solo texto en acento
  (uppercase, $fs-13), con alineación propia
  (`block__label--left/center/right`, campo `label_align`); y la tarjeta de
  texto gana el mismo halo sutil de acento que la del CTA.

## [0.4.25] — 2026-07-20

### Cambiado

- **Índice automático con sangría por profundidad real**: `BlockIndex`
  pinta `--depth` por entrada y la sangría escala por nivel (sin tope,
  acompañando al anidado multinivel de bloques); los tamaños por nivel
  (24/22/20, el tercero para "3 o más") se quedan como estaban.

## [0.4.24] — 2026-07-20

### Cambiado

- **La cita en peso 600** (`font-weight: $k-fw-semibold` en
  `.block__quote`).
- **Índice automático con jerarquía tipográfica**: peso 500
  (`$k-fw-medium`) y tamaño por nivel — 24px el nivel 1, 22px el 2 (no hay
  token entre 20 y 24) y 20px del 3 en adelante (clases
  `block__index-level-1/2/3`, la 3 agrupa "3 o más").

## [0.4.23] — 2026-07-19

### Corregido

- **CTA con imagen en estrecho: siempre arriba, a sangre y en 2:1**: por
  debajo del breakpoint sm la imagen va ARRIBA da igual la posición
  elegida (izquierda/derecha/abajo incluidas), sangrando hasta los bordes
  laterales de la tarjeta (y el superior si no hay título encima), con
  `aspect-ratio: 2/1` y recorte `cover` (no se deforma).
- **La alineación propia de título/subtítulo ahora sí se ve**: en grids con
  `justify-items: start` (el cuerpo del CTA) el elemento encogía a su
  contenido y el `text-align` no tenía efecto — título y subtítulo pasan a
  `width: 100%`. Además, en ESTRECHO (< 480px) la alineación elegida se
  revierte a la IZQUIERDA, sea cual sea.

## [0.4.22] — 2026-07-19

### Corregido

- **La cita ahora sí sale en grande**: el texto llega como richtext y el
  `.rich-content` interior machacaba el cuerpo con su `$fs-16`; dentro de
  `.block__quote` pasa a heredar el tamaño de la cita ($fs-32). Además el
  autor pierde el guion "—" delante y su alineación por defecto pasa a la
  DERECHA.

## [0.4.21] — 2026-07-19

### Cambiado

- **Más aire entre los elementos de los bloques**: nuevo token
  `$block-gap` ($space-6, 24px) que unifica la separación interior de los
  bloques — el grid del bloque (antes 20px), la tarjeta del CTA/text-card
  (antes 16px) y el cuerpo del CTA (antes 12px). Y el botón del CTA
  respira aparte: margen extra encima ($space-5) para que su hueco total
  casi DOBLE la separación normal.

## [0.4.20] — 2026-07-19

### Cambiado

- **Título y subtítulo con alineación propia**: `BlockShell` pinta
  `block--title-left/center/right` y `block--subtitle-…` cuando los campos
  comunes traen un valor explícito (mandan sobre la alineación del bloque,
  incluido el "a la izquierda" del justificado); con "La del bloque" todo
  sigue igual.
- **Bloque de cita rediseñado**: fuera el adorno del borde izquierdo; la
  cita pasa a $fs-32 (el token más cercano a ~40px) y al COLOR DE ACENTO;
  el autor va en cursiva y con alineación propia
  (`block__author--left/center/right`, campo `author_align`).

## [0.4.19] — 2026-07-19

### Cambiado

- **Bloques justificados por defecto**: `BlockShell` alinea `justify` cuando
  el bloque no trae `align` guardado (igual que el nuevo default del campo
  común del core). Títulos y subtítulos siguen en `left` con justificado,
  como estaba.
- **El subtítulo de los bloques respeta los saltos de línea**
  (`white-space: pre-line` en `.block__subtitle`): acompaña al campo, que
  pasa a textarea en el core.
- **La tarjeta del CTA gana un halo sutil del acento**: sombra del color de
  acento sin offset (igual por los cuatro bordes, `0 0 18px` al 30 %)
  además de su sombra de profundidad de siempre.
- **Botón del CTA alineable y en tamaño grande**: `BlockCta` aplica
  `button_align` (clases `block__cta-button--left/center/right` sobre el
  grid del cuerpo) y `button_large` (`block-button--large`, más padding
  interior). En formato ESTRECHO (< 480px) los botones de bloque van
  SIEMPRE centrados: el del CTA ignora su alineación y el del `related`
  (que en ancho va a la derecha de la cabecera) también se centra.

## [0.4.18] — 2026-07-19

### Añadido

- **Mixin `contrast-text($bg)` en los tokens SCSS**: texto claro u oscuro
  según la luminosidad REAL del fondo, resuelta por el navegador (relative
  color syntax, `lch(from …)`) — blanco bajo L≈49.44 y negro por encima, el
  umbral donde el contraste real cambia de bando. Fallback (navegadores sin
  soporte): texto oscuro. Disponible para los juegos vía `@use "tokens"`.

### Cambiado

- **Los botones RELLENOS dejan de fijar el color del texto a mano**
  (`contrast-text`): `edc-button` `--primary` (y su hover oscurecido),
  `--danger`/`--success`/`--info`/`--warning`, y `block-button` `--primary`
  (+ el hover de `--secondary`, que rellena de acento). Con el acento claro
  de Ajustes o el tema oscuro, el texto fijado (blanco / `$text-1`) podía no
  leerse; ahora lo decide el fondo.
- **El grid del bloque `related` siempre sale completo, sin filas cojas**
  (`PreviewGrid --compact`): el bloque trae SIEMPRE 6 ítems (ver core) y el
  grid decide cuántos enseña por ancho de viewport — 4 en 2×2 (estrecho),
  6 en 3×2 (≥768), 4 en 4×1 (≥1024) y 5 en 5×1 (≥1280); los sobrantes se
  ocultan con `nth-child`. Los demás grids de previews siguen 2 → 3 → 4.

## [0.4.17] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.16] — 2026-07-19

### Añadido

- **Locale global de formulario** (provide/inject): `provideFormLocale()`
  crea el contexto (clave `FormLocaleKey`) y `useFormLocaleField()` suscribe
  un campo — `TranslatableInput` y `TranslatableImage` ya lo llaman solos, y
  sus tabs individuales siguen siendo locales. Nuevo componente
  `FormLocaleSwitch` (segmentado compacto de códigos; solo se pinta si hay
  campos traducibles suscritos y más de un locale), y `EditModal` provee el
  contexto y lo monta en su cabecera (nueva prop `localeSwitchLabel?` para el
  texto accesible, DC-29). Cualquier otro contenedor puede montar lo mismo
  con `provideFormLocale()` + `<FormLocaleSwitch />`.

### Cambiado

- **`ImageUpload`: diferido y con la imagen actual siempre a la vista**:
  elegir fichero ya solo deja el `File` en el `v-model` (vista previa por
  object URL, SIN petición al servidor) — quien lo usa lo envía al pulsar
  GUARDAR. La vista previa se deriva del `v-model` (controlado: si el padre
  repone un `File`, la miniatura reaparece — lo aprovecha
  `TranslatableImage` al cambiar de idioma), y bajo la miniatura se muestra
  el **nombre del fichero** (el del `File` pendiente o el extraído de
  `current-url`, clase `image-upload__name`). El botón de quitar sigue
  emitiendo `remove` para que la vista lo difiera también.
- **`TranslatableImage`: contrato diferido** (RUPTURA): el mapa del `v-model`
  pasa a `Record<string, string | File>` — URL guardada o `File` pendiente
  por locale — y desaparecen las props `upload` y `removeFile`: el
  componente ya NO sube ni borra nada; quien lo usa resuelve los `File` en
  el submit (en el motor, `PageBlocks`/Ajustes con los helpers de
  `@edc-motor/admin-kit`). Quitar la imagen de un locale borra su clave del
  mapa (también diferido).
- **`IndexToolbar`: la lupa pasa a la IZQUIERDA del input** y el texto
  (placeholder y valor) empieza a su derecha (padding-left de 34px), sin
  montarse con el icono.
- **`PaletteColorPicker`: paleta nueva** en espectro cálido → frío —
  `#f15959`, `#f1753a`, `#88b033`, `#29ab5f`, `#31a28e`, `#3999cd`,
  `#408cfd`, `#7a64c8`, `#a75da5` — con el gris al final (se mantiene el
  `#64748B` heredado de kontuan). El swatch de valor libre (custom) no
  cambia.

## [0.4.15] — 2026-07-17

- Sin cambios propios: versión de tren.

## [0.4.14] — 2026-07-16

- Sin cambios propios: versión de tren.

## [0.4.13] — 2026-07-16

### Cambiado

- **`AppRightSidebar` SIEMPRE fija bajo la cabecera fija, con asa propia.**
  En el admin la barra derecha funciona porque el marco es fijo y scrollea
  el main; en la web pública el footer va al final del documento, así que
  la columna sticky scrolleaba con la página y "se acababa" al llegar al
  pie. Ahora la barra es `position: fixed` a la derecha, desde el borde
  inferior de la cabecera del cascarón (que pasa a estar SIEMPRE visible,
  sin auto-ocultado) hasta abajo: `top: var(--app-right-sidebar-top, 0px)`
  → bottom 0, `z-index: 40`, por debajo de la cabecera (50). El cascarón
  fija `--app-right-sidebar-top` a la altura real de su cabecera por
  breakpoint. Ni scrollea, ni se corta con el footer en páginas cortas, ni
  tapa nunca la cabecera. Cerrada queda fuera de pantalla
  (`translateX(100%)`) y solo asoma el asa.
  - **Asa anclada a la propia barra** (nueva, sustituye al botón Funnel del
    header del cascarón): pestañita al costado izquierdo, estilo de la de la
    RightSidebar del admin-kit, que viaja con la barra — Funnel cerrada / X
    abierta, `aria-expanded`, labels por prop (DC-29): nueva `openLabel`
    ("Abrir el panel") y la `closeLabel` de siempre. Solo asoma si la vista
    registró contenido. El botón X del header interno del panel desaparece
    (cerraba lo mismo que el asa).
  - **CSS vars**: `--app-right-sidebar-top` (techo de la barra y del telón;
    la fija el cascarón, por defecto 0), `--app-right-sidebar-width`
    (320px, en `:root`; el cascarón la usa para el hueco) y
    `--app-right-sidebar-handle-top` (altura del asa RELATIVA al techo de
    la barra; por defecto `$space-4`, es decir, asomando justo bajo la
    cabecera sin que el cascarón tenga que tocar nada).
  - **Nueva clase `app-right-sidebar--docked`** (desplegada en ancho, con
    contenido): el cascarón le hace hueco con
    `body:has(.app-right-sidebar--docked)` → `padding-right` en
    `.site-content`/`.app-footer`, con transición al ritmo del transform
    (sin telón). La cabecera NO necesita hueco: la barra queda por debajo.
    En estrecho (< 900px), drawer superpuesto con telón que arranca bajo la
    cabecera (sigue visible y clicable con el drawer abierto), click fuera
    y Escape.
  - La API de `useAppRightSidebar()` **no cambia** (`register`/`unregister`/
    `useRegister`, target `#app-right-sidebar-target`, `toggle`, `reveal`…).
- **`SortToggles` sueltos estilo action-button**: fuera el grupo segmentado
  con fondo y borde compartidos — cada toggle es un botón individual, limpio
  y sin caja (36px táctiles), con aire entre ellos. En reposo color de texto
  y tinte de acento al hover; el ACTIVO se colorea distinguiendo el sentido
  además del icono: ascendente (oldest, A-Z) acento sobre tinte suave;
  descendente (latest, Z-A) acento RELLENO con el texto en `$surface` —
  misma familia con otra intensidad, legible en claro y oscuro y con
  cualquier acento de juego. `aria-pressed` y títulos como estaban; nueva
  clase `is-desc` junto a `is-active`.

## [0.4.12] — 2026-07-15

- Sin cambios propios: versión de tren.

## [0.4.11] — 2026-07-15

- Sin cambios propios: versión de tren.

## [0.4.10] — 2026-07-14

- Sin cambios propios: versión de tren.

## [0.4.9] — 2026-07-13

### Añadido

- **`AppRightSidebar`** + **`useAppRightSidebar()`**: barra lateral derecha
  contextual de la web pública, con la MISMA mecánica que la RightSidebar
  del admin-kit. Composable singleton a nivel de módulo (como
  useToast/useConfirm; los juegos consumen el paquete como fuente con
  `optimizeDeps.exclude`): `hasContent`, `collapsed`, `mobileOpen`,
  `overlay`, `title`, `isOpen`, `toggle()`, `reveal()`, `register()` /
  `unregister()` / `useRegister(titulo)` con token de propiedad. La monta
  App.vue dentro de `.site-main`; cada vista registra sus filtros y los
  teletransporta a `#app-right-sidebar-target`. En ancho es columna
  pegajosa junto al contenido (plegable); en estrecho (< 900px), drawer
  superpuesto con telón, click fuera y Escape. El cascarón fija
  `--app-right-sidebar-top` a la altura de su cabecera fija. Props
  agnósticas de i18n (DC-29): `closeLabel` ("Cerrar el panel") y
  `fallbackTitle` ("Filtros").
- **`useDropdownPanel`** (interno, sin exportar): promociona el panel
  abierto de un dropdown a la top layer del navegador (atributo `popover`)
  y lo ancla por coordenadas fijas al trigger, reanclando en scroll/resize.
  Sin soporte de popover no hace nada (queda el CSS absolute de siempre).

### Cambiado

- **`IndexToolbar` sin botón "Filtros"**: fuera las props
  `showFilters`/`activeCount`/`filtersLabel` y el emit `open-filters` (con
  su SCSS). Queda búsqueda + `SortToggles`; los filtros de los index viven
  ahora en la barra derecha.

### Arreglado

- **Selects dentro de modales**: desplegar un `BaseSelect`/`SearchSelect`
  en un modal recortaba el panel contra el overflow de `.modal__body` y le
  añadía scroll fantasma (y el scrollbar disparaba reflows/container
  queries que deformaban el modal). Los paneles usan `useDropdownPanel`:
  se superponen sin tocar el layout del modal, siguen pegados al trigger al
  scrollear (también fuera de modales) y Escape con el panel abierto cierra
  solo el desplegable, no el modal contenedor.

### Retirado

- **`FiltersModal`** (salido en 0.4.8): los filtros de los index pasan del
  modal a la barra derecha (RightSidebar del admin-kit en el admin;
  `AppRightSidebar` en la web pública). Sus únicos consumidores (el
  cascarón) migran en esta misma versión.

## [0.4.8] — 2026-07-13

### Añadido

- **`SortToggles`**: dos toggles de ordenación para los index — fecha
  (latest ⇄ oldest) y alfabético (name ⇄ name_desc). Pulsar el inactivo lo
  activa en su primer estado; pulsar el activo invierte el sentido. Iconos
  lucide direccionales, labels accesibles por prop, 36px de alto.
- **`IndexToolbar`**: barra unificada de los index (admin y web) — búsqueda
  con lupa a la derecha (v-model, emite inmediato como el FilterBar: el
  debounce va en el consumidor), `SortToggles` integrado (`v-model:sort`) y
  botón "Filtros" con badge de activos (`activeCount`; emite `open-filters`;
  `showFilters` lo oculta en index sin filtros). Container query propia: en
  estrecho la búsqueda ocupa su fila y debajo toggles + botón se reparten el
  ancho.
- **`FiltersModal`**: modal de filtros sobre `BaseModal` (Escape, click
  fuera, aria-modal) SIN semántica de guardar — los campos del slot aplican
  en vivo. Grid de columnas por container query del ancho del modal
  (1 → 2 a 460px → 3 a 700px) y pie con "Quitar filtros" (emite `clear`,
  solo con `activeCount > 0`) y "Cerrar".

### Cambiado

- **Controles de formulario compactos**: los tokens pasan de 40px a
  `$input-height: 36px` con padding 8px/10px (antes 10px/12px). Afecta a
  inputs/selects/textarea globales (`_forms.scss`), `.form-field`,
  `BaseSelect`, `SearchSelect` (trigger e input del panel), `NumericInput` y
  `BaseButton` (min-height 36px; las variantes `text` quedan sin altura
  mínima). `BasePagination` se queda a 32px a propósito (control secundario,
  un punto por debajo).

## [0.4.7] — 2026-07-12

### Añadido

- **`BasePagination`**: paginación compacta de listados (anterior / "x de y" /
  siguiente; con una sola página no pinta nada), para los index del admin y
  de la web.

### Cambiado

- **Iconos del wysiwyg al tamaño del texto**: regla global — `img.rt-icon`
  mide SIEMPRE 1.2x el font-size del texto que lo rodea, se renderice donde
  se renderice (antes solo dentro de `.rich-content`, y en paneles sin esa
  clase salían a tamaño completo).

## [0.4.6] — 2026-07-12

### Cambiado

- **`BaseSelect` personalizado**: el `<select>` nativo se sustituye por un
  dropdown propio — botón trigger (misma altura y aspecto que un input del
  motor: reutiliza `.form-field__select` y su wrapper) + panel de opciones
  con la estética del SearchSelect (surface, borde, sombra, scroll interno).
  Teclado completo (flechas, Enter/Espacio, Escape, Home/End), aria
  (`listbox`/`option`, `aria-expanded`, `aria-selected`) y cierre por click
  exterior. **API intacta** (mismas props y emit `update:modelValue` con
  string): los usos existentes no cambian. Matices: el valor se compara y
  emite como string (igual que el DOM del nativo), el placeholder se pinta en
  el trigger y la antigua `<option value="" disabled>` deja de listarse en el
  panel (nunca era seleccionable), y `required` ya no participa en la
  validación nativa del formulario (solo asterisco + `aria-required`). SCSS
  nuevo en `components/_base-select.scss`.
- Regla base: todo lo clickable (botones, checkboxes, radios, summary,
  role=button) lleva `cursor: pointer` salvo deshabilitado.

## [0.4.5] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.4] — 2026-07-12

### Añadido

- **`PreviewGrid`**: rejilla presentacional de previews del catálogo público
  (`GET /api/catalog/{key}`). Props `items` (con `to` opcional → RouterLink),
  `loading`, `page`/`pages` (paginación prev/next con emit `page`) y variante
  `compact`; slots `item` y `actions` (scoped `{ item }`) y `empty`. Fallback
  con el nombre y proporción de carta (5/7) cuando la preview no está
  generada. Textos por prop (DC-29). SCSS en `components/_preview-grid.scss`.
- **Bloque `related`** (`BlockRelated`, clave `related` en
  `motorBlockComponents`): título/subtítulo + PreviewGrid compacta con los
  ítems de `data` y botón opcional al índice (`with_button`/`button_label`;
  texto por defecto por prop, DC-29). Los enlaces se resuelven con el mapa
  que la app provee vía **`catalogRoutesKey`** (nuevo
  `src/blocks/catalogRoutes.ts`, exporta también los tipos `CatalogItem`,
  `CatalogRouteEntry` y `CatalogRoutes`); sin mapa, los ítems se pintan sin
  enlace. SCSS en `components/_block-related.scss`.

## [0.4.3] — 2026-07-11

- Sin cambios propios: versión de tren.

## [0.4.2] — 2026-07-10

### Cambiado

- **Bloque CTA**: los botones suben a cuerpo 18 (`$fs-18`); la tarjeta es un
  poco más transparente (50% de superficie, antes 65%, como el resto de
  tarjetas de bloque); y la **imagen sangra hasta el borde de la tarjeta**
  según su posición — en columnas (izquierda/derecha) toca arriba, abajo y su
  lateral (el título y subtítulo pasan a la columna de texto), y
  arriba/abajo toca los laterales (arriba solo si no hay título encima).
  Las esquinas siguen el radio de la tarjeta.
- **Anchura `narrow` de los bloques**: sube de 680px a **800px**.

## [0.4.0] — 2026-07-07

### Añadido

- Token `$fs-32`; parcial público **`_rich-content.scss`** (márgenes entre
  párrafos/listas/títulos del wysiwyg y escala h2 28 · h3 24 · h4 20 · h5 18
  · h6 16).
- Bloques: subtítulo en todos; marco de imagen en columnas con
  `image_fit`/`image_columns`; índice con sangría por nivel.

### Cambiado

- Tipografía de bloque: texto 16 · subtítulo 20 · título 28 (cabecera
  32/24). El título es h1 SOLO en la cabecera (h2 en el resto) y nunca se
  justifica (justificado → izquierda). Anchura `wide` a **1200px**.

## [0.3.1] — 2026-07-07

- Sin cambios propios: versión de tren (fix de subida de SVG en `edc-motor/core`).

## [0.3.0] — 2026-07-07

### Cambiado

- `TranslatableImage`: la prop `upload` recibe también la URL a la que
  sustituye (`(file, replaces?)`) y hay una prop opcional `removeFile` para
  borrar el fichero al pulsar "quitar".

## [0.2.0] — 2026-07-06

### Cambiado

- **Renombrado del vendor/scope a `edc-motor`** (DC-21 revisada): el paquete
  Composer pasa de `bgm/core` a **`edc-motor/core`** (namespace PHP
  `Edc\Core`) y los npm a **`@edc-motor/ui`** y **`@edc-motor/admin-kit`**.
  Migración de un juego existente: actualizar `composer.json`/`package.json`,
  los imports (`@bgm/` → `@edc-motor/`), el namespace en `config/motor.php` y
  las clases propias, y las clases CSS `bgm-*` → `edc-*`.
- **Licencia GPL-3.0-only** y publicación en registros públicos: Packagist
  (`edc-motor/core`, vía el repo split `bildurre/edc-core`) y npmjs
  (org `edc-motor`). El consumo por clon hermano deja de ser necesario.

## [0.1.0] — 2026-07-05

Primera versión etiquetada (Fases 0–7 del plan).

### Añadido

- **Base**: `BaseButton` (variantes primary/secondary/danger/success/text,
  con icono), `BaseInput`, `BaseSelect`, `BaseTextarea`, `BaseModal`,
  `ConfirmDialog`, toasts (`useToast` + `ToastHost`), `IconButton`,
  `ThemeSelector` (claro/oscuro/sistema) y `LocaleDropdown`.
- **Contenido**: editor WYSIWYG propio con TipTap (`RichTextEditor`, DC-09)
  con toggle visual/HTML, `PageBackground`, `BlockRenderer` + bloques del
  catálogo (hero, texto, imagen, cita con fuente *especial*, CTA con
  `.block-button` de hover cruzado, columnas, índice, FAQ…), envoltorio
  `BlockShell` (align/width/background).
- **SEO**: `useHead` sin dependencias (title, description, canonical,
  hreflang) apto para el prerender (DC-18).
- **SCSS**: tokens (`tokens.scss` con fuentes/colores/espaciado/radios),
  temas claro/oscuro, parciales de componentes y utilidades
  (`rich-content`, formularios).
- `RichTextInput` se exporta **diferido** (defineAsyncComponent): TipTap
  (~450 KB) no entra en el bundle de la web pública y el admin lo trocea en
  su propio chunk.
- `BaseButton` con variantes `info` y `warning`; `.block-button` cruza sus
  estados sobre la SUPERFICIE ($surface), no sobre el fondo puro.
- **Chip único** (`.chip`): contorno con esquinas poco redondeadas
  ($radius-sm), acento por defecto (nunca gris), `$fs-12`, con estados
  `is-ok/is-info/is-missing/is-failed` — lo usan app y admin (sustituye a
  `.locale-chip` y a los chips por vista).
- i18n por props (DC-29): el paquete no lleva textos; la app los inyecta.
