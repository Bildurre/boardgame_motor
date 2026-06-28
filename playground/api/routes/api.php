<?php

use App\Http\Controllers\HouseController;
use App\Http\Controllers\Public\HouseController as PublicHouseController;
use Illuminate\Support\Facades\Route;

/*
| Rutas propias del juego (playground). Se cargan con prefijo /api y el grupo
| de middleware 'api'. Las rutas del motor (auth, ping, locales) las añade
| el propio paquete bgm/core.
*/

// --- Público (solo entidades publicadas) ---
Route::get('houses', [PublicHouseController::class, 'index']);
Route::get('houses/{slug}', [PublicHouseController::class, 'show']);

// --- Admin (admin/editor) ---
Route::middleware(['auth:sanctum', 'motor.admin'])->prefix('admin')->group(function () {
    Route::get('houses', [HouseController::class, 'index']);
    Route::post('houses', [HouseController::class, 'store']);
    Route::get('houses/{house}', [HouseController::class, 'show']);
    Route::put('houses/{house}', [HouseController::class, 'update']);
    Route::delete('houses/{house}', [HouseController::class, 'destroy']);
    Route::post('houses/{id}/restore', [HouseController::class, 'restore']);
    Route::post('houses/{house}/toggle-published', [HouseController::class, 'togglePublished']);
});
