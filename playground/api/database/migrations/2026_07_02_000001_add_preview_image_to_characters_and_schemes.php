<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Previews PNG (Fase 3, doc 01): ruta del PNG generado por locale, gestionada
 * por el trait HasPreviewImage del motor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->json('preview_image')->nullable()->after('is_published');
        });
        Schema::table('schemes', function (Blueprint $table) {
            $table->json('preview_image')->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('characters', fn (Blueprint $table) => $table->dropColumn('preview_image'));
        Schema::table('schemes', fn (Blueprint $table) => $table->dropColumn('preview_image'));
    }
};
