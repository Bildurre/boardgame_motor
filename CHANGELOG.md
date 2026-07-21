# Changelog — EdC Motor

El motor se versiona **en tren**: `edc-motor/core`, `@edc-motor/ui` y `@edc-motor/admin-kit`
comparten número de versión y se etiquetan juntos con un tag `vX.Y.Z` en este
repositorio. Cada paquete tiene su propio `CHANGELOG.md` con el detalle:

- [`packages/core/CHANGELOG.md`](packages/core/CHANGELOG.md) — backend Laravel (`edc-motor/core`).
- [`packages/ui/CHANGELOG.md`](packages/ui/CHANGELOG.md) — componentes públicos (`@edc-motor/ui`).
- [`packages/admin-kit/CHANGELOG.md`](packages/admin-kit/CHANGELOG.md) — kit del admin (`@edc-motor/admin-kit`).

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/) y el
versionado, [SemVer](https://semver.org/lang/es/) (mientras estemos en `0.x`,
los cambios de API pueden llegar en versiones menores).

## [Sin publicar]

- **Bloques: tipografía más grande, negritas en acento, índice anidado y
  layout de imágenes afinado** (`edc-motor/core` + `@edc-motor/ui`): toda la
  tipografía de bloques sube ~×1.125 para que el texto base sea de 18px
  (nuevos tokens $fs-36/$fs-40); las negritas del wysiwyg van en color de
  acento; el índice numerado usa numeración anidada (1, 1.1, 1.2.1…); los
  bloques con imagen pasan a vertical bajo 768px del contenedor con la
  imagen SIEMPRE encima del texto, las flotadas dejan 16px de margen con el
  texto, y en columnas el título y el subtítulo van siempre a ancho completo
  (el grid es solo imagen ↔ contenido); la etiqueta de la tarjeta de texto
  pierde el chip (texto en acento con alineación propia, nuevo campo
  `label_align`) y su tarjeta gana el halo de acento del CTA.
- **Las imágenes junto al texto, ancladas arriba** (`@edc-motor/ui`):
  mientras el bloque no esté en vertical, la imagen va SIEMPRE arriba — en
  columnas el escalado "contain"/"cover" ancla la imagen al borde superior
  del marco (antes "contain" la centraba), y las flotadas (clear) quedan
  fijadas arriba del texto que las rodea.

## [0.4.25] — 2026-07-20

- **Rediseño del gestor de menú: sin grupos, jerarquía del CRM y guardado
  en local** (`edc-motor/core` + `@edc-motor/admin-kit` + cascarón): fuera
  los grupos — una página madre hace de desplegable —; el anidado del menú
  deriva SIEMPRE de `pages.parent_id`, bidireccional (mover una página bajo
  otra en el gestor escribe su padre en el CRM y viceversa; las rutas
  también pueden colgar de una página raíz); el gestor trabaja en LOCAL con
  drag & drop nativo + flechas y nada se persiste hasta pulsar Guardar (un
  único PUT con el árbol entero; Descartar revierte; aviso de cambios sin
  guardar). Migración `2026_07_20_000002` (con guardas) limpia los grupos
  de quien migró la 0.4.24.
- **Drag & drop también en páginas y bloques del CRM** (`@edc-motor/
  admin-kit` + cascarón): las cards de páginas se reordenan arrastrando y
  se anidan soltando una encima de otra (un nivel, con salida a raíz y
  señal visual de la jerarquía; persistencia inmediata); los bloques ganan
  anidamiento SIN LÍMITE de niveles arrastrando encima de otra fila — el
  subárbol viaja entero, filas sangradas por profundidad y el índice
  automático refleja la profundidad real con su sangría por nivel.
- **Los textos traducibles del admin, en el locale actual**: cards y panel
  de páginas, filas de bloques y gestor de menú pintan título/resumen en el
  idioma activo del admin, con fallback al primer valor no vacío.
  **Migración del cascarón**: copiar `plantilla/admin/src/views/menu/MenuView.vue`,
  `views/pages/PagesListView.vue`, `views/pages/PageSingleView.vue`,
  `assets/scss/views/_pages.scss` y `plantilla/app/src/components/AppHeader.vue`;
  actualizar las claves i18n del menú (fuera las de grupos, dentro las de
  guardar/descartar/cambios y las del drag & drop de páginas) y ejecutar
  `php artisan migrate`.

## [0.4.24] — 2026-07-20

- **Páginas reordenables desde el admin** (`plantilla/admin` + `playground/admin`):
  el panel derecho de la página seleccionada trae ahora "Subir"/"Bajar" —
  intercambia con la hermana anterior/siguiente (mismo `parent_id`) contra el
  endpoint `POST /admin/pages/reorder` que ya existía en el motor.
