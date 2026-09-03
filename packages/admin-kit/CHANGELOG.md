# Changelog — @edc-motor/admin-kit

Kit de construcción del panel de administración (sobre `@edc-motor/ui`). Paquete
**fuente** (se consume vía Vite). Versión de tren con `edc-motor/core` y `@edc-motor/ui`.

## [Sin publicar]

### Añadido

- **Campos condicionados en `SchemaFields`** (`visible_when` del esquema,
  `Field::visibleWhen` del core): el campo solo se pinta cuando el campo
  condicionante vale lo declarado (su default si aún no tiene valor); su
  fila declarada se cierra sin él. El panel de `PageBlocks` tampoco los
  vuelca.

### Cambiado

- **Campo `icon` en `SchemaFields`**: el `IconPicker` del ui (rejilla de
  iconos lucide con buscador y «mostrar más») sustituye al select de iconos
  del juego; el valor guardado es el nombre kebab-case. Textos localizables
  por convención (`iconPicker.none` / `search` / `showMore` / `remaining` /
  `noResults`).

## [0.5.42] — 2026-09-03

### Añadido

- **Campo `icon` en `SchemaFields`**: select por nombre entre los iconos del
  juego (prop `icons`, la misma del texto rico) con vista previa; el valor
  guardado es la URL del icono. Etiqueta «Sin icono» localizable
  (`blockOptions.icon.none`).
- **Pestañas en `PageBlocks`**: los hijos de un bloque `tabs` llevan el badge
  «Pestaña N · nombre» y el contenedor avisa si hay pestañas sin bloque o
  bloques de más (etiquetas `tab`, `tabsMissing`, `tabsExtra`, con
  `{count}`); el resumen de un bloque de pestañas sin título son los nombres
  de sus pestañas.

## [0.5.41] — 2026-09-03

- Sin cambios propios: versión de tren.

## [0.5.40] — 2026-09-03

- Sin cambios propios: versión de tren.

## [0.5.39] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.38] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.37] — 2026-09-02

### Revertido

- **Botones de acción del panel derecho**: la variante `primary` vuelve al
  acento único del tema (se revierte el acento 2 de 0.5.36).

## [0.5.36] — 2026-09-02

### Cambiado

- **Botones de acción del panel derecho** (`manager-detail__actions`): la
  variante `primary` pasa al acento 2 (acción), como el botón primario del ui.

## [0.5.35] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.34] — 2026-09-02

### Corregido

- **Checkbox de un campo del esquema en fila** (`Field::boolean()->row()`
  junto a un select, p. ej. «Tarjetas estrechas» del índice de entidad):
  se alinea con la CAJA del input de al lado, no con el campo entero
  (etiqueta + input): la celda va al fondo de la fila y mide lo que un
  input, con la casilla centrada. Apilado en el modal angosto, alto natural.

## [0.5.33] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.32] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.31] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.30] — 2026-09-02

- Sin cambios propios: versión de tren.

## [0.5.29] — 2026-09-01

- Sin cambios propios: versión de tren.

## [0.5.28] — 2026-09-01

### Cambiado

- **Los paneles derechos de los gestores dejan de repetir el tipo de
  modelo**: la fila superior es ahora el NOMBRE del elemento (la tarjeta
  activa) con las flechas anterior/siguiente al lado — `PreviewManager`,
  `PdfManager` y el panel de bloque de `PageBlocks` (que pierde el título
  duplicado de abajo). Los combobox de elemento de previews/PDF ganan sus
  propias flechas al lado (`manager-panel__select-row`/`__select`, nuevos
  en `_manager-card.scss`), y el nombre de la fila superior encoge sin
  empujar los botones. Labels nuevos `prev`/`next` en los tres gestores.
- **Los toasts de las acciones de previews y PDF llevan el nombre**: el
  mensaje del servidor va prefijado con la tarjeta o el elemento sobre el
  que se actuó (antes no se sabía de quién era la acción).
- **Sin divisorias huérfanas en los paneles** (`_manager-card.scss`): dos
  `manager-panel__divider` seguidas (la fija tras las acciones + la propia
  de la sección siguiente), la primera de una `__section` que sigue a una
  divisoria, o una final sin nada debajo, se ocultan por CSS.

