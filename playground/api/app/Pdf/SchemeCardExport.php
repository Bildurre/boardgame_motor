<?php

namespace App\Pdf;

use App\Models\Scheme;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Plantilla individual: la carta de UNA argucia (x1 en este juego).
 */
class SchemeCardExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return Scheme::class;
    }

    public function items(?Model $source, string $locale): array
    {
        return [PrintableItem::preview($source)];
    }
}