- **Menú de la web configurable desde el admin** (`edc-motor/core` +
  `@edc-motor/admin-kit` + cascarón): un menú de navegación de la web pública
  que mezcla páginas del CRM y "rutas" propias del juego (índices de
  entidades, descargas…), reordenable, con visibilidad por item y agrupable
  bajo carpetas. Backend: tabla `menu_items`, `MenuSync` (mantiene un item por
  página no-home y por cada `motor.menu.routes` del juego, añade al final y
  retira huérfanos), endpoints admin (`/api/admin/menu*`) y público
  (`GET /api/menu`, cacheado como `pages/nav` y con la misma invalidación).
  Admin: `MenuManager` (admin-kit), filas con subir/bajar, visibilidad,
  select de grupo y gestión de grupos (crear/editar label/borrar). App:
  `AppHeader.vue` consume `/api/menu` en vez de `/pages/nav` (que sigue vivo,
  retrocompatible); los grupos reutilizan el patrón de submenú al hover que
  antes tenían las páginas con hijas.
  **Migración del cascarón** — ver `packages/core/CHANGELOG.md` y
  `packages/admin-kit/CHANGELOG.md` para el detalle de ficheros a copiar; en
  resumen, cada juego debe:
  - Declarar `motor.menu.routes` en su `api/config/motor.php` (las claves
    de sus `entitySections` + las suyas propias, p. ej. `downloads`).
  - Copiar `admin/src/views/menu/MenuView.vue`, sumar la ruta `/menu` (en
    `router/i18n-paths.ts`) y la entrada de navegación (grupo "La web", en
    `App.vue`, icono `ListTree`), con su propio mapa `routeLabels`.
  - Copiar las claves i18n nuevas del admin: `nav.menu`, `routes.menu`,
    `breadcrumbs.menu`, `pages.moveUp`, `pages.moveDown` y la sección
    `menu.*` completa (`title`, `hint`, `newGroup`, `newGroupTitle`,
    `editGroupTitle`, `groupLabel`, `confirmDelete`, `empty`, `root`,
    `hidden`, `visible`, `group`) en `es`/`en`/`eu`.
  - Sustituir en `app/src/components/AppHeader.vue` el fetch a
    `/pages/nav` por `/menu` y construir el mapa route_key → ruta + etiqueta
    (entitySections + descargas de tu juego; una clave sin mapear se omite
    sola, sin romper el resto del menú).
  - Ejecutar `php artisan migrate` (tabla nueva `menu_items`).
- **Cita en peso 600 e índice automático con jerarquía tipográfica**
  (`edc-motor/core` + `@edc-motor/ui`): el texto de la cita pasa a peso
  600; el índice va en peso 500 con tamaños por nivel (24/22/20px, el
  tercero para nivel 3 o más) y sus entradas se etiquetan por TÍTULO del
  bloque > subtítulo > primer contenido truncado (de un wysiwyg, solo el
  texto de su primera etiqueta).

## [0.4.23] — 2026-07-19

- **CTA con imagen en estrecho: siempre arriba, a sangre y en 2:1**
  (`@edc-motor/ui`): da igual dónde esté posicionada — en estrecho la
  imagen sube arriba, llega a los bordes de la tarjeta y se recorta a
  proporción 2:1.
- **La alineación de título/subtítulo ahora sí se aplica** (`@edc-motor/ui`):
  faltaba `width: 100%` (en el cuerpo del CTA el elemento encogía a su
  contenido y el `text-align` no se veía), y en formato estrecho la
  alineación elegida se revierte a la izquierda, sea la que sea.

## [0.4.22] — 2026-07-19

- **Cita: el tamaño grande ahora sí se aplica, y el autor a la derecha sin
  guion** (`edc-motor/core` + `@edc-motor/ui`): el `.rich-content` interior
  machacaba el $fs-32 de la cita con su cuerpo de 16px (ahora hereda);
  el autor pierde el "—" delante y su alineación por defecto pasa a la
  derecha.

## [0.4.21] — 2026-07-19

- **Más aire dentro de los bloques** (`@edc-motor/ui`): nuevo token
  `$block-gap` (24px) que unifica la separación entre los elementos de un
  bloque (antes 20/16/12px según la zona), y el botón del CTA gana margen
  extra encima — su hueco casi dobla la separación normal.

## [0.4.20] — 2026-07-19

- **Bloques: título, subtítulo y autor con alineación propia, y cita
  rediseñada** (`edc-motor/core` + `@edc-motor/ui`): título y subtítulo
  ganan alineación PROPIA en los ajustes comunes (izquierda/centrado/
  derecha, con "La del bloque" por defecto — lo guardado no cambia), que
  manda sobre la del bloque; y el bloque de CITA pierde el adorno del borde
  izquierdo, sube el texto a $fs-32 (el token más cercano a ~40px) en color
  de acento, y el autor va en cursiva con alineación propia (nuevo campo
  `author_align`).

## [0.4.19] — 2026-07-19

