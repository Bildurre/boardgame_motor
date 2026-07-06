<?php

namespace App\Pdf;

use App\Models\House;
use App\Models\Scheme;
use Edc\Core\Pdf\PdfExport;
use Edc\Core\Pdf\PrintableItem;
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

    /** Las casas disponibles en el gestor de PDF del admin. */
    public function sources(string $locale): array
    {
        return House::query()
            ->orderBy('id')
            ->get()
            ->map(fn (House $house) => [
                'id' => $house->id,
                'label' => $house->getTranslation('name', $locale) ?: "#{$house->id}",
            ])
            ->all();
    }
}
