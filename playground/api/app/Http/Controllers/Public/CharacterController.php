<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Character;

/**
 * Lectura pública de personajes (solo publicados), por slug en cualquier
 * locale (ResolvesBySlug): el payload trae los slugs por locale y la SPA
 * redirige a la canónica del idioma activo (DC-12). La forma del dato es la
 * misma que consume CharacterCard (renderData + slugs), como en el bloque
 * characters-grid.
 */
class CharacterController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $characters = Character::query()->published()
            ->orderBy("name->{$locale}")
            ->get()
            ->map(fn (Character $character) => $this->payload($character, $locale));

        return response()->json(['data' => $characters]);
    }

    public function show(string $slug)
    {
        $character = Character::query()->published()->whereSlug($slug)->firstOrFail();

        return response()->json(['data' => $this->payload($character, app()->getLocale())]);
    }

    protected function payload(Character $character, string $locale): array
    {
        return [
            ...$character->renderData($locale),
            'slug' => $character->getTranslations('slug'),
        ];
    }
}
