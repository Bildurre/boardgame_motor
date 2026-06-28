<?php

namespace App\Models;

use Bgm\Core\Media\Concerns\HasImage;
use Bgm\Core\Support\Concerns\HasFilters;
use Bgm\Core\Support\Concerns\HasPublishedState;
use Bgm\Core\Support\Concerns\ResolvesBySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Entidad demo del playground (una "house" tipo Juego de Tronos). Ejercita las
 * piezas del motor: campos traducibles, slug traducible, estado publicado,
 * filtros y soft-delete. Cada juego real tendrá las suyas.
 */
class House extends Model implements HasMedia
{
    use HasFilters;
    use HasImage;
    use HasPublishedState;
    use HasTranslatableSlug;
    use HasTranslations;
    use ResolvesBySlug;
    use SoftDeletes;

    protected $table = 'houses';

    protected $fillable = ['name', 'description', 'slug', 'color', 'is_published'];

    public array $translatable = ['name', 'description', 'slug'];

    protected array $searchable = ['name'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::createWithLocales(array_keys(config('motor.locales', ['es' => []])))
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
