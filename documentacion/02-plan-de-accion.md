# Boardgame Motor — Plan de acción general

> Hoja de ruta por fases. Cada funcionalidad tiene su plan detallado en
> `documentacion/funcionalidades/`. Aquí va el **orden**, las **dependencias** y
> los **hitos** que cierran cada fase.

---

## Principios del plan

1. **Vertical sobre horizontal.** En vez de "primero todo el backend, luego todo
   el front", cada funcionalidad se lleva de punta a punta (modelo → API →
   componente Vue) para validarla pronto.
2. **El `playground` como banco de pruebas.** Un juego-demo mínimo dentro del
   motor que ejercita cada funcionalidad según se construye. Es lo que evita
   diseñar el motor "a ciegas".
3. **choque como contrato.** Para cada servicio que refactorizamos, choque es la
   referencia de "qué tiene que poder hacer". Lo bueno se conserva, lo que duele
   se rediseña (ver el "qué cambia respecto a choque" en cada doc de funcionalidad).
4. **Nada que rompa después.** Decisiones de esquema y de API pensadas para no
   forzar `major` constantes.

## Fases

### Fase 0 — Andamiaje del monorepo (base) ✅
**Meta:** repos, paquetes vacíos y `playground` arrancando.

- [x] Estructura del monorepo del motor (`packages/*`, `playground/`).
- [x] `core` esqueleto: composer.json, `MotorServiceProvider`, config publicable, ruta `api/motor/ping`.
- [x] `@bgm/ui` y `@bgm/admin-kit` esqueleto: package.json + barrel exports apuntando a `src` (patrón kontuan, sin build en dev).
- [x] `playground`: Laravel `api` + Vue `admin` + Vue `app` que ya consumen los paquetes por *path*/workspace (enlace local), sin publicar.
- [x] **PWA** (DC-01): `vite-plugin-pwa` en admin y app (manifest + service worker). Faltan iconos reales (los pone cada juego).
- [x] Script `dev` con `concurrently` (estilo kontuan) + `dev:front` + `build`.
- [x] Linters/formatters: Pint (api + core, config de kontuan), ESLint flat + Prettier a nivel de monorepo, Pest en `playground/api` (suite completa de Fases 0–3). Scripts: `npm run lint / lint:fix / format / lint:php / fix:php / test:api / type-check`.
- **Hito:** ✅ `npm run dev` levanta api + admin + app; la app muestra un componente del motor (`MotorBadge`/`AdminLayout`) y consume `api/motor/ping` por el proxy.

### Fase 1 — Auth, usuarios y roles ✅
**Meta:** poder entrar al admin y al panel de usuario. Base transversal del resto.
> Depende de Fase 0. Plan: `funcionalidades/05-auth-usuarios-roles.md`.

- [x] Sanctum + login/logout/registro + `me` (en `bgm/core`).
- [x] Roles admin/editor/user (Spatie) + middleware `motor.admin` + comando `motor:install`.
- [x] Panel de usuario (datos de cuenta + cambio de contraseña) en la `app`.
- [x] Stores Pinia de auth + cliente axios `createApi` (`@bgm/ui`) en admin y app; guards de router.
- [x] Verificación de email (DC-14): `Registered` + ruta firmada `verification.verify` (verifica y redirige a la app), reenvío con throttle, y cambiar el email invalida y reenvía. Aviso + reenvío en 'Mi cuenta'. *(forgot/reset password: cuando se monte el correo real del juego.)*
- **Hito:** ✅ login como admin/editor entra al admin; `user` no (403/redirección); `user` entra a su panel de cuenta.

### Fase 2 — Comportamientos de modelo + Media + i18n ✅
**Meta:** los cimientos que todas las entidades usarán.
> Plan: `funcionalidades/11-comportamientos-modelo.md`, `07-media-imagenes.md`, `04-i18n-urls-traducibles.md`.

- [x] Traits: published/draft, soft-delete + restore, filtros. *(coste: cuando un juego lo pida.)* — **2a**
- [x] Media: imagen simple (Spatie MediaLibrary + `MotorPathGenerator` de rutas predecibles, `ImageUpload`). *(Imagen multilingüe: cuando el CRM/previews la pidan, doc 07.)* — **2b**
- [x] i18n: campos traducibles, slugs traducibles (admin y público), resolución de locale en API, selector de locale de contenido en front (`TranslatableInput`). — **2a + 2c**
- [x] CRUD scaffolding del admin-kit: `useResource`, `ResourceList`, `FiltersBar` + vistas de la entidad demo. *(`ResourceForm` dirigido por DSL llegará con el editor de bloques del CRM, DC-08.)* — **2c**
- **Hito:** ✅ entidad demo `House` con CRUD completo en admin —
  traducible es/eu/en, **con imagen**, publicable, filtros, soft-delete + restaurar— **verificado en navegador headless**; slug traducible resuelve en público.

### Fase 3 — Render de componentes a PNG ✅
**Meta:** capturar el componente visual de una entidad a imagen, fiable y fácil de regenerar.
> Plan: `funcionalidades/01-render-png.md`. Es la base del PDF.

