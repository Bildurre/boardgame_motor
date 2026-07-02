<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las casas también se renderizan a PNG: su preview es el token redondo que
 * usa el export de PDF house-tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->json('preview_image')->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('houses', fn (Blueprint $table) => $table->dropColumn('preview_image'));
    }
};