- **Editor de bloques: subtítulos multilínea, justificado por defecto y CTA
  con botón a medida** (`edc-motor/core` + `@edc-motor/ui` +
  `@edc-motor/admin-kit`): el subtítulo de todos los bloques pasa a
  TEXTAREA y el render respeta sus saltos de línea; la alineación por
  defecto de los bloques es JUSTIFICADO (los guardados con alineación
  explícita no cambian); el CTA gana "Alineación del botón"
  (izquierda/centrado/derecha) y "Botón grande" (más padding interior); en
  formato estrecho los botones de bloque (CTA, related…) van SIEMPRE
  centrados; la tarjeta del CTA gana un halo sutil del color de acento,
  igual por los cuatro bordes; y los "Ajustes comunes" del formulario dejan de ser un
  desplegable — sección fija, siempre visible, al fondo. **Migración del
  juego**: si tiene bloques propios con subtítulo
  (`Field::text('subtitle')`), pasarlos a `Field::textarea('subtitle')`.

## [0.4.18] — 2026-07-19

- **Botones rellenos con texto legible SIEMPRE** (`@edc-motor/ui` +
  `@edc-motor/admin-kit`): nuevo mixin `contrast-text($bg)` en los tokens —
  el navegador elige texto claro u oscuro según la luminosidad real del
  fondo (relative color syntax; fallback oscuro sin soporte). Lo usan los
  `edc-button` rellenos (primary y semánticos, con sus hovers), los
  `block-button` de la web pública y los action-buttons del panel derecho
  del admin al rellenarse (hover / interruptor encendido): se acabó el texto
  fijado a mano que no contrastaba con acentos claros o en tema oscuro.
- **El bloque "Relacionados" pierde el selector de número y su grid sale
  siempre completo** (`edc-motor/core` + `@edc-motor/ui`): el bloque trae
  SIEMPRE 6 elementos y el grid enseña los que caben en filas completas
  según el ancho — 4 en 2×2 (estrecho), 6 en 3×2, 4 en 4×1 y 5 en 5×1
  (breakpoints 768/1024/1280); el resto se oculta. El `count` de bloques ya
  guardados se ignora y se descarta solo al volver a guardar (la validación
  deriva del esquema). Los índices del catálogo siguen con su 2 → 3 → 4.

## [0.4.17] — 2026-07-19

- **Cards sin badges ni meta, sin parte inferior vacía**
  (`@edc-motor/admin-kit`): `EntityCard` y `ManagerCard` evalúan el
  contenido REAL de sus slots — declarar el template con todo v-if falso ya
  no deja la zona inferior vacía, y sin nada bajo la cabecera la divisoria
  desaparece.

## [0.4.16] — 2026-07-19

- **Inputs de imagen de los formularios: la actual a la vista y guardado
  DIFERIDO** (`@edc-motor/ui` + `@edc-motor/admin-kit` + `edc-motor/core` +
  cascarón): al abrir un formulario de edición el input muestra SIEMPRE la
  imagen que ya tiene la entidad (miniatura + nombre del fichero; el gestor
  de iconos la enseñaba vacía), y elegir o quitar una imagen ya NO toca el
  servidor hasta pulsar GUARDAR — el `File` se retiene en el estado del
  formulario (vista previa por object URL) y viaja con el submit; cancelar
  no deja ni cambios ni ficheros huérfanos. Aplica en todos los flujos: las
  entidades del juego siguen en multipart (con el nuevo `remove_image` del
  trait `HasImage` para quitar, también diferido); el fondo de página, el
  logo/favicon de Ajustes y las imágenes de bloques del CRM (simples y
  traducibles) se suben en el submit con los helpers nuevos del admin-kit
  (`uploadContentImage`/`uploadPendingImages`…), que además borran del disco
  lo sustituido/quitado SOLO tras guardar en firme y deshacen las subidas si
  el guardado falla. RUPTURA en `TranslatableImage`: su `v-model` pasa a
  `Record<string, string | File>` y desaparecen las props
  `upload`/`removeFile`. **Migración del cascarón**: copiar
  `plantilla/admin/src/views/settings/SettingsView.vue`,
  `plantilla/admin/src/components/pages/PageFormModal.vue` y
  `plantilla/admin/src/views/icons/IconsListView.vue`; en los form-modals de
  entidades PROPIOS del juego, pasar la imagen actual al `ImageUpload`
  (`:current-url`) y diferir el quitar (flag en `@remove` que añade
  `remove_image=1` al FormData del guardar — el backend ya lo entiende sin
  tocar nada, lo resuelve el trait); cualquier uso propio de
  `TranslatableImage` debe dejar de pasarle `upload`/`removeFile` y resolver
  los `File` pendientes al guardar (helpers de `@edc-motor/admin-kit`).

- **Páginas y bloques: interruptores en el panel y bloques más manejables**
  (`@edc-motor/admin-kit` + cascarón): los checks de publicada/imprimible
  del panel derecho (listado y single) pasan a BOTONES-INTERRUPTOR arriba,
  junto a las acciones (contorno de su color; encendido = relleno), y la
  card del listado gana el label "imprimible" (mantiene publicada/borrador).
  En el índice de bloques del single: botón de EDITAR a la izquierda de cada
  fila, la paleta de "añadir bloque" se cierra con Escape/click fuera, click
  en la zona vacía deselecciona el bloque (patrón de los index), el título
  del panel del bloque es su TIPO, y los checks "entra en el PDF"/"aparece
  en el índice" pasan a interruptores con su estado en texto y badges en la
  fila. Fuera el doble separador ("puntos suspensivos") de las secciones del
  panel. **Migración del cascarón**: copiar
  `plantilla/admin/src/views/pages/PagesListView.vue` y
  `PageSingleView.vue`, y añadir las claves i18n nuevas (`common.yes/no`,
  `pages.printable`, `pages.blocks.printableShort/indexableShort`).

