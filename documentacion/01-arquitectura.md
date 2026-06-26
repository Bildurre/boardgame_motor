# Boardgame Motor — Arquitectura

> Cómo se organiza el motor, qué hay en cada paquete, cómo es un juego que lo
> consume, y dónde está la frontera "motor vs juego".

---

## 1. Topología general

```
┌──────────────────────────────────────────────────────────────┐
│  boardgame_motor/   (monorepo donde se DESARROLLA el motor)    │
│                                                                │
│  packages/                                                     │
│   ├── motor-php          (paquete Composer — backend Laravel)  │
│   ├── motor-ui           (paquete npm — componentes Vue + SCSS)│
│   └── motor-admin-kit    (paquete npm — layout + CRUD admin)   │
│  playground/             (juego-demo mínimo para probar motor) │
│  documentacion/                                                │
└──────────────────────────────────────────────────────────────┘
                │ publica versiones (Git + tags)
                ▼
┌──────────────────────────────────────────────────────────────┐
│  choque_de_leyendas/  (y cada juego futuro — su PROPIO repo)   │
│                                                                │
│  api/     Laravel  → require boardgame/motor-php:^1.0          │
│  admin/   Vue SPA  → deps @boardgame/motor-ui + motor-admin-kit│
│  app/     Vue SPA  → deps @boardgame/motor-ui                  │
│  assets/  documentacion/                                       │
└──────────────────────────────────────────────────────────────┘
```

**Idea clave:** el monorepo de un *juego* se parece a kontuan (api + admin + app +
packages/shared), pero la mayor parte de `packages/shared` ya no se escribe a
mano: viene del motor. El `packages/shared` del juego queda solo para lo
específico de ese juego.

## 2. Los paquetes del motor

### 2.1. `motor-php` (Composer)

Paquete Laravel instalable (`boardgame/motor-php`). Aporta backend reutilizable
vía un `ServiceProvider` con auto-registro de rutas, migraciones publicables,
config publicable y comandos artisan.

```
motor-php/
├── composer.json                 # name: boardgame/motor-php
├── config/motor.php              # config publicable (locales, pdf, storage…)
├── database/migrations/          # tablas del motor (pages, blocks, generated_pdfs,
│                                 #   users, roles, media, …) publicables
├── routes/
│   ├── api.php                   # rutas del motor (auth, pages, blocks, pdf, backup)
│   └── console.php               # comandos programados (cleanup, previews)
├── src/
│   ├── MotorServiceProvider.php   # registro central
│   ├── Auth/                      # login, registro, roles, panel usuario
│   ├── Content/                   # CRM: Page, Block, servicios, registro de bloques
│   ├── Pdf/                       # generación PDF + colecciones temporales
│   ├── Previews/                  # render de componentes a PNG (Browsershot)
│   ├── Media/                     # imágenes simples y multilingües
│   ├── I18n/                      # slugs traducibles, routing localizado, locales
│   ├── Backup/                    # dump BBDD + zip
│   ├── Support/                   # traits de modelo (Published, Filters, SoftDelete…)
│   │   └── Concerns/
│   ├── Http/
│   │   ├── Controllers/           # controladores base + del motor
│   │   ├── Resources/             # API Resources base
│   │   ├── Requests/              # Form Requests base
│   │   └── Middleware/
│   └── Console/Commands/          # preview:manage, motor:backup, pdf:cleanup…
└── tests/
```

**Cómo lo extiende un juego** (sin forkear el motor):

- **Clases base / traits**: `Card extends MotorModel` o `use HasPreviewImage,
  HasTranslatableSlug, HasPublishedState`. El juego define columnas y relaciones.
- **CRUD base**: un controlador `MotorResourceController` con index/store/update/
  destroy/restore/toggle-published; el juego extiende y define modelo + request +
  resource.
- **Registro de bloques-con-datos**: el juego registra sus tipos de bloque en un
  `BlockTypeRegistry` (ver doc de CRM). El motor no conoce las entidades del juego.
- **Render a PNG**: el juego declara qué entidades son "renderizables" y a qué
  ruta del frontend corresponde su componente visual.

### 2.2. `motor-ui` (npm — `@boardgame/motor-ui`)

Equivalente a `@kontuan/shared`: componentes Vue 3 base + tokens y estilos SCSS,
sin lógica de negocio. Lo consumen **admin** y **app** de cada juego.

```
motor-ui/
├── package.json                  # @boardgame/motor-ui
├── src/
│   ├── components/
│   │   ├── base/                 # BaseButton, BaseInput, BaseModal, BaseTable…
│   │   ├── form/                 # TranslatableInput, ImageUpload, SortableList…
│   │   └── feedback/             # Toast, LoadingSpinner, EmptyState…
│   ├── composables/              # useTheme, useToast, useContentLocales, useHead
│   ├── lib/                      # axios factory, api client tipado
│   ├── i18n/                     # helpers de locale en front
│   └── index.ts                  # barrel export
└── scss/
    ├── _tokens.scss              # design tokens
    ├── _theme.scss · _base.scss · _forms.scss
    └── components/
```