## [0.5.27] — 2026-08-31

- Sin cambios propios: versión de tren.

## [0.5.26] — 2026-08-30

### Añadido

- **Estilos de las flechas anterior/siguiente del panel derecho**
  (`_manager-card.scss`: `manager-panel__kicker-row/__nav/__nav-btn`): la
  fila del kicker acoge dos botones compactos para recorrer el listado sin
  deseleccionar (los usan los `EntityPanel` de los juegos).

### Cambiado

- **Móvil: el layout del admin mide en `100dvh`** (fallback `100vh`): con
  la barra del navegador del teléfono visible, el pie del panel y de la
  barra lateral quedaban tapados. El cuerpo del panel derecho lleva
  `overscroll-behavior: contain` (su scroll no encadena al fondo).

## [0.5.25] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.24] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.23] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.22] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.21] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.20] — 2026-08-29

- Sin cambios propios: versión de tren.

## [0.5.19] — 2026-08-26

- Sin cambios propios: versión de tren.

## [0.5.18] — 2026-08-25

- Sin cambios propios: versión de tren.

## [0.5.17] — 2026-08-24

- Sin cambios propios: versión de tren.

## [0.5.16] — 2026-08-24

- Sin cambios propios: versión de tren.

## [0.5.15] — 2026-08-24

- Sin cambios propios: versión de tren.

## [0.5.14] — 2026-08-03

- Sin cambios propios: versión de tren.

## [0.5.13] — 2026-08-03

- Sin cambios propios: versión de tren.

## [0.5.12] — 2026-08-03

- Sin cambios propios: versión de tren.

## [0.5.11] — 2026-08-03

- Sin cambios propios: versión de tren.

## [0.5.10] — 2026-08-03

- Sin cambios propios: versión de tren.

## [0.5.9] — 2026-08-02

- Sin cambios propios: versión de tren.

## [0.5.8] — 2026-08-02

### Cambiado

- **`SchemaFields`: un campo `color` con `options` pasa sus presets
  dinámicos al `PaletteColorPicker`** — las options del esquema (valores
  `token:*` del core; hoy solo las declara el `background` común de los
  bloques) se convierten en la prop `presets` del picker, con las
  etiquetas por el MISMO camino i18n que las opciones de un select
  (`blockOptions.<campo>.<valor>`, fallback al castellano serializado).
  Los campos color sin options (y los usos directos del picker) no
  cambian.

## [0.5.7] — 2026-08-01

### Cambiado

- **`SchemaFields` sobre el sistema compartido `.form-row` del ui** — fuera
  la maquinaria propia (`__field--pair` con `auto-fit`, `__field--row` con
  el select de alineación a 140px, `__field--image-group`,
  `__field-image-settings`): las filas declaradas (`Field::row`, la API del
  core NO cambia) se pintan con `.form-row` (`--3` si la fila trae 3+
  campos; la recursión anidada se disuelve con `display: contents`); la
  pareja campo+alineación pasa a `.form-row--wide-left` (el
  input/textarea/wysiwyg ancho a la izquierda, el select a la derecha); y
  el grupo de imagen pasa a `.form-row--media` — el input de imagen es UNA
  sola celda a todo el alto de la fila y los ajustes (posición, escalado,
  reparto de columnas) van apilados en la columna derecha. Sin `auto-fit`
  en ningún caso: columnas fijas + cortes explícitos (el porqué del
  temblor a DPI fraccionario, en el [0.5.7] de `@edc-motor/ui`). Las
  filas del repeater (`.schema-fields__row`) no cambian.
- **`PageBlocks`: la sección General y los campos del tipo, en
  `.form-fieldset`** — el modal de bloque usa el mismo lenguaje que el
  resto de formularios: General como fieldset con legend (sin raya, por
  ser el primero) y sus filas como `.form-row` (adiós
  `.page-blocks__common-row`); los campos del tipo en un segundo fieldset
  con legend (`panelContent`), cuya raya dashed conserva la separación de
  antes.

## [0.5.6] — 2026-07-31

- Sin cambios propios: versión de tren.

## [0.5.5] — 2026-07-31

- Sin cambios propios: versión de tren.

## [0.5.4] — 2026-07-31

