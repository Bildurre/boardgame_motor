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
            ->withTrashed()
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return HouseResource::collection($houses);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $house = new House();
        $this->fill($house, $data);
        $house->save();

        return (new HouseResource($house))->response()->setStatusCode(201);
    }

    public function show(House $house)
    {
        return new HouseResource($house);
    }

    public function update(Request $request, House $house)
    {
        $data = $this->validateData($request);
        $this->fill($house, $data);
        $house->save();

        return new HouseResource($house);
    }

    public function destroy(House $house)
    {
        $house->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $house = House::withTrashed()->findOrFail($id);
        $house->restore();

        return new HouseResource($house);
    }

    public function togglePublished(House $house)
    {
        $house->togglePublished();

        return new HouseResource($house);
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

    protected function fill(House $house, array $data): void
    {
        $house->setTranslations('name', array_filter($data['name'] ?? []));
        $house->setTranslations('description', array_filter($data['description'] ?? []));
        $house->color = $data['color'] ?? null;
        if (array_key_exists('is_published', $data)) {
            $house->is_published = (bool) $data['is_published'];
        }
    }
}
