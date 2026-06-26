# 05 · Auth, usuarios y roles

## Qué hace

Autenticación de la API y gestión de usuarios con **3 roles simples**:
`admin` (yo), `editor` (ayuda con algunas cosas), `user` (sin acceso al admin).
Incluye el **panel de usuario** y opciones típicas de cuenta/configuración,
montados pero "vacíos", listos para que cada juego cuelgue lo suyo.

## Qué hay hoy en choque

Laravel Breeze (sesión, server-side). Auth de admin solamente; no hay API auth ni
usuarios públicos. → Se rehace para API/SPA.

## Diseño nuevo

**Backend (`motor-php/src/Auth/`):**
- **Sanctum** para tokens SPA. Endpoints: `register`, `login`, `logout`, `me`,
  `forgot/reset password`, verificación de email.
- **Spatie Permission** con 3 roles fijos sembrados por el motor: `admin`, `editor`,
  `user`. Permisos finos opcionales, pero sin sistema complejo.
- Middleware `can:access-admin` (admin + editor) para las rutas de admin; `user`
  queda fuera del admin por defecto.
- `editor` = subconjunto de capacidades del admin (configurable por juego: qué
  recursos puede tocar un editor).

**API:**
```
POST /api/v1/auth/register · login · logout
GET  /api/v1/auth/me
POST /api/v1/auth/forgot-password · reset-password
GET/PUT /api/v1/account            # datos de cuenta, preferencias
PUT  /api/v1/account/password
# Admin
GET/POST/PUT/DELETE /api/v1/admin/users   # gestión de usuarios y roles
```

**Frontend:**
- **admin** (`motor-admin-kit/src/users/` + `stores/auth`): login, gestión de
  usuarios y asignación de rol.
- **app** (panel de usuario): layout de panel + páginas de **cuenta** y
  **configuración** vacías pero funcionales, con **puntos de extensión** (slots /
  registro de secciones) para que el juego añada las suyas (ej. choque: "mis mazos").

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Sanctum, 3 roles, CRUD usuarios, panel de usuario base, stores auth | Capacidades concretas de `editor`; secciones propias del panel de usuario |

## Pasos

1. Sanctum + endpoints auth + `me`.
2. Spatie roles sembrados + middleware acceso admin.
3. CRUD usuarios + asignación de rol (admin-kit).
4. Panel de usuario base + cuenta/config (app) con slots de extensión.
5. Stores de auth (admin y app).

## Hito de aceptación

- `admin`/`editor` entran al admin; `user` no.
- Un `user` logueado ve su panel y edita su cuenta.
- El playground añade una sección propia al panel de usuario vía slot.

## Decisiones (cerradas)

- **Capacidades de `editor`** → **DC-13**: por defecto gestiona contenido (CRM) y
  entidades del juego (crear/editar), **no** usuarios/ajustes/backups/borrados;
  configurable por juego.
- **Registro de usuarios** → **DC-14**: auto-registro público con verificación de
  email (rol `user`); admin/editor a mano; toggle por juego para solo-invitación.

## Riesgos

- Mantener `editor` "seguro por defecto" al añadir recursos nuevos del juego.
