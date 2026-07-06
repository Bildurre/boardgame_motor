<?php

namespace App\Pdf;

use App\Models\House;
use Edc\Core\Pdf\PdfExport;
use Edc\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Demuestra que el export ELIGE qué preview imprime: usa la segunda preview
 * de House ('house-counter', 25x25 mm) en el layout 'counter' del motor,
 * mientras house-tokens usa la por defecto ('house', 40 mm).
 */
class HouseCountersExport extends PdfExport
{
    protected const COPIES = 9;

    public function sourceModel(): ?string
    {
        return null;
    }

    public function items(?Model $source, string $locale): array
    {
        return House::query()
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn (House $house) => PrintableItem::preview($house, copies: self::COPIES, preview: 'house-counter'))
            ->all();
    }

    public function layout(): string
    {
        return 'counter'; // preset 25x25 que ya trae el motor
    }

    public function filename(?Model $source, string $locale): string
    {
        return "house-counters-{$locale}";
    }
}
