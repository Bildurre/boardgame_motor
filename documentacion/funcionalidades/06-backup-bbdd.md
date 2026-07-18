# 06 · Backup de BBDD

> **Estado: implementado (Fase 6 ✅).** `spatie/laravel-backup` (DC-16)
> con la config derivada de `motor.backup` en
> `Edc\Core\Backup\MotorBackup::applyConfig()` (disco `backups` local por
> defecto, retención `keep_days`, media opcional; SQLite entra como fichero
> en el zip — el dump exige el binario `sqlite3`). API en
> `/api/admin/backups` (listar/crear/subir/restaurar/descargar/borrar, solo
> `manage-web`) + vista **Copias** en el admin (crear con un clic, panel
> derecho con restaurar/descargar/borrar). La copia AUTOMÁTICA se configura
> desde esa misma vista (activada, frecuencia diaria/semanal, día, hora y
> retención; servicio `BackupSettings` sobre la tabla settings + `PUT
> /api/admin/backups/schedule`) y la programa el motor en
> `MotorBackup::schedule()` — el juego solo necesita el cron de
> `schedule:run`. La copia manual va SIEMPRE en cola (`RunBackupJob`,
> respuesta 202 y flag `pending` que la vista sondea; con la cola `sync` se
> difiere a después de la respuesta). Cada copia lleva su ORIGEN
> (manual/auto/subida, por el prefijo del nombre). Se pueden SUBIR copias
> externas (`POST upload`, zip validado con la BBDD dentro, máx.
> `motor.backup.upload_max_mb`) y RESTAURAR una copia desde el admin con
> doble confirmación (`POST {file}/restore`, `BackupRestorer`: sustituye el
> fichero SQLite o vacía el esquema e importa el dump SQL; SOLO la BBDD — el
> storage del zip no se restaura — y puede invalidar los tokens de sesión);
> restore manual guiado documentado abajo. **Doc completado.**

## Qué hace

Exporta la base de datos del juego a un `.sql` (opcionalmente comprimido en `.zip`)
descargable desde el admin, y programable. Una salvaguarda simple por juego.

## Qué hay hoy en choque

`Services\Admin\ExportService` con `ifsnop/mysqldump-php` + `ZipArchive`: vuelca a
`storage/app/exports`, con opciones de compresión. Funciona; se conserva la idea.

## Diseño nuevo

**Backend (`core/src/Backup/`):**
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

## Restore guiado (manual, paso a paso)

1. **Para el sitio** (modo mantenimiento): `php artisan down`.
2. **Descarga y descomprime** el zip de la vista Copias (o de
   `storage/app/backups/{app}/`). Dentro va la BBDD y, si estaba activado,
   `storage/app/public` (media).
3. **BBDD**:
   - SQLite: repón el fichero (p. ej. `database/database.sqlite`) encima
     del actual.
   - MySQL/Postgres: `mysql base < db-dumps/mysql-base.sql` (o `psql`).
4. **Media**: repón `storage/app/public` desde el zip (previews y PDFs se
   pueden regenerar desde el admin si prefieres no restaurarlos).
5. **Cachés**: `php artisan optimize:clear` (la config del sitio y los
   permisos de Spatie se cachean).
6. `php artisan up` y comprueba `php artisan backup:list` (monitor sano).

## Riesgos

- Tamaño de backups con media incluida; vigilar disco/retención.
