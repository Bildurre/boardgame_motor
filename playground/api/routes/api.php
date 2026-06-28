<?php

use App\Http\Controllers\FactionController;
use App\Http\Controllers\Public\FactionController as PublicFactionController;
use Illuminate\Support\Facades\Route;

/*
| Rutas propias del juego (playground). Se cargan con prefijo /api y el grupo
| de middleware 'api'. Las rutas del motor (auth, ping, locales) las añade
| el propio paquete bgm/core.
*/

// --- Público (solo entidades publicadas) ---
Route::get('factions', [PublicFactionController::class, 'index']);
Route::get('factions/{slug}', [PublicFactionController::class, 'show']);

// --- Admin (admin/editor) ---
Route::middleware(['auth:sanctum', 'motor.admin'])->prefix('admin')->group(function () {
    Route::get('factions', [FactionController::class, 'index']);
    Route::post('factions', [FactionController::class, 'store']);
    Route::get('factions/{faction}', [FactionController::class, 'show']);
    Route::put('factions/{faction}', [FactionController::class, 'update']);
    Route::delete('factions/{faction}', [FactionController::class, 'destroy']);
    Route::post('factions/{id}/restore', [FactionController::class, 'restore']);
    Route::post('factions/{faction}/toggle-published', [FactionController::class, 'togglePublished']);
});