- **Usuarios: cards como las de entidades y verificado como interruptor**
  (cascarón): la card reordena al lenguaje EntityCard/ManagerCard — BADGES
  (rol + verificado/sin verificar) arriba y el email en el meta debajo — en
  el grid preset cards (1 → 5); el check de "email verificado" pasa a
  botón-interruptor en el panel (usa el endpoint `toggle-verified` del
  core), con badge en la card y su estado en texto en la barra derecha.
  **Migración del cascarón**: copiar
  `plantilla/admin/src/views/users/UsersView.vue` y añadir la clave i18n
  `users.verifiedBadge`.

- **Copias de seguridad: asíncronas, con origen, subida y RESTAURAR**
  (`edc-motor/core` + cascarón): crear una copia ya NO bloquea la web — va
  SIEMPRE en cola (`RunBackupJob`, 202; con cola `sync`, diferida tras la
  respuesta) y el gestor refleja el estado con el flag `pending` (sondeo sin
  bloquear). Cada copia lleva badge de ORIGEN (manual/automática/subida,
  derivado del prefijo del nombre). Nueva card "Subir copia" al lado de la
  de copia automática (en columna en estrecho): importa un zip validado
  (extensión, tamaño, BBDD dentro) vía `POST api/admin/backups/upload`. Y
  acción RESTAURAR en la barra derecha con DOBLE confirmación:
  `POST api/admin/backups/{file}/restore` importa la BBDD del zip machacando
  la actual (fichero SQLite tal cual o dump SQL; solo BBDD — el storage no
  se restaura — y puede cerrar la sesión; límites documentados en el propio
  panel). **Migración del cascarón**: copiar
  `plantilla/admin/src/views/backups/BackupsView.vue`,
  `plantilla/admin/src/assets/scss/views/_backups.scss`,
  `plantilla/api/tests/Feature/BackupsTest.php` y añadir las claves i18n
  nuevas de `backups.*`; si el juego usaba `MOTOR_BACKUP_QUEUE`, la clave
  desaparece (ya no hay modo síncrono).

- **La lupa de los buscadores pasa a la IZQUIERDA** (`@edc-motor/ui` +
  `@edc-motor/admin-kit`): en `IndexToolbar` y en el `FilterBar` del admin el
  icono va al lado izquierdo del input y el texto (placeholder y valor)
  empieza a su derecha, con hueco propio (padding-left): nada se monta.

- **Paleta nueva del `PaletteColorPicker`** (`@edc-motor/ui`; lo usan el color
  de facción, los colores de acento de Ajustes y los campos `color` de los
  bloques): espectro cálido → frío — `#f15959`, `#f1753a`, `#88b033`,
  `#29ab5f`, `#31a28e`, `#3999cd`, `#408cfd`, `#7a64c8`, `#a75da5` — y el
  gris al final (el `#64748B` heredado). El swatch de valor libre (custom) se
  conserva.

- **Cambio de idioma GLOBAL en los formularios traducibles** (`@edc-motor/ui`):
  los campos traducibles conservan sus tabs de locale, y `EditModal` estrena
  en su cabecera un selector compacto (`FormLocaleSwitch`, solo si el
  formulario contiene campos traducibles) que cambia el tab de TODOS a la
  vez. Mecánica provide/inject (`provideFormLocale()` /
  `useFormLocaleField()`): los `TranslatableInput`/`TranslatableImage` se
  suscriben solos (también dentro de `SchemaFields`/`PageBlocks`), así los
  juegos no tocan nada; el tab individual de cada campo sigue siendo local.

- **Gestor de iconos con grid denso y sin paginación** (`@edc-motor/admin-kit`
  + cascarón): nuevo preset `cards-dense` de `BaseGrid` — el DOBLE de
  columnas que `cards` en todos los breakpoints (2 → 4 → 6 → 8 → 10) — y el
  index de iconos lo usa; se listan TODOS los iconos (el endpoint `/icons` ya
  venía sin paginar). **Migración del cascarón**: copiar
  `plantilla/admin/src/views/icons/IconsListView.vue` (solo cambia el preset
  del grid).

- **Gestores de previews y PDF: arranque sin vacíos, selects alfabéticos y
  cards al estilo EntityCard** (`@edc-motor/admin-kit`): al cargar se
  selecciona la PRIMERA tarjeta (tipo/export) y el combobox del panel arranca
  con su primer elemento elegido; criterio general de los selects del admin —
  sin un orden explícito, opciones ALFABÉTICAS (tarjetas y combobox de ambos
  gestores); `ManagerCard` adopta el lenguaje visual de `EntityCard`
  (cabecera con divisoria, badges arriba y meta debajo — nuevo slot `badges`)
  y `.manager-grid` escala 1 → 5 columnas como el preset `cards`.