### Añadido

- **`SchemaFields`: filas declaradas en el esquema (`Field::row`)** — los
  campos de un bloque que compartan nombre de fila (`->row('nombre')` en el
  DSL de core) se pintan JUNTOS en una fila de columnas iguales mientras
  quepan (`auto-fit`), y apilan en el modal angosto (container query
  `modal-body`, como las parejas de alineación). Se resuelve por recursión
  del propio `SchemaFields` (los compañeros bajan sin `row`). La fila
  declarada es intención explícita del esquema: GANA a las convenciones
  implícitas (`_align` junto a su campo, grupo de imagen). Lo estrenan los
  bloques del motor: `related` (Entidad+Modo y Botón+Texto del botón) y
  `cta` (Texto+Enlace del botón; y Grande+Alineación+Estilo).

### Cambiado

- **`PageBlocks`: los campos «Label: valor» del panel de bloque como UN
  párrafo corrido** (`_manager-card.scss`, `.manager-detail__field`): `dt`
  y `dd` van ahora en línea — el valor arranca en la misma línea que el
  label (tras sus dos puntos) y, al envolver, ocupa el ancho completo por
  debajo, como texto normal (antes el label era columna fija y el valor
  envolvía en su propia columna). El clamp de líneas se conserva pero vive
  en el PÁRRAFO entero (el `-webkit-box` no convive con hijos inline
  sueltos): con labels de una palabra es, en la práctica, el mismo recorte
  de 4 líneas de antes. `is-stacked` (imágenes) sigue apilado como estaba.

## [0.5.3] — 2026-07-30

- Sin cambios propios: versión de tren.

## [0.5.2] — 2026-07-30

- Sin cambios propios: versión de tren.

## [0.5.1] — 2026-07-30

- Sin cambios propios: versión de tren.

## [0.5.0] — 2026-07-30

- Sin cambios propios: versión de tren.

## [0.4.39] — 2026-07-29

### Cambiado

- **`PageBlocks`: el tipo como título del panel y contenido «Label:
  valor»** — el TIPO del bloque deja la sección «Detalles» (que
  desaparece: la etiqueta `details` estrenada en 0.4.38 sale de
  `PageBlocksLabels`; basta dejar de pasarla) y pasa a ser el título del
  panel debajo de Estado, como en los paneles de entidad; y los campos
  del Contenido pasan de label-encima-del-valor a «Label: valor» EN
  LÍNEA (los dos puntos los pone el CSS), salvo las imágenes, que van
  apiladas con la miniatura debajo del label y sin dos puntos.

## [0.4.38] — 2026-07-28

### Añadido

- **`blockPreview` / `firstSentence`** (`content/blockPreview.ts`,
  exportados): preview depurado de un bloque — la PRIMERA FRASE (corta en
  el primer `. ! ? … : ;` o salto de línea, sin HTML) del primer campo con
  contenido en el orden título > subtítulo > contenido (el primer campo de
  texto restante del esquema). Lo usa `PageBlocks` y queda disponible para
  los paneles de los cascarones (que lo truncan por CSS a una línea).
- **`.manager-panel__section`** (`_manager-card.scss`): sección del panel
  derecho (divisoria + kicker + contenido) con el aire justo (`gap:
  $space-2`), reutilizable por cualquier panel.

### Cambiado

- **`PageBlocks`: resumen depurado y panel de bloque en secciones** — el
  resumen de la lista pasa de "primeros 80 caracteres" a `blockPreview`
  (la primera frase completa; card y paneles la truncan por CSS); el panel
  del bloque seleccionado se agrupa en secciones con divisoria + kicker
  (nueva etiqueta `details`, por defecto «Detalles», para la sección del
  tipo) y PIERDE las dos líneas de texto «Entra en el PDF de la página
  Sí» / «Aparece en el índice Sí» (los interruptores de Estado ya cuentan
  eso; las etiquetas `yes`/`no` quedan sin uso pero se conservan en la
  interfaz).
