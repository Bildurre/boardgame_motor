# 06 · Backup de BBDD

## Qué hace

Exporta la base de datos del juego a un `.sql` (opcionalmente comprimido en `.zip`)
descargable desde el admin, y programable. Una salvaguarda simple por juego.

## Qué hay hoy en choque

`Services\Admin\ExportService` con `ifsnop/mysqldump-php` + `ZipArchive`: vuelca a
`storage/app/exports`, con opciones de compresión. Funciona; se conserva la idea.

## Diseño nuevo

**Backend (`motor-php/src/Backup/`):**
- `BackupService`: dump (mysqldump-php o `spatie/laravel-backup` — a decidir),
  compresión opcional, almacenamiento en disco configurable, listado y borrado de
  backups antiguos, retención.
- `motor:backup` (comando) + tarea programada (retención N copias / N días).
- Opcional: incluir la carpeta de media en el zip.

**API + Admin:**
```
GET    /api/v1/admin/backups            # listar
POST   /api/v1/admin/backups            # crear (sync o en cola)
GET    /api/v1/admin/backups/{id}/download
DELETE /api/v1/admin/backups/{id}
```
- `BackupManager` en admin-kit: crear, listar, descargar, borrar; aviso de tamaño.

## Frontera motor ↔ juego

Totalmente del motor. El juego solo lo activa/configura (disco, retención,
si incluye media).

## Pasos

1. Elegir motor de dump (evaluar `spatie/laravel-backup`).
2. `BackupService` + comando + programación + retención.
3. API + `BackupManager` en admin.

## Hito de aceptación

- Crear y descargar un backup desde el admin.
- Backup programado con retención que limpia los viejos.

## Decisiones (cerradas)

- **Motor de backup** → **DC-16**: **`spatie/laravel-backup`** (retención, media,
  S3, programación, notificaciones).
- **Restore** → **DC-16**: **export ahora**; restore manual documentado; restore
  guiado más adelante. BBDD grandes → en cola.

## Riesgos

- Tamaño de backups con media incluida; vigilar disco/retención.
