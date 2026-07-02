<?php

namespace App\Pdf;

use App\Models\Character;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Plantilla individual: la carta de UN personaje repetida hasta llenar la
 * página (útil para reponer una carta concreta).
 */
class CharacterCardExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return Character::class;
    }

    public function items(?Model $source, string $locale): array
    {
        // 4 copias = una hoja A4 completa con el layout 'card'.
        return [PrintableItem::preview($source, copies: 4)];
    }
}
