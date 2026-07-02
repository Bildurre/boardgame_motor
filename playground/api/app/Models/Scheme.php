<?php

namespace App\Models;

use Bgm\Core\Media\Concerns\HasImage;
use Bgm\Core\Previews\Concerns\HasPreviewImage;
use Bgm\Core\Previews\PreviewableContract;
use Bgm\Core\Support\Concerns\HasFilters;
use Bgm\Core\Support\Concerns\HasPublishedState;
use Bgm\Core\Support\Concerns\ResolvesBySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Argucia: carta jugable que pertenece a una House.
 */
class Scheme extends Model implements HasMedia, PreviewableContract
{
    use HasFilters;
    use HasImage;
    use HasPreviewImage;
    use HasPublishedState;
    use HasTranslatableSlug;
    use HasTranslations;
    use ResolvesBySlug;
    use SoftDeletes;

    protected $table = 'schemes';

    protected $fillable = ['house_id', 'title', 'description', 'slug', 'cost', 'is_published'];

    public array $translatable = ['title', 'description', 'slug'];

    protected array $searchable = ['title'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'cost' => 'integer'];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    // --- Render a PNG (doc 01) ---

    public function previewSize(): array
    {
        return ['width' => 350, 'height' => 500];
    }

    public function previewTriggerFields(): array
    {
        return ['title', 'description', 'cost', 'house_id'];
    }

    /** Payload que consume el componente SchemeCard en /_render. */
    public function renderData(string $locale): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'cost' => $this->cost,
            'image' => $this->imageUrl(),
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::createWithLocales(array_keys(config('motor.locales', ['es' => []])))
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
