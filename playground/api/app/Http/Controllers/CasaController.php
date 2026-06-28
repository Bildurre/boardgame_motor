<?php

namespace App\Http\Controllers;

use App\Http\Resources\CasaResource;
use App\Models\Casa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** CRUD de admin para Casa (entidad demo del playground). */
class CasaController extends Controller
{
    public function index(Request $request)
    {
        $casas = Casa::query()
            ->withTrashed()
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return CasaResource::collection($casas);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $casa = new Casa();
        $this->fill($casa, $data);
        $casa->save();

        return (new CasaResource($casa))->response()->setStatusCode(201);
    }

    public function show(Casa $casa)
    {
        return new CasaResource($casa);
    }

    public function update(Request $request, Casa $casa)
    {
        $data = $this->validateData($request);
        $this->fill($casa, $data);
        $casa->save();

        return new CasaResource($casa);
    }

    public function destroy(Casa $casa)
    {
        $casa->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $casa = Casa::withTrashed()->findOrFail($id);
        $casa->restore();

        return new CasaResource($casa);
    }

    public function togglePublished(Casa $casa)
    {
        $casa->togglePublished();

        return new CasaResource($casa);
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

    protected function fill(Casa $casa, array $data): void
    {
        $casa->setTranslations('name', array_filter($data['name'] ?? []));
        $casa->setTranslations('description', array_filter($data['description'] ?? []));
        $casa->color = $data['color'] ?? null;
        if (array_key_exists('is_published', $data)) {
            $casa->is_published = (bool) $data['is_published'];
        }
    }
}