- **`MenuManager`: filas sin icono, sin chip «Oculto» y en UNA línea** —
  fuera el icono de tipo (página/ruta) y el chip «Oculto» (la etiqueta
  `hidden` desaparece de `MenuManagerLabels`: los cascarones deben dejar
  de pasarla); la fila ya no hace wrap en estrecho (la etiqueta encoge con
  elipsis y en muy estrecho baja de cuerpo); el botón de visibilidad se
  pinta en ámbar (`$warning`, relleno) cuando la entrada está oculta — el
  estado se ve por el color del botón.

## [0.4.37] — 2026-07-27

- Sin cambios propios: versión de tren.

## [0.4.36] — 2026-07-26

- Sin cambios propios: versión de tren.

## [0.4.35] — 2026-07-26

- Sin cambios propios: versión de tren.

## [0.4.34] — 2026-07-26

- Sin cambios propios: versión de tren.

## [0.4.33] — 2026-07-26

- Sin cambios propios: versión de tren.

## [0.4.32] — 2026-07-26

- Sin cambios propios: versión de tren.

## [0.4.31] — 2026-07-25

- Sin cambios propios: versión de tren.

## [0.4.30] — 2026-07-25

### Cambiado

- **Form de bloque: el cuadro de arrastre de la imagen YA NO se estira**
  (revierte el estiramiento de 0.4.28 en `_page-blocks.scss`): fuera la
  regla que le daba `flex: 1; width: 100%` a `.image-upload__zone` — el
  cuadro conserva su tamaño natural (160×160). La columna de la imagen
  sigue llenando el alto de la fila (`stretch` + `flex: 1` hasta
  `.image-upload`), y en ese espacio el cuadro se centra en vertical y
  horizontal — el centrado vive en el componente de `@edc-motor/ui` y
  aplica a todos los inputs de imagen.

## [0.4.29] — 2026-07-24

- Sin cambios propios: versión de tren.

## [0.4.28] — 2026-07-22

### Cambiado

- **Panel de bloque: PDF/Índice a su propia sección "Estado"**
  (`PageBlocks`): los interruptores dejan el bloque de acciones (editar,
  borrar) y bajan a una sección propia debajo, con su divisoria y
  titulito (patrón panel, igual que páginas y usuarios del cascarón) —
  nueva etiqueta `stateKicker` en `PageBlocksLabels` (por defecto
  "Estado"; RUPTURA suave: un consumidor con labels parciales puede
  pasarla o quedarse con el fallback).
- **Alineación junto a su campo: arriba, no al fondo, y más estrecha**
  (`SchemaFields`, `_page-blocks.scss`): `.schema-fields__field--row` pasa
  de `align-items: flex-end` a `flex-start` — con un textarea alto (p. ej.
  el subtítulo) el select de alineación quedaba descolgado al fondo de la
  fila; ahora las etiquetas de ambos campos quedan a la misma altura.
  `.schema-fields__field-align` baja de 180 a 140px (sobraba aire para
  "Centrado"/"Izquierda" + etiqueta en es/en/eu).
- **`ImageUpload` se estira dentro del grupo de imagen a dos columnas del
  form de bloque** (`_page-blocks.scss`): `.schema-fields__field--image-
  group` pasa de `align-items: start` a `stretch` y la cadena de
  contenedores hasta la dropzone (`@edc-motor/ui`) lleva `flex: 1`, así
  que la columna de la imagen llena el alto de la fila (a la del select
  de ajustes, si es más alta). SUELTO (form-modals normales,
  `TranslatableImage`) el componente no vive en esta cadena: conserva su
  tamaño natural de 160×160.

## [0.4.27] — 2026-07-21

### Cambiado

- **Formulario de bloque reorganizado** (`PageBlocks` + `SchemaFields`):
  la sección "General" sube ARRIBA del todo (fila 1: anchura + bloque
  padre; fila 2: los interruptores PDF/índice; fila 3: color de fondo +
  alineación general); cada alineación se pinta JUNTO a su campo —
  `SchemaFields` empareja genéricamente un `<base>_align` con su `<base>`
  (o `<base>_text`) de la misma lista, y `PageBlocks` inyecta los
  `title_align`/`subtitle_align` comunes junto a los campos del tipo —; la
  imagen y sus ajustes (posición, escalado, reparto) se agrupan a DOS
  columnas; y el modal de bloque abre en la talla `wide` nueva de
  `EditModal` (940px). En un modal angosto todo apila (container
  `modal-body` nuevo en el modal del ui).
