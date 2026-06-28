<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicFactionResource;
use App\Models\Faction;

/** Lectura pública de Factions (solo publicadas), por slug en cualquier locale. */
class FactionController extends Controller
{
    public function index()
    {
        return PublicFactionResource::collection(
            Faction::published()->orderBy('id')->get()
        );
    }

    public function show(string $slug)
    {
        $faction = Faction::published()->whereSlug($slug)->firstOrFail();

        return new PublicFactionResource($faction);
    }
}
