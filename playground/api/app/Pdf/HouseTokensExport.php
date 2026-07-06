<?php

namespace App\Pdf;

use App\Models\House;
use Edc\Core\Pdf\PdfExport;
use Edc\Core\Pdf\PrintableItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Colección global de OTRO tipo de pieza: 9 tokens redondos (40x40 mm, radio
 * 2 cm) por cada casa publicada, en el layout token-40 que este juego declara
 * en su AppServiceProvider. La preview de House es el componente HouseToken.
 */
class HouseTokensExport extends PdfExport
{
    /** Copias de cada token en el PDF. */
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
            ->map(fn (House $house) => PrintableItem::preview($house, copies: self::COPIES))
            ->all();
    }

    /** Preset propio del juego (no el 'card' por defecto). */
    public function layout(): string
    {
        return 'token-40';
    }

    public function filename(?Model $source, string $locale): string
    {
        return "house-tokens-{$locale}";
    }
}
