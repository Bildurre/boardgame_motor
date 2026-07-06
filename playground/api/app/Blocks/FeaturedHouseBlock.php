<?php

namespace App\Blocks;

use App\Models\House;
use App\Models\Scheme;
use Edc\Core\Content\BlockType;
use Edc\Core\Content\Fields\Field;
use Edc\Core\Content\Models\Block;

/**
 * Bloque con-datos de ESTE juego: una casa elegida a dedo (la demo canónica
 * del campo `entity` del DSL). El admin la busca en /admin/houses/options;
 * en settings queda su id y resolveData carga el modelo al renderizar.
 */
class FeaturedHouseBlock extends BlockType
{
    public static string $key = 'featured-house';

    public string $name = 'Casa destacada';

    public string $icon = 'landmark';

    public string $category = 'data';

    public function fields(): array
    {
        return [
            Field::text('title')->label('Título')->translatable(),
            Field::entity('house_id', '/admin/houses/options')->label('Casa')->required(),
            Field::richtext('intro')->label('Introducción')->translatable(),
            Field::boolean('show_schemes')->label('Mostrar sus argucias')->default(true),
        ];
    }

    public function resolveData(Block $block, string $locale): array
    {
        $settings = $this->localizeSettings($block->settings, $locale);
        $house = House::query()->published()->find($settings['house_id']);

        if ($house === null) {
            return ['house' => null, 'schemes' => []];
        }

        return [
            'house' => [
                ...$house->renderData($locale),
                'description' => $house->getTranslations('description'),
                'slug' => $house->getTranslations('slug'),
            ],
            'schemes' => $settings['show_schemes']
                ? $house->schemes()->published()->orderBy('cost')->get()
                    ->map(fn (Scheme $scheme) => $scheme->renderData($locale))
                    ->all()
                : [],
        ];
    }
}
