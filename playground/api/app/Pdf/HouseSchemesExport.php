<?php

namespace App\Pdf;

use App\Models\House;
use App\Models\Scheme;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Colección por entidad: las cartas de argucia de una casa, recortables.
 * Plantilla generalista del motor (rejilla con marcas de corte).
 */
class HouseSchemesExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return House::class;
    }

    public function items(?Model $source, string $locale): array
    {
        return $source->schemes()
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn (Scheme $scheme) => PrintableItem::preview($scheme))
            ->all();
    }
}
