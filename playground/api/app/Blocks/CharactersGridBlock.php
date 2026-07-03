<?php

namespace App\Blocks;

use App\Models\Character;
use Bgm\Core\Content\BlockType;
use Bgm\Core\Content\Fields\Field;
use Bgm\Core\Content\Models\Block;

/**
 * Bloque con-datos de ESTE juego: rejilla de cartas de personaje publicadas.
 * El motor no sabe qué es un personaje; este bloque consulta sus modelos y
 * su componente Vue vive en la app del juego.
 */
class CharactersGridBlock extends BlockType
{
    public static string $key = 'characters-grid';

    public string $name = 'Rejilla de personajes';

    public string $icon = 'users';

    public string $category = 'data';

    public function fields(): array
    {
        return [
            Field::text('title')->label('Título')->translatable(),
            Field::number('limit')->label('Máximo de cartas')->default(8)->min(1)->max(24),
            Field::select('order', [
                'recent' => 'Más recientes',
                'cost' => 'Por coste',
                'name' => 'Por nombre',
            ])->label('Orden'),
        ];
    }

    public function resolveData(Block $block, string $locale): array
    {
        $settings = $this->localizeSettings($block->settings, $locale);

        $query = Character::query()->published();
        match ($settings['order']) {
            'cost' => $query->orderBy('cost')->orderBy('id'),
            'name' => $query->orderBy("name->{$locale}"),
            default => $query->orderByDesc('id'),
        };

        return [
            'characters' => $query->limit((int) $settings['limit'])
                ->get()
                ->map(fn (Character $character) => [
                    ...$character->renderData($locale),
                    'slug' => $character->getTranslations('slug'),
                ])
                ->all(),
        ];
    }
}
