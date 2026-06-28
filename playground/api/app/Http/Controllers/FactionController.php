<?php

namespace App\Http\Controllers;

use App\Http\Resources\FactionResource;
use App\Models\Faction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** CRUD de admin para Faction (entidad demo del playground). */
class FactionController extends Controller
{
    public function index(Request $request)
    {
        $factions = Faction::query()
            ->withTrashed()
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return FactionResource::collection($factions);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $faction = new Faction();
        $this->fill($faction, $data);
        $faction->save();

        return (new FactionResource($faction))->response()->setStatusCode(201);
    }

    public function show(Faction $faction)
    {
        return new FactionResource($faction);
    }

    public function update(Request $request, Faction $faction)
    {
        $data = $this->validateData($request);
        $this->fill($faction, $data);
        $faction->save();

        return new FactionResource($faction);
    }

    public function destroy(Faction $faction)
    {
        $faction->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $faction = Faction::withTrashed()->findOrFail($id);
        $faction->restore();

        return new FactionResource($faction);
    }

    public function togglePublished(Faction $faction)
    {
        $faction->togglePublished();

        return new FactionResource($faction);
    }

    /** Valida los campos traducibles por locale. */
    protected function validateData(Request $request): array
    {
        $default = config('motor.default_locale');
        $rules = [
            'color' => ['nullable', 'string', 'max:7'],
            'is_published' => ['boolean'],
        ];
        foreach (array_keys(config('motor.locales', [])) as $locale) {
            $rules["name.$locale"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    protected function fill(Faction $faction, array $data): void
    {
        $faction->setTranslations('name', array_filter($data['name'] ?? []));
        $faction->setTranslations('description', array_filter($data['description'] ?? []));
        $faction->color = $data['color'] ?? null;
        if (array_key_exists('is_published', $data)) {
            $faction->is_published = (bool) $data['is_published'];
        }
    }
}