- **Asas e interacción de las listas**: el asa (`GripVertical`) se colorea
  de ACENTO al pasar el ratón (menú y bloques; las cards de páginas del
  cascarón igual); cursor `pointer` en filas que seleccionan panel
  (bloques) y `grab`/`grabbing` en las que solo se arrastran (menú).

## [0.4.26] — 2026-07-21

- Sin cambios propios: versión de tren.

## [0.4.25] — 2026-07-20

### Cambiado

- **`MenuManager` rediseñado**: fuera los grupos (una página madre hace de
  desplegable); la jerarquía sale SIEMPRE de las páginas del CRM y una ruta
  puede colgar de una página raíz. Trabaja sobre una copia LOCAL del árbol
  — flechas, drag & drop nativo (asa `GripVertical`; soltar entre filas
  reordena, soltar ENCIMA de una página raíz anida, al hueco raíz saca) y
  el interruptor de visibilidad solo mutan el estado local; NADA se guarda
  hasta pulsar "Guardar" (un único `PUT /admin/menu`), con "Descartar" y
  aviso de cambios sin guardar. Nueva prop `displayLocale`: los títulos en
  el idioma actual del admin, con fallback al primer valor no vacío.
- **`PageBlocks`: anidado en VARIOS niveles y `displayLocale`**: el drag &
  drop (ahora nativo — la dependencia `vue-draggable-plus` se retira del
  paquete) admite soltar ENCIMA de cualquier fila para anidar sin límite de
  niveles (solo se prohíbe uno mismo/un descendiente), moviendo el subárbol
  entero y persistiendo al momento; filas sangradas por profundidad real
  (`--depth`) y el select "Bloque padre" excluye descendientes con prefijo
  por nivel. Los resúmenes y textos traducibles se pintan en el
  `displayLocale` del admin.

## [0.4.24] — 2026-07-20

### Añadido

- **`MenuManager`** (`content/MenuManager.vue`, doc 10 ampliado): configurador
  del menú de la web pública sobre `/api/admin/menu*`. Filas tipo PageBlocks
  (sin drag): icono según tipo (página/ruta/grupo), etiqueta, badges "Oculto"
  y "Borrador", subir/bajar dentro de su nivel, interruptor de visibilidad
  (`.is-on`/`.is-off`), select de grupo (— Raíz — o un grupo existente) y, en
  grupos, editar label (modal con `TranslatableInput`) y borrar (con
  confirm). Botón "Nuevo grupo" arriba. Agnóstico de i18n (DC-29): todos los
  textos por prop (`labels`) y `routeLabels` (etiqueta de cada `route_key`)
  los pone el juego. SCSS nuevo en `scss/components/_menu-manager.scss`.

## [0.4.23] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.22] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.21] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.20] — 2026-07-19

- Sin cambios propios: versión de tren.

## [0.4.19] — 2026-07-19

### Cambiado

- **"Ajustes comunes" del formulario de bloque SIEMPRE visible**: deja de
  ser un `<details>` plegado — es una sección fija al fondo del formulario
  con su titulito discreto, así la alineación/anchura/fondo y los flags no
  pasan desapercibidos.

## [0.4.18] — 2026-07-19

### Corregido

- **Action-buttons del panel derecho: texto legible al rellenarse**: en
  hover y en los interruptores `.is-on`, el color del texto lo decide el
  FONDO (mixin `contrast-text` de los tokens del ui) en vez de `$text-1` —
  en tema oscuro el texto casi blanco no se leía sobre warning/success.

## [0.4.17] — 2026-07-19

### Corregido

- **Cards sin badges ni meta, sin parte inferior vacía** (`EntityCard` y
  `ManagerCard`): los slots se evalúan por su CONTENIDO real (helper
  `slotHasContent`), no por si el padre declara el `<template #…>` — con
  todo v-if falso o un v-for vacío dentro, la zona inferior (padding +
  hueco) ya no se pinta. Además, cuando la cabecera es lo último de la
  card, su divisoria desaparece y el padding inferior se iguala al resto.

## [0.4.16] — 2026-07-19

### Añadido

