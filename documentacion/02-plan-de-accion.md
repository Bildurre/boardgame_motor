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
- [ ] Linters/formatters: Pint (api), ESLint/Prettier (fronts), Pest — **pendiente** (se añade al empezar a escribir lógica).
- **Hito:** ✅ `npm run dev` levanta api + admin + app; la app muestra un componente del motor (`MotorBadge`/`AdminLayout`) y consume `api/motor/ping` por el proxy.

### Fase 1 — Auth, usuarios y roles ✅
**Meta:** poder entrar al admin y al panel de usuario. Base transversal del resto.
> Depende de Fase 0. Plan: `funcionalidades/05-auth-usuarios-roles.md`.

- [x] Sanctum + login/logout/registro + `me` (en `bgm/core`).
- [x] Roles admin/editor/user (Spatie) + middleware `motor.admin` + comando `motor:install`.
- [x] Panel de usuario (datos de cuenta + cambio de contraseña) en la `app`.
- [x] Stores Pinia de auth + cliente axios `createApi` (`@bgm/ui`) en admin y app; guards de router.
- [ ] Verificación de email (DC-14) — **pendiente**: se hará al montar el correo (mailpit) en una fase posterior.
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

### Fase 3 — Render de componentes a PNG
**Meta:** capturar el componente visual de una entidad a imagen, fiable y fácil de regenerar.
> Plan: `funcionalidades/01-render-png.md`. Es la base del PDF.

- [ ] Infra Browsershot + ruta de render dedicada en `app` (componente aislado).
- [ ] `HasPreviewImage` + servicio de generación, almacenamiento y limpieza de huérfanos.
- [ ] Jobs async + comando `preview:manage` rediseñado (generar/regenerar/borrar/limpiar, por lotes).
- [ ] Invalidación automática al editar la entidad.
- **Hito:** editar la entidad demo regenera su PNG sin comandos manuales; regenerar en lote por cola funciona.

### Fase 4 — Generación de PDF
**Meta:** PDF recortables a partir de los PNG, y regenerar = trivial.
> Plan: `funcionalidades/02-pdf.md`. Depende de Fase 3.

- [ ] `GeneratedPdf` (permanentes + temporales) + ensamblado desde PNG.
- [ ] Tipos de export: cartas/counters recortables, "mazos"/colecciones, páginas.
- [ ] Colecciones temporales (elegir ítems → PDF al vuelo) + limpieza programada.
- [ ] Multi-idioma. API + gestor de PDF en el admin-kit.
- **Hito:** desde el admin, generar y regenerar un PDF de una colección con un clic; PDF temporal de ítems elegidos a la carta.

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
