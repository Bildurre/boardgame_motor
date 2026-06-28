<?php

use App\Http\Controllers\CasaController;
use App\Http\Controllers\Public\CasaController as PublicCasaController;
use Illuminate\Support\Facades\Route;

/*
| Rutas propias del juego (playground). Se cargan con prefijo /api y el grupo
| de middleware 'api'. Las rutas del motor (auth, ping, locales) las añade
| el propio paquete bgm/core.
*/

// --- Público (solo entidades publicadas) ---
Route::get('casas', [PublicCasaController::class, 'index']);
Route::get('casas/{slug}', [PublicCasaController::class, 'show']);

// --- Admin (admin/editor) ---
Route::middleware(['auth:sanctum', 'motor.admin'])->prefix('admin')->group(function () {
    Route::get('casas', [CasaController::class, 'index']);
    Route::post('casas', [CasaController::class, 'store']);
    Route::get('casas/{casa}', [CasaController::class, 'show']);
    Route::put('casas/{casa}', [CasaController::class, 'update']);
    Route::delete('casas/{casa}', [CasaController::class, 'destroy']);
    Route::post('casas/{id}/restore', [CasaController::class, 'restore']);
    Route::post('casas/{casa}/toggle-published', [CasaController::class, 'togglePublished']);
});