- **Helpers de subida de imagen DIFERIDA** (`content/deferredImages`):
  `uploadContentImage(api, file)` (POST `/admin/content/uploads` → URL),
  `deleteContentImage(api, url)` (borrado silencioso),
  `uploadPendingImages(api, fields, value, uploaded)` (resuelve los `File`
  pendientes de unos settings según su esquema, recursivo en
  group/repeater; va apuntando las URLs nuevas en `uploaded` para poder
  deshacerlas si el guardado falla) y `collectImageUrls(fields, value)`
  (URLs de imagen presentes en unos settings). Los usan `PageBlocks` y las
  vistas del cascarón (Ajustes y form de página).

- **`PageBlocks` refinado**: botón de EDITAR a la IZQUIERDA de cada fila de
  bloque (abre el form sin pasar por el panel); la paleta de "añadir bloque"
  se cierra con Escape y clicando fuera; click en la zona vacía del
  contenido DESELECCIONA el bloque activo (`useCardDeselect`, como los
  index); el título del panel derecho es el TIPO del bloque (no su
  contenido); los checkboxes "entra en el PDF" y "aparece en el índice"
  pasan a botones-interruptor del bloque de acciones, con su estado en
  TEXTO en el panel y badges en la fila. `PageBlocksLabels` gana
  `printableShort`, `indexableShort`, `yes` y `no`.
- **Botones-interruptor del panel derecho**: en `.manager-detail__actions`,
  un `edc-button` con `.is-on` se pinta RELLENO de su color (estado
  activado) y con `.is-off` el contorno se atenúa — mismo contorno/hover que
  los botones de acción del panel, con el on/off visible de un vistazo.

- **Preset `cards-dense` de `BaseGrid`**: el DOBLE de columnas que `cards` en
  todos los breakpoints del contenedor `content` — 2 → 4 → 6 → 8 → 10 a
  base/480/768/1024/1280px. Para piezas pequeñas (el gestor de iconos del
  cascarón lo usa, listando TODOS los iconos sin paginación).
- **`ManagerCard` gana el slot `badges`**: como en `EntityCard`, los chips de
  estado van ARRIBA y el meta (datos secundarios) debajo, dentro de un
  `__content` común; la cabecera gana la divisoria de `EntityCard`.

### Cambiado

- **Imágenes de bloque diferidas al GUARDAR** (`SchemaFields` +
  `PageBlocks`): los campos `image` (simples y traducibles) ya no suben al
  elegir — el `File` queda en los settings del formulario y `PageBlocks` lo
  resuelve en el submit: sube los pendientes, persiste el bloque con las
  URLs y SOLO tras guardar en firme borra del disco las imágenes que el
  bloque ya no referencia (robusto ante filas de repeater reordenadas). Si
  el guardado falla se deshacen las subidas nuevas, y CANCELAR el modal no
  deja rastro en el servidor (sin huérfanos). La prop `api` de
  `SchemaFields` queda para las opciones de los campos `entity`.
- **`FilterBar`: la lupa pasa a la IZQUIERDA del input** y el texto
  (placeholder y valor) empieza a su derecha (padding-left de 34px), sin
  montarse con el icono.
- **Gestores de previews y PDF sin arranque vacío**: al cargar se selecciona
  la PRIMERA tarjeta (tipo/export) — sin abrir el panel en móvil — y el
  combobox del panel arranca con su primer elemento elegido (en las previews
  al cargar la primera página; en PDF, la primera entidad dueña de los
  exports por entidad).
- **Criterio de los selects del admin**: sin un orden explícito, las opciones
  salen en orden ALFABÉTICO. Aplicado en ambos gestores: tarjetas por
  etiqueta traducida (`typeLabels`), combobox de elementos de previews por
  `label` (el servidor manda por id) y combobox de dueñas de PDF por `label`.
- **`.manager-grid` escala como el preset `cards`**: 1 → 2 → 3 → 4 → 5
  columnas a 480/768/1024/1280px del contenedor `content` (antes 1 → 2).

### Corregido

- **Doble separador en los paneles derechos**: cuando una sección
  `.manager-detail` sigue a un `manager-panel__divider` (p. ej. el Contenido
  del bloque seleccionado o los Bloques de la página), su divisoria dashed
  propia — la línea de "puntos suspensivos" — sobraba y se quita; queda solo
  el divider del panel.

