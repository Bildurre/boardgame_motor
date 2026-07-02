<?php

namespace App\Pdf;

use App\Models\Character;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Colección global: todas las cartas de personaje publicadas, recortables.
 * Sin entidad dueña (sourceModel null).
 */
class CharactersExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return null;
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