### 2.3. `motor-admin-kit` (npm — `@boardgame/motor-admin-kit`)

Lo que evita rehacer el admin en cada juego: **layout del panel**, scaffolding de
CRUD, editor de bloques del CRM, gestor de PDF/previews, gestión de usuarios.
Construido sobre `motor-ui`.

```
motor-admin-kit/
├── package.json                  # @boardgame/motor-admin-kit
├── src/
│   ├── layout/                   # AdminLayout, Sidebar, Topbar, Breadcrumbs
│   ├── crud/                     # ResourceList, ResourceForm, FiltersBar, useResource()
│   ├── content/                  # PageEditor, BlockEditor, BlockPalette, reorder
│   ├── pdf/                      # PdfManager, PreviewManager
│   ├── users/                    # UsersAdmin, RolesAdmin
│   ├── stores/                   # auth, locales (Pinia)
│   └── index.ts
└── scss/
```

**Cómo lo extiende un juego:** el admin del juego importa `AdminLayout` y registra
sus secciones/CRUDs declarándolos (modelo, campos, columnas) o componiendo con
`ResourceList`/`ResourceForm`. Las pantallas muy específicas (ej. constructor de
mazos) las escribe el juego a mano usando `motor-ui`.

## 3. Anatomía de un juego (ej. estructura futura de choque)

```
<juego>/
├── api/                          # Laravel
│   ├── composer.json             # require boardgame/motor-php
│   ├── app/Models/               # Card, Hero, Faction… (entidades del juego)
│   ├── app/Http/Controllers/     # extienden los del motor
│   ├── app/BlockTypes/           # bloques-con-datos del juego (counters-list…)
│   ├── app/Previews/             # declaración de entidades renderizables a PNG
│   ├── database/migrations/      # tablas del juego (las del motor vienen publicadas)
│   └── routes/api.php            # rutas propias del juego
├── admin/                        # Vue SPA — @boardgame/motor-ui + motor-admin-kit
│   └── src/                      # registra CRUDs y pantallas propias
├── app/                          # Vue SPA — público + panel usuario
│   └── src/                      # vistas públicas, componentes visuales de entidades
├── packages/shared/              # SOLO lo específico del juego compartido entre admin/app
├── assets/  ·  documentacion/
└── package.json                  # workspaces (como kontuan)
```

## 4. Stack tecnológico

### Backend (`motor-php` + api de cada juego)

| Pieza | Tecnología |
|---|---|
| Framework | Laravel (última) |
| API | REST, versionada (`/api/v1`), respuestas `{ data, meta }`, slugs en URL |
| Auth | Laravel Sanctum (tokens SPA) |
| Roles | Spatie Permission (3 roles: admin/editor/user) |
| Traducciones | Spatie Translatable + slugs traducibles |
| Localización rutas | resolución por `Accept-Language` / prefijo, sin acoplar a un paquete pesado de routing en API |
| PDF | DomPDF para ensamblar (a confirmar) |
| Render PNG | Spatie Browsershot (Chromium headless) |
| Media | almacenamiento propio (revisar Spatie MediaLibrary vs. traits actuales) |
| Backup | mysqldump (revisar `ifsnop/mysqldump-php` vs. `spatie/laravel-backup`) |
| Colas | jobs async para PDF y previews |
| Tests | Pest |
| Formato | Pint |

### Frontend (`motor-ui` + `motor-admin-kit` + admin/app de cada juego)

| Pieza | Tecnología |
|---|---|
| Framework | Vue 3 (Composition API + TypeScript) |
| Build | Vite |
| Routing | Vue Router (SPA) |
| Estado | Pinia |
| HTTP | Axios (factory configurable del motor) |
| i18n | vue-i18n |
| SEO (app pública) | gestión de `<head>`/meta por ruta + prerender de páginas públicas |
| Estilos | SCSS con tokens del motor |
| Calidad | ESLint + Prettier |

## 5. La frontera motor ↔ juego

| Lo aporta el MOTOR | Lo programa el JUEGO |
|---|---|
| CRM páginas/bloques (motor + bloques de presentación) | Bloques-con-datos propios |
| Generación PDF y colecciones temporales | Qué entidades se exportan y cómo se agrupan |
| Render de componentes a PNG (infra) | El componente visual de cada entidad |
| Auth, roles, panel de usuario (vacío) | Funciones de usuario propias (ej. mazos guardados) |
| Backup BBDD, media, i18n, traits de modelo | Las entidades, sus campos y relaciones |
| Admin: layout, CRUD scaffolding, gestores | Pantallas y CRUDs específicos del juego |
| Librería de componentes Vue + tokens | Componentes visuales propios del juego |

## 6. Versionado y compatibilidad

- **SemVer** en los tres paquetes. `major` = ruptura; `minor` = features; `patch` = fixes.
- Cada juego fija rango (`^1.0`) y **sube cuando quiere y prueba**.
- Cambios de esquema (migraciones del motor) se publican como migraciones nuevas,
  nunca editando las ya publicadas → un juego migra al subir de versión.
- `CHANGELOG.md` por paquete.
