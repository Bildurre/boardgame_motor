<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseResource;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** CRUD de admin para House (entidad demo del playground). */
class HouseController extends Controller
{
    public function index(Request $request)
    {
        $houses = House::query()
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return HouseResource::collection($houses);
    }

    /** Lista ligera (id + nombre traducible) para selectores. */
    public function options()
    {
        return response()->json([
            'data' => House::orderByDesc('id')->get()->map(fn (House $h) => [
                'id' => $h->id,
                'name' => $h->getTranslations('name'),
                'slug' => $h->getTranslations('slug'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $house = new House;
        $this->fill($house, $data);
        $house->save();
        $house->setImageFromRequest($request);

        return (new HouseResource($house))->response()->setStatusCode(201);
    }

    public function show(string $slug)
    {
        $house = House::with(['schemes' => fn ($q) => $q->orderByDesc('id')])
            ->whereSlug($slug)
            ->firstOrFail();

        return new HouseResource($house);
    }

    public function update(Request $request, string $slug)
    {
        $house = House::whereSlug($slug)->firstOrFail();
        $data = $this->validateData($request);
        $this->fill($house, $data);
        $house->save();
        $house->setImageFromRequest($request);

        return new HouseResource($house);
    }

    public function destroy(string $slug)
    {
        $house = House::whereSlug($slug)->firstOrFail();
        $house->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $house = House::withTrashed()->findOrFail($id);
        $house->restore();

        return new HouseResource($house);
    }

    /** Borrado definitivo (desde la papelera): elimina la fila y su imagen. */
    public function forceDestroy(int $id)
    {
        $house = House::withTrashed()->findOrFail($id);
        $house->clearMediaCollection('image');
        $house->forceDelete();

        return response()->noContent();
    }

    public function togglePublished(string $slug)
    {
        $house = House::whereSlug($slug)->firstOrFail();
        $house->togglePublished();

        return new HouseResource($house);
    }

    /** Valida los campos traducibles por locale + la imagen opcional. */
    protected function validateData(Request $request): array
    {
        $default = config('motor.default_locale');
        $rules = [
            'color' => ['nullable', 'string', 'max:7'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            // Quitar la imagen actual, diferido desde el form (viaja al guardar).
            'remove_image' => ['sometimes', 'boolean'],
        ];
        foreach (array_keys(config('motor.locales', [])) as $locale) {
            $rules["name.$locale"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    protected function fill(House $house, array $data): void
    {
        $house->replaceTranslations('name', array_filter($data['name'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $house->replaceTranslations('description', array_filter($data['description'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $house->color = $data['color'] ?? null;
        if (array_key_exists('is_published', $data)) {
            $house->is_published = (bool) $data['is_published'];
        }
    }
}