## [0.4.15] — 2026-07-17

- **Card seleccionada más evidente en el admin** (`@edc-motor/admin-kit`):
  borde en el acento del tema doblado con un anillo + sombra suave, también
  cuando la card lleva tinte de facción (`accentColor`): la selección manda.

- **Herramientas: el flujo de ramas vuelve a ser `claude.sh` y la release se
  protege.** `claude.sh` (raíz del monorepo, `plantilla/` y los juegos):
  `--start` trae la rama o LA CREA desde main si no existe en remoto;
  `--finish` mergea la punta REMOTA de la rama en main (da igual si la copia
  local está desfasada), pushea y borra la rama local y remota.
  `tools/release.sh` gana un candado anti-"tagear antes de mergear" (nos
  pasó en 0.4.7, 0.4.9 y 0.4.12): aborta si alguna rama remota tiene
  commits que main no tiene, listándolas; se salta a sabiendas con
  `RELEASE_PERMITIR_RAMAS=1`. **Migración del cascarón**: copiar
  `claude.sh` de `plantilla/`.

## [0.4.14] — 2026-07-16

- **El grid de entidades del admin vuelve a los escalones canónicos**
  (`@edc-motor/admin-kit`): el preset `cards` pasa a 2/3/4/5 columnas a
  480/768/1024/1280px de contenedor (la escalera densa de 0.4.13 dejaba
  las tarjetas demasiado estrechas).

## [0.4.13] — 2026-07-16

- **Cabecera pública fija SIEMPRE visible y barra derecha contextual fija
  por debajo, con asa propia** (`@edc-motor/ui` + cascarón): la cabecera de
  la web pública deja de auto-ocultarse al scrollear (fuera el translateY y
  su listener de scroll: fija arriba y siempre usable), y `AppRightSidebar`
  deja de ser una columna sticky que scrolleaba con la página y "se acababa"
  al llegar al pie — ahora es fija desde el borde inferior de la cabecera
  (`--app-right-sidebar-top`, la fija el cascarón por breakpoint) hasta
  abajo, por debajo de ella (z 40 < 50), en TODOS los anchos. En ancho,
  desplegada, el cascarón le hace hueco (padding-right con transición de
  contenido y pie; la cabecera no lo necesita: la barra no la tapa); en
  estrecho sigue el drawer superpuesto con telón bajo la cabecera (que
  queda visible y clicable), click fuera y Escape. El botón Funnel sale del
  header: la barra trae su propia ASA anclada al costado (Funnel cerrada /
  X abierta), asomando justo bajo la cabecera, nueva prop `openLabel`. La
  API de `useAppRightSidebar()` no cambia. **Migración del cascarón**:
  copiar de `plantilla/app/` — `src/App.vue`,
  `src/components/AppHeader.vue`, `src/assets/scss/main.scss` y
  `src/assets/scss/components/_app-header.scss` (las claves i18n
  `nav.filters`/`nav.closeFilters` se conservan: App.vue las pasa como
  labels del asa).
- **`SortToggles` sueltos estilo action-button** (`@edc-motor/ui`): fuera el
  grupo segmentado con borde; cada toggle es un botón limpio e individual, y
  el color del activo distingue además ascendente (tinte suave de acento) de
  descendente (acento relleno).
- **Grid de entidades del admin hasta cinco columnas**
  (`@edc-motor/admin-kit`): `BaseGrid` gana el escalón genérico `xl`
  (`$bp-xl`, 1280px de ancho real del contenedor `content`) para `cols`, y
  el preset `cards` escala 1 → 2 → 3 → 4 → 5 con una escalera densa medida
  sobre el contenedor (3/4/5 a 570/660/750px).

## [0.4.12] — 2026-07-15

- Sin cambios propios: versión de tren.

## [0.4.11] — 2026-07-15

- **Grupos plegables en el menú del admin** (`@edc-motor/admin-kit`): nuevo
  `NavGroup` para el slot `#nav` del `AdminLayout` — cabecera con icono +
  etiqueta + chevron que despliega/pliega sus hijos (mezclable con nav-item
  sueltos), plegado persistido en `localStorage` por clave de grupo (por
  defecto plegado) y auto-despliegue con resalte cuando la ruta actual es de
  un hijo (prop `active`). Con el sidebar colapsado a carril de iconos los
  hijos se muestran siempre. El playground agrupa así sus taxonomías bajo
  "Juego". De propina, en móvil el drawer del menú ya solo se cierra al tocar
  un enlace (no con cualquier click).
- **Borde por entidad en `EntityCard`** (`@edc-motor/admin-kit`): nueva prop
  `accentColor` — el borde se tiñe con el color de la entidad (p. ej. su
  facción): mezclado con el borde del tema en reposo (sutil en claro y
  oscuro), más presente al hover y pleno en la seleccionada. Sin la prop,
  todo como antes. El playground lo usa en el index de casas (`item.color`).

## [0.4.10] — 2026-07-14