## [0.4.15] — 2026-07-17

### Cambiado

- **Card seleccionada más evidente** (`EntityCard` y `ManagerCard`): el
  borde pasa al acento del tema y se dobla con un anillo de 1px (box-shadow,
  sin mover el layout), más una sombra suave del acento. Con `accentColor`
  (tinte de facción), la selección manda: la card seleccionada va SIEMPRE
  con el acento del tema, no con el color de la entidad.

## [0.4.14] — 2026-07-16

### Cambiado

- **El preset `cards` deja la escalera densa por los breakpoints canónicos**
  del contenedor `content` — 2/3/4/5 columnas a 480 (`$bp-sm`) / 768
  (`$bp-md`) / 1024 (`$bp-lg`) / 1280 (`$bp-xl`): la escalera densa de
  0.4.13 dejaba las tarjetas demasiado estrechas.

## [0.4.13] — 2026-07-16

### Cambiado

- **`BaseGrid` hasta cinco columnas en el preset `cards`**: el sistema
  genérico de breakpoints del grid gana el escalón `xl` (`$bp-xl`, 1280px
  de ancho REAL del contenedor `content`, como los demás) — `cols` acepta
  `{ xl: n }` — y el preset `cards` escala 1 → 2 → 3 → 4 → **5** con una
  escalera densa medida sobre el contenedor (3/4/5 a 570/660/750px). El
  resto de presets, como estaban.

## [0.4.12] — 2026-07-15

- Sin cambios propios: versión de tren.

## [0.4.11] — 2026-07-15

### Añadido

- **`NavGroup`**: grupo plegable para el slot `#nav` del `AdminLayout`,
  mezclable con nav-item sueltos. Cabecera-botón (slot `icon` + `label` +
  chevron que rota) que despliega/pliega sus hijos con una animación
  discreta (`grid-template-rows`), `aria-expanded`/`aria-controls` y manejo
  nativo de teclado. El plegado se persiste en `localStorage` por
  `storageKey` (`edc_admin_nav_<clave>`, por defecto plegado) y con la prop
  `active` (la ruta actual es de un hijo, la app la calcula igual que el
  `active` de sus nav-item) la cabecera se resalta y el grupo se
  auto-despliega (sin persistir: solo los toggles del usuario guardan
  preferencia). Con el sidebar colapsado a carril de iconos los hijos se
  muestran siempre y la cabecera queda inerte (plegar sin etiquetas
  ocultaría rutas).
- **`accentColor` en `EntityCard`**: borde teñido con el color de la
  entidad (p. ej. su facción, de los datos del juego). En reposo nunca va
  puro — `color-mix` al 45 % con el `$border` del tema, sutil en claro y
  oscuro —, al hover sube al 75 % sobre `$border-strong` y en la tarjeta
  seleccionada (`is-active`) es pleno. Sin la prop, todo exactamente como
  antes.

### Arreglado

- **`AdminLayout`**: en móvil el drawer del menú se cerraba con CUALQUIER
  click dentro de la lista; ahora solo al tocar un enlace (los toggles de
  `NavGroup` no navegan y deben dejarlo abierto).

## [0.4.10] — 2026-07-14

### Cambiado

- **Retoques del `EntityCard`**: la franja `__media` pierde el fondo
  (`$surface-2`) — la imagen contenida se apoya directamente sobre la
  superficie de la tarjeta; el lápiz de editar (`editable`) pasa a verde
  (`$success`, con hover de fondo semitransparente al estilo de los
  icon-btn del ui); y header y content respiran menos entre sí
  (`padding-bottom` del header y `padding-top` del content bajan un
  escalón, de `$space-3` a `$space-2`).

## [0.4.9] — 2026-07-13

### Añadido

- **`useCardDeselect(onDeselect, extraIgnore?)`**: deselección de la card
  activa clickando la zona "vacía" del cuerpo de la vista (huecos del grid,
  espacio bajo las cards, alrededor del toolbar…). Escucha en document y
  solo actúa dentro de `.main-content`; ignora los clicks que nacen en una
  card (`.manager-card`/`.entity-card`), un control interactivo o las
  migas; `extraIgnore` añade selectores propios de la vista.
