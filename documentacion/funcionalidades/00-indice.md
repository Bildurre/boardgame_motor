# Planes por funcionalidad

Cada documento detalla **cómo** construir una funcionalidad del motor. Todos
siguen el mismo guión: qué hace · qué hay hoy en choque · diseño nuevo · frontera
motor/juego · pasos · hito · riesgos.

| # | Funcionalidad | Paquete principal | Fase |
|---|---|---|---|
| 01 | [Render de componentes a PNG](01-render-png.md) | core + app | 3 |
| 02 | [Generación de PDF](02-pdf.md) | core + admin-kit | 4 |
| 03 | [CRM de páginas y bloques](03-crm-paginas-bloques.md) | core + admin-kit + app | 5 |
| 04 | [i18n y URLs traducibles](04-i18n-urls-traducibles.md) | core + ui | 2 |
| 05 | [Auth, usuarios y roles](05-auth-usuarios-roles.md) | core + admin-kit + app | 1 |
| 06 | [Backup de BBDD](06-backup-bbdd.md) | core + admin-kit | 6 |
| 07 | [Media e imágenes](07-media-imagenes.md) | core + ui | 2 |
| 08 | [Admin kit (layout + CRUD)](08-admin-kit.md) | admin-kit | 2 |
| 09 | [Librería Vue + tokens SCSS](09-libreria-vue-tokens.md) | @bgm/ui | 0–2 |
| 10 | [Web pública y panel de usuario](10-web-publica-y-panel-usuario.md) | app + core | 6 |
| 11 | [Comportamientos de modelo](11-comportamientos-modelo.md) | core | 2 |

Orden de construcción real en `02-plan-de-accion.md`.
