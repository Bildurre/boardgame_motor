<?php

namespace App\Models;

use Bgm\Core\Media\Concerns\HasImage;
use Bgm\Core\Previews\Concerns\HasPreviewImage;
use Bgm\Core\Previews\PreviewableContract;
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
 * Personaje: carta jugable con estadísticas. El coste es la suma de
 * poder+prestigio+intriga+dinero (se recalcula al guardar). La defensa es
 * derivada (= coste), no se almacena.
 */
class Character extends Model implements HasMedia, PreviewableContract
{
    use HasFilters;
    use HasImage;
    use HasPreviewImage;
    use HasPublishedState;
    use HasTranslatableSlug;
    use HasTranslations;
    use ResolvesBySlug;
    use SoftDeletes;

    protected $table = 'characters';

    protected $fillable = ['name', 'description', 'ability', 'slug', 'power', 'prestige', 'intrigue', 'money', 'is_published'];

    public array $translatable = ['name', 'description', 'ability', 'slug'];

    protected array $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'cost' => 'integer',
            'power' => 'integer',
            'prestige' => 'integer',
            'intrigue' => 'integer',
            'money' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // El coste siempre es la suma de las cuatro estadísticas (no editable).
        static::saving(function (Character $character) {
            $character->cost = (int) $character->power + (int) $character->prestige
                + (int) $character->intrigue + (int) $character->money;
        });
    }

    /** Defensa derivada (= coste). No se almacena. */
    public function getDefenseAttribute(): int
    {
        return (int) $this->cost;
    }

    // --- Render a PNG (doc 01) ---

    /** Tamaño del componente carta en px CSS (proporción 88x126 mm). */
    public function previewSize(): array
    {
        return ['width' => 350, 'height' => 500];
    }

    /** Etiqueta para el gestor de previews del admin. */
    public function previewLabel(string $locale): string
    {
        return $this->getTranslation('name', $locale) ?: "#{$this->id}";
    }

    /** Cambios que invalidan la preview (declarativo; is_published no). */
    public function previewTriggerFields(): array
    {
        return ['name', 'description', 'ability', 'power', 'prestige', 'intrigue', 'money'];
    }

    /** Payload que consume el componente CharacterCard en /_render. */
    public function renderData(string $locale): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'ability' => $this->getTranslations('ability'),
            'cost' => $this->cost,
            'power' => $this->power,
            'prestige' => $this->prestige,
            'intrigue' => $this->intrigue,
            'money' => $this->money,
            'defense' => $this->defense,
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
