<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Representación de admin para Character (personaje). */
class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'ability' => $this->getTranslations('ability'),
            'slug' => $this->getTranslations('slug'),
            'cost' => $this->cost,
            'power' => $this->power,
            'prestige' => $this->prestige,
            'intrigue' => $this->intrigue,
            'money' => $this->money,
            'defense' => $this->defense,          // derivada (= coste)
            'image' => $this->imageUrl(),
            'is_published' => $this->is_published,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
