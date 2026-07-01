<?php

namespace App\Http\Controllers;

use App\Http\Resources\SchemeResource;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** CRUD de admin para Scheme (argucia). */
class SchemeController extends Controller
{
    public function index(Request $request)
    {
        $schemes = Scheme::query()
            ->with('house')
            ->filter($request->only('search', 'status'))
            ->orderByDesc('id')
            ->paginate(15);

        return SchemeResource::collection($schemes);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $scheme = new Scheme();
        $this->fill($scheme, $data);
        $scheme->save();
        $scheme->setImageFromRequest($request);

        return (new SchemeResource($scheme->load('house')))->response()->setStatusCode(201);
    }

    public function show(string $slug)
    {
        $scheme = Scheme::with('house')->whereSlug($slug)->firstOrFail();

        return new SchemeResource($scheme);
    }

    public function update(Request $request, string $slug)
    {
        $scheme = Scheme::whereSlug($slug)->firstOrFail();
        $data = $this->validateData($request);
        $this->fill($scheme, $data);
        $scheme->save();
        $scheme->setImageFromRequest($request);

        return new SchemeResource($scheme->load('house'));
    }

    public function destroy(string $slug)
    {
        Scheme::whereSlug($slug)->firstOrFail()->delete();

        return response()->noContent();
    }

    public function restore(int $id)
    {
        $scheme = Scheme::withTrashed()->findOrFail($id);
        $scheme->restore();

        return new SchemeResource($scheme->load('house'));
    }

    public function forceDestroy(int $id)
    {
        $scheme = Scheme::withTrashed()->findOrFail($id);
        $scheme->clearMediaCollection('image');
        $scheme->forceDelete();

        return response()->noContent();
    }

    public function togglePublished(string $slug)
    {
        $scheme = Scheme::whereSlug($slug)->firstOrFail();
        $scheme->togglePublished();

        return new SchemeResource($scheme->load('house'));
    }

    protected function validateData(Request $request): array
    {
        $default = config('motor.default_locale');
        $rules = [
            'house_id' => ['required', 'integer', 'exists:houses,id'],
            'cost' => ['required', 'integer', 'min:0', 'max:99'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
        foreach (array_keys(config('motor.locales', [])) as $locale) {
            $rules["title.$locale"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    protected function fill(Scheme $scheme, array $data): void
    {
        $scheme->setTranslations('title', array_filter($data['title'] ?? []));
        $scheme->setTranslations('description', array_filter($data['description'] ?? []));
        $scheme->house_id = (int) $data['house_id'];
        $scheme->cost = (int) $data['cost'];
        if (array_key_exists('is_published', $data)) {
            $scheme->is_published = (bool) $data['is_published'];
        }
    }
}
