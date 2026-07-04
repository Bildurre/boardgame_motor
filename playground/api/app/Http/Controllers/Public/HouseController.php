<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Scheme;

/**
 * Lectura pública de casas (solo publicadas), por slug en cualquier locale
 * (ResolvesBySlug): el payload trae los slugs por locale y la SPA redirige a
 * la canónica del idioma activo (DC-12). El detalle incluye sus argucias
 * publicadas (misma forma que consume SchemeCard).
 */
class HouseController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $houses = House::query()->published()
            ->orderBy("name->{$locale}")
            ->get()
            ->map(fn (House $house) => $this->payload($house, $locale));

        return response()->json(['data' => $houses]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();
        $house = House::query()->published()->whereSlug($slug)->firstOrFail();

        return response()->json(['data' => [
            ...$this->payload($house, $locale),
            'schemes' => $house->schemes()->published()->orderBy('cost')->get()
                ->map(fn (Scheme $scheme) => $scheme->renderData($locale))
                ->all(),
        ]]);
    }

    protected function payload(House $house, string $locale): array
    {
        return [
            ...$house->renderData($locale),
            'description' => $house->getTranslations('description'),
            'slug' => $house->getTranslations('slug'),
        ];
    }
}
