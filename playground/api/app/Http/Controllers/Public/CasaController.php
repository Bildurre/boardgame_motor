<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCasaResource;
use App\Models\Casa;

/** Lectura pública de Casas (solo publicadas), por slug en cualquier locale. */
class CasaController extends Controller
{
    public function index()
    {
        return PublicCasaResource::collection(
            Casa::published()->orderBy('id')->get()
        );
    }

    public function show(string $slug)
    {
        $casa = Casa::published()->whereSlug($slug)->firstOrFail();

        return new PublicCasaResource($casa);
    }
}