- **Retoques del `EntityCard`** (`@edc-motor/admin-kit`): la franja media
  pierde el fondo, el lápiz de editar pasa a verde (`$success`) y header y
  content respiran menos entre sí (un escalón menos de padding en la
  divisoria).
- **Previews PNG con fondo transparente.** El `PreviewRenderer` del core ya
  captura con `hideBackground()` (omite el fondo por defecto de Chromium),
  pero la ruta `/_render` del cascarón pintaba el fondo del tema en
  `html`/`body` y el PNG salía opaco. `RenderView.vue` fuerza ahora
  `background: transparent` en `html`/`body`: el fondo lo decide el
  componente de cada entidad — las plantillas que pintan el suyo (cartas,
  héroes) salen igual que antes; las que no (contadores/tokens redondos),
  con canal alfa de verdad en las esquinas. **Migración del cascarón**:
  copiar de `plantilla/app/` `src/views/RenderView.vue` y regenerar las
  previews afectadas.

## [0.4.9] — 2026-07-13

- **Fix** (`@edc-motor/ui`): al desplegar un `BaseSelect`/`SearchSelect`
  dentro de un modal, el panel quedaba recortado por el overflow del cuerpo
  y le añadía scroll fantasma (el scrollbar cambiaba el ancho interior y
  disparaba reflows del modal). El panel abierto vuela ahora a la top layer
  del navegador (atributo `popover`, nuevo composable interno
  `useDropdownPanel`): se superpone a cualquier overflow sin tocar el
  layout, sigue pegado al trigger al scrollear (también fuera de modales) y
  Escape con el panel abierto cierra solo el desplegable, no el modal.
- **Barra derecha contextual de la web pública** (`@edc-motor/ui`):
  `AppRightSidebar` + `useAppRightSidebar()`, con la MISMA mecánica que la
  del admin-kit (registro por vista con token y Teleport a
  `#app-right-sidebar-target`; columna junto al contenido en ancho, drawer
  superpuesto con telón + Escape en estrecho). Se despliega con el nuevo
  botón Funnel del header del cascarón, que solo aparece si la vista actual
  ha registrado contenido (sus selects de filtros). **Migración del
  cascarón**: copiar de `plantilla/app/` `src/App.vue`,
  `src/components/AppHeader.vue`, `src/assets/scss/main.scss` y
  `src/assets/scss/components/_app-header.scss`, y añadir a los tres
  `src/i18n/locales/*.json` las claves `nav.filters` y `nav.closeFilters`.
- **Retirado `FiltersModal`** (`@edc-motor/ui`, salió en 0.4.8) y el botón
  "Filtros" del `IndexToolbar` (props `showFilters`/`activeCount`/
  `filtersLabel` y emit `open-filters`): los filtros de los index viven
  ahora en la barra derecha (la del admin-kit en el admin;
  `AppRightSidebar` en la web pública).
- **Cascarón (admin): los filtros del listado, en el panel derecho.**
  `PagesListView` enseña sin selección el "selecciona…" + separador + los
  selects de filtros (aplican en vivo); con selección, el botón
  "← Volver a los filtros" (deselecciona) + separador + el panel de la
  card; y un click en la zona vacía del contenido también deselecciona
  (nuevo `useCardDeselect` del admin-kit). `ListFiltersModal` desaparece y
  `ListToolbar` queda en búsqueda + toggles de orden. **Migración del
  cascarón**: copiar de `plantilla/admin/`
  `src/views/pages/PagesListView.vue` y `src/components/ListToolbar.vue`,
  BORRAR `src/components/ListFiltersModal.vue`, copiar
  `src/assets/scss/views/_cards.scss` y, en los tres
  `src/i18n/locales/*.json`, sustituir `common.clearFilters` por
  `common.backToFilters`.
- **Index de entidades más densos** (`@edc-motor/admin-kit`): el preset
  `cards` del `BaseGrid` escala 1 → 2 → 3 → 4 columnas con el ancho real
  del contenedor `content`, y la franja media del `EntityCard` pasa a
  cuadrada (`aspect-ratio: 1/1`) con la imagen contenida
  (`object-fit: contain`): más pequeña y entera, sin deformar ni recortar.

## [0.4.8] — 2026-07-13

- **Sistema de filtros unificado de los index** en `@edc-motor/ui`:
  `IndexToolbar` (búsqueda con lupa a la derecha + toggles de ordenación +
  botón "Filtros" con badge, responsive por container query propia),
  `SortToggles` (fecha latest ⇄ oldest y alfabético name ⇄ name_desc) y
  `FiltersModal` (filtros en vivo sin guardar, grid de 1 → 2 → 3 columnas
  según el ancho del modal, "Quitar filtros" + "Cerrar"). El cascarón adopta
  el patrón: `UsersView` (búsqueda + toggles; `GET /admin/users` del core
  acepta ahora `?sort` con el contrato de los index, alfabético por defecto)
  y `PagesListView` (búsqueda + filtro de estado en el modal; el árbol no se
  reordena), con los wrappers `ListToolbar`/`ListFiltersModal` que ponen los
  textos i18n del admin. **Migración del cascarón**: copiar de
  `plantilla/admin/` `src/components/ListToolbar.vue`,
  `src/components/ListFiltersModal.vue`, `src/views/users/UsersView.vue` y
  `src/views/pages/PagesListView.vue`, y añadir a los tres
  `src/i18n/locales/*.json` las claves `common.filters`,
  `common.clearFilters`, `common.sort.*` y `pages.filters.*`.
