<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** CRUD de admin para Character (personaje). Coste = suma de estadísticas. */
class CharacterController extends Controller
{
    public function index(Request $request)
    {
        $characters = Character::query()
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return CharacterResource::collection($characters);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $character = new Character;
        $this->fill($character, $data);
        $character->save();
        $character->setImageFromRequest($request);
        if ($request->hasFile('image')) {
            // La imagen vive en MediaLibrary (no es columna): invalida a mano.
            $character->regeneratePreviews();
        }

        return (new CharacterResource($character))->response()->setStatusCode(201);
    }

    public function show(string $slug)
    {
        $character = Character::whereSlug($slug)->firstOrFail();

        return new CharacterResource($character);
    }

    public function update(Request $request, string $slug)
    {
        $character = Character::whereSlug($slug)->firstOrFail();
        $data = $this->validateData($request);
        $this->fill($character, $data);
        $character->save();
        $character->setImageFromRequest($request);
        if ($request->hasFile('image')) {
            // La imagen vive en MediaLibrary (no es columna): invalida a mano.
            $character->regeneratePreviews();
        }

        return new CharacterResource($character);
    }

    public function destroy(string $slug)
    {
        Character::whereSlug($slug)->firstOrFail()->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $character = Character::withTrashed()->findOrFail($id);
        $character->restore();

        return new CharacterResource($character);
    }

    public function forceDestroy(int $id)
    {
        $character = Character::withTrashed()->findOrFail($id);
        $character->clearMediaCollection('image');
        $character->forceDelete();

        return response()->noContent();
    }

    public function togglePublished(string $slug)
    {
        $character = Character::whereSlug($slug)->firstOrFail();
        $character->togglePublished();

        return new CharacterResource($character);
    }

    protected function validateData(Request $request): array
    {
        $default = config('motor.default_locale');
        $rules = [
            // El coste se calcula (suma de las cuatro), no se recibe.
            'power' => ['required', 'integer', 'min:0', 'max:99'],
            'prestige' => ['required', 'integer', 'min:0', 'max:99'],
            'intrigue' => ['required', 'integer', 'min:0', 'max:99'],
            'money' => ['required', 'integer', 'min:0', 'max:99'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
        foreach (array_keys(config('motor.locales', [])) as $locale) {
            $rules["name.$locale"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string'];
            $rules["ability.$locale"] = ['nullable', 'string'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    protected function fill(Character $character, array $data): void
    {
        $character->replaceTranslations('name', array_filter($data['name'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $character->replaceTranslations('description', array_filter($data['description'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $character->replaceTranslations('ability', array_filter($data['ability'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $character->power = (int) $data['power'];
        $character->prestige = (int) $data['prestige'];
        $character->intrigue = (int) $data['intrigue'];
        $character->money = (int) $data['money'];
        if (array_key_exists('is_published', $data)) {
            $character->is_published = (bool) $data['is_published'];
        }
        // cost lo calcula el modelo en saving().
    }
}
