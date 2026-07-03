<?php

namespace App\Blocks;

use App\Models\House;
use Bgm\Core\Content\BlockType;
use Bgm\Core\Content\Fields\Field;
use Bgm\Core\Content\Models\Block;

/**
 * Bloque con-datos de ESTE juego: las casas publicadas con sus argucias.
 */
class HousesSchemesBlock extends BlockType
{
    public static string $key = 'houses-schemes';

    public string $name = 'Casas y sus argucias';

    public string $icon = 'home';

    public string $category = 'data';

    public function fields(): array
    {
        return [
            Field::text('title')->label('Título')->translatable(),
            Field::richtext('intro')->label('Introducción')->translatable(),
            Field::boolean('show_empty')->label('Mostrar casas sin argucias'),
        ];
    }

    public function resolveData(Block $block, string $locale): array
    {
        $settings = $this->localizeSettings($block->settings, $locale);

        $houses = House::query()
            ->published()
            ->with(['schemes' => fn ($q) => $q->published()->orderBy('cost')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->filter(fn (House $house) => $settings['show_empty'] || $house->schemes->isNotEmpty())
            ->map(fn (House $house) => [
                'id' => $house->id,
                'name' => $house->getTranslations('name'),
                'color' => $house->color,
                'schemes' => $house->schemes
                    ->map(fn ($scheme) => $scheme->renderData($locale))
                    ->all(),
            ])
            ->values()
            ->all();

        return ['houses' => $houses];
    }
}