- **Controles de formulario compactos** (`@edc-motor/ui` y admin-kit): altura
  de inputs, selects, buscadores y botones de 40px a 36px, padding 8px/10px
  (la paginación se queda a 32px, control secundario).
- **Fix** (`edc-motor/core`): la búsqueda de `HasFilters` hace el LIKE de
  cada campo de `$searchable` sobre el json del locale activo (antes
  mezclaba locales al buscar sobre el json crudo).

- **Fix**: guardar una entidad renderizable con la cola `sync` colgaba la
  petición generando la preview inline (y podía acabar en 500) — ahora se
  difiere a después de la respuesta. La plantilla trae
  `QUEUE_CONNECTION=database`; en juegos existentes, ponerlo en `api/.env`.

## [0.4.7] — 2026-07-12

- **`BasePagination`** en `@edc-motor/ui` (controles de página para los
  listados) y regla global de iconos del wysiwyg a 1.2x el tamaño del texto
  en cualquier render (los paneles sin `.rich-content` los pintaban gigantes).

## [0.4.6] — 2026-07-12

- **`BaseSelect` personalizado** (`@edc-motor/ui`): el `<select>` nativo pasa
  a ser un dropdown propio (trigger + panel con la estética del SearchSelect)
  con teclado y aria completos y la MISMA API: los consumidores no cambian.
- Regla base de ui: todo lo clickable lleva `cursor: pointer` salvo
  deshabilitado.
- El `?sort` del catálogo público acepta también `oldest` (id ascendente).

## [0.4.5] — 2026-07-12

- **Ordenación en el catálogo público** (`edc-motor/core`): el modo lista de
  `GET /api/catalog/{key}` acepta `?sort=name|name_desc|latest` (nombre del
  locale activo asc/desc; default id desc). El modo `random` lo ignora.

## [0.4.4] — 2026-07-12

- **Catálogo público genérico**: `GET /api/catalog/{key}` sirve cualquier
  entidad del registry de previews sin auth y solo publicada — modo lista con
  paginación y `?search`, modo `?mode=random&count=N` y `?exclude` para los
  singles. Ítem `{id, name, slug|null, preview|null}`
  (`Edc\Core\Previews\CatalogItem`, compartido con el bloque `related`).
- **Bloque `related`** (primer bloque `data` del motor): rejilla de entidades
  relacionadas de cualquier clave del registry de previews — título/subtítulo,
  entidad (opciones en vivo del registry), modo `latest|random`, `count`
  (1..12) y botón opcional al índice. En `@edc-motor/ui` lo pinta
  `BlockRelated`; los enlaces se resuelven con el mapa que la app provee vía
  `catalogRoutesKey`. Requiere versión nueva de `edc-motor/core` y
  `@edc-motor/ui`.
- **`PreviewGrid`** (`@edc-motor/ui`): rejilla presentacional de previews del
  catálogo (paginación prev/next, slots `item`/`actions`/`empty`, fallback
  con el nombre cuando el PNG no está generado) para los índices públicos de
  los juegos. **Migración del cascarón**: copiar de `plantilla/`
  `app/src/main.ts` y `app/src/entities/catalogRoutes.ts` (provide de
  `catalogRoutesKey`, necesario para los enlaces del bloque `related`).

## [0.4.3] — 2026-07-11

- **Tarjetas de entidad del admin**: `EntityCard` gana `editable`/`edit` (botón
  de editar en la propia tarjeta, para entidades sin vista single) y la franja
  del emblema queda reservada a entidades con imagen o preview — las
  taxonomías ya no pintan monograma.

## [0.4.2] — 2026-07-10

- **Bloque CTA**: botones a cuerpo 18, tarjeta un poco más transparente
  (50%) y la imagen sangra hasta el borde de la tarjeta según su posición
  (en columnas toca arriba, abajo y su lateral). La anchura `narrow` de los
  bloques sube a 800px. Requiere versión nueva de `@edc-motor/ui`.
- **Pie de página con wysiwyg**: el campo de Configuración pasa de textarea a
  editor de texto rico y la web pública pinta el HTML (saneado por lista
  blanca en `edc-motor/core`, como los bloques) con la escala discreta del
  pie. Requiere versión nueva de `edc-motor/core`. **Migración del
  cascarón**: copiar de `plantilla/`
  `admin/src/views/settings/SettingsView.vue`, `app/src/App.vue` y
  `app/src/assets/scss/main.scss`.
- **Header afinado**: el logo se acota a 34 → 44 → **50px** (antes subía a
  68) y las acciones pasan a elementos sueltos — sin barras separadoras ni
  grupos, con un único gap; descargas, salir y entrar/usuario sin caja ni
  iconos, en color de texto y color solo al hover (acento; rojo en salir).
  **Migración del cascarón**: copiar de `plantilla/`
  `app/src/assets/scss/components/_app-header.scss` y
  `app/src/components/AppHeader.vue`.

