<?php

namespace App\Pdf;

use App\Models\Character;
use Edc\Core\Pdf\PdfExport;
use Edc\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Colección global: todas las cartas de personaje publicadas, recortables.
 * Sin entidad dueña (sourceModel null). Se imprimen al DOBLE de una carta
 * Magic (layout card-big); las argucias siguen en el 'card' estándar.
 */
class CharactersExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return null;
    }

    /** El tamaño de impresión se elige por export, no por entidad. */
    public function layout(): string
    {
        return 'card-big';
    }

    public function items(?Model $source, string $locale): array
    {
        return Character::query()
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn (Character $character) => PrintableItem::preview($character))
            ->all();
    }

    public function filename(?Model $source, string $locale): string
    {
        return "characters-{$locale}";
    }
}
