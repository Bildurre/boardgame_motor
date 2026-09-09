# 12 · Exportación a JSON

Descarga, desde el admin, de los modelos del juego en JSON **para leer**: el
administrador elige el modelo, los idiomas y los campos, y las relaciones
salen **desplegadas** (nombre y datos relevantes) en vez de con su id, de
modo que el fichero se lea de seguido sin ir y venir entre tablas. No es un
formato de reimportación.

## Qué hace

- Sección **Exportación** del admin, visible y usable **solo por el rol
  administrador** (gate `export-data`; un editor con `manage-game` no la ve
  ni puede llamar a la API).
- Formulario: modelo (los registrados), idiomas del sitio (checkboxes) y
  campos del modelo agrupados (datos, textos, relaciones…) con «todos /
  ninguno». Descarga `<modelo>-<AAAA-MM-DD>.json`.
- JSON: cabecera (`model`, `exported_at`, `locales`, `fields`, `count`) e
  `items`, cada uno con **solo** los campos elegidos, en el orden del
  catálogo del modelo. Con **un** idioma elegido los textos traducibles van
  como cadena; con **varios**, como mapa `{ "es": …, "en": … }` (también
  dentro de las relaciones).

## Frontera motor / juego

**Motor (core)**

- `Edc\Core\Export\ExportableContract`: lo implementa cada modelo exportable.
  - `static exportFields(): array` — catálogo `clave => grupo`, en el orden
    de salida. Grupos sugeridos: `basic`, `texts`, `relations`.
  - `static exportQuery(): Builder` — consulta base (eager loading + orden).
  - `exportItem(ExportLocalizer $l): array` — `clave => valor | Closure`
    (las Closure solo se evalúan si el campo se ha elegido).
- `Edc\Core\Export\ExportLocalizer`: idiomas elegidos; `tr($modelo, $campo)`
  para un traducible y `perLocale(fn ($locale) => …)` para textos calculados
  (p. ej. un nombre con género). Cadena con un idioma, mapa con varios; los
  vacíos salen como `null`.
- `Exports::register('heroes', Hero::class)` (facade → `ExportRegistry`),
  en el boot del `AppServiceProvider` del juego.
- Rutas (solo admin): `GET /api/admin/export/options` (modelos con campos y
  grupo, idiomas, idioma por defecto) y `POST /api/admin/export/{modelo}`
  con `locales[]` y `fields[]` → JSON como descarga.

**Motor (admin-kit)**

- `ExportManager` (props `api`, `labels`, `modelLabels`, `groupLabels`,
  `fieldLabels`): el formulario completo. Agnóstico de i18n (DC-29): el
  juego le pasa los textos y las etiquetas por clave.

**Juego**

- Implementa el contrato en sus modelos y los registra.
- Vista del admin que monta `ExportManager` con sus textos (`export.*` en
  su i18n por convención: `export.models.<clave>`, `export.groups.<grupo>`,
  `export.fieldLabels.<modelo>.<campo>`), ruta con `meta.role: 'admin'`
  (guard del router) e ítem de menú con `v-if="auth.isAdmin"`.

## Ejemplo de modelo

```php
class Hero extends Model implements ExportableContract
{
    public static function exportFields(): array
    {
        return [
            'name' => 'basic',
            'attributes' => 'basic',
            'faction' => 'relations',
            'abilities' => 'relations',
        ];
    }

    public static function exportQuery(): Builder
    {
        return static::query()
            ->with(['faction', 'heroAbilities.attackRange'])
            ->orderBy('name->'.config('motor.default_locale'));
    }

    public function exportItem(ExportLocalizer $l): array
    {
        return [
            'name' => $l->tr($this, 'name'),
            'attributes' => ['agility' => (int) $this->agility],
            // Relación DESPLEGADA: nombre y datos, no faction_id
            'faction' => fn () => $this->faction ? [
                'name' => $l->tr($this->faction, 'name'),
                'color' => $this->faction->color,
            ] : null,
            'abilities' => fn () => $this->heroAbilities->map(fn ($a) => [
                'name' => $l->tr($a, 'name'),
                'cost' => $a->cost,
                'range' => $a->attackRange ? $l->tr($a->attackRange, 'name') : null,
            ])->values()->all(),
        ];
    }
}
```

## Ejemplo de JSON (un idioma)

```json
{
  "model": "heroes",
  "exported_at": "2026-09-09T10:00:00+00:00",
  "locales": ["es"],
  "fields": ["name", "faction", "abilities"],
  "count": 1,
  "items": [
    {
      "name": "Aritz",
      "faction": { "name": "Alianza", "color": "#336699" },
      "abilities": [{ "name": "Tajo", "cost": "RG", "range": "Cuerpo a cuerpo" }]
    }
  ]
}
```

## Riesgos y decisiones

- Es un volcado completo (publicados y borradores) en una sola respuesta:
  pensado para catálogos de juego (cientos de elementos), no para tablas
  masivas. Si un juego necesita filtrar, `exportQuery()` es el sitio.
- El gate es por **rol**, no por permiso del motor: exportar todo el
  contenido es cosa del administrador aunque un editor gestione el juego.