## [0.4.1] — 2026-07-09

- **El header crece con el logo**: la línea 1 de la cabecera pública ya no es
  fija (56px); mide el alto del logo + 22px en cada breakpoint, con el resto
  de elementos centrados verticalmente y la barra lateral móvil y el hueco
  del contenido siguiéndola (antes el logo ancho se salía del header).
  **Migración del cascarón**: copiar
  `app/src/assets/scss/components/_app-header.scss` de `plantilla/`.
- **Scripts de mantenimiento en la plantilla**: los juegos nuevos nacen con
  `update-motor.sh <version>` (sube los paquetes composer/npm del motor, migra
  y limpia cachés), `copiar-plantilla.sh [-t vX.Y.Z] <rutas...>` (trae archivos
  del cascarón desde `plantilla/` del monorepo, para las notas de "migración
  del cascarón" del changelog) y `claude.sh --start/--finish <rama>` (flujo de
  ramas de Claude).
- **Fix de la plantilla (Vite)**: en los juegos que consumen los paquetes
  desde npm (no enlazados), el optimizador de dependencias de Vite
  pre-empaquetaba `@edc-motor/ui` y `@edc-motor/admin-kit` pero externalizaba
  sus `.vue`, duplicando los singletons de los composables (toast, confirm,
  panel derecho): el panel no se abría al seleccionar y los confirms/toasts no
  salían. Los `vite.config.ts` de la plantilla (admin y app) añaden
  `optimizeDeps.exclude` para servir los paquetes como fuente. **Migración de
  juegos existentes**: añadir a `admin/vite.config.ts`
  `optimizeDeps: { exclude: ['@edc-motor/admin-kit', '@edc-motor/ui'] }` y a
  `app/vite.config.ts` `optimizeDeps: { exclude: ['@edc-motor/ui'] }`.

## [0.4.0] — 2026-07-07

- **Bloques anidados** (un nivel) con índice automático **indentado** y
  tarjetas hijas sangradas en el admin.
- Imagen de los bloques texto/CTA: **modo de escalado** (contener / cubrir /
  rellenar, al alto del texto de al lado) y **reparto de columnas** (1:1 …
  4:3). Subidas hasta 10 MB.
- **Subtítulo en todos los bloques**; ningún título es obligatorio. Título h1
  solo en la cabecera (h2 en el resto, nunca justificado).
- Tipografía con tokens: texto 16 · subtítulo 20 · título 28 (cabecera
  32/24); anchura por defecto `wide` **~1200px**. Wysiwyg con márgenes entre
  elementos, escala h3–h6 y sin párrafos vacíos.
- El single de página recupera el **panel de la Página** en la barra derecha
  (acciones + flags + slugs) cuando no hay bloque seleccionado.
- El logo del header de la web crece con el ancho: 34px en estrecho y hasta
  el doble (68px) en pantallas anchas.

## [0.3.1] — 2026-07-07

- Fix: la subida de imágenes rechazaba los SVG ("debe ser una imagen");
  vuelven a admitirse y se guardan saneados (sin scripts ni handlers).

## [0.3.0] — 2026-07-07

- **Logo traducible** en la configuración de la web: uno por idioma desde el
  admin (`TranslatableImage`), con fallback al locale por defecto y el SVG
  inlineado por idioma (currentColor hereda el acento). El formato antiguo
  (URL única) se sigue aceptando.
- **Subidas de imagen sin huérfanos** (logo, favicon, fondos e imágenes de
  bloques): se guardan con el nombre original del fichero y al sustituir o
  quitar una imagen la anterior se borra del disco.

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
- **`plantilla/`**: esqueleto mínimo de juego (api + admin + app +
  `packages/shared`) con toda la infraestructura del motor funcionando y SIN
  entidades demo, cubierto por los gates del monorepo. `tools/crear-juego.sh`
  ahora genera los juegos desde ella, con dependencias por versión de los
  registros (sin carpeta `motor/` hermana).

## [0.1.0] — 2026-07-05

Primera versión etiquetada: el resultado de las Fases 0–7 del
[plan de acción](documentacion/02-plan-de-accion.md). Incluye auth y roles,
comportamientos de modelo + media + i18n, render de componentes a PNG,
generación de PDF (catálogo + colección de usuario/invitado), CRM de páginas
y bloques, configuración de la web, gestión de usuarios, backup de BBDD,
web pública (locale/SEO/sitemap/prerender, descargas) y panel de usuario
extensible. Ver el changelog de cada paquete.

Herramientas del monorepo:

- `tools/crear-juego.sh <destino> [ruta-al-motor]` — genera un proyecto de
  juego limpio a partir del playground (api + admin + app + packages/shared,
  tooling raíz y CI adaptados, solo las guías de documentación) que consume
  el motor por versión desde una carpeta hermana.
- `tools/consumo-externo/probar-consumo.sh` — prueba reproducible de consumo
  del motor por versión (Composer `path` + npm `file:`).
