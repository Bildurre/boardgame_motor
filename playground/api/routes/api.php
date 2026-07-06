<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\Public\CharacterController as PublicCharacterController;
use App\Http\Controllers\Public\HouseController as PublicHouseController;
use App\Http\Controllers\SchemeController;
use Illuminate\Support\Facades\Route;

/*
| Rutas propias del juego (playground). Se cargan con prefijo /api y el grupo
| de middleware 'api'. Las rutas del motor (auth, ping, locales) las añade
| el propio paquete edc-motor/core.
*/

// --- Público (solo entidades publicadas) ---
Route::get('houses', [PublicHouseController::class, 'index']);
Route::get('houses/{slug}', [PublicHouseController::class, 'show']);
Route::get('characters', [PublicCharacterController::class, 'index']);
Route::get('characters/{slug}', [PublicCharacterController::class, 'show']);

// --- Admin (admin/editor): las entidades del juego exigen manage-game ---
Route::middleware(['auth:sanctum', 'motor.admin', 'can:manage-game'])->prefix('admin')->group(function () {
    // Houses
    Route::get('houses/options', [HouseController::class, 'options']); // antes de {slug}
    Route::get('houses', [HouseController::class, 'index']);
    Route::post('houses', [HouseController::class, 'store']);
    Route::get('houses/{slug}', [HouseController::class, 'show']);
    Route::put('houses/{slug}', [HouseController::class, 'update']);
    Route::delete('houses/{slug}', [HouseController::class, 'destroy']);
    Route::post('houses/{id}/restore', [HouseController::class, 'restore']);
    Route::delete('houses/{id}/force', [HouseController::class, 'forceDestroy']);
    Route::post('houses/{slug}/toggle-published', [HouseController::class, 'togglePublished']);

    // Schemes (argucias)
    Route::get('schemes', [SchemeController::class, 'index']);
    Route::post('schemes', [SchemeController::class, 'store']);
    Route::get('schemes/{slug}', [SchemeController::class, 'show']);
    Route::put('schemes/{slug}', [SchemeController::class, 'update']);
    Route::delete('schemes/{slug}', [SchemeController::class, 'destroy']);
    Route::post('schemes/{id}/restore', [SchemeController::class, 'restore']);
    Route::delete('schemes/{id}/force', [SchemeController::class, 'forceDestroy']);
    Route::post('schemes/{slug}/toggle-published', [SchemeController::class, 'togglePublished']);

    // Characters (personajes)
    Route::get('characters', [CharacterController::class, 'index']);
    Route::post('characters', [CharacterController::class, 'store']);
    Route::get('characters/{slug}', [CharacterController::class, 'show']);
    Route::put('characters/{slug}', [CharacterController::class, 'update']);
    Route::delete('characters/{slug}', [CharacterController::class, 'destroy']);
    Route::post('characters/{id}/restore', [CharacterController::class, 'restore']);
    Route::delete('characters/{id}/force', [CharacterController::class, 'forceDestroy']);
    Route::post('characters/{slug}/toggle-published', [CharacterController::class, 'togglePublished']);
});
