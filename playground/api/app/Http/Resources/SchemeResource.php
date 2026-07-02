<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Representación de admin para Scheme (argucia): traducciones completas. */
class SchemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'slug' => $this->getTranslations('slug'),
            'cost' => $this->cost,
            'image' => $this->imageUrl(),
            'previews' => $this->previewUrls(),
            'is_published' => $this->is_published,
            'house_id' => $this->house_id,
            'house' => $this->whenLoaded('house', fn () => [
                'id' => $this->house->id,
                'name' => $this->house->getTranslations('name'),
                'slug' => $this->house->getTranslations('slug'),
            ]),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
