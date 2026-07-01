<?php

namespace App\Models;

use Bgm\Core\Media\Concerns\HasImage;
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
class Scheme extends Model implements HasMedia
{
    use HasFilters;
    use HasImage;
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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::createWithLocales(array_keys(config('motor.locales', ['es' => []])))
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
