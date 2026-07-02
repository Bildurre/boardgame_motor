<?php

namespace App\Pdf;

use App\Models\Scheme;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Colección global: todas las cartas de argucia publicadas, recortables.
 */
class SchemesExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return null;
    }

    public function items(?Model $source, string $locale): array
    {
        return Scheme::query()
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn (Scheme $scheme) => PrintableItem::preview($scheme))
            ->all();
    }

    public function filename(?Model $source, string $locale): string
    {
        return "schemes-{$locale}";
    }
}