- [x] Infra Browsershot (`PreviewRenderer`, config única: noSandbox, scale, waits) + ruta desnuda `/_render/:entity/:id` en `app` con token de servicio (DC-04) y señal `__bgmRenderReady`.
- [x] `PreviewableContract` + `PreviewRegistry` (facade `Previews`) + `HasPreviewImage` + `PreviewService` (PNG versionado por locale, borra el anterior, limpieza de huérfanos, status).
- [x] `GeneratePreviewJob` (cola, único por entidad+locale; DC-05: workers acotan Chromes) + `preview:manage` genérico (status/generate/regenerate/delete/clean, `--type --id --locale --sync --force --dry-run`).
- [x] Invalidación automática declarativa (`previewTriggerFields`; `is_published` no dispara; imagen nueva invalida desde el controlador).
- [x] Playground: `Character` y `Scheme` renderizables; cartas en `@playground/shared` (fuente única admin/app, D8); `PreviewPanel` en el admin (ver PNG + regenerar).
- [x] **Gestor de previews** (`PreviewManager`, admin-kit) + sección "Imágenes" del admin: estado por tipo, lotes (generar pendientes / regenerar todo / borrar todo), acciones por entidad y limpieza de huérfanos, sobre los endpoints `api/admin/previews/*`.
- **Hito:** ✅ editar la entidad demo regenera sus PNG (es/eu/en) por cola sin comandos; `preview:manage regenerate --type=character` en lote funciona; cero glob de CSS y cero base64.

### Fase 4 — Generación de PDF ✅
**Meta:** PDF recortables a partir de los PNG, y regenerar = trivial.
> Plan: `funcionalidades/02-pdf.md`. Depende de Fase 3.

- [x] `GeneratedPdf` **polimórfico** (permanentes + temporales, estado pending/ready/failed con error visible) + ensamblado DomPDF desde los PNG (genera al vuelo las previews que falten).
- [x] `PdfExportRegistry` (facade `Pdfs`) + `PdfExport`: el juego describe el contenido (colección por entidad / global / individual) y opcionalmente su vista Blade; el motor pone el pipeline. `PrintLayout` con presets (card 88×126, counter) en config publicable (DC-07); rejilla genérica `motor::pdf.grid` con marcas de corte (posicionado absoluto en mm: DomPDF fiable).
- [x] Colección temporal por usuario (`/api/pdf-collection`: ítems con copias, snapshot en payload, `expires_at`) + `pdf:cleanup` programable.
- [x] Multi-idioma (un PDF por locale). API `/api/admin/pdfs` (+ catálogo `GET /admin/pdfs/exports`) + descarga pública de permanentes + `PdfManager` en admin-kit: **toda la gestión centralizada en la sección PDF del admin** (catálogo dirigido por los exports registrados; nada en los singles). Layout `card` por defecto = **Magic 63×88** (9/A4); presets propios del juego con `Pdfs::layout()` y **tamaño por export** vía `layout()` (playground: personajes al doble `card-big`, argucias en Magic, y `house-tokens` con 9 tokens redondos de 40 mm por casa).
- **Hito:** ✅ generar y regenerar desde el admin con un clic (fichero versionado, borra el anterior); PDF temporal a la carta cubierto por API + tests (la UI pública llega con la Fase 6). Verificado con PDF real (4 cartas por A4 con marcas de corte).

### Fase 5 — CRM de páginas y bloques
**Meta:** construir la web pública por bloques, y añadir un bloque nuevo sin sufrir.
> Plan: `funcionalidades/03-crm-paginas-bloques.md`.

- [ ] Modelos Page/Block (jerárquicos, traducibles, reordenables), plantillas, SEO, printable/indexable.
- [ ] **Registro de tipos de bloque**: presentación (motor) + con-datos (juego), con esquema declarativo de campos y settings.
- [ ] Editor de bloques en admin-kit (paleta, formularios por tipo, drag, preview).
- [ ] Render público de páginas en `app` + SEO/meta + integración con PDF (páginas imprimibles).
- **Hito:** crear una página con varios bloques (incluido uno con-datos del playground), reordenar, traducir, publicar y verla en público con su URL traducible.

### Fase 6 — Backup, web pública y panel de usuario extensible
**Meta:** rematar lo transversal y dejar ganchos de extensión por juego.
> Plan: `funcionalidades/06-backup-bbdd.md`, `10-web-publica-y-panel-usuario.md`.

- [ ] Backup BBDD (dump + zip + descarga) desde admin; programado.
- [ ] Andamiaje de la web pública (home, navegación por páginas del CRM, listados de entidades genéricos extensibles).
- [ ] Panel de usuario extensible (puntos de extensión para que el juego cuelgue lo suyo).
- **Hito:** backup descargable desde admin; web pública navegable; un "slot" de panel de usuario rellenado por el playground.

### Fase 7 — Publicación de paquetes y endurecido
**Meta:** dejar el motor consumible por versión y documentado.

- [ ] Distribución (Git + tags / registry privado) y prueba de consumo desde un repo externo.
- [ ] Versionado, CHANGELOGs, guía de "cómo arrancar un juego nuevo".
- [ ] Cobertura de tests y CI.
- **Hito:** crear un repo de juego nuevo que instale el motor por versión y tenga admin + público funcionando en < 1 día.

### Fase 8 (posterior) — Migración de choque
Fuera del alcance inicial. Cuando el motor esté terminado, choque pasa a ser el
primer juego sobre el motor. Plan propio cuando lleguemos.

## Dependencias entre fases

```
0 ─► 1 ─► 2 ─► 3 ─► 4
            └► 5 ─► 6 ─► 7 ─► (8)
```

- 3 y 5 dependen de 2 (traits, media, i18n).
- 4 depende de 3 (PNG antes que PDF).
- 5 puede empezar en paralelo a 3/4 una vez cerrada la 2.

## Cómo trabajaremos cada funcionalidad

Para cada una, su doc en `funcionalidades/` sigue el mismo guión:

1. **Qué hace** y alcance.
2. **Qué hay hoy en choque** (lo que se conserva / lo que duele).
3. **Diseño nuevo**: modelo de datos, API, servicios, componentes Vue.
4. **Frontera motor ↔ juego** y puntos de extensión.
5. **Cómo se construye** (pasos) y **hito de aceptación**.
6. **Decisiones (cerradas)** (ver `03-decisiones-cerradas.md`) **y riesgos.**
