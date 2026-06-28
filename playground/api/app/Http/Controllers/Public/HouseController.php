<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicHouseResource;
use App\Models\House;

/** Lectura pública de Houses (solo publicadas), por slug en cualquier locale. */
class HouseController extends Controller
{
    public function index()
    {
        return PublicHouseResource::collection(
            House::published()->orderBy('id')->get()
        );
    }

    public function show(string $slug)
    {
        $house = House::published()->whereSlug($slug)->firstOrFail();

        return new PublicHouseResource($house);
    }
}
