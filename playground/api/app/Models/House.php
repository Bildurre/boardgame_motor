<?php

namespace App\Models;

use Bgm\Core\Media\Concerns\HasImage;
use Bgm\Core\Previews\Concerns\HasPreviewImage;
use Bgm\Core\Previews\PreviewableContract;
use Bgm\Core\Support\Concerns\HasFilters;
use Bgm\Core\Support\Concerns\HasPublishedState;
use Bgm\Core\Support\Concerns\ResolvesBySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Entidad demo del playground (una "house" tipo Juego de Tronos). Ejercita las
 * piezas del motor: campos traducibles, slug traducible, estado publicado,
 * filtros y soft-delete. Cada juego real tendrá las suyas.
 *
 * Sus previews NO son cartas: son el token redondo de 40 mm ('house', lo
 * imprime house-tokens) y el contador de 25 mm ('house-counter', lo imprime
 * house-counters). Mismo componente HouseToken, tamaños distintos.
 */
class House extends Model implements HasMedia, PreviewableContract
{
    use HasFilters;
    use HasImage;
    use HasPreviewImage;
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

    /** Una casa agrupa argucias (Scheme). */
    public function schemes(): HasMany
    {
        return $this->hasMany(Scheme::class);
    }

    // --- Render a PNG (doc 01): el token redondo de la casa ---

    /**
     * Tamaño del componente en px CSS (5 px/mm, como las cartas). La casa
     * tiene DOS previews: 'house' = token de 40x40 mm y 'house-counter' =
     * contador de 25x25 mm (mismo componente, escala distinta).
     */
    public function previewSize(?string $type = null): array
    {
        return $type === 'house-counter'
            ? ['width' => 125, 'height' => 125]
            : ['width' => 200, 'height' => 200];
    }

    /** Etiqueta para el gestor de previews del admin. */
    public function previewLabel(string $locale): string
    {
        return $this->getTranslation('name', $locale) ?: "#{$this->id}";
    }

    /** Cambios que invalidan el token (declarativo; is_published no). */
    public function previewTriggerFields(): array
    {
        return ['name', 'color'];
    }

    /** Payload que consume el componente HouseToken en /_render (ambas previews). */
    public function renderData(string $locale, ?string $type = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'color' => $this->color,
            'image' => $this->imageUrl(),
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::createWithLocales(array_keys(config('motor.locales', ['es' => []])))
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