- **`.manager-panel__back`**: botón-volver de texto con flecha para el
  panel derecho (del detalle de la card seleccionada a los filtros del
  listado).

### Cambiado

- **Index de entidades a 4 columnas**: el preset `cards` del `BaseGrid`
  pasa de `{base:1, sm:2, lg:3}` a `{base:1, sm:2, md:3, lg:4}` — escala
  1 → 2 → 3 → 4 con el ancho real del contenedor `content`.
- **`.entity-card__media` cuadrada**: `aspect-ratio` de `16/9` a `1/1` y la
  imagen de `object-fit: cover` a `contain` — con tarjetas más estrechas la
  imagen queda contenida y centrada (más bien pequeña), nunca deformada ni
  recortada.

## [0.4.8] — 2026-07-13

### Cambiado

- **`FilterBar` compacto**: la caja de búsqueda pasa a `$input-height`
  (36px) y padding 10px, alineada con los nuevos tokens compactos del ui.

## [0.4.7] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.6] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.5] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.4] — 2026-07-12

- Sin cambios propios: versión de tren.

## [0.4.3] — 2026-07-11

### Añadido

- `EntityCard`: prop `editable` + evento `edit` — botón de lápiz integrado en
  la cabecera para editar desde la propia tarjeta (pensado para entidades sin
  vista single). `editLabel` opcional para el texto accesible (DC-29).

### Cambiado

- Convención de la franja `media` de `EntityCard`: solo para entidades con
  imagen o preview; las entidades sin imagen (taxonomías) no llevan emblema.

## [0.4.0] — 2026-07-07

### Añadido

- `PageBlocks`: **bloque padre** en el formulario (anidado de un nivel),
  tarjetas hijas **indentadas** bajo su padre (y recolocadas tras el drag), y
  slot **`#panel-default`** para que la vista pinte su propio panel (p. ej.
  las acciones de la página) cuando no hay bloque seleccionado.

### Corregido

- Aire entre el selector de color de fondo y los checkboxes de los ajustes
  comunes del bloque.

## [0.3.1] — 2026-07-07

- Sin cambios propios: versión de tren (fix de subida de SVG en `edc-motor/core`).

## [0.3.0] — 2026-07-07

### Cambiado

- `SchemaFields` (imágenes de bloques): al sustituir una imagen se manda
  `replaces` y el backend borra la anterior; el botón "quitar" borra la
  subida del disco.

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

- **Listados (DC-30)**: `FilterBar` (búsqueda + filtros), `BaseTabs`,
  `BaseGrid` (responsive por `@container`), `EntityCard` (badges/meta/media,
  clicable), `EmptyState`.
- **Edición**: `EditModal`, `useResource` (CRUD por slug + restore/force +
  toggle), `fieldErrors` de validación, `SearchSelect` (combobox con
  buscador en servidor o filtro en cliente).
- **Gestores**: `PreviewManager` (imágenes PNG por tipo: lotes, por entidad,
  huérfanos), `PdfManager` (catálogo de exports: estado por idioma,
  generar/regenerar/descargar/borrar, por-entidad con combobox),
  `PageBlocks` (árbol de bloques reordenable con vue-draggable-plus) y
  `SchemaFields` (formulario **autorecursivo** del DSL: group, repeater
  con añadir/quitar/mover, entity-ref con `EntityRefSelect`).
- **Layout kontuan**: panel derecho contextual (acciones arriba, contenido
  del elemento), breadcrumbs dinámicas, nav con secciones por permiso.
- `FontUpload` (webfonts del sitio) y utilidades compartidas.
- **Panel derecho estandarizado**: acciones primero + separadores
  (`.manager-panel__divider`) entre secciones, en todos los gestores; los
  botones de acción del panel van en CONTORNO con el color de su acción
  (hover: el color pasa al fondo). Las filas de páginas/bloques no hacen
  wrap: cambian de layout en bloque (container query).
- `PdfManager` con el resumen de las previews (total + listos por idioma) y
  acciones Generar faltantes / Regenerar todo / Borrar todo.
- i18n por props (DC-29): etiquetas vía `labels`/`typeLabels`.
